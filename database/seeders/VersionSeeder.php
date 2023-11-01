<?php

namespace Database\Seeders;

use App\Models\Appversion;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class VersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $versions = [
            ['id' => 1, 'minversion' => '1.6', 'version' => '1.7', 'applink' => 'https://play.google.com/store/apps/details?id=com.whatsapp&hl=en_IN&gl=US', 'platform' => 'Android', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['id' => 2, 'minversion' => '1.6', 'version' => '1.7', 'applink' => 'https://apps.apple.com/in/app/whatsapp-messenger/id310633997', 'platform' => 'iOS', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ];

        Appversion::insert($versions);
    }
}
