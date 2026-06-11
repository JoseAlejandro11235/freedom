<?php

namespace App\Console\Commands;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class MakeAdminUser extends Command
{
    protected $signature = 'freedom:make-admin
                            {email : Admin email address}
                            {--name= : Display name}
                            {--password= : Password (prompted if omitted)}';

    protected $description = 'Create or promote a user to the admin role (back-office access)';

    public function handle(): int
    {
        $email = strtolower($this->argument('email'));
        $name = $this->option('name') ?? 'Administrator';
        $password = $this->option('password') ?? $this->secret('Password');

        $validator = Validator::make(
            ['email' => $email, 'password' => $password, 'name' => $name],
            [
                'email' => ['required', 'email'],
                'password' => ['required', 'string', 'min:12'],
                'name' => ['required', 'string', 'max:255'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        Role::findOrCreate('admin', 'web');

        $user = User::query()->firstOrNew(['email' => $email]);
        $user->name = $name;

        if (! $user->exists) {
            $user->password = Hash::make($password);
            $user->email_verified_at = now();
        }

        $user->save();
        $user->syncRoles(['admin']);

        $this->info("Admin ready: {$user->email}");
        $this->line('Back-office: '.Filament::getPanel('admin')->getUrl());

        return self::SUCCESS;
    }
}
