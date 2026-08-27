<?php

namespace App\Services\Users;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class UserRestoreBackupService
{
    public function run(): bool
    {
        return Artisan::call('backup:run') === Command::SUCCESS;
    }
}
