<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_petugas_can_view_reports_from_all_users(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        $reporter = User::factory()->create();
        $facility = $this->createFacility();
        Report::create([
            'user_id' => $reporter->id,
            'facility_id' => $facility->id,
            'category' => 'Listrik',
            'description' => 'Lampu aula mati',
            'status' => 'new',
        ]);
        Report::create([
            'user_id' => $reporter->id,
            'facility_id' => $facility->id,
            'category' => 'AC',
            'description' => 'Laporan sudah selesai',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($petugas)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSeeText('Lampu aula mati');
        $response->assertSeeText($reporter->name);
        $response->assertDontSeeText('Laporan sudah selesai');
    }

    public function test_petugas_can_resolve_report_and_update_facility_availability(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        $reporter = User::factory()->create();
        $facility = $this->createFacility();
        $report = Report::create([
            'user_id' => $reporter->id,
            'facility_id' => $facility->id,
            'category' => 'Listrik',
            'description' => 'Lampu aula mati',
            'status' => 'processing',
        ]);

        $response = $this->actingAs($petugas)
            ->from(route('reports.index'))
            ->post(route('reports.updateStatus', $report), [
                'status' => 'completed',
                'resolution_note' => 'Perbaikan dijadwalkan',
                'facility_status' => 'maintenance',
            ]);

        $response->assertRedirect(route('reports.index'));
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'completed',
            'resolution_note' => 'Perbaikan dijadwalkan',
        ]);
        $this->assertDatabaseHas('facilities', [
            'id' => $facility->id,
            'status' => 'maintenance',
        ]);
    }

    public function test_regular_user_cannot_update_report_or_facility_status(): void
    {
        $user = User::factory()->create();
        $reporter = User::factory()->create();
        $facility = $this->createFacility();
        $report = Report::create([
            'user_id' => $reporter->id,
            'facility_id' => $facility->id,
            'category' => 'Listrik',
            'description' => 'Lampu aula mati',
            'status' => 'new',
        ]);

        $response = $this->actingAs($user)->post(route('reports.updateStatus', $report), [
            'status' => 'completed',
            'facility_status' => 'maintenance',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('reports', ['id' => $report->id, 'status' => 'new']);
        $this->assertDatabaseHas('facilities', ['id' => $facility->id, 'status' => 'active']);
    }

    public function test_closing_report_requires_a_resolution_note(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        $reporter = User::factory()->create(['role' => 'user']);
        $facility = $this->createFacility();
        $report = Report::create([
            'user_id' => $reporter->id,
            'facility_id' => $facility->id,
            'category' => 'Listrik',
            'description' => 'Lampu aula mati',
            'status' => 'processing',
        ]);

        $response = $this->actingAs($petugas)->post(route('reports.updateStatus', $report), [
            'status' => 'completed',
        ]);

        $response->assertSessionHasErrors('resolution_note');
        $this->assertDatabaseHas('reports', ['id' => $report->id, 'status' => 'processing']);
    }

    public function test_user_must_attach_a_photo_when_submitting_a_report(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $facility = $this->createFacility();

        $response = $this->actingAs($user)->post(route('reports.store'), [
            'facility_id' => $facility->id,
            'category' => 'Listrik',
            'description' => 'Lampu aula mati',
        ]);

        $response->assertSessionHasErrors('image');
        $this->assertDatabaseCount('reports', 0);
    }

    public function test_user_can_submit_a_report_with_a_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'user']);
        $facility = $this->createFacility();

        $response = $this->actingAs($user)->post(route('reports.store'), [
            'facility_id' => $facility->id,
            'category' => 'Listrik',
            'description' => 'Lampu aula mati',
            'image' => UploadedFile::fake()->image('lampu-rusak.jpg'),
        ]);

        $response->assertRedirect(route('reports.index'));
        $report = Report::query()->firstOrFail();
        $this->assertSame('new', $report->status);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $report->image_url));
    }

    private function createFacility(): Facility
    {
        return Facility::create([
            'name' => 'Auditorium Utama',
            'type' => 'Ruang Acara',
            'capacity' => 300,
            'location' => 'Gedung A',
            'description' => 'Aula kampus',
            'status' => 'active',
        ]);
    }
}
