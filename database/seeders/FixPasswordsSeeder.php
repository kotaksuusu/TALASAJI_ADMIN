<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class FixPasswordsSeeder extends Seeder
{
    /**
     * Re-hash any user passwords that are still stored as plain text
     * (not already using bcrypt $2y$ or $2a$ prefix).
     */
    public function run(): void
    {
        $users = User::where('password', 'not like', '\$2y$%')
            ->where('password', 'not like', '\$2a$%')
            ->get();

        $count = 0;
        foreach ($users as $user) {
            $plain = $user->getOriginal('password');
            $user->password = $plain;
            $user->save();
            $count++;
        }

        $this->command?->info("Fixed {$count} user(s) with plain-text passwords.");
    }
}
