<?php

namespace App\Traits;

use App\Models\User;
use App\Models\Order;
use App\Models\Store;
use App\Models\Product;
use App\Traits\NotificationTrait;

trait EcommerceNotificationTrait
{
    use NotificationTrait;

    /**
     * Send order created notification
     */
    public function sendOrderCreatedNotification(Order $order)
    {
        $data = [
            'activity_type' => 'order_created',
            'activity_message' => 'New order #' . $order->formatted_order_number . ' has been created',
            'order' => $order,
            'order_number' => $order->formatted_order_number,
            'total_amount' => getPriceFormat($order->total_amount),
            'order_date' => $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
            'store_name' => $order->is_admin_order ? 'Admin Store' : ($order->store ? $order->store->name : 'N/A'),
            'user_name' => $order->customer ? $order->customer->display_name : 'Guest',
            'datetime' => now()->format('Y-m-d H:i:s')
        ];

        $this->sendNotification($data);
    }

    /**
     * Send order status updated notification
     */
    public function sendOrderStatusUpdatedNotification(Order $order, $oldStatus, $notes = null)
    {
        $data = [
            'activity_type' => 'order_status_updated',
            'activity_message' => 'Order #' . $order->formatted_order_number . ' status updated to ' . ucfirst(str_replace('_', ' ', $order->status)),
            'order' => $order,
            'order_number' => $order->formatted_order_number,
            'order_status' => ucfirst(str_replace('_', ' ', $order->status)),
            'old_status' => ucfirst(str_replace('_', ' ', $oldStatus)),
            'total_amount' => getPriceFormat($order->total_amount),
            'store_name' => $order->is_admin_order ? 'Admin Store' : ($order->store ? $order->store->name : 'N/A'),
            'user_name' => $order->customer ? $order->customer->display_name : 'Guest',
            'status_notes' => $notes,
            'datetime' => now()->format('Y-m-d H:i:s')
        ];

        $this->sendNotification($data);
    }

    /**
     * Send order delivered notification
     */
    public function sendOrderDeliveredNotification(Order $order)
    {
        $data = [
            'activity_type' => 'order_delivered',
            'activity_message' => 'Order #' . $order->formatted_order_number . ' has been delivered successfully',
            'order' => $order,
            'order_number' => $order->formatted_order_number,
            'total_amount' => getPriceFormat($order->total_amount),
            'delivered_date' => now()->format('Y-m-d H:i:s'),
            'store_name' => $order->is_admin_order ? 'Admin Store' : ($order->store ? $order->store->name : 'N/A'),
            'user_name' => $order->customer ? $order->customer->display_name : 'Guest',
            'datetime' => now()->format('Y-m-d H:i:s')
        ];

        $this->sendNotification($data);
    }

    /**
     * Send order cancelled notification
     */
    public function sendOrderCancelledNotification(Order $order, $reason = null)
    {
        $data = [
            'activity_type' => 'order_cancelled',
            'activity_message' => 'Order #' . $order->formatted_order_number . ' has been cancelled',
            'order' => $order,
            'order_number' => $order->formatted_order_number,
            'total_amount' => getPriceFormat($order->total_amount),
            'cancelled_date' => now()->format('Y-m-d H:i:s'),
            'store_name' => $order->is_admin_order ? 'Admin Store' : ($order->store ? $order->store->name : 'N/A'),
            'user_name' => $order->customer ? $order->customer->display_name : 'Guest',
            'cancellation_reason' => $reason,
            'datetime' => now()->format('Y-m-d H:i:s')
        ];

        $this->sendNotification($data);
    }

    /**
     * Send store application submitted notification
     */
    public function sendStoreApplicationSubmittedNotification(Store $store)
    {
        $data = [
            'activity_type' => 'store_application_submitted',
            'activity_message' => 'New store application submitted by ' . ($store->provider ? $store->provider->display_name : 'Unknown'),
            'store' => $store,
            'store_name' => $store->name,
            'provider_name' => $store->provider ? $store->provider->display_name : 'Unknown',
            'store_address' => $store->address,
            'store_phone' => $store->phone ?: 'N/A',
            'application_date' => $store->created_at->format('Y-m-d H:i:s'),
            'user_name' => 'Admin',
            'datetime' => now()->format('Y-m-d H:i:s')
        ];

        $this->sendNotification($data);
    }

