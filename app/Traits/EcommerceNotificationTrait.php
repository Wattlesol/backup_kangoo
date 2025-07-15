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
            'order_date' => $order->created_at->format('Y-m-d H:i:s'),
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
        // Set specific recipients based on notification type
        if (isset($data['order'])) {
            $recipients = $this->getOrderNotificationRecipients($data['order']);
            $data['recipients'] = $recipients;
        } elseif (isset($data['store'])) {
            $recipients = $this->getStoreNotificationRecipients($data['store']);
            $data['recipients'] = $recipients;
        } elseif (isset($data['product'])) {
            $store = isset($data['store']) ? $data['store'] : null;
            $recipients = $this->getProductNotificationRecipients($data['product'], $store);
            $data['recipients'] = $recipients;
        }

        // TODO: Implement actual notification sending logic
        // For now, just log the notification data for testing
        \Log::info('E-commerce notification would be sent', [
            'type' => get_class($this),
            'recipients' => $data['recipients'] ?? [],
            'data' => $data
        ]);
    }
}
