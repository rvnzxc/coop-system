<?php

namespace Tests\Feature\FullCoverage;

use App\Models\AuditLog;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionAndActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_transaction_index_lists_sales_and_summaries(): void
    {
        $cashier = $this->cashier();
        $member = $this->member();
        $sale = $this->sale([
            'cashier_id' => $cashier->id,
            'member_id' => $member->id,
            'member_number' => $member->member_number,
            'customer_type' => 'member',
            'total' => 250,
        ]);

        $this->actingAs($this->admin())->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('#'.$sale->id)
            ->assertSee($cashier->name)
            ->assertSee('250.00');
    }

    public function test_transaction_filters_are_applied(): void
    {
        $first = $this->cashier();
        $second = $this->cashier();
        $one = $this->sale(['cashier_id' => $first->id, 'total' => 100, 'payment_method' => 'cash']);
        $two = $this->sale(['cashier_id' => $second->id, 'total' => 200, 'payment_method' => 'credit']);

        $response = $this->actingAs($this->admin())->get(route('transactions.index', [
            'cashier_id' => $first->id,
            'payment_method' => 'cash',
        ]));

        $response->assertOk()
            ->assertSee('#'.$one->id)
            ->assertDontSee('#'.$two->id);
    }

    public function test_invalid_transaction_date_range_is_rejected(): void
    {
        $this->actingAs($this->admin())->get(route('transactions.index', [
            'date_from' => '2026-03-31',
            'date_to' => '2026-03-01',
        ]))->assertSessionHasErrors('date_to');
    }

    public function test_transaction_export_downloads_xlsx_file(): void
    {
        $sale = $this->sale(['cashier_id' => $this->cashier()->id, 'total' => 125]);
        $this->saleItem(['sale_id' => $sale->id, 'product_name' => 'Exported Pencil', 'quantity' => 1, 'unit_price' => 125, 'subtotal' => 125]);

        $response = $this->actingAs($this->admin())->get(route('transactions.export'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('Content-Disposition');
        $this->assertNotEmpty($response->getContent());
    }

    public function test_activity_log_hides_void_actions_and_records_real_actions(): void
    {
        $user = $this->admin();
        $this->audit([
            'user_id' => $user->id,
            'action' => AuditLog::ACTION_LOGIN,
            'details' => ['username' => 'visible-login-marker', 'role' => 'admin'],
        ]);
        $this->audit([
            'user_id' => $user->id,
            'action' => 'void',
            'details' => ['username' => 'hidden-void-marker'],
        ]);
        $this->audit([
            'user_id' => $user->id,
            'action' => 'sale_voided',
            'details' => ['username' => 'hidden-sale-void-marker'],
        ]);

        $response = $this->actingAs($user)->get(route('transactions.activity'));

        $response->assertOk()
            ->assertSee('visible-login-marker')
            ->assertDontSee('hidden-void-marker')
            ->assertDontSee('hidden-sale-void-marker');
        $this->assertArrayNotHasKey('void', AuditLog::ACTIONS);
        $this->assertArrayNotHasKey('sale_voided', AuditLog::ACTIONS);
        $this->assertSame(0, Sale::where('status', Sale::STATUS_VOIDED)->count());
    }

    public function test_cashier_cannot_access_admin_transaction_pages(): void
    {
        $this->actingAs($this->cashier());

        $this->getJson(route('transactions.index'))->assertForbidden();
        $this->getJson(route('transactions.export'))->assertForbidden();
        $this->getJson(route('transactions.activity'))->assertForbidden();
        $this->get(route('my-transactions.index'))->assertOk();
    }
}