    /**
     * Send store approved notification
     */
    public function sendStoreApprovedNotification(Store $store)
    {
        $data = [
            'activity_type' => 'store_approved',
            'activity_message' => 'Your store ' . $store->name . ' has been approved',
            'store' => $store,
            'store_name' => $store->name,
            'provider_name' => $store->provider ? $store->provider->display_name : 'Unknown',
            'store_address' => $store->address,
            'approval_date' => now()->format('Y-m-d H:i:s'),
            'user_name' => $store->provider ? $store->provider->display_name : 'Provider',
            'datetime' => now()->format('Y-m-d H:i:s')
        ];

        $this->sendNotification($data);
    }

    /**
     * Send store rejected notification
     */
    public function sendStoreRejectedNotification(Store $store, $reason = null)
    {
        $data = [
            'activity_type' => 'store_rejected',
            'activity_message' => 'Your store application ' . $store->name . ' has been rejected',
            'store' => $store,
            'store_name' => $store->name,
            'provider_name' => $store->provider ? $store->provider->display_name : 'Unknown',
            'store_address' => $store->address,
            'rejection_date' => now()->format('Y-m-d H:i:s'),
            'rejection_reason' => $reason,
            'user_name' => $store->provider ? $store->provider->display_name : 'Provider',
            'datetime' => now()->format('Y-m-d H:i:s')
        ];

        $this->sendNotification($data);
    }

    /**
     * Send low stock alert notification
     */
    public function sendLowStockAlertNotification(Product $product, $store = null)
    {
        $stockQuantity = $store ?
            $product->storeProducts()->where('store_id', $store->id)->first()->stock_quantity ?? 0 :
            $product->stock_quantity;

        $data = [
            'activity_type' => 'low_stock_alert',
            'activity_message' => 'Low stock alert for ' . $product->name . ' - ' . $stockQuantity . ' remaining',
            'product' => $product,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'stock_quantity' => $stockQuantity,
            'low_stock_threshold' => $product->low_stock_threshold ?? 10,
            'store_name' => $store ? $store->name : null,
            'user_name' => $store ? ($store->provider ? $store->provider->display_name : 'Provider') : 'Admin',
            'datetime' => now()->format('Y-m-d H:i:s')
        ];

        $this->sendNotification($data);
    }

    /**
     * Get notification recipients based on order
     */
    protected function getOrderNotificationRecipients(Order $order)
    {
        $recipients = [];

        // Add customer
        if ($order->customer) {
            $recipients[] = $order->customer->id;
        }

        // Add admin
        $admin = User::where('user_type', 'admin')->first();
        if ($admin) {
            $recipients[] = $admin->id;
        }

        // Add provider if it's a store order
        if (!$order->is_admin_order && $order->store && $order->store->provider) {
            $recipients[] = $order->store->provider->id;
        }

        return $recipients;
    }

    /**
     * Get notification recipients for store-related notifications
     */
    protected function getStoreNotificationRecipients(Store $store, $includeAdmin = true)
    {
        $recipients = [];

        // Add provider
        if ($store->provider) {
            $recipients[] = $store->provider->id;
        }

        // Add admin
        if ($includeAdmin) {
            $admin = User::where('user_type', 'admin')->first();
            if ($admin) {
                $recipients[] = $admin->id;
            }
        }

        return $recipients;
    }

    /**
     * Get notification recipients for product-related notifications
     */
    protected function getProductNotificationRecipients(Product $product, $store = null)
    {
        $recipients = [];

        // Add admin for admin products
        if ($product->created_by_type === 'admin') {
            $admin = User::where('user_type', 'admin')->first();
            if ($admin) {
                $recipients[] = $admin->id;
            }
        }

        // Add provider for provider products or store-specific alerts
        if ($product->created_by_type === 'provider' && $product->creator) {
            $recipients[] = $product->creator->id;
        } elseif ($store && $store->provider) {
            $recipients[] = $store->provider->id;
        }

        return $recipients;
    }

