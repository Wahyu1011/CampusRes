<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Report;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FacilityControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_facility_search_displays_only_matching_facilities(): void
    {
        $user = User::factory()->create();
        Facility::create([
            'name' => 'Auditorium Utama',
            'type' => 'Ruang Acara',
            'capacity' => 300,
            'location' => 'Gedung A',
            'description' => 'Aula kampus',
            'status' => 'active',
        ]);
        Facility::create([
            'name' => 'Lapangan Barat',
            'type' => 'Lapangan',
            'capacity' => 50,
            'location' => 'Area Barat',
            'description' => 'Lapangan olahraga',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/facilities?search=Auditorium');

        $response->assertOk();
        $response->assertSeeText('Auditorium Utama');
        $response->assertDontSeeText('Lapangan Barat');
    }

    public function test_authenticated_user_can_filter_facilities_by_type_location_and_capacity(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Facility::create([
            'name' => 'Auditorium Utama',
            'type' => 'Ruang Acara',
            'capacity' => 300,
            'location' => 'Gedung A',
            'description' => 'Aula kampus',
            'status' => 'active',
        ]);
        Facility::create([
            'name' => 'Lapangan Barat',
            'type' => 'Lapangan',
            'capacity' => 50,
            'location' => 'Area Barat',
            'description' => 'Lapangan olahraga',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/facilities?type=Ruang&location=Gedung%20A&minimum_capacity=200');

        $response->assertOk();
        $response->assertSeeText('Auditorium Utama');
        $response->assertDontSeeText('Lapangan Barat');
    }

    public function test_guest_can_view_slot_availability_without_reservation_details(): void
    {
        $user = User::factory()->create();
        $facility = Facility::create([
            'name' => 'Auditorium Utama',
            'type' => 'Ruang Acara',
            'capacity' => 300,
            'location' => 'Gedung A',
            'description' => 'Deskripsi internal fasilitas',
            'status' => 'active',
        ]);
        Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-12 09:00:00',
            'end_time' => '2026-10-12 10:00:00',
            'status' => 'approved',
            'activity_name' => 'Rapat Rahasia',
        ]);

        $response = $this->get('/?date=2026-10-12');

        $response->assertOk();
        $response->assertSeeText('Auditorium Utama');
        $response->assertSeeText('Tersedia');
        $response->assertSeeText('Tidak tersedia');
        $response->assertDontSeeText('Rapat Rahasia');
        $response->assertDontSeeText('Deskripsi internal fasilitas');
    }

    public function test_guest_can_filter_public_facilities_by_type_location_and_capacity(): void
    {
        Facility::create([
            'name' => 'Auditorium Utama',
            'type' => 'Ruang Acara',
            'capacity' => 300,
            'location' => 'Gedung A',
            'description' => 'Aula kampus',
            'status' => 'active',
        ]);
        Facility::create([
            'name' => 'Lapangan Barat',
            'type' => 'Lapangan',
            'capacity' => 50,
            'location' => 'Area Barat',
            'description' => 'Lapangan olahraga',
            'status' => 'active',
        ]);

        $response = $this->get('/?type=Ruang&location=Gedung%20A&minimum_capacity=200');

        $response->assertOk();
        $response->assertSeeText('Auditorium Utama');
        $response->assertDontSeeText('Lapangan Barat');
        $response->assertDontSeeText('Aula kampus');
    }

    public function test_petugas_cannot_edit_master_facilities(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        $facility = Facility::create([
            'name' => 'Auditorium Utama',
            'type' => 'Ruang Acara',
            'capacity' => 300,
            'location' => 'Gedung A',
            'description' => 'Aula kampus',
            'status' => 'active',
        ]);

        $response = $this->actingAs($petugas)->get(route('facilities.edit', $facility));

        $response->assertForbidden();
    }

    public function test_public_schedule_marks_inactive_facilities_unavailable(): void
    {
        Facility::create([
            'name' => 'Auditorium Timur',
            'type' => 'Ruang Acara',
            'capacity' => 300,
            'location' => 'Gedung A',
            'description' => 'Aula kampus',
            'status' => 'inactive',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSeeText('Auditorium Timur');
        $response->assertSeeText('Nonaktif');
        $response->assertSeeText('Tidak tersedia');
    }

    public function test_public_schedule_marks_legacy_overnight_reservations_unavailable(): void
    {
        $user = User::factory()->create();
        $facility = Facility::create([
            'name' => 'Auditorium Utama',
            'type' => 'Ruang Acara',
            'capacity' => 300,
            'location' => 'Gedung A',
            'description' => 'Aula kampus',
            'status' => 'active',
        ]);
        Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-11 19:30:00',
            'end_time' => '2026-10-12 07:30:00',
            'status' => 'approved',
        ]);

        $response = $this->get('/?date=2026-10-12');

        $response->assertOk();
        $response->assertSeeText('Auditorium Utama');
        $response->assertSeeText('Tidak tersedia');
    }

    public function test_admin_can_deactivate_a_facility_without_deleting_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $facility = Facility::create([
            'name' => 'Auditorium Utama',
            'type' => 'Ruang Acara',
            'capacity' => 300,
            'location' => 'Gedung A',
            'description' => 'Aula kampus',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->put(route('facilities.update', $facility), [
            'name' => $facility->name,
            'type' => $facility->type,
            'capacity' => $facility->capacity,
            'location' => $facility->location,
            'description' => $facility->description,
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('facilities.index'));
        $this->assertDatabaseHas('facilities', [
            'id' => $facility->id,
            'status' => 'inactive',
        ]);
    }

    public function test_admin_cannot_delete_a_facility_with_history(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'user']);
        $facility = Facility::create([
            'name' => 'Auditorium Utama',
            'type' => 'Ruang Acara',
            'capacity' => 300,
            'location' => 'Gedung A',
            'description' => 'Aula kampus',
            'status' => 'active',
        ]);
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-12 09:00:00',
            'end_time' => '2026-10-12 10:00:00',
            'status' => 'approved',
        ]);
        $report = Report::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'category' => 'Listrik',
            'description' => 'Lampu aula mati',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('facilities.index'))
            ->delete(route('facilities.destroy', $facility));

        $response->assertRedirect(route('facilities.index'));
        $response->assertSessionHasErrors();
        $this->assertModelExists($facility);
        $this->assertModelExists($reservation);
        $this->assertModelExists($report);
    }

    public function test_database_restricts_deleting_a_facility_with_history(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $facility = Facility::create([
            'name' => 'Auditorium Utama',
            'type' => 'Ruang Acara',
            'capacity' => 300,
            'location' => 'Gedung A',
            'description' => 'Aula kampus',
            'status' => 'active',
        ]);
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-12 09:00:00',
            'end_time' => '2026-10-12 10:00:00',
            'status' => 'approved',
        ]);
        $report = Report::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'category' => 'Listrik',
            'description' => 'Lampu aula mati',
            'status' => 'new',
        ]);

        try {
            DB::table('facilities')->where('id', $facility->id)->delete();
            self::fail('Database should restrict facility deletion while history exists.');
        } catch (QueryException) {
            $this->assertModelExists($facility);
            $this->assertModelExists($reservation);
            $this->assertModelExists($report);
        }
    }

    public function test_database_restricts_deleting_a_user_with_history(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $facility = Facility::create([
            'name' => 'Auditorium Utama',
            'type' => 'Ruang Acara',
            'capacity' => 300,
            'location' => 'Gedung A',
            'description' => 'Aula kampus',
            'status' => 'active',
        ]);
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-12 09:00:00',
            'end_time' => '2026-10-12 10:00:00',
            'status' => 'approved',
        ]);
        $report = Report::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'category' => 'Listrik',
            'description' => 'Lampu aula mati',
            'status' => 'new',
        ]);

        try {
            DB::table('users')->where('id', $user->id)->delete();
            self::fail('Database should restrict user deletion while reservation history exists.');
        } catch (QueryException) {
            $this->assertModelExists($user);
            $this->assertModelExists($reservation);
            $this->assertModelExists($report);
        }
    }
}
