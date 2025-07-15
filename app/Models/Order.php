<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\EcommerceNotificationTrait;

class Order extends BaseModel
{
    use HasFactory, SoftDeletes, EcommerceNotificationTrait;

    protected $table = 'orders';

    protected $fillable = [
        'order_number',
        'customer_id',
        'store_id',
        'order_type',
        'status',
        'subtotal',
        'tax_amount',
        'delivery_fee',
        'discount_amount',
        'total_amount',
        'currency',
        'payment_status',
        'payment_method',
        'payment_transaction_id',
        'delivery_address',
        'delivery_phone',
        'delivery_notes',
        'estimated_delivery_at',
        'delivered_at',
        'notes',
        'cancellation_reason',
        'cancelled_by',
        'cancelled_at'
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'delivery_address' => 'array',
        'estimated_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(OrderStatusHistory::class);
    }

    // Scopes
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByStore($query, $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPaymentStatus($query, $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    public function scopeAdminOrders($query)
    {
        return $query->where('order_type', 'admin');
    }

    public function scopeStoreOrders($query)
    {
        return $query->where('order_type', 'store');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'delivered');
    }

    // Accessors
    public function getFormattedOrderNumberAttribute()
    {
        return '#' . $this->order_number;
    }

    public function getIsAdminOrderAttribute()
    {
        return $this->order_type === 'admin';
    }

    public function getCanBeCancelledAttribute()
    {
        return in_array($this->status, ['pending', 'confirmed']);
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'warning',
            'confirmed' => 'info',
            'processing' => 'primary',
            'shipped' => 'secondary',
            'delivered' => 'success',
            'cancelled' => 'danger',
            'refunded' => 'dark'
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    // Methods
    public function updateStatus($newStatus, $notes = null, $userId = null)
    {
        $oldStatus = $this->status;

        $this->update(['status' => $newStatus]);

        // Create status history
        $this->statusHistories()->create([
            'status' => $newStatus,
            'notes' => $notes,
            'changed_by' => $userId,
            'changed_at' => now()
        ]);

        // Handle specific status changes
        switch ($newStatus) {
            case 'delivered':
                $this->update(['delivered_at' => now()]);
                break;
            case 'cancelled':
                $this->update([
                    'cancelled_at' => now(),
                    'cancelled_by' => $userId,
                    'cancellation_reason' => $notes
                ]);
                // Restore stock
                $this->restoreStock();
                break;
        }

        // Send notifications
        if ($oldStatus !== $newStatus) {
            $this->sendOrderStatusUpdatedNotification($this, $oldStatus, $notes);

            // Send specific notification for delivered status
            if ($newStatus === 'delivered') {
                $this->sendOrderDeliveredNotification($this);
            }

            // Send specific notification for cancelled status
            if ($newStatus === 'cancelled') {
                $this->sendOrderCancelledNotification($this, $notes);
            }
        }

        return true;
    }

    public function cancel($reason = null, $userId = null)
    {
        return $this->updateStatus('cancelled', $reason, $userId);
    }

    public function restoreStock()
    {
        foreach ($this->items as $item) {
            if ($item->product_variant_id) {
                $item->productVariant->increaseStock($item->quantity);
            } else {
                // Handle store product stock
                if ($this->store_id) {
                    $storeProduct = StoreProduct::where('store_id', $this->store_id)
                                                ->where('product_id', $item->product_id)
                                                ->first();
                    if ($storeProduct) {
                        $storeProduct->increaseStock($item->quantity);
                    }
                }
                $item->product->increaseStock($item->quantity);
            }
        }
    }

    public function calculateTotals()
    {
        $subtotal = $this->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        $this->update([
            'subtotal' => $subtotal,
            'total_amount' => $subtotal + $this->tax_amount + $this->delivery_fee - $this->discount_amount
        ]);
    }

    // Static methods
    public static function generateOrderNumber()
    {
        $prefix = 'ORD';
        $timestamp = now()->format('ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        return $prefix . $timestamp . $random;
    }
}