    /**
     * Override the base sendNotification method to handle e-commerce specific logic
     */
    public function sendNotification($data)
    {
        \Log::info('EcommerceNotificationTrait::sendNotification called with data: ', $data);
        // Get site setup and general settings
        $app_setting = \App\Models\Setting::getValueByKey('site-setup', 'site-setup');
        date_default_timezone_set($app_setting->time_zone ?? 'UTC');
        $data['datetime'] = date('Y-m-d H:i:s');

        $admin = \App\Models\User::where('user_type', 'admin')->first();
        $notification_type = $data['activity_type'];

        // Set up order-specific data
        if (isset($data['order'])) {
            $order = $data['order'];
            $id = $order->id;
            $userId = $order->customer_id;

            // Get provider IDs from order items
            $providerIds = [];
            foreach ($order->items as $item) {
                if ($item->product && $item->product->created_by_type === 'provider') {
                    $providerIds[] = $item->product->created_by;
                }
            }
            $providerIds = array_unique($providerIds);
        }

        // Get general settings for company info
        $generalsetting = \App\Models\Setting::getValueByKey('general-setting', 'general-setting');

        // Prepare notification data
        $notification_data = [
            'id' => $id ?? 0,
            'type' => $data['activity_type'],
            'message' => $data['activity_message'],
            "ios_badgeType" => "Increase",
            "ios_badgeCount" => 1,
            "notification-type" => $notification_type,
            'logged_in_user_fullname' => $admin ? $admin['display_name'] ?: default_user_name() : '',
            'logged_in_user_role' => $admin ? ucfirst($admin->user_type) ?? '-' : '',
            'company_name' => env('APP_NAME'),
            'company_contact_info' => implode('', [
                $generalsetting->helpline_number ?? '' . PHP_EOL,
                $generalsetting->inquriy_email ?? '',
            ]),
        ];

        // Add order-specific data to notification
        if (isset($order)) {
            $notification_data['user_name'] = $order->customer ? $order->customer->display_name : 'Guest';
            $notification_data['order_number'] = $order->formatted_order_number;
            $notification_data['total_amount'] = getPriceFormat($order->total_amount);
            $notification_data['order_date'] = $order->created_at->format('Y-m-d H:i:s');
            $notification_data['store_name'] = $order->is_admin_order ? 'Admin Store' : ($order->store ? $order->store->name : 'Main Store');
            $notification_data['order_status'] = ucfirst(str_replace('_', ' ', $order->status));

            // Add status-specific data
            if (isset($data['old_status'])) {
                $notification_data['old_status'] = ucfirst(str_replace('_', ' ', $data['old_status']));
            }
            if (isset($data['status_notes'])) {
                $notification_data['status_notes'] = $data['status_notes'];
            }
            if (isset($data['delivered_date'])) {
                $notification_data['delivered_date'] = $data['delivered_date'];
            }
            if (isset($data['cancellation_reason'])) {
                $notification_data['cancellation_reason'] = $data['cancellation_reason'];
            }
        }

        // Find notification template
        $mailable = \App\Models\NotificationTemplate::where('type', $notification_type)
                    ->with('defaultNotificationTemplateMap')
                    ->first();

        if ($mailable != null && $mailable->to != null) {
            $mails = json_decode($mailable->to);

            foreach ($mails as $key => $mailTo) {
                switch ($mailTo) {
                    case 'admin':
                        $admin = \App\Models\User::role('admin')->first();
                        if (isset($admin->email)) {
                            try {
                                $admin->notify(new \App\Notifications\CommonNotification($notification_type, $notification_data));
                            } catch (\Exception $e) {
                                \Log::error('Failed to send admin notification: ' . $e->getMessage());
                            }
                        }
                        break;

                    case 'provider':
                        if (isset($providerIds) && !empty($providerIds)) {
                            foreach ($providerIds as $providerId) {
                                $provider = \App\Models\User::find($providerId);
                                if (isset($provider->email)) {
                                    try {
                                        $provider->notify(new \App\Notifications\CommonNotification($notification_type, $notification_data));
                                    } catch (\Exception $e) {
                                        \Log::error('Failed to send provider notification: ' . $e->getMessage());
                                    }
                                }
                            }
                        }
                        break;

                    case 'user':
                        if (isset($userId)) {
                            $user = \App\Models\User::find($userId);
                            if (isset($user->email)) {
                                try {
                                    $user->notify(new \App\Notifications\CommonNotification($notification_type, $notification_data));
                                } catch (\Exception $e) {
                                    \Log::error('Failed to send user notification: ' . $e->getMessage());
                                }
                            }
                        }
                        break;
                }
            }
        } else {
            \Log::warning('No notification template found for type: ' . $notification_type);
        }
    }
}
