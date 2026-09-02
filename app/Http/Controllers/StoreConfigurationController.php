<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\PaymentGateway;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StoreConfigurationController extends Controller
{
    /**
     * Get all store settings
     */
    public function getStoreSettings(Request $request)
    {
        try {
            // Get general settings
            $generalSetting = Setting::where('type', 'general-setting')->first();
            $generalData = $generalSetting ? json_decode($generalSetting->value, true) : [];

            // Get site setup settings
            $siteSetting = Setting::where('type', 'site-setup')->first();
            $siteData = $siteSetting ? json_decode($siteSetting->value, true) : [];

            // Get service configuration
            $serviceSetting = Setting::where('type', 'service-configuration')->first();
            $serviceData = $serviceSetting ? json_decode($serviceSetting->value, true) : [];

            // Get earning settings
            $earningSetting = Setting::where('type', 'earning-setting')->first();
            $earningData = $earningSetting ? $earningSetting->value : 'commission';

            // Get payment gateways
            $paymentGateways = PaymentGateway::select('id', 'title', 'type', 'status', 'is_test')->get();

            // Combine all settings
            $storeSettings = [
                'general' => [
                    'store_name' => $generalData['site_name'] ?? 'Quick',
                    'store_tagline' => $generalData['site_description'] ?? 'Your one-stop shop for everything',
                    'inquiry_email' => $generalData['inquriy_email'] ?? '',
                    'helpline_number' => $generalData['helpline_number'] ?? '',
                    'website' => $generalData['website'] ?? '',
                    'address' => [
                        'country_id' => $generalData['country_id'] ?? null,
                        'state_id' => $generalData['state_id'] ?? null,
                        'city_id' => $generalData['city_id'] ?? null,
                        'zipcode' => $generalData['zipcode'] ?? '',
                        'address' => $generalData['address'] ?? ''
                    ]
                ],
                'site' => [
                    'currency' => $siteData['default_currency'] ?? 'USD',
                    'currency_position' => $siteData['currency_position'] ?? 'left',
                    'date_format' => $siteData['date_format'] ?? 'Y-m-d',
                    'time_format' => $siteData['time_format'] ?? 'H:i:s',
                    'time_zone' => $siteData['time_zone'] ?? 'UTC',
                    'language_option' => $siteData['language_option'] ?? ['en'],
                    'decimal_points' => $siteData['digitafter_decimal_point'] ?? 2
                ],
                'ecommerce' => [
                    'enable_reviews' => $serviceData['enable_reviews'] ?? true,
                    'enable_wishlist' => $serviceData['enable_wishlist'] ?? true,
                    'enable_notifications' => $serviceData['enable_notifications'] ?? true,
                    'auto_approve_products' => $serviceData['auto_approve_products'] ?? false,
                    'require_product_approval' => $serviceData['require_product_approval'] ?? true,
                    'max_products_per_provider' => $serviceData['max_products_per_provider'] ?? 100,
                    'tax_rate' => $serviceData['tax_rate'] ?? 0,
                    'commission_rate' => $serviceData['commission_rate'] ?? 15.0,
                    'earning_type' => $earningData
                ],
                'payment_gateways' => $paymentGateways->map(function($gateway) {
                    return [
                        'id' => $gateway->id,
                        'name' => $gateway->title,
                        'type' => $gateway->type,
                        'enabled' => $gateway->status == 1,
                        'test_mode' => $gateway->is_test == 1
                    ];
                })
            ];

            return comman_custom_response([
                'message' => 'Store settings retrieved successfully',
                'data' => $storeSettings,
                'status' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Get store settings error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return comman_message_response('Failed to retrieve store settings: ' . $e->getMessage());
        }
    }

    /**
     * Update store configuration
     */
    public function updateStoreSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_name' => 'required|string|max:255',
            'store_tagline' => 'nullable|string|max:500',
            'currency' => 'required|string|max:10',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'max_products_per_provider' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return comman_message_response($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            // Update general settings
            $generalData = [
                'site_name' => $request->store_name,
                'site_description' => $request->store_tagline,
                'inquriy_email' => $request->inquiry_email,
                'helpline_number' => $request->helpline_number,
                'website' => $request->website,
                'country_id' => $request->country_id,
                'state_id' => $request->state_id,
                'city_id' => $request->city_id,
                'zipcode' => $request->zipcode,
                'address' => $request->address
            ];

            Setting::updateOrCreate(
                ['type' => 'general-setting', 'key' => 'general-setting'],
                ['value' => json_encode($generalData)]
            );

            // Update site settings
            $siteData = [
                'default_currency' => $request->currency,
                'currency_position' => $request->currency_position ?? 'left',
                'date_format' => $request->date_format ?? 'Y-m-d',
                'time_format' => $request->time_format ?? 'H:i:s',
                'time_zone' => $request->time_zone ?? 'UTC',
                'digitafter_decimal_point' => $request->decimal_points ?? 2
            ];

            Setting::updateOrCreate(
                ['type' => 'site-setup', 'key' => 'site-setup'],
                ['value' => json_encode($siteData)]
            );

            // Update service configuration
            $serviceData = [
                'enable_reviews' => $request->enable_reviews ?? false,
                'enable_wishlist' => $request->enable_wishlist ?? false,
                'enable_notifications' => $request->enable_notifications ?? false,
                'auto_approve_products' => $request->auto_approve_products ?? false,
                'require_product_approval' => $request->require_product_approval ?? true,
                'max_products_per_provider' => $request->max_products_per_provider ?? 100,
                'tax_rate' => $request->tax_rate ?? 0,
                'commission_rate' => $request->commission_rate ?? 15.0
            ];

            Setting::updateOrCreate(
                ['type' => 'service-configuration', 'key' => 'service-configuration'],
                ['value' => json_encode($serviceData)]
            );

            DB::commit();

            return comman_custom_response([
                'message' => 'Store settings updated successfully',
                'status' => true
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update store settings error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);
            
            return comman_message_response('Failed to update store settings: ' . $e->getMessage());
        }
    }

    /**
     * Update payment settings
     */
    public function updatePaymentSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enable_stripe' => 'boolean',
            'enable_paypal' => 'boolean',
            'enable_wallet' => 'boolean',
            'enable_cod' => 'boolean'
        ]);

        if ($validator->fails()) {
            return comman_message_response($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            // Update Stripe settings
            if ($request->has('enable_stripe')) {
                $stripeData = [
                    'stripe_key' => $request->stripe_public_key,
                    'stripe_secret' => $request->stripe_secret_key
                ];

                PaymentGateway::updateOrCreate(
                    ['type' => 'stripe'],
                    [
                        'title' => 'Stripe',
                        'status' => $request->enable_stripe ? 1 : 0,
                        'is_test' => $request->stripe_test_mode ?? 1,
                        'value' => json_encode($stripeData)
                    ]
                );
            }

            // Update PayPal settings
            if ($request->has('enable_paypal')) {
                $paypalData = [
                    'paypal_client_id' => $request->paypal_client_id,
                    'paypal_secret' => $request->paypal_secret
                ];

                PaymentGateway::updateOrCreate(
                    ['type' => 'paypal'],
                    [
                        'title' => 'PayPal',
                        'status' => $request->enable_paypal ? 1 : 0,
                        'is_test' => $request->paypal_test_mode ?? 1,
                        'value' => json_encode($paypalData)
                    ]
                );
            }

            // Update Wallet settings
            if ($request->has('enable_wallet')) {
                PaymentGateway::updateOrCreate(
                    ['type' => 'wallet'],
                    [
                        'title' => 'Wallet',
                        'status' => $request->enable_wallet ? 1 : 0,
                        'is_test' => 0,
                        'value' => json_encode([])
                    ]
                );
            }

            // Update COD settings
            if ($request->has('enable_cod')) {
                PaymentGateway::updateOrCreate(
                    ['type' => 'cash'],
                    [
                        'title' => 'Cash on Delivery',
                        'status' => $request->enable_cod ? 1 : 0,
                        'is_test' => 0,
                        'value' => json_encode([])
                    ]
                );
            }

            DB::commit();

            return comman_custom_response([
                'message' => 'Payment settings updated successfully',
                'status' => true
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update payment settings error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);
            
            return comman_message_response('Failed to update payment settings: ' . $e->getMessage());
        }
    }

    /**
     * Update shipping settings
     */
    public function updateShippingSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'default_shipping_cost' => 'nullable|numeric|min:0',
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'express_shipping_cost' => 'nullable|numeric|min:0',
            'international_shipping_cost' => 'nullable|numeric|min:0',
            'estimated_delivery_days' => 'nullable|integer|min:1',
            'express_delivery_days' => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return comman_message_response($validator->errors()->first());
        }

        try {
            $shippingData = [
                'default_shipping_cost' => $request->default_shipping_cost ?? 5.99,
                'free_shipping_threshold' => $request->free_shipping_threshold ?? 50.00,
                'express_shipping_cost' => $request->express_shipping_cost ?? 12.99,
                'international_shipping' => $request->international_shipping ?? false,
                'international_shipping_cost' => $request->international_shipping_cost ?? 25.00,
                'estimated_delivery_days' => $request->estimated_delivery_days ?? 3,
                'express_delivery_days' => $request->express_delivery_days ?? 1
            ];

            Setting::updateOrCreate(
                ['type' => 'shipping-settings', 'key' => 'shipping-settings'],
                ['value' => json_encode($shippingData)]
            );

            return comman_custom_response([
                'message' => 'Shipping settings updated successfully',
                'status' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Update shipping settings error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            return comman_message_response('Failed to update shipping settings: ' . $e->getMessage());
        }
    }

    /**
     * Update email templates
     */
    public function updateEmailTemplates(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_confirmation_template' => 'nullable|string',
            'order_shipped_template' => 'nullable|string',
            'product_approved_template' => 'nullable|string',
            'product_rejected_template' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return comman_message_response($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            // Update order confirmation template
            if ($request->has('order_confirmation_template')) {
                NotificationTemplate::updateOrCreate(
                    ['type' => 'order_confirmation', 'channels' => 'email'],
                    [
                        'title' => 'Order Confirmation',
                        'subject' => 'Order Confirmation - #{order_number}',
                        'template_detail' => $request->order_confirmation_template,
                        'status' => 1
                    ]
                );
            }

            // Update order shipped template
            if ($request->has('order_shipped_template')) {
                NotificationTemplate::updateOrCreate(
                    ['type' => 'order_shipped', 'channels' => 'email'],
                    [
                        'title' => 'Order Shipped',
                        'subject' => 'Your Order Has Been Shipped - #{order_number}',
                        'template_detail' => $request->order_shipped_template,
                        'status' => 1
                    ]
                );
            }

            // Update product approved template
            if ($request->has('product_approved_template')) {
                NotificationTemplate::updateOrCreate(
                    ['type' => 'product_approved', 'channels' => 'email'],
                    [
                        'title' => 'Product Approved',
                        'subject' => 'Your Product Has Been Approved',
                        'template_detail' => $request->product_approved_template,
                        'status' => 1
                    ]
                );
            }

            // Update product rejected template
            if ($request->has('product_rejected_template')) {
                NotificationTemplate::updateOrCreate(
                    ['type' => 'product_rejected', 'channels' => 'email'],
                    [
                        'title' => 'Product Rejected',
                        'subject' => 'Your Product Needs Revision',
                        'template_detail' => $request->product_rejected_template,
                        'status' => 1
                    ]
                );
            }

            DB::commit();

            return comman_custom_response([
                'message' => 'Email templates updated successfully',
                'status' => true
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update email templates error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            return comman_message_response('Failed to update email templates: ' . $e->getMessage());
        }
    }
}
