<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\Store;
// Removed StoreProduct import - using direct product-provider relationships
use Yajra\DataTables\DataTables;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display provider's products
     */
    public function index(Request $request)
    {
        $auth_user = authSession();
        
        if ($auth_user->user_type !== 'provider') {
            return redirect()->route('home')->withErrors('Unauthorized access');
        }

        $filter = [
            'status' => $request->status,
            'category_id' => $request->category_id,
        ];
        $pageTitle = trans('messages.my_products');
        $assets = ['datatable'];
        $categories = ProductCategory::active()->get();
        
        return view('provider.product.index', compact('pageTitle', 'auth_user', 'assets', 'filter', 'categories'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        $auth_user = authSession();
        
        $query = Product::with(['category'])
                       ->where('created_by', $auth_user->id)
                       ->where('created_by_type', 'provider');
        
        $filter = $request->filter;
        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
            if (isset($filter['category_id']) && $filter['category_id'] != '') {
                $query->where('product_category_id', $filter['category_id']);
            }
        }

        return $datatable->eloquent($query)
            ->editColumn('name', function($query) {
                return '<a class="btn-link btn-link-hover" href='.route('provider.product.show', $query->id).'>'.$query->name.'</a>';
            })
            ->editColumn('category', function($query) {
                return $query->category ? $query->category->name : '-';
            })
            ->editColumn('base_price', function($query) {
                return getPriceFormat($query->base_price);
            })
            ->editColumn('effective_price', function($query) {
                return getPriceFormat($query->effective_price);
            })
            ->editColumn('stock_quantity', function($query) {
                $stockClass = $query->is_low_stock ? 'text-warning' : ($query->is_in_stock ? 'text-success' : 'text-danger');
                return '<span class="'.$stockClass.'">'.$query->stock_quantity.'</span>';
            })
            ->editColumn('status' , function ($query){
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input  change_status" data-type="provider_product_status" '.($query->status ? "checked" : "").' value="'.$query->id.'" id="'.$query->id.'" data-id="'.$query->id.'">
                        <label class="custom-control-label" for="'.$query->id.'" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->addColumn('action', function($product){
                return view('provider.product.action',compact('product'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['action','status','name','stock_quantity'])
            ->toJson();
    }

    /**
     * Show the form for creating a new product
     */
    public function create()
    {
        $auth_user = authSession();
        
        if ($auth_user->user_type !== 'provider') {
            return redirect()->route('home')->withErrors('Unauthorized access');
        }

        $pageTitle = trans('messages.add_product');
        $categories = ProductCategory::active()->get();
        
        return view('provider.product.create', compact('pageTitle', 'categories', 'auth_user'));
    }

    /**
     * Store a newly created product
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
            'short_description' => 'nullable|string',
            'product_category_id' => 'required|exists:product_categories,id',
            'base_price' => 'required|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        $data = $request->all();
        $data['slug'] = Str::slug($data['name']);
        $data['sku'] = 'PRV-' . strtoupper(Str::random(8));
        $data['created_by'] = $auth_user->id;
        $data['created_by_type'] = 'provider';
        
        // Handle dimensions as JSON
        if (isset($data['dimensions'])) {
            $data['dimensions'] = json_encode($data['dimensions']);
        }

        // Set provider_id for provider products
        $data['provider_id'] = $auth_user->id;

        // Provider products need approval before being available
        $data['approval_status'] = 'pending';
        $data['is_available'] = false; // Not available until approved
        $data['status'] = false; // Inactive until approved

        $product = Product::create($data);

        // In single-store architecture, products need admin approval before being available

        return redirect()->route('provider.product.index')->withSuccess('Product created successfully');
    }

    /**
     * Display the specified product
     */
    public function show($id)
    {
        $auth_user = authSession();
        
        if ($auth_user->user_type !== 'provider') {
            return redirect()->route('home')->withErrors('Unauthorized access');
        }

        $product = Product::with(['category', 'variants'])
                         ->where('created_by', $auth_user->id)
                         ->where('created_by_type', 'provider')
                         ->findOrFail($id);
        
        $pageTitle = trans('messages.product_details');
        
        return view('provider.product.view', compact('pageTitle', 'product', 'auth_user'));
    }

    /**
     * Show the form for editing the specified product
     */
    public function edit($id)
    {
        $auth_user = authSession();
        
        if ($auth_user->user_type !== 'provider') {
            return redirect()->route('home')->withErrors('Unauthorized access');
        }

        $product = Product::with(['category', 'variants'])
                         ->where('created_by', $auth_user->id)
                         ->where('created_by_type', 'provider')
                         ->findOrFail($id);
        
        $pageTitle = trans('messages.edit_product');
        $categories = ProductCategory::active()->get();
        
        return view('provider.product.edit', compact('pageTitle', 'product', 'categories', 'auth_user'));
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, $id)
    {
        $auth_user = authSession();
        
        if ($auth_user->user_type !== 'provider') {
            return redirect()->route('home')->withErrors('Unauthorized access');
        }

        $product = Product::where('created_by', $auth_user->id)
                         ->where('created_by_type', 'provider')
                         ->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string',
            'product_category_id' => 'required|exists:product_categories,id',
            'base_price' => 'required|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        $data = $request->all();
        $data['slug'] = Str::slug($data['name']);
        
        // Handle dimensions as JSON
        if (isset($data['dimensions'])) {
            $data['dimensions'] = json_encode($data['dimensions']);
        }

        $product->update($data);

        // In single-store architecture, product updates are automatically reflected

        return redirect()->route('provider.product.index')->withSuccess('Product updated successfully');
    }

    /**
     * Remove the specified product
     */
    public function destroy($id)
    {
        $auth_user = authSession();
        
        if ($auth_user->user_type !== 'provider') {
            return redirect()->route('home')->withErrors('Unauthorized access');
        }

        $product = Product::where('created_by', $auth_user->id)
                         ->where('created_by_type', 'provider')
                         ->findOrFail($id);

        $product->delete();

        return comman_custom_response(['message'=> 'Product deleted successfully' , 'status' => true]);
    }

    // Removed availableProducts method - not needed in single-store architecture
}
