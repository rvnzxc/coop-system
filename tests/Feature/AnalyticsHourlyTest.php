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

    private function insertSale(string $createdAt, float $total, string $status = 'completed', string $customerType = 'member'): void
    {
        DB::table('sales')->insert([
            'customer_type' => $customerType,
            'payment_method' => 'cash',
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
            ->assertJsonStructure(['data' => [['label', 'value']], 'total_revenue', 'average_sales', 'peak_sales'])
            ->assertJsonMissingPath('date');
    }

    public function test_monthly_and_yearly_responses_keep_their_shape(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Monthly and yearly analytics use MySQL-only date functions.');
        }

        $this->insertSale('2026-11-10 09:00:00', 20.00);

        $monthly = $this->getJson('/api/analytics/sales?period=monthly&year=2026');
        $monthly->assertOk()->assertJsonCount(12, 'data');

        $yearly = $this->getJson('/api/analytics/sales?period=yearly');
        $yearly->assertOk()->assertJsonStructure(['data' => [['label', 'value']], 'total_revenue']);
    }
}
