<?php

namespace Database\Seeders;

use App\Models\OfficerTerm;
use Illuminate\Database\Seeder;

/** Seed the About page's existing 2026-2027 display once without replacing adviser edits. */
class OfficerTermSeeder extends Seeder
{
    public function run(): void
    {
        $officers = [
            ['Khyle Alegre', 'Adviser', 'images/officerimg/Khyle Alegre 1.png'],
            ['Lance Carlo Bernabe', 'Student Adviser', 'resources/images/officers/lance-carlo-bernabe.jpg'],
            ['Michaelle Vickeemae Sarmiento', 'President', 'resources/images/officers/michaelle-vickeema-sarmiento.jpg'],
            ['Irish Nicole Bernardo', 'Vice President (Internal)', 'resources/images/officers/irish-nicole-bernabe.jpg'],
            ['Charlotte Sapnu', 'Vice President (External)', 'resources/images/officers/charlotte-sapnu.jpg'],
            ['Lian San Diego', 'Secretary', 'resources/images/officers/lian-san-diego.jpg'],
            ['John Daniel Bayani', 'Treasurer', null],
            ['Xyra Shannel Alvarez', 'Auditor', null],
            ['Chanel Jeraldine Fernandez', 'Public Information Officer', null],
            ['Robert John Garcia', 'Business Manager', null],
            ['Jhan Mino Daracan', 'Social Media Manager', null],
            ['Febbie Ann Escoto', 'Multimedia (Creative)', null],
            ['Sophia Cassandra Pare', 'Multimedia (Documentation)', null],
            ['Ashley Alessandra Annunciation', 'IT Representative I', null],
            ['Izhar Henjie Allague', 'IT Representative II', null],
        ];

        foreach ($officers as $order => [$name, $position, $photo]) {
            OfficerTerm::firstOrCreate(
                ['academic_year' => '2026-2027', 'name' => $name, 'position' => $position],
                ['photo' => $photo, 'sort_order' => $order]
            );
        }

        // TODO: Add the real officers for A.Y. 2023-2024; do not seed invented names.
        // ['2023-2024', 'Real name', 'Real position', null],
        // TODO: Add the real officers for A.Y. 2024-2025; do not seed invented names.
        // ['2024-2025', 'Real name', 'Real position', null],
        // TODO: Add the real officers for A.Y. 2025-2026; do not seed invented names.
        // ['2025-2026', 'Real name', 'Real position', null],
    }
}
