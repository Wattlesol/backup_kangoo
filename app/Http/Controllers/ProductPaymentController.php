<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentHistory;
use App\Models\Wallet;
use App\Traits\NotificationTrait;

class ProductPaymentController extends Controller
{
    use NotificationTrait;

    /**
     * Create Stripe payment session for product order
     */
    public function createStripePayment(Request $request)
    {
        try {
            $data = $request->all();
            
            // Validate required fields
            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'total_amount' => 'required|numeric|min:0.01',
                'currency_code' => 'required|string|max:3'
            ]);

            // Get the order
            $order = Order::with(['items.product', 'customer'])->findOrFail($data['order_id']);
            
            // Ensure the order belongs to the authenticated user
            if ($order->customer_id !== Auth::id()) {
                return comman_message_response('Unauthorized access to order');
            }

            // Check if order is in valid state for payment
            if ($order->payment_status !== 'pending') {
                return comman_message_response('Order payment is not pending');
            }

            // Create payment record
            $payment = Payment::create([
                'booking_id' => null, // For products, we'll use order_id in a custom field
                'customer_id' => $order->customer_id,
                'total_amount' => $data['total_amount'],
                'payment_type' => 'stripe',
                'payment_status' => 'pending',
                'datetime' => now(),
                'other_transaction_detail' => null, // Will be updated with Stripe session ID
                'order_id' => $order->id // Custom field for product orders
            ]);

            // Prepare data for Stripe
            $stripeData = [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'total_amount' => $data['total_amount'],
                'currency_code' => $data['currency_code'],
                'customer_email' => $order->customer->email,
                'customer_name' => $order->customer->display_name,
                'order_items' => $order->items->map(function ($item) {
                    return [
                        'name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'amount' => $item->unit_price
                    ];
                })->toArray()
            ];

            // Create Stripe checkout session
            $checkout_session = $this->createStripeCheckoutSession($stripeData);

