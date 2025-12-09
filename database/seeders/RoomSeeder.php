<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Room;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = [
            [
                'name' => 'Ruang Rapat Executive',
                'capacity' => 20,
                'description' => 'Ruang rapat eksklusif dengan fasilitas lengkap untuk meeting eksekutif dan presentasi penting. Dilengkapi dengan proyektor 4K, whiteboard interaktif, dan sistem audio premium.'
            ],
            [
                'name' => 'Ruang Rapat Lantai 1',
                'capacity' => 10,
                'description' => 'Ruang rapat berkapasitas sedang, cocok untuk team meeting dan diskusi kelompok. Tersedia proyektor, flip chart, dan koneksi internet berkecepatan tinggi.'
            ],
            [
                'name' => 'Ruang Rapat Kecil A',
                'capacity' => 6,
                'description' => 'Ruang meeting intim untuk diskusi kecil atau interview. Dilengkapi dengan meja bundar, TV LED, dan suasana yang nyaman untuk brainstorming.'
            ],
            [
                'name' => 'Ruang Rapat Kecil B',
                'capacity' => 6,
                'description' => 'Ruang meeting kompak dengan desain modern, ideal untuk one-on-one meeting atau small group discussion. Tersedia whiteboard dan koneksi video conference.'
            ],
            [
                'name' => 'Aula Serbaguna',
                'capacity' => 50,
                'description' => 'Ruang multifungsi berkapasitas besar untuk seminar, workshop, atau acara perusahaan. Fasilitas lengkap termasuk sound system, proyektor besar, dan area panggung.'
            ],
            [
                'name' => 'Ruang Rapat Lantai 2',
                'capacity' => 15,
                'description' => 'Ruang rapat dengan view outdoor yang menyegarkan, cocok untuk meeting yang membutuhkan suasana inspiratif. Dilengkapi dengan standing desk dan AC.'
            ],
            [
                'name' => 'Meeting Room Express',
                'capacity' => 4,
                'description' => 'Ruang meeting super praktis untuk quick discussion atau urgent meeting. Lokasi strategis dengan akses mudah dan fasilitas dasar yang memadai.'
            ],
            [
                'name' => 'Ruang Kreasi & Inovasi',
                'capacity' => 12,
                'description' => 'Ruang meeting kreatif dengan desain unik dan colorful, sempurna untuk brainstorming session dan creative workshop. Tersedia bean bags dan whiteboard wall.'
            ],
            [
                'name' => 'Ruang Rapat VIP',
                'capacity' => 8,
                'description' => 'Ruang meeting premium dengan interior mewah untuk client meeting atau board meeting. Dilengkapi dengan leather chairs, smart TV 65", dan coffee corner.'
            ],
            [
                'name' => 'Ruang Training Center',
                'capacity' => 30,
                'description' => 'Ruang training berkapasitas besar dengan layout classroom style. Ideal untuk pelatihan, workshop, dan orientasi karyawan baru. Tersedia komputer dan proyektor.'
            ]
        ];

        foreach ($rooms as $room) {
            Room::create($room);
        }
    }
}
