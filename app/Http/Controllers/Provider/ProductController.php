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
        $auth_user = auth()->user();

        // Debug logging
        \Log::info('Provider Product Controller - Index called', [
            'user_id' => $auth_user ? $auth_user->id : 'null',
            'user_type' => $auth_user ? $auth_user->user_type : 'null',
            'email' => $auth_user ? $auth_user->email : 'null'
        ]);

        if ($auth_user->user_type !== 'provider') {
            \Log::error('Provider Product Controller - Unauthorized access', [
                'user_type' => $auth_user->user_type,
                'expected' => 'provider'
            ]);
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
        $auth_user = auth()->user();
        
        $query = Product::with(['category'])
                       ->where('created_by', $auth_user->id)
                       ->where('created_by_type', 'provider');
        
        $filter = $request->filter;
        if (isset($filter)) {
            if (isset($filter['status']) && $filter['status'] !== '') {
                $query->where('status', $filter['status']);
            }
            if (isset($filter['category_id']) && $filter['category_id'] !== '') {
                $query->where('product_category_id', $filter['category_id']);
            }
            if (isset($filter['approval_status']) && $filter['approval_status'] !== '') {
                $query->where('approval_status', $filter['approval_status']);
            }
        }

        return $datatable->eloquent($query)
            ->editColumn('name', function($query) {
                return '<a class="btn-link btn-link-hover" href='.route('provider.product.show', $query->id).'>'.$query->name.'</a>';
            })
            ->editColumn('category', function($query) {
                return $query->category ? $query->category->name : '-';
            })
            ->editColumn('sku', function($query) {
                return $query->sku;
            })
            ->editColumn('price', function($query) {
                return getPriceFormat($query->base_price);
            })
            ->editColumn('stock', function($query) {
                $stockClass = $query->stock_quantity <= 0 ? 'text-danger' : ($query->stock_quantity <= ($query->low_stock_threshold ?? 5) ? 'text-warning' : 'text-success');
                return '<span class="'.$stockClass.'">'.$query->stock_quantity.'</span>';
            })
            ->editColumn('approval_status', function($query) {
                $status = $query->approval_status ?? 'pending';
                $badgeClass = $status == 'approved' ? 'success' : ($status == 'rejected' ? 'danger' : 'warning');
                return '<span class="badge badge-'.$badgeClass.'">'.ucfirst($status).'</span>';
            })
            ->editColumn('created_at', function($query) {
                return $query->created_at->format('M d, Y');
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
            ->rawColumns(['action','status','name','stock','approval_status'])
            ->toJson();
    }

    /**
     * Show the form for creating a new product
     */
    public function create()
    {
        $auth_user = auth()->user();

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
        $auth_user = auth()->user();

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
        $auth_user = auth()->user();

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
        $auth_user = auth()->user();

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
        $auth_user = auth()->user();

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
        $auth_user = auth()->user();

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
