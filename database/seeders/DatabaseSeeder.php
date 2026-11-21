<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds initial Admin, Penyewa accounts, vehicles, and sample garages
 * for verifying and testing the Vehicle Garage Rental System.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Populate baseline accounts, vehicles, and garage units inside a transaction.
     *
     * @return void
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $now = now();
            $passwordHash = Hash::make('password');

            DB::table('users')->insert([
                'nik' => null,
                'name' => 'Admin Garasi',
                'phone' => '081100000001',
                'email' => 'admin@garage.test',
                'password' => $passwordHash,
                'role' => 'admin',
                'status' => 'approved',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $budiId = DB::table('users')->insertGetId([
                'nik' => '3578010101900001',
                'name' => 'Michael West',
                'phone' => '081234567890',
                'email' => 'michael.west@email.com',
                'password' => $passwordHash,
                'role' => 'penyewa',
                'status' => 'approved',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('users')->insert([
                'nik' => '3578020202950002',
                'name' => 'Shawn Mendes',
                'phone' => '081298765432',
                'email' => 'shawn.mendes@email.com',
                'password' => $passwordHash,
                'role' => 'penyewa',
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('vehicles')->insert([
                [
                    'user_id' => $budiId,
                    'type' => 'mobil',
                    'plate_number' => 'L 1234 AB',
                    'brand_model' => 'Toyota Avanza',
                    'battery_capacity_kwh' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'user_id' => $budiId,
                    'type' => 'mobil_listrik',
                    'plate_number' => 'L 5678 EV',
                    'brand_model' => 'Hyundai Creta',
                    'battery_capacity_kwh' => 80.00,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);

            DB::table('garages')->insert([
                [
                    'code' => 'S001',
                    'type' => 'sepeda',
                    'daily_rate' => 10000.00,
                    'kwh_price' => null,
                    'status' => 'available',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'code' => 'C001',
                    'type' => 'mobil',
                    'daily_rate' => 50000.00,
                    'kwh_price' => null,
                    'status' => 'available',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'code' => 'E001',
                    'type' => 'mobil_listrik',
                    'daily_rate' => 75000.00,
                    'kwh_price' => 2500.00,
                    'status' => 'available',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        });
    }
}
