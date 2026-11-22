<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        $response->assertSee('Juan Dela Cruz');
        $response->assertSee('MEM00001');
        $response->assertSee('data:image/jpeg;base64,', false);
        $response->assertSee('<svg', false);
        $response->assertDontSee('MEMBERSHIP CARD');
        $response->assertDontSee('Cavite College of Fisheries Multi-Purpose Cooperative');
        $response->assertDontSee('logo-trimmed.png');
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
        $response->assertSee('aria-label="Back to Members"', false);
        $response->assertSee('href="'.route('members.index').'"', false);
        $response->assertSee('Back');
        $response->assertSee($member->first_name.' '.$member->last_name);
        $response->assertSee('<svg xmlns="http://www.w3.org/2000/svg" width="700" height="450"', false);
        $response->assertSee('memberCardClip', false);
        $response->assertSee('data:image/jpeg;base64,', false);
        $response->assertSee('max-w-[350px]', false);
        $response->assertSee('rounded-[16px]', false);
        $response->assertDontSee('max-w-[700px]', false);
        $response->assertDontSee('images/IDCARD.jpg');
        $response->assertDontSee('MEMBERSHIP CARD');
        $response->assertDontSee('logo-trimmed.png');
    }

    public function test_preview_and_download_render_the_same_card_markup(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();
        $cardMarkup = view('members.partials.member-card', ['member' => $member])->render();

        $this->assertSame(1, substr_count($cardMarkup, $member->first_name.' '.$member->last_name));
        $this->assertSame(1, substr_count($cardMarkup, 'transform="translate(97 281) scale(2)"'));

        $this->actingAs($admin)->get(route('members.card', $member->id))
            ->assertOk()
            ->assertSee($cardMarkup, false);

        $this->actingAs($admin)->get(route('members.card.download', $member->id))
            ->assertOk()
            ->assertSee($cardMarkup, false);
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

    public function test_cashier_is_redirected_away_from_member_card_downloads(): void
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
            ->assertRedirect(route('shop.index'));
    }

    public function test_admin_can_upload_member_photo_used_on_card(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $photoContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==', true);

        $this->actingAs($admin)->post(route('members.store'), [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria@example.com',
            'phone' => '+639123456789',
            'photo' => UploadedFile::fake()->createWithContent('member-one.png', $photoContent),
        ])->assertRedirect(route('members.index'));

        $member = Member::where('email', 'maria@example.com')->firstOrFail();
        $firstPhotoPath = $member->photo;

        Storage::disk('public')->assertExists($firstPhotoPath);
        $this->assertStringStartsWith('members/photos/', $firstPhotoPath);
        $this->assertStringStartsWith(Storage::disk('public')->url($firstPhotoPath).'?v=', $member->photo_url);

        $this->actingAs($admin)->get(route('members.card', $member->id))
            ->assertOk()
            ->assertSee(base64_encode(Storage::disk('public')->get($member->photo)), false)
            ->assertSee('clip-path="url(#memberAvatarClip)"', false);

        $this->actingAs($admin)->put(route('members.update', $member->id), [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'maria@example.com',
            'phone' => '+639123456789',
            'is_active' => 1,
            'photo' => UploadedFile::fake()->createWithContent('member-two.png', $photoContent),
        ])->assertRedirect(route('members.index'));

        $member->refresh();

        Storage::disk('public')->assertMissing($firstPhotoPath);
        Storage::disk('public')->assertExists($member->photo);

        $download = $this->actingAs($admin)->get(route('members.card.download', $member->id));

        $download->assertOk();
        $download->assertSee(base64_encode(Storage::disk('public')->get($member->photo)), false);
    }

    public function test_member_card_preview_normalizes_legacy_storage_path(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $member = $this->createMember();
        $photoContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==', true);
        $relativePath = 'members/photos/legacy.jpg';

        Storage::disk('public')->put($relativePath, $photoContent);
        $member->update(['photo' => '/storage/'.$relativePath]);

        $preview = $this->actingAs($admin)->get(route('members.card', $member->id));
        $preview->assertOk()
            ->assertSee(base64_encode($photoContent), false)
            ->assertDontSee('/storage/'.$relativePath, false);

        $download = $this->actingAs($admin)->get(route('members.card.download', $member->id));
        $download->assertOk()
            ->assertSee(base64_encode($photoContent), false);
    }

    public function test_member_card_preview_hides_missing_photo_and_keeps_initials(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $member = $this->createMember();
        $member->update(['photo' => 'members/photos/missing.jpg']);

        $response = $this->actingAs($admin)->get(route('members.card', $member->id));
        $response->assertOk()
            ->assertDontSee('members/photos/missing.jpg', false)
            ->assertSee('<svg xmlns="http://www.w3.org/2000/svg" width="700" height="450"', false)
            ->assertSee('memberCardClip', false)
            ->assertSee('JD');
    }

    public function test_downloaded_card_embeds_photo_when_app_url_has_subpath(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $member = $this->createMember();
        $downloadUrl = route('members.card.download', $member->id);
        $photoContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==', true);

        Storage::disk('public')->put('members/photos/subpath.jpg', $photoContent);
        $member->update(['photo' => 'members/photos/subpath.jpg']);
        config(['app.url' => 'http://localhost/coop-system-main/public']);

        $response = $this->actingAs($admin)->get($downloadUrl);

        $response->assertOk();
        $response->assertSee(base64_encode($photoContent), false);
    }

    public function test_member_forms_include_live_photo_preview(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();

        $this->actingAs($admin)->get(route('members.create'))
            ->assertOk()
            ->assertSee('data-photo-upload', false)
            ->assertSee('data-photo-dropzone', false)
            ->assertSee('Click to upload or drag and drop')
            ->assertSee('JPG, PNG or WEBP. Max 2 MB.', false)
            ->assertSee('URL.createObjectURL', false)
            ->assertSee('URL.revokeObjectURL', false)
            ->assertSee('accept="image/jpeg,image/png,image/webp"', false)
            ->assertSee('name="remove_photo"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertDontSee('Choose File', false)
            ->assertDontSee('No file chosen', false);

        $this->actingAs($admin)->get(route('members.edit', $member->id))
            ->assertOk()
            ->assertSee('data-photo-upload', false)
            ->assertSee('data-photo-image', false)
            ->assertSee('data-photo-initials', false)
            ->assertSee('data-photo-dropzone', false)
            ->assertSee('data-photo-file-name', false)
            ->assertSee('data-photo-remove', false)
            ->assertSee('Click to upload or drag and drop')
            ->assertSee('Remove photo')
            ->assertSee('JPG, PNG or WEBP. Max 2 MB.', false)
            ->assertSee('Please choose a JPG, PNG or WEBP image.', false)
            ->assertSee('Photo must not be larger than 2 MB.', false)
            ->assertSee('URL.createObjectURL', false)
            ->assertSee('URL.revokeObjectURL', false)
            ->assertSee('accept="image/jpeg,image/png,image/webp"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertDontSee('Upload image', false);
    }

    public function test_photo_validation_errors_are_clear(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('members.store'), [
            'first_name' => 'Invalid',
            'last_name' => 'Format',
            'email' => 'invalid.format@example.com',
            'phone' => '+639123456789',
            'photo' => UploadedFile::fake()->createWithContent('photo.heic', 'not-an-image'),
        ])->assertSessionHasErrors(['photo' => 'Please upload a JPG, PNG, or WebP image.']);

        $photoContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==', true);
        $oversizePhoto = $photoContent.str_repeat("\0", 2 * 1024 * 1024);

        $this->actingAs($admin)->post(route('members.store'), [
            'first_name' => 'Too',
            'last_name' => 'Large',
            'email' => 'too.large@example.com',
            'phone' => '+639123456789',
            'photo' => UploadedFile::fake()->createWithContent('big.png', $oversizePhoto),
        ])->assertSessionHasErrors(['photo' => 'Photo must not be larger than 2 MB.']);
    }

    public function test_member_update_keeps_existing_photo_when_no_new_file_is_chosen(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $member = $this->createMember();
        $photoContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==', true);
        $photoPath = 'members/photos/kept.jpg';

        Storage::disk('public')->put($photoPath, $photoContent);
        $member->update(['photo' => $photoPath]);

        $this->actingAs($admin)->put(route('members.update', $member->id), [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.com',
            'phone' => '+639123456789',
            'is_active' => 1,
        ])->assertRedirect(route('members.index'));

        $member->refresh();

        $this->assertSame($photoPath, $member->photo);
        Storage::disk('public')->assertExists($photoPath);
    }

    public function test_member_update_removes_photo_when_requested(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $member = $this->createMember();
        $photoContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==', true);
        $photoPath = 'members/photos/removed.jpg';

        Storage::disk('public')->put($photoPath, $photoContent);
        $member->update(['photo' => $photoPath]);

        $this->actingAs($admin)->put(route('members.update', $member->id), [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.com',
            'phone' => '+639123456789',
            'is_active' => 1,
            'remove_photo' => 1,
        ])->assertRedirect(route('members.index'));

        $member->refresh();

        $this->assertNull($member->photo);
        Storage::disk('public')->assertMissing($photoPath);
    }

    public function test_member_edit_shows_photo_validation_error(): void
    {
        Storage::fake('public');
        $admin = $this->createAdmin();
        $member = $this->createMember();

        $this->actingAs($admin)->put(route('members.update', $member->id), [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan@example.com',
            'phone' => '+639123456789',
            'is_active' => 1,
            'photo' => UploadedFile::fake()->createWithContent('notes.pdf', 'not-an-image'),
        ])->assertSessionHasErrors(['photo' => 'Please upload a JPG, PNG, or WebP image.']);

        $this->actingAs($admin)->get(route('members.edit', $member->id))
            ->assertOk()
            ->assertSee('Please upload a JPG, PNG, or WebP image.');
    }
}
