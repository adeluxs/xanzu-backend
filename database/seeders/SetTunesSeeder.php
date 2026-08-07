<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SetTunesSeeder extends Seeder
{
    public function run(): void
    {
        $records = [
            ['id' => 1, 'icon' => 'global/tune-icon/bewitched.png', 'name' => 'Bewitched', 'tune' => 'global/tune/bewitched.mp3', 'status' => 1, 'created_at' => null, 'updated_at' => '2023-05-26 05:37:38'],
            ['id' => 2, 'icon' => 'global/tune-icon/crunchy.png', 'name' => 'Crunchy', 'tune' => 'global/tune/crunchy.mp3', 'status' => 0, 'created_at' => null, 'updated_at' => '2023-05-26 05:37:38'],
            ['id' => 3, 'icon' => 'global/tune-icon/expert_notification.png', 'name' => 'Expert Notification', 'tune' => 'global/tune/expert_notification.mp3', 'status' => 0, 'created_at' => null, 'updated_at' => '2023-05-26 05:37:38'],
            ['id' => 4, 'icon' => 'global/tune-icon/knock_knock.png', 'name' => 'knock knock', 'tune' => 'global/tune/knock_knock.mp3', 'status' => 0, 'created_at' => null, 'updated_at' => '2023-05-26 05:37:38'],
            ['id' => 5, 'icon' => 'global/tune-icon/silencer.png', 'name' => 'Silencer', 'tune' => 'global/tune/silencer.mp3', 'status' => 0, 'created_at' => null, 'updated_at' => '2023-05-26 05:37:38'],
            ['id' => 6, 'icon' => 'global/tune-icon/sticky.png', 'name' => 'Sticky', 'tune' => 'global/tune/sticky.mp3', 'status' => 0, 'created_at' => null, 'updated_at' => '2023-05-26 05:37:38'],
            ['id' => 7, 'icon' => 'global/tune-icon/vopvoopvooop.png', 'name' => 'Vopvoopvooop', 'tune' => 'global/tune/vopvoopvooop.mp3', 'status' => 0, 'created_at' => null, 'updated_at' => '2023-05-26 05:37:38'],
        ];

        foreach ($records as $record) {
            DB::table('set_tunes')->updateOrInsert(
                ['id' => $record['id']],
                $record
            );
        }
    }
}
