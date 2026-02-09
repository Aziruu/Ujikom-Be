<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use App\Models\Teacher;

class TeacherSeeder extends Seeder
{
    public function run()
    {
        $response = Http::get('https://zieapi.zielabs.id/api/getguru?tahun=2025');

        if ($response->successful()) {
            $dataGuru = $response->json();

            foreach ($dataGuru as $guru) {

                $nip = $guru['nip'];
                if (empty(trim($nip)) || $nip == '-') {
                    $nip = null;
                }

                Teacher::updateOrCreate(
                    ['guru_id' => $guru['guru_id']],
                    [
                        'nip'   => $nip,
                        'name'  => $guru['nama'],
                        'email' => $guru['email'] ?? null,
                        'jenis_kelamin' => $guru['jenis_kelamin'],
                        'password' => Hash::make('12345678'),
                        'photo_url' => $guru['photo'] ?? null,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
