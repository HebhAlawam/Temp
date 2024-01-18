<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\ExcludingUsersController;
use Illuminate\Console\Command;

class ExcludeUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:exclude';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exclude users from the system based on their marks';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $controller = new ExcludingUsersController();
        $controller->excludeUsers();

        return 0;
    }
}
