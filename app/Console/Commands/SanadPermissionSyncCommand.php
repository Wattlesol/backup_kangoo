<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\SanadEmployeePermissions;
use Illuminate\Console\Command;

class SanadPermissionSyncCommand extends Command
{
    protected $signature = 'sanad:permissions-sync {email? : Optional employee email to sync}';

    protected $description = 'Normalize Sanad employee permission matrices and sync derived Spatie permissions.';

    public function handle(): int
    {
        $email = $this->argument('email');

        $query = User::where('user_type', 'handyman')
            ->where(function ($query) {
                $query->whereNotNull('sanad_permission_matrix')
                    ->orWhereNotNull('sanad_permissions');
            });

        if ($email) {
            $query->where('email', $email);
        }

        $users = $query->get();

        if ($email && $users->isEmpty()) {
            $this->error("No Sanad employee found for {$email}.");
            return self::FAILURE;
        }

        $results = [];

        foreach ($users as $user) {
            $results[] = array_merge([
                'id' => $user->id,
                'email' => $user->email,
            ], SanadEmployeePermissions::syncUser($user));
        }

        $this->line(json_encode([
            'synced_count' => count($results),
            'users' => $results,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
