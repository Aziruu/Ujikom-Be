<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Teacher;
use App\Models\User;

class AttendanceTransactionTest extends TestCase
{
    use RefreshDatabase; // Memastikan database kembali bersih setelah diuji

    protected $user;
    protected $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup data dummy untuk testing
        $this->user = User::factory()->create();

        $this->teacher = Teacher::create([
            'name' => 'Guru Penguji',
            'nip' => '198001012010011001',
            'rfid_uid' => 'RFID-TEST-001',
            'is_active' => true
        ]);
    }

    /**
     * Skenario 1: Menguji validasi gagal (Data tidak lengkap)
     */
    public function test_absen_gagal_karena_data_tidak_lengkap()
    {
        // Simulasi hit endpoint tanpa mengirim teacher_id, latitude, dan longitude
        $response = $this->actingAs($this->user)->postJson('/api/attendance', [
            'method' => 'manual'
        ]);

        // Ekspektasi: Server menolak dengan status 422 (Unprocessable Entity)
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['teacher_id', 'latitude', 'longitude']);
    }

    /**
     * Skenario 2: Menguji absensi berhasil menggunakan RFID
     */
    public function test_absen_berhasil_menggunakan_rfid()
    {
        // Simulasi hit endpoint menggunakan RFID (tidak butuh lokasi)
        $response = $this->actingAs($this->user)->postJson('/api/attendance', [
            'method' => 'rfid',
            'rfid_uid' => 'RFID-TEST-001'
        ]);

        // Ekspektasi: Server menerima dan membuat data (status 201)
        $response->assertStatus(201)
            ->assertJson([
                'success' => true
            ]);

        // Memastikan data absensi benar-benar tersimpan di tabel database
        $this->assertDatabaseHas('attendances', [
            'teacher_id' => $this->teacher->id,
            'method' => 'rfid'
        ]);
    }
}
