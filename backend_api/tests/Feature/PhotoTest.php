<?php

namespace Tests\Feature;

use App\Models\Subject;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoTest extends TestCase
{
    private function upload(Subject $subject, UploadedFile $file)
    {
        return $this->post("/api/v1/subjects/{$subject->id}/photo", ['photo' => $file], ['Accept' => 'application/json']);
    }

    private function png(string $name = 'portrait.png', int $size = 120): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $this->pngBytes($size, $size));
    }

    public function test_a_detective_can_upload_a_portrait(): void
    {
        Storage::fake('local');
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $this->upload($subject, $this->png())->assertOk()->assertJsonPath('data.photo_source', 'upload');

        $path = $subject->fresh()->photo_path;

        $this->assertStringStartsWith('subject-photos/', $path);
        $this->assertStringEndsWith('.png', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_the_name_the_client_sent_is_never_used(): void
    {
        Storage::fake('local');
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $this->upload($subject, $this->png('../../evil.php.png'))->assertOk();

        $path = $subject->fresh()->photo_path;

        $this->assertStringNotContainsString('evil', $path);
        $this->assertStringNotContainsString('..', $path);
    }

    public function test_the_link_in_the_response_is_signed_and_works_without_a_token(): void
    {
        Storage::fake('local');
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $url = $this->upload($subject, $this->png())->json('data.photo_url');

        $this->assertStringContainsString('signature=', $url);
        $this->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_the_photo_cannot_be_fetched_without_a_valid_signature(): void
    {
        Storage::fake('local');
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $this->upload($subject, $this->png())->assertOk();

        $this->get("/api/v1/subjects/{$subject->id}/photo")->assertForbidden();
        $this->get("/api/v1/subjects/{$subject->id}/photo?expires=9999999999&signature=forged")->assertForbidden();
    }

    public function test_a_file_that_is_not_an_image_is_rejected_even_with_an_image_name(): void
    {
        Storage::fake('local');
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $this->upload($subject, UploadedFile::fake()->createWithContent('photo.png', '<?php echo "hi";'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('photo');

        $this->assertNull($subject->fresh()->photo_path);
    }

    public function test_svg_is_rejected(): void
    {
        Storage::fake('local');
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200"><script>alert(1)</script></svg>';

        $this->upload($subject, UploadedFile::fake()->createWithContent('logo.svg', $svg))->assertStatus(422);
    }

    public function test_images_that_are_too_small_or_too_large_are_rejected(): void
    {
        Storage::fake('local');
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $this->upload($subject, UploadedFile::fake()->createWithContent('tiny.png', $this->pngBytes(50, 50)))->assertStatus(422);
        $this->upload($subject, UploadedFile::fake()->create('huge.png', 3000, 'image/png'))->assertStatus(422);
    }

    public function test_uploads_need_detective_clearance(): void
    {
        Storage::fake('local');
        $subject = Subject::factory()->create();
        $this->actingAsLevel(2);

        $this->upload($subject, $this->png())->assertForbidden();
    }

    public function test_a_new_upload_replaces_and_deletes_the_old_file(): void
    {
        Storage::fake('local');
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $this->upload($subject, $this->png())->assertOk();
        $first = $subject->fresh()->photo_path;

        $this->upload($subject, $this->png())->assertOk();
        $second = $subject->fresh()->photo_path;

        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);
    }

    public function test_removing_the_photo_deletes_the_file(): void
    {
        Storage::fake('local');
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $this->upload($subject, $this->png())->assertOk();
        $path = $subject->fresh()->photo_path;

        $this->deleteJson("/api/v1/subjects/{$subject->id}/photo")->assertOk()->assertJsonPath('data.photo_source', null);

        Storage::disk('local')->assertMissing($path);
        $this->assertNull($subject->fresh()->photo_path);
    }

    public function test_pasting_an_outside_link_replaces_the_upload(): void
    {
        Storage::fake('local');
        $subject = Subject::factory()->create();
        $this->actingAsLevel(3);

        $this->upload($subject, $this->png())->assertOk();
        $path = $subject->fresh()->photo_path;

        $this->putJson("/api/v1/subjects/{$subject->id}", ['photo_url' => 'https://example.com/portrait.jpg'])
            ->assertOk()
            ->assertJsonPath('data.photo_source', 'url')
            ->assertJsonPath('data.photo_url', 'https://example.com/portrait.jpg');

        Storage::disk('local')->assertMissing($path);
    }

    public function test_the_export_does_not_include_live_image_links(): void
    {
        Storage::fake('local');
        $subject = Subject::factory()->create();
        $this->actingAsLevel(4);

        $this->upload($subject, $this->png())->assertOk();

        $rows = json_decode($this->get('/api/v1/subjects/export')->assertOk()->streamedContent(), true);

        $this->assertNull($rows[0]['photo_url']);
        $this->assertSame('upload', $rows[0]['photo_source']);
    }
}
