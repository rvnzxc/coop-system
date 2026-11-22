<?php

namespace Tests\Feature\FullCoverage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_routes_require_authentication(): void
    {
        $this->get(route('reports.credits'))->assertRedirect('/login');
    }

    public function test_cashier_report_access_is_denied(): void
    {
        $this->actingAs($this->cashier())->get(route('reports.credits', ['format' => 'csv']))->assertForbidden();
    }

    public function test_admin_can_export_all_reports_as_csv(): void
    {
        $member = $this->member();
        $item = $this->item(['price' => 50, 'quantity' => 10]);
        $sale = $this->sale([
            'cashier_id' => $this->cashier()->id,
            'member_id' => $member->id,
            'member_number' => $member->member_number,
            'customer_type' => 'member',
            'total' => 100,
        ]);
        $this->saleItem(['sale_id' => $sale->id, 'item_id' => $item->id, 'quantity' => 2, 'unit_price' => 50, 'subtotal' => 100]);
        $this->credit(['member_id' => $member->id, 'amount' => 100, 'sale_reference' => now()->format('Y-m-d H:i:s')]);

        $this->actingAs($this->admin());

        foreach (['credits', 'sales', 'inventory', 'member-purchases', 'transactions'] as $report) {
            $response = $this->get(route('reports.'.$report, ['format' => 'csv']));

            $response->assertOk()
                ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
            $this->assertStringContainsString($report, $response->headers->get('Content-Disposition'));
            $this->assertStringContainsString(',', $response->streamedContent());
        }
    }

    public function test_admin_can_export_pdf_with_expected_headers(): void
    {
        $item = $this->item(['price' => 75, 'quantity' => 5]);

        $response = $this->actingAs($this->admin())->get(route('reports.inventory', ['format' => 'pdf']));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('inventory', $response->headers->get('Content-Disposition'));
    }
}
