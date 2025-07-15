<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationTemplate;
use App\Models\NotificationTemplateContentMapping;
use Illuminate\Support\Facades\DB;

class EcommerceNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Disable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        echo "Insert: E-commerce notification templates \n\n";

        // Order Created Notification
        $template = NotificationTemplate::create([
            'type' => 'order_created',
            'name' => 'order_created',
            'label' => 'Order Created',
            'status' => 1,
            'to' => '["admin","provider","user"]',
            'channels' => ['IS_MAIL' => '1','PUSH_NOTIFICATION' => '1'],
        ]);

        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'New order #{{order_number}} has been created',
            'subject' => 'Order Confirmation - #{{order_number}}',
            'template_detail' => '<p>Dear {{user_name}},</p>
                <p>Your order #{{order_number}} has been successfully created.</p>
                <p><strong>Order Details:</strong></p>
                <ul>
                    <li>Order Number: {{order_number}}</li>
                    <li>Total Amount: {{total_amount}}</li>
                    <li>Order Date: {{order_date}}</li>
                    <li>Store: {{store_name}}</li>
                </ul>
                <p>You will receive updates as your order progresses.</p>
                <p>Thank you for your order!</p>
                <p>Best regards,<br>{{company_name}}</p>',
            'status' => 1,
        ]);

        // Order Status Updated Notification
        $template = NotificationTemplate::create([
            'type' => 'order_status_updated',
            'name' => 'order_status_updated',
            'label' => 'Order Status Updated',
            'status' => 1,
            'to' => '["admin","provider","user"]',
            'channels' => ['IS_MAIL' => '1','PUSH_NOTIFICATION' => '1'],
        ]);

        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Order #{{order_number}} status updated to {{order_status}}',
            'subject' => 'Order Update - #{{order_number}}',
            'template_detail' => '<p>Dear {{user_name}},</p>
                <p>Your order #{{order_number}} status has been updated.</p>
                <p><strong>New Status:</strong> {{order_status}}</p>
                <p><strong>Order Details:</strong></p>
                <ul>
                    <li>Order Number: {{order_number}}</li>
                    <li>Total Amount: {{total_amount}}</li>
                    <li>Store: {{store_name}}</li>
                </ul>
                {{#if status_notes}}
                <p><strong>Notes:</strong> {{status_notes}}</p>
                {{/if}}
                <p>Thank you for your business!</p>
                <p>Best regards,<br>{{company_name}}</p>',
            'status' => 1,
        ]);

        // Order Delivered Notification
        $template = NotificationTemplate::create([
            'type' => 'order_delivered',
            'name' => 'order_delivered',
            'label' => 'Order Delivered',
            'status' => 1,
            'to' => '["admin","provider","user"]',
            'channels' => ['IS_MAIL' => '1','PUSH_NOTIFICATION' => '1'],
        ]);

        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Order #{{order_number}} has been delivered successfully',
            'subject' => 'Order Delivered - #{{order_number}}',
            'template_detail' => '<p>Dear {{user_name}},</p>
                <p>Great news! Your order #{{order_number}} has been delivered successfully.</p>
                <p><strong>Order Details:</strong></p>
                <ul>
                    <li>Order Number: {{order_number}}</li>
                    <li>Total Amount: {{total_amount}}</li>
                    <li>Delivered Date: {{delivered_date}}</li>
                    <li>Store: {{store_name}}</li>
                </ul>
                <p>We hope you enjoy your purchase! Please consider leaving a review.</p>
                <p>Thank you for choosing us!</p>
                <p>Best regards,<br>{{company_name}}</p>',
            'status' => 1,
        ]);

        // Order Cancelled Notification
        $template = NotificationTemplate::create([
            'type' => 'order_cancelled',
            'name' => 'order_cancelled',
            'label' => 'Order Cancelled',
            'status' => 1,
            'to' => '["admin","provider","user"]',
            'channels' => ['IS_MAIL' => '1','PUSH_NOTIFICATION' => '1'],
        ]);

        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Order #{{order_number}} has been cancelled',
            'subject' => 'Order Cancelled - #{{order_number}}',
            'template_detail' => '<p>Dear {{user_name}},</p>
                <p>Your order #{{order_number}} has been cancelled.</p>
                <p><strong>Order Details:</strong></p>
                <ul>
                    <li>Order Number: {{order_number}}</li>
                    <li>Total Amount: {{total_amount}}</li>
                    <li>Cancellation Date: {{cancelled_date}}</li>
                    <li>Store: {{store_name}}</li>
                </ul>
                {{#if cancellation_reason}}
                <p><strong>Reason:</strong> {{cancellation_reason}}</p>
                {{/if}}
                <p>If you have any questions, please contact our support team.</p>
                <p>Best regards,<br>{{company_name}}</p>',
            'status' => 1,
        ]);

        // Store Application Submitted Notification
        $template = NotificationTemplate::create([
            'type' => 'store_application_submitted',
            'name' => 'store_application_submitted',
            'label' => 'Store Application Submitted',
            'status' => 1,
            'to' => '["admin","provider"]',
            'channels' => ['IS_MAIL' => '1','PUSH_NOTIFICATION' => '1'],
        ]);

        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'New store application submitted by {{provider_name}}',
            'subject' => 'Store Application Submitted - {{store_name}}',
            'template_detail' => '<p>Dear {{user_name}},</p>
                <p>A new store application has been submitted and is pending review.</p>
                <p><strong>Store Details:</strong></p>
                <ul>
                    <li>Store Name: {{store_name}}</li>
                    <li>Provider: {{provider_name}}</li>
                    <li>Address: {{store_address}}</li>
                    <li>Phone: {{store_phone}}</li>
                    <li>Application Date: {{application_date}}</li>
                </ul>
                <p>Please review and approve/reject the application.</p>
                <p>Best regards,<br>{{company_name}}</p>',
            'status' => 1,
        ]);

        // Store Approved Notification
        $template = NotificationTemplate::create([
            'type' => 'store_approved',
            'name' => 'store_approved',
            'label' => 'Store Approved',
            'status' => 1,
            'to' => '["admin","provider"]',
            'channels' => ['IS_MAIL' => '1','PUSH_NOTIFICATION' => '1'],
        ]);

        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Your store {{store_name}} has been approved',
            'subject' => 'Store Approved - {{store_name}}',
            'template_detail' => '<p>Dear {{provider_name}},</p>
                <p>Congratulations! Your store application has been approved.</p>
                <p><strong>Store Details:</strong></p>
                <ul>
                    <li>Store Name: {{store_name}}</li>
                    <li>Address: {{store_address}}</li>
                    <li>Approval Date: {{approval_date}}</li>
                </ul>
                <p>You can now start adding products to your store and receive orders.</p>
                <p>Welcome to our marketplace!</p>
                <p>Best regards,<br>{{company_name}}</p>',
            'status' => 1,
        ]);

        // Store Rejected Notification
        $template = NotificationTemplate::create([
            'type' => 'store_rejected',
            'name' => 'store_rejected',
            'label' => 'Store Rejected',
            'status' => 1,
            'to' => '["admin","provider"]',
            'channels' => ['IS_MAIL' => '1','PUSH_NOTIFICATION' => '1'],
        ]);

        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Your store application {{store_name}} has been rejected',
            'subject' => 'Store Application Rejected - {{store_name}}',
            'template_detail' => '<p>Dear {{provider_name}},</p>
                <p>We regret to inform you that your store application has been rejected.</p>
                <p><strong>Store Details:</strong></p>
                <ul>
                    <li>Store Name: {{store_name}}</li>
                    <li>Address: {{store_address}}</li>
                    <li>Rejection Date: {{rejection_date}}</li>
                </ul>
                {{#if rejection_reason}}
                <p><strong>Reason:</strong> {{rejection_reason}}</p>
                {{/if}}
                <p>You can resubmit your application after addressing the issues mentioned above.</p>
                <p>If you have any questions, please contact our support team.</p>
                <p>Best regards,<br>{{company_name}}</p>',
            'status' => 1,
        ]);

        // Low Stock Alert Notification
        $template = NotificationTemplate::create([
            'type' => 'low_stock_alert',
            'name' => 'low_stock_alert',
            'label' => 'Low Stock Alert',
            'status' => 1,
            'to' => '["admin","provider"]',
            'channels' => ['IS_MAIL' => '1','PUSH_NOTIFICATION' => '1'],
        ]);

        $template->defaultNotificationTemplateMap()->create([
            'language' => 'en',
            'notification_link' => '',
            'notification_message' => 'Low stock alert for {{product_name}} - {{stock_quantity}} remaining',
            'subject' => 'Low Stock Alert - {{product_name}}',
            'template_detail' => '<p>Dear {{user_name}},</p>
                <p>This is an alert that the following product is running low on stock:</p>
                <p><strong>Product Details:</strong></p>
                <ul>
                    <li>Product Name: {{product_name}}</li>
                    <li>SKU: {{product_sku}}</li>
                    <li>Current Stock: {{stock_quantity}}</li>
                    <li>Low Stock Threshold: {{low_stock_threshold}}</li>
                    {{#if store_name}}
                    <li>Store: {{store_name}}</li>
                    {{/if}}
                </ul>
                <p>Please restock this product to avoid stockouts.</p>
                <p>Best regards,<br>{{company_name}}</p>',
            'status' => 1,
        ]);

        // Enable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        echo "E-commerce notification templates seeded successfully!\n\n";
    }
}
