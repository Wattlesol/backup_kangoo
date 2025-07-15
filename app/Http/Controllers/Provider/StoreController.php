<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\Product;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Str;

class StoreController extends Controller
{
    /**
     * Display provider's store
     */
    public function index()
    {
        $auth_user = authSession();
        
        if ($auth_user->user_type !== 'provider') {
            return redirect()->route('home')->withErrors('Unauthorized access');
        }

        $store = Store::where('provider_id', $auth_user->id)->first();
        $pageTitle = trans('messages.my_store');
        
        return view('provider.store.index', compact('pageTitle', 'store', 'auth_user'));
    }

    /**
     * Show the form for creating a new store
     */
    public function create()
    {
        $auth_user = authSession();
        
        if ($auth_user->user_type !== 'provider') {
            return redirect()->route('home')->withErrors('Unauthorized access');
        }

        // Check if provider already has a store
        $existingStore = Store::where('provider_id', $auth_user->id)->first();
        if ($existingStore) {
            return redirect()->route('provider.store.index')->withErrors('You already have a store');
        }

        $pageTitle = trans('messages.create_store');
        $countries = Country::get();
        
        return view('provider.store.create', compact('pageTitle', 'countries', 'auth_user'));
    }

    /**
     * Store a newly created store
     */
    public function store(Request $request)
    {
        $auth_user = authSession();
        
        if ($auth_user->user_type !== 'provider') {
            return redirect()->route('home')->withErrors('Unauthorized access');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'address' => 'required|string',
            'country_id' => 'nullable|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'delivery_radius' => 'nullable|numeric|min:0',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0'
        ]);

        $data = $request->all();
        $data['provider_id'] = $auth_user->id;
        $data['slug'] = Str::slug($data['name'] . '-' . $auth_user->id);
        $data['status'] = 'pending';

        Store::create($data);

        return redirect()->route('provider.store.index')->withSuccess('Store created successfully and is pending approval');
    }

    /**
     * Show the form for editing the store
     */
    public function edit()
    {
        $auth_user = authSession();
        
        if ($auth_user->user_type !== 'provider') {
            return redirect()->route('home')->withErrors('Unauthorized access');
        }

        $store = Store::where('provider_id', $auth_user->id)->firstOrFail();
        $pageTitle = trans('messages.edit_store');
        $countries = Country::get();
        
        return view('provider.store.edit', compact('pageTitle', 'store', 'countries', 'auth_user'));
    }

    /**
     * Update the store
     */
    public function update(Request $request)
    {
        $auth_user = authSession();
        
        if ($auth_user->user_type !== 'provider') {
            return redirect()->route('home')->withErrors('Unauthorized access');
        }

        $store = Store::where('provider_id', $auth_user->id)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'address' => 'required|string',
            'country_id' => 'nullable|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'delivery_radius' => 'nullable|numeric|min:0',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0'
        ]);

        $data = $request->all();
        $data['slug'] = Str::slug($data['name'] . '-' . $auth_user->id);

        $store->update($data);

        return redirect()->route('provider.store.index')->withSuccess('Store updated successfully');
    }

    /**
     * Display store products
     */
    public function products(Request $request)
    {
        $auth_user = authSession();
        
        if ($auth_user->user_type !== 'provider') {
            return redirect()->route('home')->withErrors('Unauthorized access');
        }

        $store = Store::where('provider_id', $auth_user->id)->firstOrFail();
        
        $filter = [
            'is_available' => $request->is_available,
        ];
        $pageTitle = trans('messages.store_products');
        $assets = ['datatable'];
        
        return view('provider.store.products', compact('pageTitle', 'store', 'auth_user', 'assets', 'filter'));
    }

    public function products_data(DataTables $datatable, Request $request)
    {
        $auth_user = authSession();
        $store = Store::where('provider_id', $auth_user->id)->firstOrFail();
        
        $query = StoreProduct::with(['product.category'])
                            ->where('store_id', $store->id);
        
        $filter = $request->filter;
        if (isset($filter)) {
            if (isset($filter['column_is_available'])) {
                $query->where('is_available', $filter['column_is_available']);
            }
        }

        return $datatable->eloquent($query)
            ->editColumn('product_name', function($query) {
                return $query->product ? $query->product->name : '-';
            })
            ->editColumn('category', function($query) {
                return $query->product && $query->product->category ? $query->product->category->name : '-';
            })
            ->editColumn('store_price', function($query) {
                return getPriceFormat($query->store_price);
            })
            ->editColumn('final_price', function($query) {
                return getPriceFormat($query->final_price);
            })
            ->editColumn('stock_quantity', function($query) {
                $stockClass = $query->stock_quantity <= 10 ? 'text-warning' : ($query->stock_quantity > 0 ? 'text-success' : 'text-danger');
                return '<span class="'.$stockClass.'">'.$query->stock_quantity.'</span>';
            })
            ->editColumn('is_available' , function ($query){
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input  change_status" data-type="store_product_status" '.($query->is_available ? "checked" : "").' value="'.$query->id.'" id="'.$query->id.'" data-id="'.$query->id.'">
                        <label class="custom-control-label" for="'.$query->id.'" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->addColumn('action', function($storeProduct){
                return view('provider.store.product_action',compact('storeProduct'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['action','is_available','stock_quantity'])
            ->toJson();
    }

    /**
     * Add product to store
     */
    public function addProduct(Request $request)
    {
        $auth_user = authSession();
        $store = Store::where('provider_id', $auth_user->id)->firstOrFail();
        
        if ($store->status !== 'approved') {
            return comman_custom_response(['message'=> 'Store must be approved to add products' , 'status' => false]);
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'store_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'minimum_order_quantity' => 'nullable|integer|min:1',
            'maximum_order_quantity' => 'nullable|integer|min:1',
            'store_notes' => 'nullable|string'
        ]);

        // Check if product already exists in store
        $existingProduct = StoreProduct::where('store_id', $store->id)
                                     ->where('product_id', $request->product_id)
                                     ->first();

        if ($existingProduct) {
            return comman_custom_response(['message'=> 'Product already exists in your store' , 'status' => false]);
        }

        $data = $request->all();
        $data['store_id'] = $store->id;

        StoreProduct::create($data);

        return comman_custom_response(['message'=> 'Product added to store successfully' , 'status' => true]);
    }

    /**
     * Update store product
     */
    public function updateProduct(Request $request, $id)
    {
        $auth_user = authSession();
        $store = Store::where('provider_id', $auth_user->id)->firstOrFail();
        
        $storeProduct = StoreProduct::where('store_id', $store->id)->findOrFail($id);

        $request->validate([
            'store_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'minimum_order_quantity' => 'nullable|integer|min:1',
            'maximum_order_quantity' => 'nullable|integer|min:1',
            'store_notes' => 'nullable|string'
        ]);

        $storeProduct->update($request->all());

        return comman_custom_response(['message'=> 'Product updated successfully' , 'status' => true]);
    }

    /**
     * Remove product from store
     */
    public function removeProduct($id)
    {
        $auth_user = authSession();
        $store = Store::where('provider_id', $auth_user->id)->firstOrFail();
        
        $storeProduct = StoreProduct::where('store_id', $store->id)->findOrFail($id);
        $storeProduct->delete();

        return comman_custom_response(['message'=> 'Product removed from store successfully' , 'status' => true]);
    }
}
