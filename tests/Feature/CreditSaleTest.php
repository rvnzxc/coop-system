<?php

namespace Tests\Feature;

use App\Models\Credit;
use App\Models\CreditPayment;
use App\Models\Item;
use App\Models\Member;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditSaleTest extends TestCase
{
    use RefreshDatabase;

    private int $memberCounter = 0;

    private function createMember(array $overrides = []): Member
    {
        $this->memberCounter++;
        return Member::create(array_merge([
            'first_name'      => 'Member',
            'last_name'       => $this->memberCounter,
            'member_number'   => 'MEM' . str_pad($this->memberCounter, 5, '0', STR_PAD_LEFT),
            'email'           => "member{$this->memberCounter}@example.com",
            'total_purchases' => 0,
            'purchase_count'  => 0,
            'is_active'       => true,
        ], $overrides));
    }

    private function createItem(array $overrides = []): Item
    {
        return Item::create(array_merge([
            'item_name' => 'Rice',
            'quantity'  => 100,
            'price'     => 50.00,
            'category'  => 'essentials',
        ], $overrides));
    }

    private function createCashier(): User
    {
        return User::create([
            'name'     => 'Test Cashier',
            'email'    => 'cashier@test.com',
            'password' => bcrypt('password'),
            'role'     => 'cashier',
        ]);
    }

    // ── Credit sale requires member ──────────────────────────

    public function test_credit_sale_rejected_for_non_member(): void
    {
        $this->createItem();
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)
            ->postJson('/checkout', [
                'items'          => [['name' => 'Rice', 'quantity' => 2]],
                'member_id'      => '',
                'is_non_member'  => '1',
                'payment_method' => 'credit',
            ]);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Credit payment is only available for members.']);
    }

    public function test_credit_sale_rejected_without_member(): void
    {
        $this->createItem();
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)
            ->postJson('/checkout', [
                'items'          => [['name' => 'Rice', 'quantity' => 1]],
                'member_id'      => '',
                'is_non_member'  => '0',
                'payment_method' => 'credit',
            ]);

        $response->assertStatus(400);
    }

    public function test_credit_sale_rejected_for_inactive_member(): void
    {
        $member = $this->createMember(['is_active' => false]);
        $this->createItem();
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)
            ->postJson('/checkout', [
                'items'          => [['name' => 'Rice', 'quantity' => 1]],
                'member_id'      => (string) $member->id,
                'is_non_member'  => '0',
                'payment_method' => 'credit',
            ]);

        $response->assertStatus(400);
        $response->assertJson(['message' => 'Credit is only available for active members.']);
    }

    // ── Credit record creation ───────────────────────────────

    public function test_credit_sale_creates_credit_record(): void
    {
        $member = $this->createMember();
        $this->createItem(['price' => 100.00]);
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)
            ->postJson('/checkout', [
                'items'          => [['name' => 'Rice', 'quantity' => 3]],
                'member_id'      => (string) $member->id,
                'is_non_member'  => '0',
                'payment_method' => 'credit',
            ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('credits', [
            'member_id'   => $member->id,
            'amount'      => 300.00,
            'amount_paid' => 0,
            'status'      => 'unpaid',
        ]);
    }

    public function test_credit_sale_marks_purchases_as_credit(): void
    {
        $member = $this->createMember();
        $this->createItem();
        $cashier = $this->createCashier();

        $this->actingAs($cashier)
            ->postJson('/checkout', [
                'items'          => [['name' => 'Rice', 'quantity' => 2]],
                'member_id'      => (string) $member->id,
                'is_non_member'  => '0',
                'payment_method' => 'credit',
            ]);

        $this->assertDatabaseHas('purchases', [
            'member_id'      => $member->id,
            'payment_method' => 'credit',
        ]);
    }

    public function test_cash_sale_marks_purchases_as_cash(): void
    {
        $member = $this->createMember();
        $this->createItem();
        $cashier = $this->createCashier();

        $this->actingAs($cashier)
            ->postJson('/checkout', [
                'items'          => [['name' => 'Rice', 'quantity' => 1]],
                'member_id'      => (string) $member->id,
                'is_non_member'  => '0',
                'payment_method' => 'cash',
            ]);

        $this->assertDatabaseHas('purchases', [
            'member_id'      => $member->id,
            'payment_method' => 'cash',
        ]);
    }

    // ── Partial payment ──────────────────────────────────────

    public function test_partial_payment_updates_balance_and_status(): void
    {
        $member = $this->createMember();
        $credit = Credit::create([
            'member_id'   => $member->id,
            'amount'      => 500.00,
            'amount_paid' => 0,
            'status'      => 'unpaid',
        ]);

        $credit->amount_paid = 200.00;
        $credit->markPaidIfFull();

        $this->assertEquals(200.00, $credit->fresh()->amount_paid);
        $this->assertEquals('partial', $credit->fresh()->status);
        $this->assertEquals(300.00, $credit->fresh()->balance);
    }

    // ── Full payment ─────────────────────────────────────────

    public function test_full_payment_updates_status_to_paid(): void
    {
        $member = $this->createMember();
        $credit = Credit::create([
            'member_id'   => $member->id,
            'amount'      => 500.00,
            'amount_paid' => 0,
            'status'      => 'unpaid',
        ]);

        $credit->amount_paid = 500.00;
        $credit->markPaidIfFull();

        $this->assertEquals('paid', $credit->fresh()->status);
        $this->assertEquals(0.00, $credit->fresh()->balance);
    }

    // ── Outstanding balance across multiple credits ──────────

    public function test_member_outstanding_balance_across_multiple_credits(): void
    {
        $member = $this->createMember();

        Credit::create([
            'member_id'   => $member->id,
            'amount'      => 500.00,
            'amount_paid' => 100.00,
            'status'      => 'partial',
        ]);
        Credit::create([
            'member_id'   => $member->id,
            'amount'      => 300.00,
            'amount_paid' => 0,
            'status'      => 'unpaid',
        ]);
        Credit::create([
            'member_id'   => $member->id,
            'amount'      => 200.00,
            'amount_paid' => 200.00,
            'status'      => 'paid',
        ]);

        $this->assertEquals(700.00, $member->outstanding_credit_balance);
        $this->assertTrue($member->has_unpaid_credit);
    }

    public function test_member_zero_balance_when_fully_paid(): void
    {
        $member = $this->createMember();

        Credit::create([
            'member_id'   => $member->id,
            'amount'      => 500.00,
            'amount_paid' => 500.00,
            'status'      => 'paid',
        ]);

        $this->assertEquals(0.00, $member->outstanding_credit_balance);
        $this->assertFalse($member->has_unpaid_credit);
    }

    // ── Scopes ───────────────────────────────────────────────

    public function test_credit_scopes_filter_correctly(): void
    {
        $member = $this->createMember();

        Credit::create(['member_id' => $member->id, 'amount' => 100, 'amount_paid' => 0, 'status' => 'unpaid']);
        Credit::create(['member_id' => $member->id, 'amount' => 200, 'amount_paid' => 50, 'status' => 'partial']);
        Credit::create(['member_id' => $member->id, 'amount' => 300, 'amount_paid' => 300, 'status' => 'paid']);

        $this->assertEquals(1, Credit::unpaid()->count());
        $this->assertEquals(1, Credit::partial()->count());
        $this->assertEquals(1, Credit::paid()->count());
        $this->assertEquals(2, Credit::outstanding()->count());
    }

    // ── CreditPayment model ──────────────────────────────────

    public function test_credit_payment_recorded_correctly(): void
    {
        $member = $this->createMember();
        $cashier = $this->createCashier();

        $credit = Credit::create([
            'member_id'   => $member->id,
            'amount'      => 500.00,
            'amount_paid' => 0,
            'status'      => 'unpaid',
        ]);

        $payment = CreditPayment::create([
            'credit_id'   => $credit->id,
            'amount_paid' => 250.00,
            'paid_at'     => now(),
            'received_by' => $cashier->id,
        ]);

        $this->assertDatabaseHas('credit_payments', [
            'credit_id'   => $credit->id,
            'amount_paid' => 250.00,
            'received_by' => $cashier->id,
        ]);

        $this->assertEquals($credit->id, $payment->credit->id);
        $this->assertEquals($cashier->id, $payment->receiver->id);
    }
}
