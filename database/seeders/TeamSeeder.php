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
            ['name' => 'Luigi Battaglioli', 'email' => 'him@theluigi.com', 'username' => 'luigi'],
            ['name' => 'Garret Premo', 'email' => 'garret@518.codes', 'username' => 'garret'],
            ['name' => 'Frank Matranga', 'email' => 'frank@518.codes', 'username' => 'frank'],
        ];

        foreach ($members as $member) {
            User::firstOrCreate(
                ['email' => $member['email']],
                [
                    'name' => $member['name'],
                    'username' => $member['username'],
                    'password' => Hash::make('password'),
                    'is_admin' => true,
                ],
            )->update(['is_admin' => true, 'username' => $member['username']]);
        }
    }
}
