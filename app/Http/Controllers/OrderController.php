<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Models\Store;
use Yajra\DataTables\DataTables;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $filter = [
            'status' => $request->status,
            'payment_status' => $request->payment_status,
            'store_id' => $request->store_id,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ];

        // Calculate statistics
        $statistics = [
            'total' => Order::count(),
            'pending' => Order::where('status', 'pending')->count(),
            'delivered' => Order::where('status', 'delivered')->count(),
            'revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
        ];

        $pageTitle = trans('messages.list_form_title',['form' => trans('messages.order')] );
        $auth_user = authSession();
        $assets = ['datatable'];
        $stores = Store::where('status', 'approved')->get();
        return view('order.index', compact('pageTitle','auth_user','assets','filter','stores','statistics'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        $query = Order::with(['customer', 'store']);
        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['status']) && $filter['status'] != '') {
                $query->where('status', $filter['status']);
            }
            if (isset($filter['payment_status']) && $filter['payment_status'] != '') {
                $query->where('payment_status', $filter['payment_status']);
            }
            if (isset($filter['store_id']) && $filter['store_id'] != '') {
                if ($filter['store_id'] === 'admin') {
                    $query->whereNull('store_id'); // Admin orders
                } else {
                    $query->where('store_id', $filter['store_id']);
                }
            }
            if (isset($filter['date_from']) && $filter['date_from'] != '') {
                $query->whereDate('created_at', '>=', $filter['date_from']);
            }
            if (isset($filter['date_to']) && $filter['date_to'] != '') {
                $query->whereDate('created_at', '<=', $filter['date_to']);
            }
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$row->id.'"  name="datatable_ids[]" value="'.$row->id.'" onclick="dataTableRowCheck('.$row->id.')">';
            })
            ->editColumn('order_number', function($query) {
                return '<a class="btn-link btn-link-hover" href='.route('order.show', $query->id).'>'.$query->formatted_order_number.'</a>';
            })
            ->editColumn('customer', function($query) {
                return $query->customer ? $query->customer->display_name : '-';
            })
            ->editColumn('store', function($query) {
                if ($query->is_admin_order) {
                    return '<span class="badge badge-primary">Admin</span>';
                }
                return $query->store ? $query->store->name : '-';
            })
            ->editColumn('total_amount', function($query) {
                return getPriceFormat($query->total_amount);
            })
            ->editColumn('status', function($query) {
                return '<span class="badge badge-'.$query->status_color.'">'.ucfirst(str_replace('_', ' ', $query->status)).'</span>';
            })
            ->editColumn('payment_status', function($query) {
                $colors = [
                    'pending' => 'warning',
                    'paid' => 'success',
                    'failed' => 'danger',
                    'refunded' => 'info'
                ];
                $color = $colors[$query->payment_status] ?? 'secondary';
                return '<span class="badge badge-'.$color.'">'.ucfirst($query->payment_status).'</span>';
            })
            ->editColumn('created_at', function($query) {
                return $query->created_at ? $query->created_at->format('Y-m-d H:i:s') : '-';
            })
            ->addColumn('action', function($order){
                return view('order.action',compact('order'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['action','status','payment_status','check','order_number','store'])
            ->toJson();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $order = Order::with([
            'customer',
            'store.provider',
            'items.product',
            'items.productVariant',
            'statusHistories.changedBy'
        ])->findOrFail($id);

        $pageTitle = trans('messages.view_form_title',['form'=>trans('messages.order')]);
        $auth_user = authSession();
        return view('order.view', compact('pageTitle','order','auth_user'));
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
            'notes' => 'nullable|string|max:500'
        ]);

        $order = Order::findOrFail($data['order_id']);
        $order->updateStatus($data['status'], $data['notes'], auth()->id());

        $message = trans('messages.order_status_updated_successfully');
        return comman_custom_response(['message'=> $message , 'status' => true]);
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(Request $request, $id = null)
    {
        // Handle both URL parameter and request body for order_id
        $orderId = $id ?? $request->order_id;

        $data = $request->validate([
            'payment_status' => 'required|in:pending,paid,failed,refunded',
            'payment_method' => 'nullable|string',
            'transaction_id' => 'nullable|string'
        ]);

        if (!$orderId) {
            $data['order_id'] = 'required|exists:orders,id';
            $request->validate($data);
            $orderId = $request->order_id;
        }

        $order = Order::findOrFail($orderId);

        $updateData = [
            'payment_status' => $data['payment_status']
        ];

        if (isset($data['payment_method'])) {
            $updateData['payment_method'] = $data['payment_method'];
        }

        if (isset($data['transaction_id'])) {
            $updateData['payment_transaction_id'] = $data['transaction_id'];
        }

        $order->update($updateData);

        $message = trans('messages.payment_status_updated_successfully');

        if($request->is('api/*')) {
            return comman_custom_response([
                'message' => $message,
                'data' => [
                    'order_id' => $order->id,
                    'payment_status' => $order->payment_status,
                    'payment_method' => $order->payment_method,
                    'transaction_id' => $order->payment_transaction_id
                ],
                'status' => true
            ]);
        }

        return comman_custom_response(['message'=> $message , 'status' => true]);
    }

    /**
     * Cancel order
     */
    public function cancel(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'reason' => 'required|string|max:500'
        ]);

        $order = Order::findOrFail($data['order_id']);

        if (!$order->can_be_cancelled) {
            return comman_custom_response(['message'=> trans('messages.order_cannot_be_cancelled') , 'status' => false]);
        }

        $order->cancel($data['reason'], auth()->id());

        $message = trans('messages.order_cancelled_successfully');
        return comman_custom_response(['message'=> $message , 'status' => true]);
    }

    /**
     * Get order statistics
     */
    public function statistics()
    {
        $stats = [
            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'confirmed_orders' => Order::where('status', 'confirmed')->count(),
            'delivered_orders' => Order::where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount'),
            'admin_orders' => Order::where('order_type', 'admin')->count(),
            'store_orders' => Order::where('order_type', 'store')->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Export orders
     */
    public function export(Request $request)
    {
        // Implementation for exporting orders to CSV/Excel
        // This would typically use Laravel Excel or similar package

        $message = trans('messages.export_feature_coming_soon');
        return comman_custom_response(['message'=> $message , 'status' => false]);
    }

    /**
     * Print order as PDF
     */
    public function print($id)
    {
        $order = Order::with([
            'customer',
            'store.provider',
            'items.product',
            'items.productVariant',
            'statusHistories.changedBy'
        ])->findOrFail($id);

        $data = \App\Models\AppSetting::first();

        // Return view for printing instead of PDF download
        return view('order.print', [
            'order' => $order,
            'data' => $data
        ]);
    }

    /**
     * Bulk actions on orders
     */
    public function bulkAction(Request $request)
    {
        $data = $request->validate([
            'action' => 'required|in:update_status,export',
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id',
            'status' => 'required_if:action,update_status|in:pending,confirmed,processing,shipped,delivered,cancelled'
        ]);

        if ($data['action'] == 'update_status') {
            $orders = Order::whereIn('id', $data['order_ids'])->get();

            foreach ($orders as $order) {
                $order->updateStatus($data['status'], 'Bulk status update', auth()->id());
            }

            $message = trans('messages.bulk_status_updated_successfully');
            return comman_custom_response(['message'=> $message , 'status' => true]);
        }

        return comman_custom_response(['message'=> trans('messages.invalid_action') , 'status' => false]);
    }

    /**
     * Get orders for API (JSON format)
     */
    public function getOrdersAPI(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $status = $request->get('status');
            $paymentStatus = $request->get('payment_status');
            $storeId = $request->get('store_id');
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $query = Order::with(['customer', 'store', 'items.product']);

            // Apply role-based filtering for security
            $user = auth()->user();
            if ($user->user_type === 'provider') {
                // Providers can only see orders containing their own products
                $query->whereHas('items.product', function($q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->where('created_by_type', 'provider');
                });
            }
            // Admin users can see all orders (no additional filtering needed)

            // Apply filters
            if ($status) {
                $query->where('status', $status);
            }

            if ($paymentStatus) {
                $query->where('payment_status', $paymentStatus);
            }

            if ($storeId) {
                $query->where('store_id', $storeId);
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                      ->orWhereHas('customer', function($customerQuery) use ($search) {
                          $customerQuery->where('first_name', 'like', "%{$search}%")
                                       ->orWhere('last_name', 'like', "%{$search}%")
                                       ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            // Apply sorting
            $query->orderBy($sortBy, $sortOrder);

            $orders = $query->paginate($perPage);

            // Transform data for API response
            $transformedOrders = $orders->getCollection()->map(function($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer' => $order->customer ? [
                        'id' => $order->customer->id,
                        'name' => $order->customer->display_name,
                        'email' => $order->customer->email
                    ] : null,
                    'store' => $order->store ? [
                        'id' => $order->store->id,
                        'name' => $order->store->name
                    ] : null,
                    'status' => $order->status,
                    'payment_status' => $order->payment_status,
                    'payment_method' => $order->payment_method,
                    'subtotal' => $order->subtotal,
                    'tax_amount' => $order->tax_amount,
                    'delivery_fee' => $order->delivery_fee,
                    'discount_amount' => $order->discount_amount,
                    'total_amount' => $order->total_amount,
                    'currency' => $order->currency ?? 'USD',
                    'items_count' => $order->items->count(),
                    'delivery_address' => $this->formatDeliveryAddress($order->delivery_address),
                    'delivery_phone' => $order->delivery_phone,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at
                ];
            });

            $response = [
                'data' => $transformedOrders,
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
                'from' => $orders->firstItem(),
                'to' => $orders->lastItem()
            ];

            return comman_custom_response([
                'message' => 'Orders retrieved successfully',
                'data' => $response,
                'status' => true
            ]);

        } catch (\Exception $e) {
            \Log::error('Get orders API error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            return comman_message_response('Failed to retrieve orders: ' . $e->getMessage());
        }
    }

    /**
     * Get single order for API (JSON format)
     */
    public function getOrderAPI($id)
    {
        try {
            $user = auth()->user();
            $query = Order::with([
                'customer',
                'store.provider',
                'items.product',
                'items.productVariant',
                'statusHistories.changedBy'
            ]);

            // Apply role-based filtering for security
            if ($user->user_type === 'provider') {
                // Providers can only see orders containing their own products
                $query->whereHas('items.product', function($q) use ($user) {
                    $q->where('created_by', $user->id)
                      ->where('created_by_type', 'provider');
                });
            }
            // Admin users can see all orders (no additional filtering needed)

            $order = $query->findOrFail($id);

            $orderData = [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'customer' => $order->customer ? [
                    'id' => $order->customer->id,
                    'name' => $order->customer->display_name,
                    'email' => $order->customer->email,
                    'phone' => $order->customer->mobile
                ] : null,
                'store' => $order->store ? [
                    'id' => $order->store->id,
                    'name' => $order->store->name,
                    'provider' => $order->store->provider ? [
                        'id' => $order->store->provider->id,
                        'name' => $order->store->provider->display_name,
                        'email' => $order->store->provider->email
                    ] : null
                ] : null,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'payment_method' => $order->payment_method,
                'payment_transaction_id' => $order->payment_transaction_id,
                'subtotal' => $order->subtotal,
                'tax_amount' => $order->tax_amount,
                'delivery_fee' => $order->delivery_fee,
                'discount_amount' => $order->discount_amount,
                'total_amount' => $order->total_amount,
                'currency' => $order->currency ?? 'USD',
                'delivery_address' => $this->formatDeliveryAddress($order->delivery_address),
                'delivery_phone' => $order->delivery_phone,
                'delivery_notes' => $order->delivery_notes,
                'items' => $order->items->map(function($item) {
                    return [
                        'id' => $item->id,
                        'product' => $item->product ? [
                            'id' => $item->product->id,
                            'name' => $item->product->name,
                            'sku' => $item->product->sku,
                            'image' => $item->product->featured_image
                        ] : [
                            'name' => $item->product_name
                        ],
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->total_price
                    ];
                }),
                'status_history' => $order->statusHistories->map(function($history) {
                    return [
                        'status' => $history->status,
                        'notes' => $history->notes,
                        'changed_by' => $history->changedBy ? $history->changedBy->display_name : 'System',
                        'created_at' => $history->created_at
                    ];
                }),
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at
            ];

            return comman_custom_response([
                'message' => 'Order details retrieved successfully',
                'data' => $orderData,
                'status' => true
            ]);

        } catch (\Exception $e) {
            \Log::error('Get order API error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'order_id' => $id
            ]);

            return comman_message_response('Failed to retrieve order: ' . $e->getMessage());
        }
    }

    /**
     * Format delivery address to ensure consistent JSON object format
     */
    private function formatDeliveryAddress($deliveryAddress)
    {
        if (is_null($deliveryAddress)) {
            return null;
        }

        // If it's already an array (properly cast), check for double-encoded data
        if (is_array($deliveryAddress)) {
            // Check if it's a migrated format with double-encoded JSON
            if (isset($deliveryAddress['address']) && isset($deliveryAddress['note']) &&
                $deliveryAddress['note'] === 'Migrated from legacy format') {

                // Try to decode the double-encoded JSON
                $doubleDecoded = json_decode($deliveryAddress['address'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($doubleDecoded)) {
                    return $doubleDecoded;
                }
            }
            return $deliveryAddress;
        }

        // If it's a JSON string, decode it
        if (is_string($deliveryAddress)) {
            $decoded = json_decode($deliveryAddress, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // Check if the decoded result contains double-encoded JSON
                if (isset($decoded['address']) && is_string($decoded['address'])) {
                    $doubleDecoded = json_decode($decoded['address'], true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($doubleDecoded)) {
                        return $doubleDecoded;
                    }
                }
                return $decoded;
            }

            // If JSON decode failed, treat as plain text address
            return [
                'address' => $deliveryAddress,
                'note' => 'Legacy address format'
            ];
        }

        // If it's an object, convert to array
        if (is_object($deliveryAddress)) {
            return (array) $deliveryAddress;
        }

        // Fallback for any other type
        return [
            'address' => (string) $deliveryAddress,
            'note' => 'Unknown address format'
        ];
    }
}
