<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Store;
use Yajra\DataTables\DataTables;

class OrderController extends Controller
{
    /**
     * Display provider's orders
     */
    public function index(Request $request)
    {
        $auth_user = authSession();
        
        if ($auth_user->user_type !== 'provider') {
            return redirect()->route('home')->withErrors('Unauthorized access');
        }

        $store = Store::where('provider_id', $auth_user->id)->first();
        
        if (!$store) {
            return redirect()->route('provider.store.create')->withErrors('Please create a store first');
        }

        $filter = [
            'status' => $request->status,
            'payment_status' => $request->payment_status,
        ];
        $pageTitle = trans('messages.my_orders');
        $assets = ['datatable'];
        
        return view('provider.order.index', compact('pageTitle', 'store', 'auth_user', 'assets', 'filter'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        $auth_user = authSession();
        $store = Store::where('provider_id', $auth_user->id)->firstOrFail();
        
        $query = Order::with(['customer', 'items.product'])
                     ->where('store_id', $store->id);
        
        $filter = $request->filter;
        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
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
     * Display the specified order
     */
    public function show($id)
    {
        $auth_user = authSession();
        
        if ($auth_user->user_type !== 'provider') {
            return redirect()->route('home')->withErrors('Unauthorized access');
        }

        $store = Store::where('provider_id', $auth_user->id)->firstOrFail();
        
        $order = Order::with([
            'customer', 
            'items.product', 
            'items.productVariant',
            'statusHistories.changedBy'
        ])->where('store_id', $store->id)->findOrFail($id);
        
        $pageTitle = trans('messages.order_details');
        
        return view('provider.order.view', compact('pageTitle', 'order', 'store', 'auth_user'));
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request)
    {
        $auth_user = authSession();
        $store = Store::where('provider_id', $auth_user->id)->firstOrFail();

        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'status' => 'required|in:confirmed,processing,shipped,delivered',
            'notes' => 'nullable|string|max:500'
        ]);

        $order = Order::where('store_id', $store->id)->findOrFail($request->order_id);
        
        // Providers can only update certain statuses
        $allowedStatuses = ['confirmed', 'processing', 'shipped', 'delivered'];
        if (!in_array($request->status, $allowedStatuses)) {
            return comman_custom_response(['message'=> 'Invalid status update' , 'status' => false]);
        }

        $order->updateStatus($request->status, $request->notes, $auth_user->id);

        return comman_custom_response(['message'=> 'Order status updated successfully' , 'status' => true]);
    }

    /**
     * Get order statistics for provider
     */
    public function statistics()
    {
        $auth_user = authSession();
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
     * Provider dashboard
     */
    public function dashboard()
    {
        $auth_user = authSession();
        
        if ($auth_user->user_type !== 'provider') {
            return redirect()->route('home')->withErrors('Unauthorized access');
        }

        $store = Store::where('provider_id', $auth_user->id)->first();
        $pageTitle = trans('messages.provider_dashboard');
        
        // Get recent orders
        $recentOrders = collect();
        if ($store) {
            $recentOrders = Order::with(['customer', 'items'])
                                ->where('store_id', $store->id)
                                ->orderBy('created_at', 'desc')
                                ->limit(5)
                                ->get();
        }
        
        return view('provider.dashboard', compact('pageTitle', 'store', 'auth_user', 'recentOrders'));
    }
}
