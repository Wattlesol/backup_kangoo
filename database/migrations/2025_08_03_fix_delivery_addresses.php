<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Fix inconsistent delivery address formats in orders table
        $orders = DB::table('orders')->whereNotNull('delivery_address')->get();
        
        foreach ($orders as $order) {
            $deliveryAddress = $order->delivery_address;
            
            // Skip if already null
            if (is_null($deliveryAddress)) {
                continue;
            }
            
            // If it's a JSON string, decode and re-encode to ensure consistency
            if (is_string($deliveryAddress)) {
                $decoded = json_decode($deliveryAddress, true);
                
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    // Re-encode to ensure consistent format
                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update(['delivery_address' => json_encode($decoded)]);
                } else {
                    // Convert plain text to structured format
                    $structuredAddress = [
                        'address' => $deliveryAddress,
                        'note' => 'Migrated from legacy format'
                    ];
                    
                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update(['delivery_address' => json_encode($structuredAddress)]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // This migration is for data cleanup, no reverse needed
    }
};
