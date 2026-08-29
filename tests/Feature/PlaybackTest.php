<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PlaybackTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Video $video;

    private string $assetPath;

    protected function setUp(): void
    {
        parent::setUp();

        $dir = public_path('videos');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $this->assetPath = $dir.DIRECTORY_SEPARATOR.'audit-test-asset.mp4';
        file_put_contents($this->assetPath, 'stub-video-bytes');

        $this->user = User::factory()->create();

        $course = Course::factory()->published()->create();
        $this->video = Video::factory()->for($course)->create([
            'is_free_designated' => true,
            'manifest_reference' => 'audit-test-asset.mp4',
        ]);

        $this->user->enrollments()->create([
            'course_id' => $course->id,
            'enrolled_at' => now(),
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->assetPath);

        parent::tearDown();
    }

    public function test_playback_returns_a_signed_url_that_streams_the_asset(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('videos.playback', $this->video))
            ->assertOk()
            ->assertJsonStructure(['playback_url', 'expires_at']);

        $playbackUrl = $response->json('playback_url');

        $this->assertStringContainsString('/video-playback/', $playbackUrl);

        // The signed media URL re-checks entitlement and streams the video's
        // own manifest_reference (not a hardcoded sample file).
        $this->get($playbackUrl)->assertOk();
    }

    public function test_tampered_signature_is_rejected(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('videos.playback', $this->video));
        $tampered = str_replace('video-playback/', 'video-playback/x', $response->json('playback_url'));

        $this->get($tampered)->assertForbidden();
    }

    public function test_expired_signature_is_rejected(): void
    {
        $expired = URL::temporarySignedRoute('videos.media', now()->subMinutes(10), ['video' => $this->video->id]);

        $this->actingAs($this->user)->get($expired)->assertForbidden();
    }

    public function test_media_path_traversal_is_blocked(): void
    {
        // '..' passes the charset check but realpath containment must stop it.
        $this->video->forceFill(['manifest_reference' => '..'])->save();

        $signed = URL::temporarySignedRoute('videos.media', now()->addMinutes(5), ['video' => $this->video->id]);

        $this->actingAs($this->user)->get($signed)->assertNotFound();
    }

    public function test_media_requires_entitlement(): void
    {
        $signed = URL::temporarySignedRoute('videos.media', now()->addMinutes(5), ['video' => $this->video->id]);

        $stranger = User::factory()->create();
        $this->actingAs($stranger)->get($signed)->assertForbidden();
    }

    public function test_provision_media_command_copies_placeholder_assets(): void
    {
        $this->artisan('broca:provision-media')->assertExitCode(0);

        $this->assertFileExists(public_path('videos/sample-video.mp4'));
        $this->assertFileExists(storage_path('app/private/notes/electrophysiology-summary.pdf'));
        $this->assertFileExists(storage_path('app/private/notes/neuroanatomy-broca-atlas.pdf'));
        $this->assertFileExists(storage_path('app/private/notes/thorax-clinical-guide.pdf'));
    }
}
