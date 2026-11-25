<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia;
use RuntimeException;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->group(function (): void {
            Route::get('_test/forbidden', fn () => abort(403));
            Route::get('_test/down', fn () => abort(503));
            Route::get('_test/broken', fn () => throw new RuntimeException('boom'));
            Route::post('_test/expired', fn () => throw new TokenMismatchException);
        });
    }

    public function test_an_unknown_url_shows_the_error_page_to_guests_and_signed_in_users(): void
    {
        $this->get('/no-such-page')
            ->assertNotFound()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Error')->where('status', 404));

        $this->actingAs(User::factory()->create())->get('/date-night/999999')
            ->assertNotFound()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Error')->where('status', 404));
    }

    public function test_forbidden_and_unavailable_show_the_error_page(): void
    {
        $this->get('_test/forbidden')
            ->assertForbidden()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Error')->where('status', 403));

        $this->get('_test/down')
            ->assertStatus(503)
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Error')->where('status', 503));
    }

    public function test_a_server_error_shows_the_error_page_unless_debugging(): void
    {
        config(['app.debug' => false]);
        $this->get('_test/broken')
            ->assertStatus(500)
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Error')->where('status', 500));

        config(['app.debug' => true]);
        $this->get('_test/broken')
            ->assertStatus(500)
            ->assertSee('boom');
    }

    public function test_json_requests_still_get_json(): void
    {
        $this->getJson('/no-such-page')
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_an_expired_page_goes_back_with_a_message(): void
    {
        $this->from('/login')->post('_test/expired')
            ->assertRedirect('/login')
            ->assertSessionHas('error', 'The page expired, please try again.');
    }
}
