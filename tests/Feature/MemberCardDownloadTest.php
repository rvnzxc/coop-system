<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberCardDownloadTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    private function createMember(): Member
    {
        return Member::create([
            'member_number' => 'MEM00001',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_download_a_member_card(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();

        $response = $this->actingAs($admin)->get(route('members.card.download', $member->id));

        $response->assertOk();
        $response->assertHeader('content-type', 'image/svg+xml; charset=UTF-8');
        $response->assertHeader('content-disposition', 'attachment; filename="member-card-MEM00001.svg"');
        $response->assertSee('MEMBERSHIP CARD');
        $response->assertSee('Juan Dela Cruz');
        $response->assertSee('MEM00001');
        $response->assertSee('<svg', false);
    }

    public function test_member_card_page_offers_download_instead_of_print(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();

        $response = $this->actingAs($admin)->get(route('members.card', $member->id));

        $response->assertOk();
        $response->assertSee('Download Card');
        $response->assertSee('data-download-png', false);
        $response->assertDontSee('Print Card');
    }

    public function test_member_analytics_page_has_no_print_report(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();

        $response = $this->actingAs($admin)->get(route('members.analytics', $member->id));

        $response->assertOk();
        $response->assertDontSee('Print Report');
        $response->assertSee('Download Card');
        $response->assertSee('data-download-png', false);
    }

    public function test_cashier_cannot_download_a_member_card(): void
    {
        $cashier = User::create([
            'name' => 'Test Cashier',
            'email' => 'cashier@test.com',
            'password' => bcrypt('password'),
            'role' => 'cashier',
            'is_active' => true,
        ]);
        $member = $this->createMember();

        $this->actingAs($cashier)
            ->get(route('members.card.download', $member->id))
            ->assertForbidden();
    }
}
