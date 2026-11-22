<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Credit;
use App\Models\Member;
use App\Models\Sale;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Report Admin',
            'username' => 'report-admin',
            'email' => 'report-admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    private function createCashier(string $suffix = ''): User
    {
        return User::create([
            'name' => 'Report Cashier'.$suffix,
            'username' => 'report-cashier'.$suffix,
            'email' => 'report-cashier'.$suffix.'@example.com',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => true,
        ]);
    }

    private function createMember(): Member
    {
        return Member::create([
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'member_number' => 'MEM00001',
            'email' => 'report-member@example.com',
            'total_purchases' => 0,
            'purchase_count' => 0,
            'is_active' => true,
        ]);
    }

    private function createSale(User $cashier, Member $member, float $total, string $paymentMethod = 'cash', ?string $createdAt = null): Sale
    {
        $timestamp = $createdAt ? Carbon::parse($createdAt, 'Asia/Manila') : now('Asia/Manila');

        $sale = new Sale;
        $sale->created_at = $timestamp;
        $sale->updated_at = $timestamp;
        $sale->fill([
            'cashier_id' => $cashier->id,
            'member_id' => $member->id,
            'member_number' => $member->member_number,
            'customer_type' => 'member',
            'payment_method' => $paymentMethod,
            'total' => $total,
            'amount_received' => $paymentMethod === 'cash' ? $total : null,
            'change_amount' => 0,
            'status' => Sale::STATUS_COMPLETED,
        ]);
        $sale->save();

        return $sale;
    }

    public function test_admin_can_download_credits_csv_with_bom_and_raw_amounts(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();

        Credit::create([
            'member_id' => $member->id,
            'amount' => 150.00,
            'sale_reference' => now('Asia/Manila')->format('Y-m-d H:i:s'),
            'notes' => 'Test credit',
        ]);

        $response = $this->actingAs($admin)->get(route('reports.credits', [
            'format' => 'csv',
            'scope' => 'period',
            'period' => 'yearly',
        ]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="ccfmpc-credits-report-'.now('Asia/Manila')->toDateString().'.csv"');

        $content = $response->streamedContent();

        $this->assertSame("\xEF\xBB\xBF", substr($content, 0, 3));
        $this->assertStringContainsString('"Member ID",Name,"Total Credit Taken","Total Paid","Outstanding Balance"', $content);
        $this->assertStringContainsString('MEM00001', $content);
        $this->assertStringContainsString('150.00', $content);
        $this->assertStringContainsString('TOTAL', $content);
        $this->assertStringNotContainsString('₱', $content);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditLog::ACTION_REPORT_EXPORTED,
            'target_type' => 'report',
        ]);
    }

    public function test_cashier_can_access_report_routes_and_sees_only_own_activity(): void
    {
        $cashier = $this->createCashier();
        $otherCashier = $this->createCashier('-other');
        $member = $this->createMember();

        $today = now('Asia/Manila')->toDateString();

        $this->createSale($cashier, $member, 111.00, 'cash', $today.' 10:01:00');
        $this->createSale($otherCashier, $member, 999.00, 'cash', $today.' 10:02:00');

        $creditSale = $this->createSale($cashier, $member, 75.00, 'credit', $today.' 10:03:00');
        Credit::create([
            'member_id' => $member->id,
            'amount' => 75.00,
            'sale_reference' => $creditSale->created_at->format('Y-m-d H:i:s'),
        ]);

        $otherCreditSale = $this->createSale($otherCashier, $member, 900.00, 'credit', $today.' 10:04:00');
        Credit::create([
            'member_id' => $member->id,
            'amount' => 900.00,
            'sale_reference' => $otherCreditSale->created_at->format('Y-m-d H:i:s'),
        ]);

        $range = [
            'format' => 'csv',
            'scope' => 'range',
            'from' => $today,
            'to' => $today,
        ];

        foreach (['credits', 'sales', 'inventory', 'member-purchases', 'transactions'] as $type) {
            $this->actingAs($cashier)->get(route('reports.'.$type, $range))
                ->assertOk();
        }

        $salesCsv = $this->actingAs($cashier)->get(route('reports.sales', $range))
            ->assertHeader('Content-Disposition', 'attachment; filename="ccfmpc-my-sales-report-'.now('Asia/Manila')->toDateString().'.csv"')
            ->streamedContent();
        $this->assertStringContainsString('186.00', $salesCsv);
        $this->assertStringNotContainsString('999.00', $salesCsv);
        $this->assertStringNotContainsString('900.00', $salesCsv);

        $transactionsCsv = $this->actingAs($cashier)->get(route('reports.transactions', $range))->streamedContent();
        $this->assertStringContainsString('111.00', $transactionsCsv);
        $this->assertStringContainsString('75.00', $transactionsCsv);
        $this->assertStringNotContainsString('999.00', $transactionsCsv);
        $this->assertStringNotContainsString('900.00', $transactionsCsv);

        $creditsCsv = $this->actingAs($cashier)->get(route('reports.credits', $range))->streamedContent();
        $this->assertStringContainsString('75.00', $creditsCsv);
        $this->assertStringNotContainsString('900.00', $creditsCsv);
        $this->assertStringNotContainsString('999.00', $creditsCsv);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $cashier->id,
            'action' => AuditLog::ACTION_REPORT_EXPORTED,
            'target_type' => 'report',
        ]);
    }

    public function test_cashier_has_my_reports_dialog_in_analytics_without_sidebar_tab(): void
    {
        $cashier = $this->createCashier();

        $this->actingAs($cashier)->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('My Reports')
            ->assertSee('id="reportDialogTitle"', false)
            ->assertDontSee('href="'.route('analytics.index').'#reports"', false);

        $this->actingAs($cashier)->get(route('credits.index'))
            ->assertOk()
            ->assertSee('Download My Credits Report');
    }

    public function test_empty_report_still_produces_a_valid_csv(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('reports.transactions', [
            'format' => 'csv',
            'scope' => 'range',
            'from' => '2099-01-01',
            'to' => '2099-01-02',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('No records for this period', $response->streamedContent());
    }

    public function test_invalid_format_is_rejected(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->getJson(route('reports.credits', ['format' => 'xls']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('format');
    }

    public function test_pdf_report_is_generated_when_dompdf_is_installed(): void
    {
        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $this->markTestSkipped('Install barryvdh/laravel-dompdf to run PDF export tests.');
        }

        $admin = $this->createAdmin();
        $member = $this->createMember();
        Credit::create([
            'member_id' => $member->id,
            'amount' => 250.00,
            'sale_reference' => now('Asia/Manila')->format('Y-m-d H:i:s'),
        ]);

        $response = $this->actingAs($admin)->get(route('reports.credits', [
            'format' => 'pdf',
            'scope' => 'period',
            'period' => 'yearly',
        ]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="ccfmpc-credits-report-'.now('Asia/Manila')->toDateString().'.pdf"');

        $this->assertStringStartsWith('%PDF', $response->getContent());
    }
}
