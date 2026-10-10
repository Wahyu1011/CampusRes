<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ReservationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_reservation_accepts_multiple_aligned_slots(): void
    {
        $this->travelTo('2026-10-10 08:00:00');
        $user = User::factory()->create(['role' => 'user']);
        $facility = $this->createFacility();

        $response = $this->actingAs($user)->post(route('reservations.store'), $this->reservationData($facility));

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-12 09:00:00',
            'end_time' => '2026-10-12 10:00:00',
            'status' => 'pending',
        ]);
    }

    public function test_reservation_accepts_end_time_at_closing_hour(): void
    {
        $this->travelTo('2026-10-10 08:00:00');
        $user = User::factory()->create(['role' => 'user']);
        $facility = $this->createFacility();
        $data = $this->reservationData($facility);
        $data['start_time'] = '2026-10-12 19:30:00';
        $data['end_time'] = '2026-10-12 20:00:00';

        $response = $this->actingAs($user)->post(route('reservations.store'), $data);

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('reservations', [
            'facility_id' => $facility->id,
            'start_time' => '2026-10-12 19:30:00',
            'end_time' => '2026-10-12 20:00:00',
        ]);
    }

    public function test_reservation_rejects_a_range_that_crosses_dates(): void
    {
        $this->travelTo('2026-10-10 08:00:00');
        $user = User::factory()->create(['role' => 'user']);
        $facility = $this->createFacility();
        $data = $this->reservationData($facility);
        $data['start_time'] = '2026-10-12 19:30:00';
        $data['end_time'] = '2026-10-13 07:30:00';

        $response = $this->actingAs($user)->post(route('reservations.store'), $data);

        $response->assertSessionHasErrors('end_time');
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_reservation_rejects_a_facility_under_maintenance(): void
    {
        $this->travelTo('2026-10-10 08:00:00');
        $user = User::factory()->create(['role' => 'user']);
        $facility = $this->createFacility();
        $facility->update(['status' => 'maintenance']);

        $response = $this->actingAs($user)->post(route('reservations.store'), $this->reservationData($facility));

        $response->assertSessionHasErrors('facility_id');
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_petugas_cannot_approve_a_reservation_for_an_inactive_facility(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        $user = User::factory()->create(['role' => 'user']);
        $facility = $this->createFacility();
        $facility->update(['status' => 'inactive']);
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-12 09:00:00',
            'end_time' => '2026-10-12 10:00:00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($petugas)
            ->from(route('reservations.index'))
            ->post(route('reservations.updateStatus', $reservation), ['status' => 'approved']);

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'status' => 'pending']);
    }

    public function test_petugas_can_approve_a_reservation(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        $facility = $this->createFacility();
        $reservation = Reservation::create([
            'user_id' => User::factory()->create()->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-12 09:00:00',
            'end_time' => '2026-10-12 10:00:00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($petugas)
            ->from(route('reservations.index'))
            ->post(route('reservations.updateStatus', $reservation), ['status' => 'approved']);

        $response->assertRedirect(route('reservations.index'));
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'approved',
        ]);
    }

    public function test_petugas_cannot_approve_a_reservation_that_overlaps_an_approved_one(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        $user = User::factory()->create(['role' => 'user']);
        $facility = $this->createFacility();
        Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-12 09:00:00',
            'end_time' => '2026-10-12 10:00:00',
            'status' => 'approved',
        ]);
        $conflictingReservation = Reservation::create([
            'user_id' => User::factory()->create(['role' => 'user'])->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-12 09:30:00',
            'end_time' => '2026-10-12 10:30:00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($petugas)
            ->from(route('reservations.index'))
            ->post(route('reservations.updateStatus', $conflictingReservation), ['status' => 'approved']);

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('reservations', [
            'id' => $conflictingReservation->id,
            'status' => 'pending',
        ]);
    }

    public function test_petugas_reservation_queue_defaults_to_pending_and_can_filter_approved(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        $facility = $this->createFacility();
        $user = User::factory()->create(['role' => 'user']);
        Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-12 09:00:00',
            'end_time' => '2026-10-12 10:00:00',
            'status' => 'pending',
            'activity_name' => 'Reservasi Menunggu',
        ]);
        Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-12 10:00:00',
            'end_time' => '2026-10-12 11:00:00',
            'status' => 'approved',
            'activity_name' => 'Reservasi Disetujui',
        ]);

        $pendingResponse = $this->actingAs($petugas)->get(route('reservations.index'));
        $approvedResponse = $this->get(route('reservations.index', ['status' => 'approved']));

        $pendingResponse->assertOk();
        $pendingResponse->assertSeeText('Reservasi Menunggu');
        $pendingResponse->assertDontSeeText('Reservasi Disetujui');
        $approvedResponse->assertOk();
        $approvedResponse->assertSeeText('Reservasi Disetujui');
        $approvedResponse->assertDontSeeText('Reservasi Menunggu');
    }

    public function test_petugas_cannot_submit_a_reservation(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);

        $response = $this->actingAs($petugas)->post(route('reservations.store'), []);

        $response->assertForbidden();
        $this->assertDatabaseCount('reservations', 0);
    }

    public function test_petugas_can_cancel_a_reservation_with_a_reason(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        $facility = $this->createFacility();
        $reservation = Reservation::create([
            'user_id' => User::factory()->create()->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-12 09:00:00',
            'end_time' => '2026-10-12 10:00:00',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($petugas)
            ->from(route('reservations.index'))
            ->post(route('reservations.adminCancel', $reservation), ['cancellation_reason' => 'Perubahan jadwal kampus']);

        $response->assertRedirect(route('reservations.index'));
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Perubahan jadwal kampus',
        ]);
    }

    public function test_petugas_cannot_cancel_a_pending_reservation(): void
    {
        $petugas = User::factory()->create(['role' => 'petugas']);
        $facility = $this->createFacility();
        $reservation = Reservation::create([
            'user_id' => User::factory()->create(['role' => 'user'])->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-12 09:00:00',
            'end_time' => '2026-10-12 10:00:00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($petugas)
            ->from(route('reservations.index'))
            ->post(route('reservations.adminCancel', $reservation), ['cancellation_reason' => 'Perubahan mendadak']);

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'pending',
        ]);
    }

    public function test_regular_user_cannot_approve_a_reservation(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $facility = $this->createFacility();
        $reservation = Reservation::create([
            'user_id' => User::factory()->create()->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-12 09:00:00',
            'end_time' => '2026-10-12 10:00:00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->post(route('reservations.updateStatus', $reservation), ['status' => 'approved']);

        $response->assertForbidden();
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_cancel_their_reservation_before_start_time(): void
    {
        $this->travelTo('2026-10-10 08:00:00');
        $user = User::factory()->create(['role' => 'user']);
        $facility = $this->createFacility();
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-12 09:00:00',
            'end_time' => '2026-10-12 10:00:00',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->post(route('reservations.cancel', $reservation));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'status' => 'cancelled']);
    }

    public function test_user_cannot_cancel_their_reservation_after_start_time(): void
    {
        $this->travelTo('2026-10-12 09:01:00');
        $user = User::factory()->create(['role' => 'user']);
        $facility = $this->createFacility();
        $reservation = Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-12 09:00:00',
            'end_time' => '2026-10-12 10:00:00',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->post(route('reservations.cancel', $reservation));

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'status' => 'approved']);
    }

    #[DataProvider('invalidReservationTimes')]
    public function test_reservation_rejects_times_outside_slot_boundaries(string $field, string $value): void
    {
        $this->travelTo('2026-10-10 08:00:00');
        $user = User::factory()->create(['role' => 'user']);
        $facility = $this->createFacility();
        $data = $this->reservationData($facility);
        $data[$field] = $value;

        $response = $this->actingAs($user)->post(route('reservations.store'), $data);

        $response->assertSessionHasErrors($field);
        $this->assertDatabaseCount('reservations', 0);
    }

    public static function invalidReservationTimes(): array
    {
        return [
            'start has seconds' => ['start_time', '2026-10-12 09:00:15'],
            'end has seconds' => ['end_time', '2026-10-12 10:00:15'],
            'start is not slot-aligned' => ['start_time', '2026-10-12 09:15:00'],
            'end is not slot-aligned' => ['end_time', '2026-10-12 10:15:00'],
            'start is at closing time' => ['start_time', '2026-10-12 20:00:00'],
            'end exceeds closing time' => ['end_time', '2026-10-12 20:30:00'],
        ];
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

    private function reservationData(Facility $facility): array
    {
        return [
            'facility_id' => $facility->id,
            'start_time' => '2026-10-12 09:00:00',
            'end_time' => '2026-10-12 10:00:00',
            'nim' => '12345678',
            'department' => 'Informatika',
            'phone' => '081234567890',
            'activity_name' => 'Rapat Unit',
            'participant_count' => 20,
            'commitment_agreed' => '1',
        ];
    }

    public function test_user_sees_only_their_reservations_for_the_selected_date(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
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
            'start_time' => '2026-10-12 09:00:00',
            'end_time' => '2026-10-12 10:00:00',
            'status' => 'pending',
            'activity_name' => 'Rapat Saya',
        ]);
        Reservation::create([
            'user_id' => $otherUser->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-12 11:00:00',
            'end_time' => '2026-10-12 12:00:00',
            'status' => 'pending',
            'activity_name' => 'Rapat Pengguna Lain',
        ]);
        Reservation::create([
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'start_time' => '2026-10-13 09:00:00',
            'end_time' => '2026-10-13 10:00:00',
            'status' => 'pending',
            'activity_name' => 'Rapat Hari Lain',
        ]);

        $response = $this->actingAs($user)->get('/reservations?date=2026-10-12');

        $response->assertOk();
        $response->assertSeeText('Rapat Saya');
        $response->assertDontSeeText('Rapat Pengguna Lain');
        $response->assertDontSeeText('Rapat Hari Lain');
    }
}
