<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Computer;
use App\Models\Place;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();


        Place::create([
            'name' => '電気炉',
        ]);

        Place::create([
            'name' => '生型製造ライン', 
        ]);

        Place::create([
            'name' => 'フラン',
        ]);

        Place::create([
            'name' => '中子',
        ]);
        Place::create([
            'name' => '塗装',
        ]);
        Place::create([
            'name' => 'ショット',
        ]);
        Place::create([
            'name' => '仕上げ',
        ]);
        Place::create([
            'name' => '出荷検査',
        ]);
        Place::create([
            'name' => '外気温',
        ]);
        Place::create([
            'name' => '事務所',
        ]);


        Computer::create([
            'name' => 'Akioka-Raspberrypi3-01',
            'place_id' => 1,
            'ip_address' => '192.168.210.92',
            'mac_address' => 'b8:27:eb:5a:61:cc',
            'signage' => 0,
            'sensor' => 1,
        ]);
        Computer::create([
            'name' => 'Akioka-Raspberrypi3-02',
            'place_id' => 2,
            'ip_address' => '192.168.210.93',
            'mac_address' => 'b8:27:eb:48:7c:c2',
            'signage' => 0,
            'sensor' => 1,
        ]);
        Computer::create([
            'name' => 'Akioka-Raspberrypi4-01',
            'place_id' => 3,
            'ip_address' => '192.168.210.94',
            'mac_address' => 'd8:3a:dd:85:b5:84',
            'signage' => 1,
            'sensor' => 1,
        ]);
        Computer::create([
            'name' => 'Akioka-Raspberrypi4-02',
            'place_id' => 4,
            'ip_address' => '192.168.210.95',
            'mac_address' => 'd8:3a:dd:85:b5:61',
            'signage' => 1,
            'sensor' => 1,
        ]);
        Computer::create([
            'name' => 'Akioka-Raspberrypi4-03',
            'place_id' => 5,
            'ip_address' => '192.168.210.96',
            'mac_address' => 'd8:3a:dd:85:b6:5d',
            'signage' => 1,
            'sensor' => 1,
        ]);
        Computer::create([
            'name' => 'Akioka-Raspberrypi4-04',
            'place_id' => 6,
            'ip_address' => '192.168.210.97',
            'mac_address' => 'd8:3a:dd:85:b6:24',
            'signage' => 1,
            'sensor' => 1,
        ]);
        Computer::create([
            'name' => 'Akioka-Raspberrypi4-05',
            'place_id' => 7,
            'ip_address' => '192.168.210.98',
            'mac_address' => 'd8:3a:dd:85:b5:e9',
            'signage' => 1,
            'sensor' => 1,
        ]);
        Computer::create([
            'name' => 'Akioka-Raspberrypi4-06',
            'place_id' => 8,
            'ip_address' => '192.168.210.99',
            'mac_address' => 'd8:3a:dd:85:b6:f4',
            'signage' => 1,
            'sensor' => 1,
        ]);
        Computer::create([
            'name' => 'Akioka-Raspberrypi4-07',
            'place_id' => 9,
            'ip_address' => '192.168.210.100',
            'mac_address' => 'd8:3a:dd:85:b6:0e',
            'signage' => 0,
            'sensor' => 1,
        ]);
        Computer::create([
            'name' => 'Akioka-Raspberrypi4-08',
            'place_id' => 10,
            'ip_address' => '192.168.210.89',
            'mac_address' => 'd8:3a:dd:c1:73:e6',
            'signage' => 1,
            'sensor' => 1,
        ]);
    }
}
