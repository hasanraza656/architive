<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

/**
 * Creates or resets an admin account without keeping credentials in any file:
 *   php artisan admin:create admin@example.com --password="..."   (or leave --password out to be asked)
 */
class CreateAdmin extends Command
{
    protected $signature = 'admin:create {email} {--password= : Leave empty to be asked} {--first=Admin} {--last=}';

    protected $description = 'Create an admin account, or reset the password of an existing admin';

    public function handle(): int
    {
        $email = mb_strtolower($this->argument('email'));
        $password = $this->option('password') ?: $this->secret('Password');

        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen((string) $password) < 8) {
            $this->error('Use a valid e-mail and a password of at least 8 characters.');

            return self::FAILURE;
        }

        $existing = User::where('email', $email)->first();
        if ($existing && ! $existing->isAdmin()) {
            $this->error('That e-mail already belongs to a customer account.');

            return self::FAILURE;
        }

        User::updateOrCreate(['email' => $email], [
            'role' => User::ROLE_ADMIN,
            'first_name' => $existing->first_name ?? $this->option('first'),
            'last_name' => $existing->last_name ?? $this->option('last'),
            'password' => Hash::make($password),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->info($existing ? "Admin {$email} updated." : "Admin {$email} created.");

        return self::SUCCESS;
    }
}
