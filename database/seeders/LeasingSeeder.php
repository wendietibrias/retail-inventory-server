<?php

namespace Database\Seeders;

use App\Models\Leasing;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LeasingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        Leasing::insert([
            [
                'code' => 'AKULAKU',
                'name' => "AKULAKU",
                'created_at' => $now
            ],
            [
                'code' => 'INDODANA',
                'name' => "INDODANA",
                'created_at' => $now
            ],
            [
                'code' => 'HOME CREDIT',
                'name' => "HOME CREDIT",
                'created_at' => $now
            ],
            [
                'code' => 'FIF',
                'name' => "FIF",
                'created_at' => $now
            ],
            [
                'code' => 'KREDIVO',
                'name' => "KREDIVO",
                'created_at' => $now
            ]
        ]);
    }
}
