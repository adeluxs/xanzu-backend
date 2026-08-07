<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CountriesSeeder::class,
            LanguagesSeeder::class,
            RolesSeeder::class,
            PermissionsSeeder::class,
            PageSettingsSeeder::class,
            SetTunesSeeder::class,
            SocialsSeeder::class,
            ThemesSeeder::class,
            TemplatesSeeder::class,
            NavigationsSeeder::class,
            LandingContentsSeeder::class,
            LandingPagesSeeder::class,
        ]);
    }
}
