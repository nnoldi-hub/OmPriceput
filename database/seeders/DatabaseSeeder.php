<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Patron',
            'email' => 'admin@omulpotrivit.test',
            'phone' => '0700 111 222',
            'trade' => 'general',
        ]);
        $admin->assignRole('admin');

        $sales = User::factory()->create([
            'name' => 'Agent Programari',
            'email' => 'programari@omulpotrivit.test',
            'phone' => '0700 111 223',
        ]);
        $sales->assignRole('vanzari');

        $electrician = User::factory()->create([
            'name' => 'Meseras Electrician',
            'email' => 'electrician@omulpotrivit.test',
            'phone' => '0700 111 224',
            'trade' => 'electric',
        ]);
        $electrician->assignRole('tehnic');

        $plumber = User::factory()->create([
            'name' => 'Meseras Sanitar',
            'email' => 'sanitar@omulpotrivit.test',
            'phone' => '0700 111 225',
            'trade' => 'sanitar',
        ]);
        $plumber->assignRole('tehnic');

        $painter = User::factory()->create([
            'name' => 'Meseras Vopsire',
            'email' => 'vopsire@omulpotrivit.test',
            'phone' => '0700 111 226',
            'trade' => 'vopsire',
        ]);
        $painter->assignRole('tehnic');

        $support = User::factory()->create([
            'name' => 'Suport Clienti',
            'email' => 'suport@omulpotrivit.test',
            'phone' => '0700 111 227',
        ]);
        $support->assignRole('suport');

        $this->call([
            ServiceSeeder::class,
            EquipmentSeeder::class,
            PostSeeder::class,
        ]);
    }
}