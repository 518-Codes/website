<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            ['name' => 'Luigi Battaglioli', 'email' => 'him@theluigi.com'],
            ['name' => 'Garret Premo', 'email' => 'garret@518.codes'],
            ['name' => 'Frank Matranga', 'email' => 'frank@518.codes'],
        ];

        foreach ($members as $member) {
            User::firstOrCreate(
                ['email' => $member['email']],
                ['name' => $member['name'], 'password' => Hash::make('password')],
            );
        }
    }
}
