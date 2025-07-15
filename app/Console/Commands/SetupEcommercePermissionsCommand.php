<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetupEcommercePermissionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ecommerce:permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup e-commerce permissions for admin and provider roles';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Setting up e-commerce permissions...');

        // Run the permission seeder
        Artisan::call('db:seed', ['--class' => 'EcommercePermissionSeeder']);
        
        $this->info('E-commerce permissions setup completed!');
        $this->info('');
        $this->info('You can now access e-commerce features:');
        $this->info('- Admin users can access all e-commerce features');
        $this->info('- Provider users can access their store, products, and orders');
        $this->info('- Dynamic pricing is admin-only');
        $this->info('');
        $this->info('Please refresh your browser and login again to see the menu items.');

        return 0;
    }
}
