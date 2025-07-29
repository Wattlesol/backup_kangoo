<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\Product;
use App\Models\ProductCategory;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    /**
     * Display provider's product management dashboard
     * In single-store architecture, providers manage their products within the main store
     */
    public function index()
    {
        $auth_user = authSession();

        if ($auth_user->user_type !== 'provider') {
            return redirect()->route('home')->withErrors('Unauthorized access');
        }

        // Get the main store (there should only be one)
        $mainStore = Store::where('store_type', 'main')->first();

        if (!$mainStore) {
            return redirect()->route('home')->withErrors('Main store not configured. Please contact administrator.');
        }

        // Get provider's product statistics
        $stats = [
            'total_products' => Product::where('provider_id', $auth_user->id)->count(),
            'active_products' => Product::where('provider_id', $auth_user->id)->where('is_available', true)->where('status', true)->count(),
            'pending_products' => Product::where('provider_id', $auth_user->id)->where('status', false)->count(),
            'low_stock_products' => Product::where('provider_id', $auth_user->id)->where('stock_quantity', '<=', 10)->count(),
        ];

        $pageTitle = 'My Store - Product Management';

        return view('provider.store.product-dashboard', compact('pageTitle', 'mainStore', 'auth_user', 'stats'));
    }

    /**
     * Redirect to product creation
     * In single-store architecture, providers create products instead of stores
     */
    public function create()
    {
        return redirect()->route('provider.product.create');
    }

    /**
     * Not applicable in single-store architecture
     */
    public function store(Request $request)
    {
        return redirect()->route('provider.store.index')->withErrors('Store creation not available in single-store mode');
    }

    /**
     * Not applicable in single-store architecture
     */
    public function edit()
    {
        return redirect()->route('provider.store.index')->withErrors('Store editing not available in single-store mode');
    }

    /**
     * Not applicable in single-store architecture
     */
    public function update(Request $request)
    {
        return redirect()->route('provider.store.index')->withErrors('Store editing not available in single-store mode');
    }

    /**
     * Display provider's products in the main store
     */
    public function products(Request $request)
    {
        $auth_user = authSession();

        if ($auth_user->user_type !== 'provider') {
            return redirect()->route('home')->withErrors('Unauthorized access');
        }

        $mainStore = Store::where('store_type', 'main')->first();

        if (!$mainStore) {
            return redirect()->route('home')->withErrors('Main store not configured. Please contact administrator.');
        }

        $filter = [
            'is_available' => $request->is_available,
            'status' => $request->status,
        ];
        $pageTitle = 'My Products';
        $assets = ['datatable'];

        return view('provider.store.products', compact('pageTitle', 'mainStore', 'auth_user', 'assets', 'filter'));
    }

    public function products_data(DataTables $datatable, Request $request)
    {
        $auth_user = authSession();

        // Get provider's products directly (no store_products table needed)
        $query = Product::with(['category'])
                       ->where('provider_id', $auth_user->id);

        $filter = $request->filter;
        if (isset($filter)) {
            if (isset($filter['is_available']) && $filter['is_available'] !== '') {
                $query->where('is_available', $filter['is_available']);
            }
            if (isset($filter['status']) && $filter['status'] !== '') {
                $query->where('status', $filter['status']);
            }
        }

        return $datatable->eloquent($query)
            ->editColumn('name', function($query) {
                return $query->name;
            })
            ->editColumn('category', function($query) {
                return $query->category ? $query->category->name : '-';
            })
            ->editColumn('base_price', function($query) {
                return getPriceFormat($query->base_price);
            })
            ->editColumn('selling_price', function($query) {
                return $query->selling_price ? getPriceFormat($query->selling_price) : getPriceFormat($query->base_price);
            })
            ->editColumn('stock_quantity', function($query) {
                $stockClass = $query->stock_quantity <= 10 ? 'text-warning' : ($query->stock_quantity > 0 ? 'text-success' : 'text-danger');
                return '<span class="'.$stockClass.'">'.$query->stock_quantity.'</span>';
            })
            ->editColumn('status', function($query) {
                $statusClass = $query->status ? 'success' : 'warning';
                $statusText = $query->status ? 'Approved' : 'Pending';
                return '<span class="badge badge-'.$statusClass.'">'.$statusText.'</span>';
            })
            ->editColumn('is_available' , function ($query){
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input  change_status" data-type="product_availability" '.($query->is_available ? "checked" : "").' value="'.$query->id.'" id="'.$query->id.'" data-id="'.$query->id.'">
                        <label class="custom-control-label" for="'.$query->id.'" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->addColumn('action', function($product){
                return view('provider.store.product_action',compact('product'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['action','is_available','stock_quantity','status'])
            ->toJson();
    }

    /**
     * Update product availability
     */
    public function updateProductAvailability(Request $request, $id)
    {
        $auth_user = authSession();

        $product = Product::where('provider_id', $auth_user->id)->findOrFail($id);

        $product->update([
            'is_available' => $request->is_available
        ]);

        return comman_custom_response(['message'=> 'Product availability updated successfully' , 'status' => true]);
    }

    /**
     * Update product selling price and stock
     */
    public function updateProductDetails(Request $request, $id)
    {
        $auth_user = authSession();

        $product = Product::where('provider_id', $auth_user->id)->findOrFail($id);

        $request->validate([
            'selling_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'minimum_order_quantity' => 'nullable|integer|min:1',
            'maximum_order_quantity' => 'nullable|integer|min:1',
            'provider_notes' => 'nullable|string'
        ]);

        $product->update($request->all());

        return comman_custom_response(['message'=> 'Product updated successfully' , 'status' => true]);
    }

    /**
     * Remove product (soft delete)
     */
    public function removeProduct($id)
    {
        $auth_user = authSession();

        $product = Product::where('provider_id', $auth_user->id)->findOrFail($id);
        $product->delete();

        return comman_custom_response(['message'=> 'Product removed successfully' , 'status' => true]);
    }
}
