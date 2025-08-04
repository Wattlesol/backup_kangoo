<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Product;
use App\Models\Store;

class SimpleOrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get required data
        $customers = User::where('user_type', 'customer')->take(3)->get();
        $products = Product::where('status', true)->where('is_available', true)->take(10)->get();
        $store = Store::where('status', 'approved')->first();

        if ($customers->isEmpty()) {
            $this->createTestCustomers();
            $customers = User::where('user_type', 'customer')->take(3)->get();
        }

        if (!$store) {
            $this->command->error('No approved store found. Please create a store first.');
            return;
        }

        if ($products->isEmpty()) {
            $this->command->error('No products found. Please create products first.');
            return;
        }

        $this->command->info('Creating test orders...');

        // Create orders using direct DB inserts to avoid notifications
        $this->createTestOrders($customers, $products, $store);

        $this->command->info('Test orders created successfully!');
    }

    private function createTestCustomers()
    {
        $this->command->info('Creating test customers...');
        
        $customers = [
            [
                'username' => 'johndoe',
                'first_name' => 'John',
                'last_name' => 'Doe',
                'display_name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'contact_number' => '+1234567890',
                'user_type' => 'customer',
                'status' => 1,
                'email_verified_at' => now(),
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'janesmith',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'display_name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'contact_number' => '+1234567891',
                'user_type' => 'customer',
                'status' => 1,
                'email_verified_at' => now(),
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username' => 'mikejohnson',
                'first_name' => 'Mike',
                'last_name' => 'Johnson',
                'display_name' => 'Mike Johnson',
                'email' => 'mike.johnson@example.com',
                'contact_number' => '+1234567892',
                'user_type' => 'customer',
                'status' => 1,
                'email_verified_at' => now(),
                'password' => bcrypt('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        foreach ($customers as $customerData) {
            if (!User::where('email', $customerData['email'])->exists()) {
                DB::table('users')->insert($customerData);
            }
        }
    }

    private function createTestOrders($customers, $products, $store)
    {
        $orderStatuses = [
            ['status' => 'pending', 'payment_status' => 'pending', 'count' => 3],
            ['status' => 'confirmed', 'payment_status' => 'paid', 'count' => 2],
            ['status' => 'processing', 'payment_status' => 'paid', 'count' => 2],
            ['status' => 'shipped', 'payment_status' => 'paid', 'count' => 2],
            ['status' => 'delivered', 'payment_status' => 'paid', 'count' => 4],
            ['status' => 'cancelled', 'payment_status' => 'refunded', 'count' => 2],
        ];

        $orderCounter = 1;

        foreach ($orderStatuses as $statusGroup) {
            for ($i = 1; $i <= $statusGroup['count']; $i++) {
                $customer = $customers->random();
                $orderProducts = $products->random(rand(1, 3));
                
                $subtotal = 0;
                foreach ($orderProducts as $product) {
                    $quantity = rand(1, 3);
                    $unitPrice = $product->base_price;
                    $subtotal += $quantity * $unitPrice;
                }

                $taxAmount = $subtotal * 0.08; // 8% tax
                $deliveryFee = rand(5, 10) + 0.99;
                $discountAmount = $subtotal > 50 ? 5.00 : 0;
                $totalAmount = $subtotal + $taxAmount + $deliveryFee - $discountAmount;

                $createdAt = now()->subDays(rand(1, 30));
                $deliveredAt = $statusGroup['status'] === 'delivered' ? $createdAt->copy()->addDays(rand(2, 7)) : null;
                $cancelledAt = $statusGroup['status'] === 'cancelled' ? $createdAt->copy()->addHours(rand(2, 48)) : null;

                $orderNumber = 'ORD' . now()->format('ymd') . str_pad($orderCounter++, 4, '0', STR_PAD_LEFT);

                // Insert order directly into database
                $orderId = DB::table('orders')->insertGetId([
                    'order_number' => $orderNumber,
                    'customer_id' => $customer->id,
                    'store_id' => $store->id,
                    'order_type' => 'store',
                    'status' => $statusGroup['status'],
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'delivery_fee' => $deliveryFee,
                    'discount_amount' => $discountAmount,
                    'total_amount' => $totalAmount,
                    'currency' => 'USD',
                    'payment_status' => $statusGroup['payment_status'],
                    'payment_method' => ['credit_card', 'paypal', 'cash_on_delivery'][rand(0, 2)],
                    'payment_transaction_id' => $statusGroup['payment_status'] === 'paid' ? 'txn_' . uniqid() : null,
                    'delivery_address' => json_encode([
                        'street' => rand(100, 999) . ' ' . ['Main St', 'Oak Ave', 'Pine Rd', 'Elm Dr'][rand(0, 3)],
                        'city' => ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Miami'][rand(0, 4)],
                        'state' => ['NY', 'CA', 'IL', 'TX', 'FL'][rand(0, 4)],
                        'zip' => rand(10000, 99999),
                        'country' => 'USA'
                    ]),
                    'delivery_phone' => $customer->contact_number,
                    'delivery_notes' => 'Please ring the doorbell',
                    'estimated_delivery_at' => in_array($statusGroup['status'], ['confirmed', 'processing', 'shipped']) ? now()->addDays(rand(1, 5)) : null,
                    'delivered_at' => $deliveredAt,
                    'cancelled_at' => $cancelledAt,
                    'cancellation_reason' => $statusGroup['status'] === 'cancelled' ? ['Customer requested', 'Out of stock', 'Payment failed'][rand(0, 2)] : null,
                    'cancelled_by' => $statusGroup['status'] === 'cancelled' ? $customer->id : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                // Create order items
                foreach ($orderProducts as $product) {
                    $quantity = rand(1, 3);
                    $unitPrice = $product->base_price;
                    
                    DB::table('order_items')->insert([
                        'order_id' => $orderId,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                        'product_details' => json_encode([
                            'name' => $product->name,
                            'description' => $product->description,
                            'category' => $product->category->name ?? null,
                        ]),
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total_price' => $quantity * $unitPrice,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }

                // Create status history
                $this->createStatusHistory($orderId, $customer->id, $statusGroup['status'], $createdAt, $deliveredAt, $cancelledAt);
            }
        }
    }

    private function createStatusHistory($orderId, $customerId, $finalStatus, $createdAt, $deliveredAt, $cancelledAt)
    {
        $statusFlow = [
            'pending' => ['pending'],
            'confirmed' => ['pending', 'confirmed'],
            'processing' => ['pending', 'confirmed', 'processing'],
            'shipped' => ['pending', 'confirmed', 'processing', 'shipped'],
            'delivered' => ['pending', 'confirmed', 'processing', 'shipped', 'delivered'],
            'cancelled' => ['pending', 'cancelled'],
        ];

        $statuses = $statusFlow[$finalStatus] ?? ['pending'];
        $timeOffset = 0;

        foreach ($statuses as $status) {
            $changedAt = $createdAt->copy()->addHours($timeOffset);
            
            if ($status === 'delivered' && $deliveredAt) {
                $changedAt = $deliveredAt;
            } elseif ($status === 'cancelled' && $cancelledAt) {
                $changedAt = $cancelledAt;
            }

            DB::table('order_status_histories')->insert([
                'order_id' => $orderId,
                'status' => $status,
                'notes' => $this->getStatusNote($status),
                'changed_by' => $status === 'pending' ? $customerId : 1, // Admin ID = 1
                'changed_at' => $changedAt,
                'created_at' => $changedAt,
                'updated_at' => $changedAt,
            ]);

            $timeOffset += rand(1, 24); // Random hours between status changes
        }
    }

    private function getStatusNote($status)
    {
        $notes = [
            'pending' => 'Order placed by customer',
            'confirmed' => 'Order confirmed and payment processed',
            'processing' => 'Order is being prepared',
            'shipped' => 'Order shipped with tracking number',
            'delivered' => 'Order delivered successfully',
            'cancelled' => 'Order cancelled',
        ];

        return $notes[$status] ?? 'Status updated';
    }
}
