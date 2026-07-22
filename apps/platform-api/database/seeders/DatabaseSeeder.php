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
        if (! config('toggleflow.demo.enabled')) {
            return;
        }

        User::query()->updateOrCreate([
            'email' => config('toggleflow.demo.email'),
        ], [
            'name' => config('toggleflow.demo.name'),
            'password' => config('toggleflow.demo.password'),
        ]);
    }
}