            if (isset($checkout_session['message'])) {
                // Delete the payment record if Stripe session creation failed
                $payment->delete();
                return comman_custom_response($checkout_session);
            } else {
                // Update payment with Stripe session ID
                $payment->update(['other_transaction_detail' => $checkout_session['id']]);
                
                return comman_custom_response([
                    'status' => true,
                    'data' => $checkout_session,
                    'message' => 'Stripe payment session created successfully'
                ]);
            }

        } catch (\Exception $e) {
            return comman_message_response('Failed to create payment session: ' . $e->getMessage());
        }
    }

    /**
     * Save Stripe payment after successful payment
     */
    public function saveStripePayment(Request $request, $orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            
            // Find the payment record for this order
            $payment = Payment::where('order_id', $orderId)
                             ->where('payment_type', 'stripe')
                             ->where('payment_status', 'pending')
                             ->first();

            if (!$payment) {
                return redirect()->route('customer.orders')->with('error', 'Payment record not found');
            }

            $stripe_session_id = $payment->other_transaction_detail;
            
            // Verify payment with Stripe
            $session_object = $this->getStripePaymentDetails($stripe_session_id);

            if ($session_object['payment_intent'] !== '' && $session_object['payment_status'] == 'paid') {
                // Update payment record
                $payment->update([
                    'txn_id' => $session_object['payment_intent'],
                    'payment_status' => 'paid'
                ]);

                // Update order
                $order->update([
                    'payment_status' => 'paid',
                    'payment_transaction_id' => $session_object['payment_intent']
                ]);

                // Create payment history
                PaymentHistory::create([
                    'payment_id' => $payment->id,
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'amount' => $payment->total_amount,
                    'action' => 'order_payment_completed',
                    'status' => 'completed',
                    'datetime' => now(),
                    'text' => 'Order payment completed via Stripe'
                ]);

                // Send notification (if needed)
                // $this->sendPaymentNotification($order, $payment);

                return redirect()->route('order.success', ['orders' => $orderId])
                               ->with('success', 'Payment completed successfully');
            } else {
                return redirect()->route('products.payment-failed', ['order_id' => $orderId])
                               ->with('error', 'Payment verification failed. Please try again.');
            }

        } catch (\Exception $e) {
            return redirect()->route('products.payment-failed', ['order_id' => $orderId])
                           ->with('error', 'Payment processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Process wallet payment for product order
     */
    public function processWalletPayment(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'total_amount' => 'required|numeric|min:0.01'
            ]);

            $order = Order::findOrFail($request->order_id);
            $user = Auth::user();

            // Ensure the order belongs to the authenticated user
            if ($order->customer_id !== $user->id) {
                return comman_message_response('Unauthorized access to order');
            }

            // Check wallet balance
            $wallet = Wallet::where('user_id', $user->id)->first();
            
            if (!$wallet || $wallet->amount < $request->total_amount) {
                return comman_message_response('Insufficient wallet balance');
            }

            DB::beginTransaction();

            // Deduct from wallet
            $wallet->amount -= $request->total_amount;
            $wallet->save();

            // Create payment record
            $payment = Payment::create([
                'customer_id' => $user->id,
                'total_amount' => $request->total_amount,
                'payment_type' => 'wallet',
                'payment_status' => 'paid',
                'datetime' => now(),
                'order_id' => $order->id
            ]);

            // Update order
            $order->update([
                'payment_status' => 'paid',
                'payment_method' => 'wallet'
            ]);

            // Create payment history
            PaymentHistory::create([
                'payment_id' => $payment->id,
                'order_id' => $order->id,
                'customer_id' => $user->id,
                'amount' => $request->total_amount,
                'action' => 'wallet_payment_for_order',
                'status' => 'completed',
                'datetime' => now(),
                'text' => 'Order payment completed via wallet'
            ]);

            DB::commit();

            return comman_custom_response([
                'status' => true,
                'data' => ['order_id' => $order->id],
                'message' => 'Wallet payment completed successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            return comman_message_response('Wallet payment failed: ' . $e->getMessage());
        }
    }

    /**
     * Retry payment for a failed order
     */
    public function retryPayment(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'payment_method' => 'required|string'
            ]);

            $order = Order::findOrFail($request->order_id);
            $user = Auth::user();

            // Ensure the order belongs to the authenticated user
            if ($order->customer_id !== $user->id) {
                return comman_message_response('Unauthorized access to order');
            }

            // Check if order is in valid state for retry
            if ($order->payment_status === 'paid') {
                return comman_message_response('Order payment is already completed');
            }

            // Update payment method if different
            if ($order->payment_method !== $request->payment_method) {
                $order->update(['payment_method' => $request->payment_method]);
            }

            // Process payment based on method
            if ($request->payment_method === 'stripe' || $request->payment_method === 'card') {
                return $this->createStripePayment($request);
            } else if ($request->payment_method === 'wallet') {
                return $this->processWalletPayment($request);
            } else {
                // For cash and other methods, just update status
                $order->update(['payment_status' => 'pending']);

                return comman_custom_response([
                    'status' => true,
                    'data' => ['order_id' => $order->id],
                    'message' => 'Payment method updated successfully'
                ]);
            }

        } catch (\Exception $e) {
            return comman_message_response('Payment retry failed: ' . $e->getMessage());
        }
    }

    /**
     * Get available payment methods for products
     */
    public function getPaymentMethods()
    {
        try {
            $methods = [];

            // Get enabled payment gateways from database
            $gateways = PaymentGateway::where('status', 1)->get();

            foreach ($gateways as $gateway) {
                switch ($gateway->type) {
                    case 'cash':
                        $methods[] = [
                            'id' => 'cash',
                            'name' => 'Cash on Delivery',
                            'description' => 'Pay when your order is delivered',
                            'icon' => 'fas fa-money-bill-wave',
                            'enabled' => true
                        ];
                        break;
                    
                    case 'stripe':
                        $methods[] = [
                            'id' => 'stripe',
                            'name' => 'Credit/Debit Card',
                            'description' => 'Pay securely with your card via Stripe',
                            'icon' => 'fas fa-credit-card',
                            'enabled' => true
                        ];
                        break;
                    
                    case 'paypal':
                        $methods[] = [
                            'id' => 'paypal',
                            'name' => 'PayPal',
                            'description' => 'Pay with your PayPal account',
                            'icon' => 'fab fa-paypal',
                            'enabled' => true
                        ];
                        break;
                    
                    case 'razorPay':
                        $methods[] = [
                            'id' => 'razorpay',
                            'name' => 'RazorPay',
                            'description' => 'Pay with RazorPay',
                            'icon' => 'fas fa-credit-card',
                            'enabled' => true
                        ];
                        break;
                }
            }

            // Always add wallet option
            $methods[] = [
                'id' => 'wallet',
                'name' => 'Wallet',
                'description' => 'Pay from your wallet balance',
                'icon' => 'fas fa-wallet',
                'enabled' => true
            ];

            return comman_custom_response([
                'status' => true,
                'data' => ['methods' => $methods],
                'message' => 'Payment methods fetched successfully'
            ]);

        } catch (\Exception $e) {
            return comman_message_response('Failed to fetch payment methods');
        }
    }

    /**
     * Create Stripe checkout session (adapted from service booking)
     */
    private function createStripeCheckoutSession($data)
    {
        // Get Stripe configuration
        $stripe_gateway = PaymentGateway::where('type', 'stripe')->where('status', 1)->first();
        
        if (!$stripe_gateway) {
            return [
                'message' => 'Stripe payment gateway is not configured',
                'status' => false
            ];
        }

        $stripe_config = json_decode($stripe_gateway->value, true);
        $stripe_secret = $stripe_config['stripe_key'] ?? null;

        if (!$stripe_secret) {
            return [
                'message' => 'Stripe secret key is not configured',
                'status' => false
            ];
        }

        try {
            $stripe = new \Stripe\StripeClient($stripe_secret);
            
            $line_items = [];
            foreach ($data['order_items'] as $item) {
                $line_items[] = [
                    'price_data' => [
                        'currency' => strtolower($data['currency_code']),
                        'product_data' => [
                            'name' => $item['name'],
                        ],
                        'unit_amount' => $item['amount'] * 100, // Convert to cents
                    ],
                    'quantity' => $item['quantity'],
                ];
            }

            $checkout_session = $stripe->checkout->sessions->create([
                'success_url' => url('/save-stripe-payment-order/' . $data['order_id']),
                'cancel_url' => url('/payment-failed?order_id=' . $data['order_id']),
                'payment_method_types' => ['card'],
                'billing_address_collection' => 'required',
                'line_items' => $line_items,
                'mode' => 'payment',
                'customer_email' => $data['customer_email'],
                'metadata' => [
                    'order_id' => $data['order_id'],
                    'payment_id' => $data['payment_id']
                ]
            ]);

            return $checkout_session;

        } catch (\Exception $e) {
            return [
                'message' => $e->getMessage(),
                'status' => false
            ];
        }
    }

    /**
     * Get Stripe payment details (adapted from service booking)
     */
    private function getStripePaymentDetails($session_id)
    {
        $stripe_gateway = PaymentGateway::where('type', 'stripe')->where('status', 1)->first();
        $stripe_config = json_decode($stripe_gateway->value, true);
        $stripe_secret = $stripe_config['stripe_key'] ?? null;

        try {
            $stripe = new \Stripe\StripeClient($stripe_secret);
            $session = $stripe->checkout->sessions->retrieve($session_id);

            return [
                'payment_intent' => $session->payment_intent,
                'payment_status' => $session->payment_status
            ];

        } catch (\Exception $e) {
            return [
                'payment_intent' => '',
                'payment_status' => 'failed'
            ];
        }
    }
}
