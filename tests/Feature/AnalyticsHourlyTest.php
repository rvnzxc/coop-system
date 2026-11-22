<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AnalyticsHourlyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::create(2026, 11, 16, 10, 0, 0, 'Asia/Manila'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function insertSale(string $createdAt, float $total, string $status = 'completed', string $customerType = 'member', string $paymentMethod = 'cash'): void
    {
        DB::table('sales')->insert([
            'customer_type' => $customerType,
            'payment_method' => $paymentMethod,
            'total' => $total,
            'status' => $status,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    public function test_daily_sales_return_24_hourly_buckets_for_the_selected_day(): void
    {
        $this->insertSale('2026-11-10 09:15:00', 20.00, 'completed', 'member');
        $this->insertSale('2026-11-10 09:45:00', 10.00, 'completed', 'non_member');
        $this->insertSale('2026-11-10 14:05:00', 10.00, 'completed', 'member');
        $this->insertSale('2026-11-10 22:30:00', 5.00, 'completed', 'member');
        $this->insertSale('2026-11-10 09:30:00', 99.00, 'voided', 'member');
        $this->insertSale('2026-11-11 09:00:00', 50.00, 'completed', 'member');

        $response = $this->getJson('/api/analytics/sales?period=daily&date=2026-11-10');

        $response->assertOk()
            ->assertJsonPath('date', '2026-11-10')
            ->assertJsonCount(24, 'data')
            ->assertJsonPath('data.0.label', '12 AM')
            ->assertJsonPath('data.9.label', '9 AM')
            ->assertJsonPath('data.12.label', '12 PM')
            ->assertJsonPath('data.9.value', 30)
            ->assertJsonPath('data.14.value', 10)
            ->assertJsonPath('data.22.value', 5)
            ->assertJsonPath('data.10.value', 0)
            ->assertJsonPath('total_revenue', 45)
            ->assertJsonPath('peak_sales', 30);
    }

    public function test_daily_sales_group_by_manila_hour(): void
    {
        $this->insertSale('2026-11-10 03:43:00', 12.00);

        $response = $this->getJson('/api/analytics/sales?period=daily&date=2026-11-10');

        $response->assertOk()
            ->assertJsonPath('data.3.value', 12)
            ->assertJsonPath('data.4.value', 0);
    }

    public function test_daily_sales_default_to_today_and_clamp_future_dates(): void
    {
        $response = $this->getJson('/api/analytics/sales?period=daily&date=2999-01-01');

        $response->assertOk()->assertJsonPath('date', '2026-11-16');

        $this->getJson('/api/analytics/sales?period=daily')
            ->assertOk()
            ->assertJsonPath('date', '2026-11-16');
    }

    public function test_daily_customer_type_breakdown_uses_completed_sales_for_the_day(): void
    {
        $this->insertSale('2026-11-10 09:00:00', 20.00, 'completed', 'member');
        $this->insertSale('2026-11-10 10:00:00', 30.00, 'completed', 'non_member');
        $this->insertSale('2026-11-10 11:00:00', 99.00, 'voided', 'member');
        $this->insertSale('2026-11-11 12:00:00', 70.00, 'completed', 'member');

        $response = $this->getJson('/api/analytics/customer-type?period=daily&date=2026-11-10');

        $response->assertOk()
            ->assertJsonPath('member_total', 20)
            ->assertJsonPath('member_count', 1)
            ->assertJsonPath('non_member_total', 30)
            ->assertJsonPath('non_member_count', 1);
    }

    public function test_daily_credit_sales_only_count_completed_credit_sales_for_the_day(): void
    {
        $this->insertSale('2026-11-10 09:00:00', 20.00, 'completed', 'member', 'credit');
        $this->insertSale('2026-11-10 10:00:00', 30.00, 'completed', 'member', 'cash');
        $this->insertSale('2026-11-10 11:00:00', 99.00, 'voided', 'member', 'credit');
        $this->insertSale('2026-11-11 12:00:00', 70.00, 'completed', 'member', 'credit');

        $this->getJson('/api/analytics/sales?period=daily&date=2026-11-10')
            ->assertOk()
            ->assertJsonPath('credit_sales', 20)
            ->assertJsonPath('credit_count', 1);
    }

    public function test_daily_tab_renders_the_date_selector(): void
    {
        $admin = User::create([
            'name' => 'Test Admin',
            'username' => 'admin',
            'email' => 'admin@test.com',
            'password' => 'adminpass123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->get('/analytics');

        $response->assertOk()
            ->assertSee('id="dateSelector"', false)
            ->assertSee('id="prevDayBtn"', false)
            ->assertSee('id="nextDayBtn"', false)
            ->assertSee('Hourly Sales (Mon, Nov 16, 2026)', false)
            ->assertDontSee('id="weekSelector"', false);
    }

    public function test_weekly_response_keeps_its_shape(): void
    {
        $this->insertSale('2026-11-10 09:00:00', 20.00);

        $weekly = $this->getJson('/api/analytics/sales?period=weekly&month=2026-11');

        $weekly->assertOk()
            ->assertJsonStructure(['data' => [['label', 'value']], 'total_revenue', 'credit_sales', 'credit_count', 'peak_sales'])
            ->assertJsonMissingPath('date');
    }

    private function insertPurchase(string $createdAt, float $amount, string $customerType = 'member'): void
    {
        DB::table('purchases')->insert([
            'member_id' => null,
            'amount' => $amount,
            'quantity' => 1,
            'product_name' => 'Test Item',
            'purchase_date' => substr($createdAt, 0, 10),
            'customer_type' => $customerType,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    public function test_weekly_sales_use_month_date_ranges_and_sum_to_the_monthly_total(): void
    {
        $this->insertPurchase('2026-11-02 09:00:00', 10.00);
        $this->insertPurchase('2026-11-09 09:00:00', 20.00);
        $this->insertPurchase('2026-11-16 09:00:00', 30.00);
        $this->insertPurchase('2026-11-25 09:00:00', 99.00);

        $response = $this->getJson('/api/analytics/sales?period=weekly&month=2026-11');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.label', 'Nov 1-7')
            ->assertJsonPath('data.0.value', 10)
            ->assertJsonPath('data.1.label', 'Nov 8-14')
            ->assertJsonPath('data.1.value', 20)
            ->assertJsonPath('data.2.label', 'Nov 15-21')
            ->assertJsonPath('data.2.value', 30)
            ->assertJsonPath('total_revenue', 60)
            ->assertJsonPath('credit_sales', 0)
            ->assertJsonPath('credit_count', 0)
            ->assertJsonPath('peak_sales', 30)
            ->assertJsonPath('peak_label', 'Nov 15-21');
    }

    public function test_credit_sales_respect_the_selected_month_and_payment_method(): void
    {
        $this->insertSale('2026-11-10 09:00:00', 20.00, 'completed', 'member', 'credit');
        $this->insertSale('2026-11-16 09:00:00', 15.00, 'completed', 'member', 'credit');
        $this->insertSale('2026-11-16 10:00:00', 50.00, 'completed', 'member', 'cash');
        $this->insertSale('2026-11-16 11:00:00', 99.00, 'voided', 'member', 'credit');
        $this->insertSale('2026-12-01 09:00:00', 99.00, 'completed', 'member', 'credit');

        $this->getJson('/api/analytics/sales?period=weekly&month=2026-11')
            ->assertOk()
            ->assertJsonPath('credit_sales', 35)
            ->assertJsonPath('credit_count', 2);
    }

    public function test_quarterly_sales_group_by_quarter_and_skip_future_quarters(): void
    {
        $this->insertPurchase('2026-01-15 09:00:00', 10.00);
        $this->insertPurchase('2026-04-15 09:00:00', 20.00);
        $this->insertPurchase('2026-08-15 09:00:00', 30.00);
        $this->insertPurchase('2026-11-15 09:00:00', 40.00);

        $this->getJson('/api/analytics/sales?period=quarterly&year=2026')
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.label', 'Q1')
            ->assertJsonPath('data.0.value', 10)
            ->assertJsonPath('data.1.label', 'Q2')
            ->assertJsonPath('data.1.value', 20)
            ->assertJsonPath('data.2.label', 'Q3')
            ->assertJsonPath('data.2.value', 30)
            ->assertJsonPath('data.3.label', 'Q4')
            ->assertJsonPath('data.3.value', 40)
            ->assertJsonPath('total_revenue', 100)
            ->assertJsonPath('credit_sales', 0)
            ->assertJsonPath('credit_count', 0)
            ->assertJsonPath('peak_sales', 40)
            ->assertJsonPath('peak_label', 'Q4');

        Carbon::setTestNow(Carbon::create(2026, 5, 20, 10, 0, 0, 'Asia/Manila'));

        $this->getJson('/api/analytics/sales?period=quarterly&year=2026')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.1.label', 'Q2')
            ->assertJsonPath('total_revenue', 30);

        $this->getJson('/api/analytics/sales?period=quarterly&year=2027')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('total_revenue', 0);
    }

    public function test_quarterly_credit_sales_respect_the_selected_year(): void
    {
        $this->insertSale('2026-02-01 09:00:00', 10.00, 'completed', 'member', 'credit');
        $this->insertSale('2026-08-01 09:00:00', 25.00, 'completed', 'member', 'credit');
        $this->insertSale('2025-12-31 09:00:00', 99.00, 'completed', 'member', 'credit');
        $this->insertSale('2027-01-01 09:00:00', 99.00, 'completed', 'member', 'credit');

        $this->getJson('/api/analytics/sales?period=quarterly&year=2026')
            ->assertOk()
            ->assertJsonPath('credit_sales', 35)
            ->assertJsonPath('credit_count', 2);
    }

    public function test_quarterly_customer_type_breakdown_uses_the_selected_year(): void
    {
        $this->insertPurchase('2026-02-01 09:00:00', 10.00, 'member');
        $this->insertPurchase('2026-05-01 09:00:00', 20.00, 'non_member');
        $this->insertPurchase('2025-12-31 09:00:00', 99.00, 'member');

        $this->getJson('/api/analytics/customer-type?period=quarterly&year=2026')
            ->assertOk()
            ->assertJsonPath('member_total', 10)
            ->assertJsonPath('member_count', 1)
            ->assertJsonPath('non_member_total', 20)
            ->assertJsonPath('non_member_count', 1);
    }

    public function test_quarterly_tab_renders_with_a_year_dropdown_and_straight_lines(): void
    {
        $admin = User::create([
            'name' => 'Test Admin',
            'username' => 'admin',
            'email' => 'admin@test.com',
            'password' => 'adminpass123',
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->insertPurchase('2024-02-01 09:00:00', 10.00);

        $this->actingAs($admin)
            ->get('/analytics')
            ->assertOk()
            ->assertSee('id="quarterlyBtn"', false)
            ->assertSee('id="quarterYearSelector"', false)
            ->assertSee('value="2024"', false)
            ->assertSee('Quarterly Sales (', false)
            ->assertSee('Credit Sales', false)
            ->assertSee('id="creditSalesAmount"', false)
            ->assertSee('id="creditSalesCount"', false)
            ->assertSee('bg-violet-50', false)
            ->assertSee('text-violet-600', false)
            ->assertSee('M1760 1408q66 0', false)
            ->assertDontSee('Average Sales', false)
            ->assertDontSee('avgSalesAmount', false)
            ->assertDontSee('average_sales', false)
            ->assertSee('tension: 0.4', false)
            ->assertSee("cubicInterpolationMode: 'monotone'", false)
            ->assertSee('beginAtZero: true', false)
            ->assertSee('offset: true', false)
            ->assertSee('overflow-x-auto', false)
            ->assertSee('whitespace-nowrap', false);
    }

    public function test_monthly_and_yearly_responses_keep_their_shape(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Monthly and yearly analytics use MySQL-only date functions.');
        }

        $this->insertSale('2026-11-10 09:00:00', 20.00);
        $this->insertSale('2026-11-15 09:00:00', 15.00, 'completed', 'member', 'credit');

        $monthly = $this->getJson('/api/analytics/sales?period=monthly&year=2026');
        $monthly->assertOk()
            ->assertJsonCount(12, 'data')
            ->assertJsonPath('credit_sales', 15)
            ->assertJsonPath('credit_count', 1);

        $yearly = $this->getJson('/api/analytics/sales?period=yearly');
        $yearly->assertOk()
            ->assertJsonStructure(['data' => [['label', 'value']], 'total_revenue', 'credit_sales', 'credit_count'])
            ->assertJsonPath('credit_sales', 15)
            ->assertJsonPath('credit_count', 1);
    }
}
