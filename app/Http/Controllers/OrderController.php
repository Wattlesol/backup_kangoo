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
    public function updatePaymentStatus(Request $request)
    {
        $data = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_status' => 'required|in:pending,paid,failed,refunded',
            'payment_transaction_id' => 'nullable|string'
        ]);

        $order = Order::findOrFail($data['order_id']);
        $order->update([
            'payment_status' => $data['payment_status'],
            'payment_transaction_id' => $data['payment_transaction_id'] ?? $order->payment_transaction_id
        ]);

        $message = trans('messages.payment_status_updated_successfully');
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
}
