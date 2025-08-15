<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Store;
use App\Models\OrderItem;
use App\Models\Product;
use Yajra\DataTables\DataTables;

class OrderController extends Controller
{
    /**
     * Display provider's orders (Single Store Architecture)
     * Shows orders containing products created by this provider
     */
    public function index(Request $request)
    {
        $auth_user = auth()->user();

        if ($auth_user->user_type !== 'provider') {
            abort(403, 'User does not have the right permissions.');
        }

        $filter = [
            'status' => $request->status,
            'payment_status' => $request->payment_status,
        ];
        $pageTitle = trans('messages.my_orders');
        $assets = ['datatable'];

        return view('provider.order.index', compact('pageTitle', 'auth_user', 'assets', 'filter'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        $auth_user = auth()->user();

        // Get orders that contain products created by this provider (Single Store Architecture)
        $query = Order::with(['customer', 'items.product'])
                     ->whereHas('items.product', function($q) use ($auth_user) {
                         $q->where('created_by', $auth_user->id)
                           ->where('created_by_type', 'provider');
                     });

        $filter = $request->filter;
        if (isset($filter)) {
            if (isset($filter['status']) && $filter['status'] != '') {
                $query->where('status', $filter['status']);
            }
            if (isset($filter['payment_status']) && $filter['payment_status'] != '') {
                $query->where('payment_status', $filter['payment_status']);
            }
        }

        return $datatable->eloquent($query)
            ->editColumn('order_number', function($query) {
                return '<a class="btn-link btn-link-hover" href='.route('provider.order.show', $query->id).'>'.$query->formatted_order_number.'</a>';
            })
            ->editColumn('customer', function($query) {
                return $query->customer ? $query->customer->display_name : '-';
            })
            ->addColumn('provider_items', function($query) use ($auth_user) {
                $providerItems = $query->items->filter(function($item) use ($auth_user) {
                    return $item->product &&
                           $item->product->created_by == $auth_user->id &&
                           $item->product->created_by_type == 'provider';
                });
                return $providerItems->count() . ' of ' . $query->items->count() . ' item(s)';
            })
            ->addColumn('provider_total', function($query) use ($auth_user) {
                $providerTotal = $query->items->filter(function($item) use ($auth_user) {
                    return $item->product &&
                           $item->product->created_by == $auth_user->id &&
                           $item->product->created_by_type == 'provider';
                })->sum('total_price');
                return getPriceFormat($providerTotal);
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
                return dateAgoFormate($query->created_at, true);
            })
            ->addColumn('action', function($order){
                return view('provider.order.action',compact('order'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['action','status','payment_status','order_number'])
            ->toJson();
    }

    /**
     * Display the specified order (Single Store Architecture)
     */
    public function show($id)
    {
        $auth_user = auth()->user();

        if ($auth_user->user_type !== 'provider') {
            return redirect()->route('home')->withErrors('Unauthorized access');
        }

        // Verify this order contains products from this provider
        $order = Order::with([
            'customer',
            'items.product',
            'items.productVariant',
            'statusHistories.changedBy'
        ])->whereHas('items.product', function($q) use ($auth_user) {
            $q->where('created_by', $auth_user->id)
              ->where('created_by_type', 'provider');
        })->findOrFail($id);

        // Filter items to show only provider's products
        $providerItems = $order->items->filter(function($item) use ($auth_user) {
            return $item->product &&
                   $item->product->created_by == $auth_user->id &&
                   $item->product->created_by_type == 'provider';
        });

        $pageTitle = trans('messages.order_details');

        return view('provider.order.view', compact('pageTitle', 'order', 'auth_user', 'providerItems'));
    }

    /**
     * Update order status (Single Store Architecture)
     */
    public function updateStatus(Request $request)
    {
        $auth_user = auth()->user();

        if ($auth_user->user_type !== 'provider') {
            return response()->json(['status' => false, 'message' => 'Unauthorized access']);
        }

        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'status' => 'required|in:processing,shipped,delivered,cancelled',
            'notes' => 'nullable|string|max:500'
        ]);

        // Verify this order contains products from this provider
        $order = Order::whereHas('items.product', function($q) use ($auth_user) {
            $q->where('created_by', $auth_user->id)
              ->where('created_by_type', 'provider');
        })->findOrFail($request->order_id);

        // Update order status
        $order->updateStatus($request->status, $request->notes, $auth_user->id);

        return response()->json([
            'status' => true,
            'message' => __('messages.order_status_updated_successfully')
        ]);
    }

    /**
     * Get order statistics for provider
     */
    public function statistics()
    {
        $auth_user = auth()->user();
        $store = Store::where('provider_id', $auth_user->id)->first();
        
        if (!$store) {
            return response()->json([
                'total_orders' => 0,
                'pending_orders' => 0,
                'confirmed_orders' => 0,
                'delivered_orders' => 0,
                'total_revenue' => 0,
            ]);
        }

        $stats = [
            'total_orders' => Order::where('store_id', $store->id)->count(),
            'pending_orders' => Order::where('store_id', $store->id)->where('status', 'pending')->count(),
            'confirmed_orders' => Order::where('store_id', $store->id)->where('status', 'confirmed')->count(),
            'processing_orders' => Order::where('store_id', $store->id)->where('status', 'processing')->count(),
            'shipped_orders' => Order::where('store_id', $store->id)->where('status', 'shipped')->count(),
            'delivered_orders' => Order::where('store_id', $store->id)->where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('store_id', $store->id)->where('status', 'cancelled')->count(),
            'total_revenue' => Order::where('store_id', $store->id)->where('payment_status', 'paid')->sum('total_amount'),
            'pending_revenue' => Order::where('store_id', $store->id)->where('payment_status', 'pending')->sum('total_amount'),
        ];

        return response()->json($stats);
    }

    /**
     * Provider dashboard (Single Store Architecture)
     */
    public function dashboard()
    {
        $auth_user = auth()->user();

        if ($auth_user->user_type !== 'provider') {
            return redirect()->route('home')->withErrors('Unauthorized access');
        }

        $pageTitle = trans('messages.provider_dashboard');

        // Get provider statistics
        $stats = [
            'total_products' => Product::where('created_by', $auth_user->id)
                                     ->where('created_by_type', 'provider')
                                     ->count(),
            'approved_products' => Product::where('created_by', $auth_user->id)
                                         ->where('created_by_type', 'provider')
                                         ->where('approval_status', 'approved')
                                         ->count(),
            'pending_products' => Product::where('created_by', $auth_user->id)
                                        ->where('created_by_type', 'provider')
                                        ->where('approval_status', 'pending')
                                        ->count(),
            'total_orders' => Order::whereHas('items.product', function($q) use ($auth_user) {
                                $q->where('created_by', $auth_user->id)
                                  ->where('created_by_type', 'provider');
                            })->count(),
            'pending_orders' => Order::whereHas('items.product', function($q) use ($auth_user) {
                                  $q->where('created_by', $auth_user->id)
                                    ->where('created_by_type', 'provider');
                              })->where('status', 'pending')->count(),
        ];

        // Get recent orders containing provider's products
        $recentOrders = Order::with(['customer', 'items.product'])
                            ->whereHas('items.product', function($q) use ($auth_user) {
                                $q->where('created_by', $auth_user->id)
                                  ->where('created_by_type', 'provider');
                            })
                            ->orderBy('created_at', 'desc')
                            ->limit(5)
                            ->get();

        return view('provider.dashboard', compact('pageTitle', 'auth_user', 'recentOrders', 'stats'));
    }



}
