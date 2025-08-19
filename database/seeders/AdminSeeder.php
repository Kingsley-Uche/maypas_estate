<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table("admins")->insert([
            [
              'id' => 1,
              'name'=> 'Emeka David',
              'email'=> 'admin@ffsd.com',
              'role_id' => 1,
              'email_verified_at' => now(),
              'password'=> Hash::make('testingPassword'),
            ]
        ]);
    }
}
