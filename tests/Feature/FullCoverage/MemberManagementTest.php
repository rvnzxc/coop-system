<?php

namespace Tests\Feature\FullCoverage;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_member_gets_sequential_member_number(): void
    {
        $this->actingAs($this->admin())->post(route('members.store'), [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.com',
            'phone' => '0917 123 4567',
        ])->assertRedirect(route('members.index'));

        $member = Member::sole();
        $this->assertSame('MEM00001', $member->member_number);
        $this->assertSame('+639171234567', $member->phone);
    }

    public function test_member_validation_rejects_invalid_names_phone_and_email(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('members.store'), [
            'first_name' => 'Juan123',
            'last_name' => 'Cruz',
        ])->assertSessionHasErrors('first_name');

        $this->post(route('members.store'), [
            'first_name' => 'Juan',
            'last_name' => 'Cruz',
            'phone' => '12345',
        ])->assertSessionHasErrors('phone');

        $this->post(route('members.store'), [
            'first_name' => 'Juan',
            'last_name' => 'Cruz',
            'email' => 'not-an-email',
        ])->assertSessionHasErrors('email');

        $this->assertSame(0, Member::count());
    }

    public function test_member_photo_upload_and_removal_use_public_disk(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());

        $this->post(route('members.store'), [
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'photo' => UploadedFile::fake()->createWithContent(
                'ana.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==')
            ),
        ])->assertRedirect(route('members.index'));

        $member = Member::sole();
        $this->assertNotNull($member->photo);
        Storage::disk('public')->assertExists($member->photo);
        $this->assertNotNull($member->photo_url);

        $this->put(route('members.update', $member->id), [
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'is_active' => '1',
            'remove_photo' => '1',
        ])->assertRedirect(route('members.index'));

        $this->assertNull($member->fresh()->photo);
    }

    public function test_member_with_credit_cannot_be_archived_until_balance_is_cleared(): void
    {
        $member = $this->member();
        $this->credit(['member_id' => $member->id, 'amount' => 500]);

        $this->actingAs($this->admin())->patch(route('members.archive', $member->id))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull($member->fresh()->archived_at);
    }

    public function test_member_can_be_archived_and_restored(): void
    {
        $member = $this->member();

        $this->actingAs($this->admin())->patch(route('members.archive', $member->id))
            ->assertRedirect();
        $this->assertNotNull($member->fresh()->archived_at);
        $this->assertFalse($member->fresh()->is_active);

        $this->patch(route('members.restore', $member->id))
            ->assertRedirect();
        $this->assertNull($member->fresh()->archived_at);
        $this->assertTrue($member->fresh()->is_active);
    }

    public function test_member_lookup_accepts_partial_id_and_blocks_inactive_members(): void
    {
        $this->actingAs($this->cashier());
        $member = $this->member(['member_number' => 'MEM00042']);

        $this->getJson(route('members.lookup', ['q' => '42']))
            ->assertOk()
            ->assertJsonPath('found', true)
            ->assertJsonPath('member.id', $member->id);

        $member->is_active = false;
        $member->save();

        $this->getJson(route('members.lookup', ['q' => 'MEM00042']))
            ->assertStatus(403)
            ->assertJsonPath('is_active', false);
    }

    public function test_member_card_and_download_render_with_expected_headers(): void
    {
        $member = $this->member(['member_number' => 'MEM00077']);

        $this->actingAs($this->admin())->get(route('members.card', $member->id))
            ->assertOk()
            ->assertSee('MEM00077');

        $this->get(route('members.card.download', $member->id))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/svg+xml; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="member-card-MEM00077.svg"');
    }
}
