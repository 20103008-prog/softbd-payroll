<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    /**
     * Run the database seeds for Bangladesh Government Public Holidays.
     */
    public function run(): void
    {
        $holidays = [
            ['date' => '2026-02-21', 'name' => 'Shaheed Day & International Mother Language Day (আন্তর্জাতিক মাতৃভাষা দিবস)'],
            ['date' => '2026-03-26', 'name' => 'Independence & National Day (স্বাধীনতা দিবস)'],
            ['date' => '2026-04-14', 'name' => 'Bengali New Year / Pohela Boishakh (পহেলা বৈশাখ)'],
            ['date' => '2026-05-01', 'name' => 'May Day (মে দিবস)'],
            ['date' => '2026-08-15', 'name' => 'National Mourning Day (জাতীয় শোক দিবস)'],
            ['date' => '2026-09-25', 'name' => 'Government Public Holiday (আজকের সরকারি ছুটি)'],
            ['date' => '2026-10-20', 'name' => 'Durga Puja (বিজয়া দশমী)'],
            ['date' => '2026-12-16', 'name' => 'Victory Day (বিজয় দিবস)'],
            ['date' => '2026-12-25', 'name' => 'Christmas Day (যিশু খ্রিস্টের জন্মদিন)'],
        ];

        foreach ($holidays as $h) {
            Holiday::query()->updateOrCreate(
                ['date' => $h['date']],
                ['name' => $h['name']]
            );
        }
    }
}
