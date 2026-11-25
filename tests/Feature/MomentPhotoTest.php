<?php

namespace Tests\Feature;

use App\Models\Moment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MomentPhotoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.uploads'));
        $this->user = User::factory()->create(['onboarding_completed' => true]);
    }

    private function momentWithPhoto(): Moment
    {
        return Moment::factory()->create([
            'user_id' => $this->user->id,
            'photo' => UploadedFile::fake()->image('old.jpg')->store('moments', config('filesystems.uploads')),
            'tags' => ['beach'],
        ]);
    }

    /** The edit form posts with a spoofed PUT so the file is carried. */
    private function updateMoment(Moment $moment, array $data = [], ?User $as = null)
    {
        return $this->actingAs($as ?? $this->user)->post(route('moments.update', $moment), [
            '_method' => 'put',
            'title' => 'Updated',
            'date' => '2026-07-01',
            ...$data,
        ]);
    }

    private function connectPartner(): User
    {
        $partner = User::factory()->create(['onboarding_completed' => true, 'partner_id' => $this->user->id]);
        $this->user->update(['partner_id' => $partner->id]);

        return $partner;
    }

    public function test_owner_and_partner_can_fetch_the_photo(): void
    {
        $moment = $this->momentWithPhoto();
        $partner = $this->connectPartner();

        foreach ([$this->user, $partner] as $viewer) {
            $this->actingAs($viewer)->get(route('moments.photo', $moment))
                ->assertOk()
                ->assertHeader('Content-Type', 'image/jpeg');
        }
    }

    public function test_a_stranger_and_a_guest_cannot_fetch_the_photo(): void
    {
        $moment = $this->momentWithPhoto();
        $stranger = User::factory()->create(['onboarding_completed' => true]);

        $this->actingAs($stranger)->get(route('moments.photo', $moment))->assertForbidden();

        auth()->logout();
        $this->get(route('moments.photo', $moment))->assertRedirect(route('login'));
    }

    public function test_photo_is_not_stored_on_the_public_disk(): void
    {
        Storage::fake('public');
        $moment = $this->momentWithPhoto();

        Storage::disk('public')->assertMissing($moment->photo);
    }

    public function test_a_moment_without_a_photo_has_no_photo_to_fetch(): void
    {
        $moment = Moment::factory()->create(['user_id' => $this->user->id, 'photo' => null]);

        $this->actingAs($this->user)->get(route('moments.photo', $moment))->assertNotFound();
    }

    public function test_photo_is_stored_on_create_and_served_through_the_photo_route(): void
    {
        $this->actingAs($this->user)->post(route('moments.store'), [
            'title' => 'Picnic',
            'date' => '2026-07-01',
            'photo' => UploadedFile::fake()->image('picnic.jpg'),
        ])->assertRedirect(route('moments.index'));

        $moment = Moment::sole();
        $this->assertNotNull($moment->photo);
        Storage::disk(config('filesystems.uploads'))->assertExists($moment->photo);

        $url = route('moments.photo', [$moment, 'v' => basename($moment->photo)]);

        $this->actingAs($this->user)->get(route('moments.index'))
            ->assertInertia(fn ($page) => $page->where('moments.0.photo', $url));
        $this->actingAs($this->user)->get(route('moments.show', $moment))
            ->assertInertia(fn ($page) => $page->where('moment.photo', $url));
    }

    public function test_photo_can_be_added_on_edit(): void
    {
        $moment = Moment::factory()->create(['user_id' => $this->user->id, 'photo' => null]);

        $this->updateMoment($moment, ['photo' => UploadedFile::fake()->image('new.png')])
            ->assertRedirect(route('moments.index'));

        Storage::disk(config('filesystems.uploads'))->assertExists($moment->fresh()->photo);
    }

    public function test_replacing_the_photo_deletes_the_old_file(): void
    {
        $moment = $this->momentWithPhoto();
        $oldPath = $moment->photo;

        $this->updateMoment($moment, ['photo' => UploadedFile::fake()->image('new.jpg')]);

        $newPath = $moment->fresh()->photo;
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk(config('filesystems.uploads'))->assertMissing($oldPath);
        Storage::disk(config('filesystems.uploads'))->assertExists($newPath);
    }

    public function test_editing_without_a_new_photo_keeps_the_photo(): void
    {
        $moment = $this->momentWithPhoto();

        $this->updateMoment($moment, ['photo' => null, 'tags' => ['beach']]);

        $moment->refresh();
        $this->assertSame('Updated', $moment->title);
        $this->assertNotNull($moment->photo);
        Storage::disk(config('filesystems.uploads'))->assertExists($moment->photo);
    }

    public function test_photo_can_be_removed_on_edit(): void
    {
        $moment = $this->momentWithPhoto();
        $oldPath = $moment->photo;

        $this->updateMoment($moment, ['remove_photo' => true]);

        $this->assertNull($moment->fresh()->photo);
        Storage::disk(config('filesystems.uploads'))->assertMissing($oldPath);
    }

    public function test_edit_without_tags_clears_them(): void
    {
        $moment = $this->momentWithPhoto();

        $this->updateMoment($moment);

        $this->assertSame([], $moment->fresh()->tags);
    }

    public function test_oversized_and_non_image_files_are_rejected(): void
    {
        $tooBig = UploadedFile::fake()->image('huge.jpg')->size(5121);
        $notAnImage = UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf');

        foreach ([$tooBig, $notAnImage] as $file) {
            $this->actingAs($this->user)->post(route('moments.store'), [
                'title' => 'Picnic',
                'date' => '2026-07-01',
                'photo' => $file,
            ])->assertSessionHasErrors('photo');
        }

        $moment = $this->momentWithPhoto();
        $this->updateMoment($moment, ['photo' => $notAnImage])->assertSessionHasErrors('photo');

        $this->assertSame(1, Moment::count());
        Storage::disk(config('filesystems.uploads'))->assertExists($moment->fresh()->photo);
    }

    public function test_partner_cannot_change_the_photo(): void
    {
        $moment = $this->momentWithPhoto();
        $partner = $this->connectPartner();

        $this->updateMoment($moment, ['remove_photo' => true], $partner)->assertForbidden();

        Storage::disk(config('filesystems.uploads'))->assertExists($moment->fresh()->photo);
    }
}
