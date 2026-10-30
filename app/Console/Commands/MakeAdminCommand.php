<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class MakeAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:admin 
                            {email? : The email address of the administrator}
                            {--name= : Name of the administrator if creating a new account}
                            {--password= : Password for the new administrator account}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provision a new administrator account or promote an existing user to admin';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email') ?? $this->ask('Enter administrator email address');

        $emailValidator = Validator::make(['email' => $email], [
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        if ($emailValidator->fails()) {
            $this->error('Invalid email address format.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if ($user->role === 'admin') {
                $this->info("User [{$email}] is already an administrator.");

                return self::SUCCESS;
            }

            $user->role = 'admin';
            $user->save();

            $this->info("User [{$email}] successfully promoted to administrator.");

            return self::SUCCESS;
        }

        // Creating a new admin user
        $name = $this->option('name') ?? $this->ask('Enter administrator name', 'TapH5 Administrator');
        $password = $this->option('password');

        if (! $password) {
            $password = $this->secret('Enter administrator password');
        }

        $validator = Validator::make([
            'name' => $name,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', Password::defaults()],
        ]);

        if ($validator->fails()) {
            $this->error('Validation failed: '.implode(' ', $validator->errors()->all()));

            return self::FAILURE;
        }

        $newUser = new User;
        $newUser->name = $name;
        $newUser->email = $email;
        $newUser->password = Hash::make($password);
        $newUser->role = 'admin';
        $newUser->email_verified_at = now();
        $newUser->save();

        $this->info("Administrator [{$email}] created successfully.");

        return self::SUCCESS;
    }
}

