<?php

namespace Tests\Feature;

use App\Models\Credit;
use App\Models\Item;
use App\Models\Member;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
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

    private function createAdmin(): User
    {
        return User::create([
            'name'     => 'Test Admin',
            'email'    => 'admin@test.com',
            'password' => bcrypt('password'),
            'role'     => 'admin',
        ]);
    }

    private function createCredit(Member $member, string $amount): Credit
    {
        return Credit::create([
            'member_id' => $member->id,
            'amount'    => $amount,
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
        $this->assertSame(0, Credit::count());
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
            'member_id' => $member->id,
            'amount'    => 300.00,
        ]);
    }

    public function test_credit_sale_allowed_for_member_with_existing_credit(): void
    {
        $member = $this->createMember();
        $this->createCredit($member, '500.00');
        $this->createItem(['price' => 100.00]);
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)
            ->postJson('/checkout', [
                'items'          => [['name' => 'Rice', 'quantity' => 2]],
                'member_id'      => (string) $member->id,
                'is_non_member'  => '0',
                'payment_method' => 'credit',
            ]);

        $response->assertJson(['success' => true]);
        $this->assertSame(2, Credit::where('member_id', $member->id)->count());
        $this->assertDatabaseHas('credits', [
            'member_id' => $member->id,
            'amount'    => 200.00,
        ]);
        $this->assertEquals(700.0, (float) Credit::where('member_id', $member->id)->sum('amount'));
    }

    public function test_credit_sale_allowed_for_member_without_credit_history_and_visible_on_pages(): void
    {
        $member = $this->createMember(['member_number' => 'MEM00002']);
        $this->createItem(['price' => 100.00]);
        $cashier = $this->createCashier();
        $admin = $this->createAdmin();

        $response = $this->actingAs($cashier)
            ->postJson('/checkout', [
                'items'          => [['name' => 'Rice', 'quantity' => 2]],
                'member_id'      => (string) $member->id,
                'is_non_member'  => '0',
                'payment_method' => 'credit',
            ]);

        $response->assertJson(['success' => true]);

        $sale = Sale::where('member_id', $member->id)->firstOrFail();
        $this->assertSame('credit', $sale->payment_method);
        $this->assertSame('200.00', $sale->total);
        $this->assertSame(1, Credit::where('member_id', $member->id)->count());

        $creditsResponse = $this->actingAs($cashier)->get(route('credits.index'));
        $creditsResponse->assertOk();
        $creditsResponse->assertSee($member->member_number);
        $creditsResponse->assertSee($member->first_name . ' ' . $member->last_name);
        $creditsResponse->assertSee('&#8369;200.00', false);

        $transactionsResponse = $this->actingAs($admin)->get(route('transactions.index'));
        $transactionsResponse->assertOk();
        $transactionsResponse->assertSee('#' . $sale->id);
        $transactionsResponse->assertSee($member->first_name . ' ' . $member->last_name);
        $transactionsResponse->assertSee('Credit');
        $transactionsResponse->assertSee('&#8369;200.00', false);
    }

    public function test_credit_sale_allowed_for_newly_registered_member(): void
    {
        $admin = $this->createAdmin();
        $cashier = $this->createCashier();
        $this->createItem(['price' => 50.00]);

        $registerResponse = $this->actingAs($admin)->post(route('members.store'), [
            'first_name' => 'New',
            'last_name'  => 'Member',
            'email'      => 'new.member@example.com',
            'phone'      => '09171234567',
        ]);

        $registerResponse->assertRedirect(route('members.index'));

        $member = Member::where('email', 'new.member@example.com')->firstOrFail();
        $this->assertTrue($member->is_active);

        $response = $this->actingAs($cashier)
            ->postJson('/checkout', [
                'items'          => [['name' => 'Rice', 'quantity' => 2]],
                'member_id'      => (string) $member->id,
                'is_non_member'  => '0',
                'payment_method' => 'credit',
            ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('credits', [
            'member_id' => $member->id,
            'amount'    => 100.00,
        ]);
    }

    public function test_pos_credit_modal_has_no_balance_display_or_insufficient_credit_gate(): void
    {
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)->get(route('shop.index'));

        $response->assertOk();
        $response->assertSee('Confirm credit charge');
        $response->assertSee('Total Amount Due');
        $response->assertSee('creditPaymentModal', false);
        $response->assertDontSee('Current balance');
        $response->assertDontSee('Balance after this purchase');
        $response->assertDontSee('Outstanding Credit');
        $response->assertDontSee('creditCurrentBalance', false);
        $response->assertDontSee('creditBalanceAfter', false);
        $response->assertDontSee('creditBalanceSection', false);
        $response->assertDontSee('Credit/Balance');
        $response->assertDontSee('Insufficient credit');
        $response->assertDontSee('creditModalError', false);
        $response->assertDontSee('openPayCreditModal');
        $response->assertDontSee('Record Payment');
    }

    public function test_credit_payment_and_balance_routes_are_not_available(): void
    {
        $member = $this->createMember();
        $credit = $this->createCredit($member, '200.00');
        $cashier = $this->createCashier();

        $this->actingAs($cashier)
            ->post('/credits/' . $credit->id . '/pay', ['amount_paid' => '100.00'])
            ->assertNotFound();

        $this->actingAs($cashier)
            ->post('/members/' . $member->id . '/credits/pay', ['amount_paid' => '100.00'])
            ->assertNotFound();

        $this->actingAs($cashier)
            ->get('/members/' . $member->id . '/credit-balance')
            ->assertNotFound();
    }

    public function test_member_lookup_does_not_return_outstanding_credit_balance(): void
    {
        $member = $this->createMember();
        $this->createCredit($member, '100.00');
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)
            ->getJson('/members/lookup?exact=1&q=' . $member->member_number);

        $response->assertOk();
        $response->assertJsonMissingPath('outstanding_credit_balance');
    }

    public function test_credit_show_renders_ledger_without_payment_history(): void
    {
        $member = $this->createMember();
        $cashier = $this->createCashier();
        $credit = $this->createCredit($member, '500.00');

        $response = $this->actingAs($cashier)->get(route('credits.show', $credit));

        $response->assertOk();
        $response->assertSee('Credit #' . $credit->id);
        $response->assertSee('500.00');
        $response->assertDontSee('Payment History');
        $response->assertDontSee('Amount Paid');
        $response->assertDontSee('Remaining Balance');
        $response->assertDontSee('openPayCreditModal');
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

    // ── Credit schema ────────────────────────────────────────

    public function test_credit_payment_schema_is_removed(): void
    {
        $this->assertFalse(Schema::hasTable('credit_payments'));
        $this->assertFalse(Schema::hasColumn('credits', 'amount_paid'));
        $this->assertFalse(Schema::hasColumn('credits', 'status'));
    }

    public function test_credit_ledger_sums_all_credit_purchases(): void
    {
        $member = $this->createMember();
        $this->createCredit($member, '500.00');
        $this->createCredit($member, '300.00');
        $this->createCredit($member, '200.00');

        $this->assertEquals(1000.0, (float) Credit::where('member_id', $member->id)->sum('amount'));
    }

    // ── Credits page table ────────────────────────────────────────────────────

    public function test_credit_index_renders_without_payment_action_columns(): void
    {
        $member = $this->createMember();
        $this->createCredit($member, '1000.00');
        $this->createCredit($member, '581.00');
        $this->createCredit($member, '200.00');
        $cashier = $this->createCashier();

        $response = $this->actingAs($cashier)->get(route('credits.index'));

        $response->assertOk();
        $response->assertSee('Total Credit');
        $response->assertSee('Credit Records');
        $response->assertSee('member-credits-');
        $response->assertDontSee('Total Paid');
        $response->assertDontSee('Partial Payments');
        $response->assertDontSee('data-credit-filter', false);
        $response->assertDontSee('>Actions<', false);
        $response->assertDontSee('data-balance-cents', false);
        $response->assertDontSee('data-member-balance-cents', false);
        $response->assertDontSee('data-credit-balance-cents', false);
        $response->assertDontSee('openPayMemberModal');
        $response->assertDontSee('openPayModal');
        $response->assertDontSee('Record Credit Payment');
        $response->assertDontSee('Payment History');
    }

    public function test_credit_index_ranks_top_five_members_by_total_credit(): void
    {
        $cashier = $this->createCashier();

        foreach ([600, 500, 400, 300, 200, 100] as $amount) {
            $member = $this->createMember();
            $this->createCredit($member, number_format($amount, 2, '.', ''));
        }

        $topMember = $this->createMember();
        $this->createCredit($topMember, '700.00');

        $response = $this->actingAs($cashier)->get(route('credits.index'));

        $response->assertOk();
        $response->assertSee('Members with highest credit');
        $response->assertSee('data-credit-table', false);
        $response->assertDontSee('Total Paid');
        $response->assertDontSee('Partial');

        $rankings = $response->viewData('creditRankings');

        $this->assertSame(['all'], array_keys($rankings));
        $this->assertCount(5, $rankings['all']);
        $this->assertSame([700.0, 600.0, 500.0, 400.0, 300.0], array_column($rankings['all'], 'total'));
        $this->assertSame($topMember->member_number, $rankings['all'][0]['member_id']);
        $this->assertSame($topMember->first_name . ' ' . $topMember->last_name, $rankings['all'][0]['name']);
        $this->assertArrayNotHasKey('total_paid', $rankings['all'][0]);
        $this->assertArrayNotHasKey('balance', $rankings['all'][0]);
    }

}
