<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('lodgeos:ensure-master {email} {--name=Bachiro} {--password=}', function (string $email): int {
    $password = (string) ($this->option('password') ?: Str::password(18));

    $user = User::query()->updateOrCreate(
        ['email' => $email],
        [
            'name' => (string) $this->option('name'),
            'property_id' => null,
            'role' => 'super_admin',
            'web_access_enabled' => true,
            'mobile_access_enabled' => false,
            'permissions' => null,
            'locale' => 'pt_PT',
            'theme_mode' => 'system',
            'password' => Hash::make($password),
        ],
    );

    $user->properties()->detach();

    $this->info("Conta master pronta: {$user->email}");

    if (! $this->option('password')) {
        $this->warn("Palavra-passe temporária: {$password}");
    }

    return 0;
})->purpose('Create or restore a protected LodgeOS master account');
