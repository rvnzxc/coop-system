<?php

namespace Tests\Feature;

use App\Models\Credit;
use App\Models\Member;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberArchiveTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    private function createCashier(): User
    {
        return User::create([
            'name' => 'Cashier',
            'email' => 'cashier@example.com',
            'password' => bcrypt('password'),
            'role' => 'cashier',
        ]);
    }

    private function createMember(array $overrides = []): Member
    {
        return Member::create(array_merge([
            'member_number' => 'MEM00001',
            'first_name' => 'Reiven',
            'last_name' => 'Balbuena',
            'email' => 'reiven@example.com',
            'phone' => '+639123456789',
            'is_active' => true,
        ], $overrides));
    }

    public function test_members_page_has_archive_button_and_confirmation_modal(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();

        $this->actingAs($admin)->get(route('members.index'))
            ->assertOk()
            ->assertSee('title="Archive member"', false)
            ->assertSee('aria-label="Archive member"', false)
            ->assertSee('data-archive-url="'.route('members.archive', $member->id).'"', false)
            ->assertSee('Archive Member')
            ->assertSee('Are you sure you want to archive this member?')
            ->assertSee('data-member-name="Reiven Balbuena"', false)
            ->assertSee('data-member-number="MEM00001"', false)
            ->assertSee("Archived members won't appear in the active list or POS, but their purchase history is kept. You can restore them later.", false)
            ->assertSee('Yes, Archive');
    }

    public function test_admin_can_archive_member_and_search_is_preserved(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();

        $response = $this->actingAs($admin)->patch(route('members.archive', $member->id), [
            'search' => 'Reiven',
        ]);

        $response->assertRedirect(route('members.index', ['search' => 'Reiven']))
            ->assertSessionHas('success', 'Member archived successfully.');

        $member->refresh();

        $this->assertNotNull($member->archived_at);
        $this->assertTrue($member->is_archived);
        $this->assertFalse($member->is_active);

        $index = $this->actingAs($admin)->get(route('members.index'));
        $index->assertOk()->assertDontSee('Reiven Balbuena');
        $this->assertCount(0, $index->viewData('members'));
    }

    public function test_member_with_credit_cannot_be_archived(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();

        Credit::create([
            'member_id' => $member->id,
            'amount' => 500,
            'sale_reference' => 'TEST-CREDIT',
            'notes' => 'Unpaid credit',
        ]);

        $response = $this->actingAs($admin)->patch(route('members.archive', $member->id));

        $response->assertRedirect(route('members.index'))
            ->assertSessionHas('error', "This member still has an unpaid credit balance and can't be archived.");

        $member->refresh();

        $this->assertNull($member->archived_at);
        $this->assertFalse($member->is_archived);
        $this->assertTrue($member->is_active);
    }

    public function test_archived_member_is_not_found_in_pos_lookup_or_checkout(): void
    {
        $cashier = $this->createCashier();
        $member = $this->createMember();
        $member->forceFill(['archived_at' => now(), 'is_active' => false])->save();

        $this->actingAs($cashier)->getJson(route('members.lookup', [
            'q' => $member->member_number,
            'exact' => 1,
        ]))->assertOk()->assertJson(['found' => false]);

        $this->actingAs($cashier)->postJson(route('shop.checkout'), [
            'items' => [['id' => 1, 'quantity' => 1]],
            'member_id' => $member->id,
            'is_non_member' => '0',
        ])->assertStatus(400)->assertJson(['message' => 'This member is archived.']);
    }

    public function test_archiving_preserves_purchase_history_and_member_name(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();

        Purchase::create([
            'member_id' => $member->id,
            'member_number' => $member->member_number,
            'customer_type' => 'member',
            'amount' => 250,
            'quantity' => 1,
            'product_name' => 'Rice',
            'purchase_date' => now()->toDateString(),
        ]);

        Sale::create([
            'member_id' => $member->id,
            'member_number' => $member->member_number,
            'customer_type' => 'member',
            'payment_method' => 'cash',
            'total' => 250,
            'status' => Sale::STATUS_COMPLETED,
        ]);

        $this->actingAs($admin)->patch(route('members.archive', $member->id))
            ->assertRedirect(route('members.index'));

        $this->assertSame(1, Purchase::where('member_id', $member->id)->count());
        $this->assertSame('Reiven Balbuena', Purchase::where('member_id', $member->id)->firstOrFail()->member->full_name);
        $this->assertSame('Reiven Balbuena', Sale::where('member_id', $member->id)->firstOrFail()->member_name);

        $this->actingAs($admin)->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('Reiven Balbuena');
    }

    public function test_members_page_shows_active_and_archived_tabs_with_count(): void
    {
        $admin = $this->createAdmin();
        $this->createMember();
        $archived = $this->createMember([
            'member_number' => 'MEM00002',
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'email' => 'ana@example.com',
        ]);
        $archived->forceFill(['archived_at' => now(), 'is_active' => false])->save();

        $this->actingAs($admin)->get(route('members.index'))
            ->assertOk()
            ->assertSee('data-tab="active"', false)
            ->assertSee('Active Members')
            ->assertSee('data-tab="archived"', false)
            ->assertSee(route('members.index', ['tab' => 'archived']), false)
            ->assertSee('>1</span>', false);
    }

    public function test_archived_tab_lists_only_archived_members_with_restore_controls(): void
    {
        $admin = $this->createAdmin();
        $this->createMember();
        $archived = $this->createMember([
            'member_number' => 'MEM00002',
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'email' => 'ana@example.com',
        ]);
        $archived->forceFill(['archived_at' => now(), 'is_active' => false])->save();

        $this->actingAs($admin)->get(route('members.index', ['tab' => 'archived']))
            ->assertOk()
            ->assertSee('data-tab="archived"', false)
            ->assertSee('aria-selected="true"', false)
            ->assertSee('Archived Members')
            ->assertSee('Archived On')
            ->assertSee('Ana Santos')
            ->assertSee('MEM00002')
            ->assertDontSee('Reiven Balbuena')
            ->assertDontSee('Add Member')
            ->assertDontSee('Total Members')
            ->assertSee('title="Restore member"', false)
            ->assertSee('aria-label="Restore member"', false)
            ->assertSee('data-restore-url="'.route('members.restore', $archived->id).'"', false)
            ->assertSee('Restore Member')
            ->assertSee('Are you sure you want to restore this member?')
            ->assertSee('They will appear in the active list and can be used in POS again.')
            ->assertSee('Yes, Restore');
    }

    public function test_admin_can_restore_archived_member_from_archived_tab(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();
        $member->forceFill(['archived_at' => now(), 'is_active' => false])->save();

        $response = $this->actingAs($admin)->patch(route('members.restore', $member->id), [
            'search' => 'Reiven',
        ]);

        $response->assertRedirect(route('members.index', ['tab' => 'archived', 'search' => 'Reiven']))
            ->assertSessionHas('success', 'Member restored successfully.');

        $member->refresh();

        $this->assertNull($member->archived_at);
        $this->assertTrue($member->is_active);
        $this->assertFalse($member->is_archived);

        $this->actingAs($admin)->get(route('members.index', ['tab' => 'archived', 'search' => 'Reiven']))
            ->assertOk()
            ->assertSee('value="Reiven"', false)
            ->assertDontSee('Reiven Balbuena');

        $this->actingAs($admin)->get(route('members.index', ['search' => 'Reiven']))
            ->assertOk()
            ->assertSee('Reiven Balbuena');
    }

    public function test_active_tab_keeps_existing_members_page_controls(): void
    {
        $admin = $this->createAdmin();
        $this->createMember();

        $this->actingAs($admin)->get(route('members.index'))
            ->assertOk()
            ->assertSee('data-tab="active"', false)
            ->assertSee('Member Records')
            ->assertSee('Total Members')
            ->assertSee('Add Member')
            ->assertSee('Reiven Balbuena')
            ->assertSee('title="Archive member"', false)
            ->assertDontSee('title="Restore member"', false);
    }
}
