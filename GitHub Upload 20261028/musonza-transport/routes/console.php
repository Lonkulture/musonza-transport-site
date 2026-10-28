<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;

Artisan::command('musonza:admin {email} {--reset}', function () {
    $email = strtolower(trim($this->argument('email')));
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Use a valid email.');

        return 1;
    }
    $u = User::where('email', $email)->first();
    if (($u && ! $this->option('reset')) || (! $u && $this->option('reset'))) {
        $this->error('Account state does not match create/reset.');

        return 1;
    }
    $p = $this->secret('Temporary password (12 to 72 bytes)');
    $again = $this->secret('Repeat password');
    if (! is_string($p) || strlen($p) < 12 || strlen($p) > 72 || $p !== $again || str_contains($p, "\0")) {
        $this->error('Passwords must match and meet the length limit.');

        return 1;
    }
    if ($u) {
        $u->update(['password' => $p, 'session_version' => $u->session_version + 1, 'must_change_password' => true]);
    } else {
        User::create(['name' => 'Musonza administrator', 'email' => $email, 'password' => $p]);
    }
    $this->info('Administrator ready. Password change required at sign-in.');
});
