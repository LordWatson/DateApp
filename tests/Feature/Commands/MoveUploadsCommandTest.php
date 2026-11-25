<?php

namespace Tests\Feature\Commands;

use App\Models\Moment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MoveUploadsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_moves_moment_photos_off_the_public_disk_and_can_run_again(): void
    {
        Storage::fake('public');
        $uploads = Storage::fake(config('filesystems.uploads'));

        Storage::disk('public')->put('moments/old.jpg', 'old');
        Storage::disk('public')->put('unrelated.txt', 'stay');
        $uploads->put('moments/new.jpg', 'new');
        Moment::factory()->create(['photos' => ['moments/old.jpg']]);
        Moment::factory()->create(['photos' => ['moments/new.jpg']]);
        Moment::factory()->create(['photos' => ['moments/lost.jpg']]);

        $this->artisan('datenight:move-uploads')->assertSuccessful();
        $this->artisan('datenight:move-uploads')->assertSuccessful();

        $this->assertSame('old', $uploads->get('moments/old.jpg'));
        $this->assertSame('new', $uploads->get('moments/new.jpg'));
        Storage::disk('public')->assertMissing('moments/old.jpg');
        Storage::disk('public')->assertExists('unrelated.txt');
    }
}
