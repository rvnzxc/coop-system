<?php

namespace Tests\Feature\FullCoverage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AnalyticsBoundaryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_daily_sales_include_last_hour_of_month_boundary(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-31 23:45:00', 'Asia/Manila'));
        $this->sale([
            'total' => 123.45,
            'status' => 'completed',
            'created_at' => '2026-03-31 23:30:00',
        ]);
        $this->sale([
            'total' => 10,
            'status' => 'completed',
            'created_at' => '2026-03-30 23:30:00',
        ]);

        $this->getJson('/api/analytics/sales?period=daily&date=2026-03-31')
            ->assertOk()
            ->assertJsonPath('date', '2026-03-31')
            ->assertJsonCount(24, 'data')
            ->assertJsonPath('data.23.total', 123.45)
            ->assertJsonPath('total_revenue', 123.45);
    }

    public function test_weekly_sales_respect_first_week_and_future_periods(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-31 12:00:00', 'Asia/Manila'));
        $this->purchase(['amount' => 50, 'created_at' => '2026-03-01 10:00:00']);
        $this->purchase(['amount' => 100, 'created_at' => '2026-03-07 10:00:00']);
        $this->purchase(['amount' => 25, 'created_at' => '2026-03-08 10:00:00']);

        $this->getJson('/api/analytics/sales?period=weekly&month=2026-03')
            ->assertOk()
            ->assertJsonPath('data.0.label', 'Mar 1-7')
            ->assertJsonPath('data.0.value', 150)
            ->assertJsonPath('data.1.value', 25)
            ->assertJsonPath('total_revenue', 175);

        $this->getJson('/api/analytics/sales?period=weekly&month=2026-04')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_quarterly_sales_respect_year_boundary_and_hide_future_quarters(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-31 23:59:59', 'Asia/Manila'));
        $this->purchase(['amount' => 999, 'created_at' => '2025-12-31 23:59:59']);
        $this->purchase(['amount' => 100, 'created_at' => '2026-01-01 00:00:00']);
        $this->purchase(['amount' => 50, 'created_at' => '2026-03-31 23:59:59']);

        $this->getJson('/api/analytics/sales?period=quarterly&year=2026')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.label', 'Q1')
            ->assertJsonPath('data.0.value', 150)
            ->assertJsonPath('total_revenue', 150);

        $this->getJson('/api/analytics/sales?period=quarterly&year=2027')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_monthly_sales_api_works_on_sqlite_test_database(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-31 12:00:00', 'Asia/Manila'));
        $this->purchase(['amount' => 250, 'created_at' => '2026-03-15 10:00:00']);

        $this->getJson('/api/analytics/sales?period=monthly&year=2026')
            ->assertOk()
            ->assertJsonPath('data.2.label', 'Mar')
            ->assertJsonPath('data.2.value', 250);
    }

    public function test_yearly_sales_api_works_on_sqlite_test_database(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-31 12:00:00', 'Asia/Manila'));
        $this->purchase(['amount' => 750, 'created_at' => '2026-03-15 10:00:00']);

        $this->getJson('/api/analytics/sales?period=yearly')
            ->assertOk()
            ->assertJsonPath('data.0.label', '2026')
            ->assertJsonPath('data.0.value', 750);
    }

    public function test_daily_customer_type_breakdown_uses_completed_sales(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-31 12:00:00', 'Asia/Manila'));
        $this->sale(['total' => 100, 'customer_type' => 'member', 'created_at' => '2026-03-31 09:00:00']);
        $this->sale(['total' => 50, 'customer_type' => 'non_member', 'created_at' => '2026-03-31 10:00:00']);
        $this->sale(['total' => 999, 'customer_type' => 'member', 'status' => 'voided', 'created_at' => '2026-03-31 11:00:00']);

        $this->getJson('/api/analytics/customer-type?period=daily&date=2026-03-31')
            ->assertOk()
            ->assertJsonPath('member_total', 100)
            ->assertJsonPath('non_member_total', 50)
            ->assertJsonPath('member_count', 1)
            ->assertJsonPath('non_member_count', 1);
    }

    public function test_year_dropdown_only_lists_years_with_sales(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-31 12:00:00', 'Asia/Manila'));
        $this->purchase(['amount' => 100, 'created_at' => '2025-05-10 10:00:00']);

        $this->actingAs($this->admin())->get(route('analytics.index'))
            ->assertOk()
            ->assertSee('value="2025"', false)
            ->assertDontSee('value="2026"', false);
    }
}
