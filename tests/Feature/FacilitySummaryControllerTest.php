<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Report;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FacilitySummaryControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_sees_occupancy_hours_and_damage_frequency_per_facility(): void
    {
        $this->travelTo('2026-10-10 12:00:00');
        $admin = User::factory()->create(['role' => 'admin']);
        $reporter = User::factory()->create(['role' => 'user']);
        $facility = $this->createFacility();
        Reservation::create([
            'user_id' => $reporter->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-10 09:00:00',
            'end_time' => '2026-10-10 11:00:00',
            'status' => 'approved',
        ]);
        Reservation::create([
            'user_id' => $reporter->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-10 11:00:00',
            'end_time' => '2026-10-10 12:00:00',
            'status' => 'pending',
        ]);
        Report::create([
            'user_id' => $reporter->id,
            'facility_id' => $facility->id,
            'category' => 'Listrik',
            'description' => 'Lampu mati',
            'status' => 'new',
        ]);
        Report::create([
            'user_id' => $reporter->id,
            'facility_id' => $facility->id,
            'category' => 'AC',
            'description' => 'AC bocor',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin)->get(route('facility-reports.index', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));

        $response->assertOk();
        $response->assertViewHas('summary', function ($summary) use ($facility): bool {
            $row = $summary->firstWhere('facility_id', $facility->id);

            $this->assertSame(1, $row['reservation_count']);
            $this->assertSame(2.0, $row['reserved_hours']);
            $this->assertSame(2, $row['damage_report_count']);

            return true;
        });
    }

    public function test_facility_summary_can_be_exported_as_csv(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $facility = $this->createFacility();

        $response = $this->actingAs($admin)->get(route('facility-reports.export', [
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment;', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('Auditorium Utama', $response->streamedContent());
    }

    public function test_petugas_cannot_access_facility_summary(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);

        $response = $this->actingAs($petugas)->get(route('facility-reports.index'));

        $response->assertForbidden();
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
