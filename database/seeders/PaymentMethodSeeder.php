<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();

        PaymentMethod::insert([
            [
                'name'=>'KREDIT',
                'created_at'=> $now,
            ],
            [
                'name'=>'DEBIT',
                'created_at'=> $now,
            ],
            [
                'name'=>'TRANSFER',
                'created_at'=> $now,
            ],
            [
                'name'=>'CASH',
                'created_at'=> $now,
            ],
            [
                'name'=> 'LEASING',
                'created_at'=> $now,
            ],
            [
                'name'=>'QR',
                'created_at'=> $now,
            ]
        ]);
    }
}
