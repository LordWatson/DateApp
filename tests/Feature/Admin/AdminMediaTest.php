<?php

namespace Tests\Feature\Admin;

use App\Models\AdminAuditLog;
use App\Models\MediaLibrary;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminMediaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('filesystems.uploads'));
        $this->admin = User::factory()->create(['role_id' => Role::factory()->superAdmin()->create()->id]);
    }

    private function upload(UploadedFile $file): MediaLibrary
    {
        $this->actingAs($this->admin)->post('/admin/media', ['file' => $file])->assertSessionHasNoErrors();

        return MediaLibrary::sole();
    }

    /**
     * A fake upload reports the type its name suggests; a real file is judged by its content.
     */
    private function realFile(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    public function test_an_image_is_stored_on_the_uploads_disk_and_served_to_admins_only(): void
    {
        $media = $this->upload(UploadedFile::fake()->image('banner.png'));

        $this->assertSame(config('filesystems.uploads'), $media->disk);
        $this->assertStringEndsWith('.png', $media->path);
        Storage::disk($media->disk)->assertExists($media->path);
        $this->assertSame(route('admin.media.show', $media), $media->url);

        $this->actingAs($this->admin)->get($media->url)->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->actingAs(User::factory()->create())->get($media->url)->assertForbidden();
    }

    public function test_files_that_are_not_images_are_rejected(): void
    {
        $files = [
            UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
            // The name says image, the content does not.
            $this->realFile('shell.png', '<?php echo 1;'),
            $this->realFile('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
            UploadedFile::fake()->image('huge.jpg')->size(10241),
        ];

        foreach ($files as $file) {
            $this->actingAs($this->admin)->post('/admin/media', ['file' => $file])->assertSessionHasErrors('file');
        }

        $this->assertSame(0, MediaLibrary::count());
    }

    public function test_updating_media_is_audited(): void
    {
        $media = $this->upload(UploadedFile::fake()->image('banner.png'));

        $this->actingAs($this->admin)->put("/admin/media/{$media->id}", ['alt' => 'A banner', 'folder' => 'heroes'])
            ->assertRedirect();

        $log = AdminAuditLog::where('action', 'media.updated')->sole();
        $this->assertSame($media->id, $log->auditable_id);
        $this->assertSame(['alt' => 'A banner', 'folder' => 'heroes'], $log->after);
    }
}
