<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
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
            'category_id' => $request->category_id,
            'created_by_type' => $request->created_by_type,
            'stock_status' => $request->stock_status,
        ];
        $pageTitle = trans('messages.list_form_title',['form' => trans('messages.product')] );
        $auth_user = authSession();
        $assets = ['datatable'];
        $categories = ProductCategory::active()->get();
        return view('product.index', compact('pageTitle','auth_user','assets','filter','categories'));
    }

    public function index_data(DataTables $datatable = null, Request $request)
    {
        // If it's an API request, return mobile-friendly JSON
        if($request->is('api/*')) {
            return $this->getApiProductList($request);
        }

        $query = Product::with(['category', 'creator']);
        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['status']) && $filter['status'] != '') {
                $query->where('status', $filter['status']);
            }
            if (isset($filter['category_id']) && $filter['category_id'] != '') {
                $query->where('product_category_id', $filter['category_id']);
            }
            if (isset($filter['created_by_type']) && $filter['created_by_type'] != '') {
                $query->where('created_by_type', $filter['created_by_type']);
            }
            if (isset($filter['stock_status']) && $filter['stock_status'] != '') {
                switch($filter['stock_status']) {
                    case 'in_stock':
                        $query->where('stock_quantity', '>', 0);
                        break;
                    case 'low_stock':
                        $query->where('stock_quantity', '>', 0)
                              ->where('stock_quantity', '<=', 10); // Assuming low stock threshold is 10
                        break;
                    case 'out_of_stock':
                        $query->where('stock_quantity', '<=', 0);
                        break;
                }
            }
        }
        // Apply permission-based filtering
        $user = auth()->user();
        if ($user->user_type === 'admin') {
            $query = $query->withTrashed();
        } elseif ($user->user_type === 'provider') {
            // Providers can only see their own products
            $query->where('created_by', $user->id)
                  ->where('created_by_type', 'provider');
        } else {
            // Regular users shouldn't access this endpoint, but if they do, show nothing
            $query->whereRaw('1 = 0');
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$row->id.'"  name="datatable_ids[]" value="'.$row->id.'" onclick="dataTableRowCheck('.$row->id.')">';
            })
            ->editColumn('name', function($query) {
                return '<a class="btn-link btn-link-hover" href='.route('product.create', ['id' => $query->id]).'>'.$query->name.'</a>';
            })
            ->addColumn('category', function($query) {
                return $query->category ? $query->category->name : '-';
            })
            ->addColumn('price', function($query) {
                return getPriceFormat($query->base_price);
            })
            ->editColumn('base_price', function($query) {
                return getPriceFormat($query->base_price);
            })
            ->addColumn('stock', function($query) {
                $stockClass = $query->is_low_stock ? 'text-warning' : ($query->is_in_stock ? 'text-success' : 'text-danger');
                return '<span class="'.$stockClass.'">'.$query->stock_quantity.'</span>';
            })
            ->addColumn('creator', function($query) {
                $badgeClass = $query->created_by_type == 'admin' ? 'badge-primary' : 'badge-info';
                return '<span class="badge '.$badgeClass.'">'.ucfirst($query->created_by_type).'</span>';
            })
            ->editColumn('created_by_type', function($query) {
                $badgeClass = $query->created_by_type == 'admin' ? 'badge-primary' : 'badge-info';
                return '<span class="badge '.$badgeClass.'">'.ucfirst($query->created_by_type).'</span>';
            })
            ->editColumn('status' , function ($query){
                $disabled = $query->trashed() ? 'disabled': '';
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input  change_status" data-type="product_status" '.($query->status ? "checked" : "").' '.$disabled.' value="'.$query->id.'" id="'.$query->id.'" data-id="'.$query->id.'">
                        <label class="custom-control-label" for="'.$query->id.'" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->addColumn('action', function($product){
                return view('product.action',compact('product'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['action','status','check','name','stock','creator','created_by_type'])
            ->filterColumn('name', function($query, $keyword) {
                $query->where('name', 'like', "%{$keyword}%");
            })
            ->filterColumn('category', function($query, $keyword) {
                $query->whereHas('category', function($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('sku', function($query, $keyword) {
                $query->where('sku', 'like', "%{$keyword}%");
            })
            ->filterColumn('price', function($query, $keyword) {
                $query->where('base_price', 'like', "%{$keyword}%");
            })
            ->filterColumn('stock', function($query, $keyword) {
                $query->where('stock_quantity', 'like', "%{$keyword}%");
            })
            ->filterColumn('creator', function($query, $keyword) {
                $query->where('created_by_type', 'like', "%{$keyword}%");
            })
            ->filterColumn('status', function($query, $keyword) {
                $query->where('status', 'like', "%{$keyword}%");
            })
            ->filterColumn('created_at', function($query, $keyword) {
                $query->where('created_at', 'like', "%{$keyword}%");
            })
            ->toJson();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $auth_user = authSession();
        $pageTitle = trans('messages.add_form_title',['form' => trans('messages.product')]);
        $productdata = new Product;

        $categories = ProductCategory::active()->pluck('name', 'id');
        $providers = User::where('user_type', 'provider')->get();

        return view('product.create', compact('pageTitle' ,'productdata','auth_user','categories','providers'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $auth_user = authSession();
        $productdata = Product::with(['category', 'variants'])->findOrFail($id);
        $pageTitle = trans('messages.edit_form_title',['form' => trans('messages.product')]);

        $categories = ProductCategory::active()->pluck('name', 'id');
        $providers = User::where('user_type', 'provider')->get();

        return view('product.edit', compact('pageTitle' ,'productdata','auth_user','categories','providers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate the request
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:255|unique:products,sku',
            'description' => 'nullable|string',
            'product_category_id' => 'required|exists:product_categories,id',
            'base_price' => 'nullable|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'status' => 'required|boolean',
        ]);

        $data = $request->all();
        $data['slug'] = Str::slug($data['name']);
        $data['created_by'] = $data['created_by'] ?? auth()->id();
        $data['created_by_type'] = $data['created_by_type'] ?? 'admin';

        // Handle dimensions as JSON
        if (isset($data['dimensions'])) {
            $data['dimensions'] = json_encode($data['dimensions']);
        }

        // Handle meta_data as JSON
        if (isset($data['meta_data'])) {
            $data['meta_data'] = json_encode($data['meta_data']);
        }

        // Auto-approve admin products
        if ($data['created_by_type'] === 'admin') {
            $data['approval_status'] = 'approved';
            $data['approved_at'] = now();
            $data['approved_by'] = auth()->id();
        }

        // Create new product
        unset($data['id']); // Remove id if it exists but is null/empty
        $result = Product::create($data);

        // Handle variants if provided
        if (isset($data['variants']) && is_array($data['variants'])) {
            // Delete existing variants if updating
            if (!$result->wasRecentlyCreated) {
                $result->variants()->delete();
            }

            foreach ($data['variants'] as $variantData) {
                if (!empty($variantData['name'])) {
                    $variantData['product_id'] = $result->id;
                    $variantData['sku'] = $variantData['sku'] ?? $result->sku . '-' . Str::random(4);
                    $variantData['attributes'] = json_encode($variantData['attributes'] ?? []);
                    ProductVariant::create($variantData);
                }
            }
        }

        $message = trans('messages.save_form',['form' => trans('messages.product')]);

        if($request->is('api/*')) {
            return comman_message_response($message);
        }

        return redirect(route('product.index'))->withSuccess($message);
    }

    /**
     * Get mobile-friendly API product list for providers
     */
    private function getApiProductList(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }

            $perPage = $request->get('per_page', 15);
            $categoryId = $request->get('category_id');
            $status = $request->get('status');
            $search = $request->get('search');

            $query = Product::with(['category', 'creator']);

            // Apply permission-based filtering
            if ($user->user_type === 'admin') {
                // Admin can see all products
                $query = $query->withTrashed();
            } elseif ($user->user_type === 'provider') {
                // Providers can only see their own products
                $query->where('created_by', $user->id)
                      ->where('created_by_type', 'provider');
            } else {
                // Regular users shouldn't access this endpoint
                return response()->json([
                    'status' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            // Apply filters
            if ($categoryId) {
                $query->where('product_category_id', $categoryId);
            }

            if ($status !== null) {
                $query->where('status', $status);
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            $products = $query->orderBy('created_at', 'desc')
                            ->paginate($perPage);

            // Transform to mobile-friendly format
            $mobileProducts = $products->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'short_description' => $product->short_description,
                    'slug' => $product->slug,
                    'sku' => $product->sku,
                    'base_price' => (float) $product->base_price,
                    'selling_price' => (float) $product->selling_price,
                    'stock_quantity' => $product->stock_quantity,
                    'low_stock_threshold' => $product->low_stock_threshold,
                    'is_featured' => (bool) $product->is_featured,
                    'status' => (bool) $product->status,
                    'approval_status' => $product->approval_status,
                    'is_available' => (bool) $product->is_available,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name
                    ] : null,
                    'creator' => $product->creator ? [
                        'id' => $product->creator->id,
                        'name' => $product->creator->display_name,
                        'type' => $product->created_by_type
                    ] : null,
                    'price_formatted' => getPriceFormat($product->base_price),
                    'is_low_stock' => $product->stock_quantity <= ($product->low_stock_threshold ?? 5),
                    'created_at' => $product->created_at->toISOString(),
                    'updated_at' => $product->updated_at->toISOString()
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $mobileProducts,
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem()
                ],
                'message' => 'Products retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('API Product list error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve products'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, Request $request)
    {
        try {
            $user = auth()->user();
            $product = Product::with(['category', 'creator', 'variants'])->findOrFail($id);

            // Check if user has permission to view this product
            if ($user && $user->user_type === 'provider') {
                // Providers can only view their own products
                if ($product->created_by !== $user->id || $product->created_by_type !== 'provider') {
                    if($request->is('api/*')) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Access denied'
                        ], 403);
                    }
                    abort(403, 'Access denied');
                }
            }

            // If it's an API request, return JSON
            if($request->is('api/*')) {
                $productData = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'short_description' => $product->short_description,
                    'slug' => $product->slug,
                    'sku' => $product->sku,
                    'base_price' => (float) $product->base_price,
                    'selling_price' => (float) $product->selling_price,
                    'stock_quantity' => $product->stock_quantity,
                    'low_stock_threshold' => $product->low_stock_threshold,
                    'is_featured' => (bool) $product->is_featured,
                    'status' => (bool) $product->status,
                    'approval_status' => $product->approval_status,
                    'is_available' => (bool) $product->is_available,
                    'weight' => $product->weight,
                    'dimensions' => $product->dimensions,
                    'track_inventory' => (bool) $product->track_inventory,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                        'slug' => $product->category->slug
                    ] : null,
                    'creator' => $product->creator ? [
                        'id' => $product->creator->id,
                        'name' => $product->creator->display_name,
                        'type' => $product->created_by_type
                    ] : null,
                    'variants' => $product->variants->map(function($variant) {
                        return [
                            'id' => $variant->id,
                            'name' => $variant->name,
                            'price' => (float) $variant->price,
                            'stock_quantity' => $variant->stock_quantity
                        ];
                    }),
                    'price_formatted' => getPriceFormat($product->base_price),
                    'is_low_stock' => $product->stock_quantity <= ($product->low_stock_threshold ?? 5),
                    'created_at' => $product->created_at->toISOString(),
                    'updated_at' => $product->updated_at->toISOString()
                ];

                return response()->json([
                    'status' => true,
                    'data' => $productData,
                    'message' => 'Product details retrieved successfully'
                ]);
            }

            // For web requests, return view
            $pageTitle = trans('messages.view_form_title',['form'=>trans('messages.product')]);
            $auth_user = authSession();
            return view('product.view', compact('pageTitle','product','auth_user'));

        } catch (\Exception $e) {
            if($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Product not found'
                ], 404);
            }
            abort(404, 'Product not found');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $product = Product::findOrFail($id);

            // Check if user has permission to update this product
            if ($user && $user->user_type === 'provider') {
                // Providers can only update their own products
                if ($product->created_by !== $user->id || $product->created_by_type !== 'provider') {
                    if($request->is('api/*')) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Access denied'
                        ], 403);
                    }
                    abort(403, 'Access denied');
                }
            }

            // Validate the request
            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'sku' => 'sometimes|required|string|max:255|unique:products,sku,' . $id,
                'description' => 'nullable|string',
                'product_category_id' => 'sometimes|required|exists:product_categories,id',
                'base_price' => 'nullable|numeric|min:0',
                'selling_price' => 'nullable|numeric|min:0',
                'stock_quantity' => 'sometimes|required|integer|min:0',
                'low_stock_threshold' => 'nullable|integer|min:0',
                'status' => 'sometimes|required|boolean',
                'is_featured' => 'nullable|boolean',
            ]);

            $data = $request->all();

            // Only update slug if name is provided
            if (isset($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Handle dimensions as JSON
            if (isset($data['dimensions'])) {
                $data['dimensions'] = json_encode($data['dimensions']);
            }

            // Handle meta_data as JSON
            if (isset($data['meta_data'])) {
                $data['meta_data'] = json_encode($data['meta_data']);
            }

            $product->update($data);

            $message = trans('messages.update_form',['form' => trans('messages.product')]);

            if($request->is('api/*')) {
                return comman_message_response($message);
            }

            return redirect(route('product.index'))->withSuccess($message);

        } catch (\Exception $e) {
            if($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to update product: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->withErrors('Failed to update product: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        try {
            if(demoUserPermission()){
                if($request->is('api/*')) {
                    return response()->json([
                        'status' => false,
                        'message' => trans('messages.demo_permission_denied')
                    ], 403);
                }
                return redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
            }

            $user = auth()->user();
            $product = Product::find($id);

            if (!$product) {
                $msg = __('messages.not_found_entry',['name' => __('messages.product')]);
                if($request->is('api/*')) {
                    return response()->json([
                        'status' => false,
                        'message' => $msg
                    ], 404);
                }
                return comman_custom_response(['message'=> $msg , 'status' => false]);
            }

            // Check if user has permission to delete this product
            if ($user && $user->user_type === 'provider') {
                // Providers can only delete their own products
                if ($product->created_by !== $user->id || $product->created_by_type !== 'provider') {
                    if($request->is('api/*')) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Access denied'
                        ], 403);
                    }
                    abort(403, 'Access denied');
                }
            }

            $product->delete();
            $msg = __('messages.msg_deleted',['name' => __('messages.product')]);

            if($request->is('api/*')) {
                return response()->json([
                    'status' => true,
                    'message' => $msg
                ]);
            }
            return comman_custom_response(['message'=> $msg , 'status' => true]);

        } catch (\Exception $e) {
            if($request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to delete product: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->withErrors('Failed to delete product: ' . $e->getMessage());
        }
    }

    public function action(Request $request){
        $id = $request->id;
        $product  = Product::withTrashed()->where('id',$id)->first();
        $msg = __('messages.not_found_entry',['name' => __('messages.product')] );
        if($request->type == 'restore') {
            $product->restore();
            $msg = __('messages.msg_restored',['name' => __('messages.product')] );
        }
        if($request->type == 'forcedelete') {
            $product->forceDelete();
            $msg = __('messages.msg_forcedelete',['name' => __('messages.product')] );
        }
        return comman_custom_response(['message'=> $msg , 'status' => true]);
    }

    /**
     * Get product analytics
     */
    public function analytics(Request $request)
    {
        try {
            $productId = $request->get('product_id');
            $period = $request->get('period', '30days');

            // Calculate date range based on period
            $startDate = match($period) {
                '7days' => now()->subDays(7),
                '30days' => now()->subDays(30),
                '90days' => now()->subDays(90),
                '1year' => now()->subYear(),
                default => now()->subDays(30)
            };

            if ($productId) {
                // Analytics for specific product
                $product = Product::findOrFail($productId);

                $analytics = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'views' => 0, // TODO: Implement view tracking
                    'orders' => $product->orderItems()->whereHas('order', function($q) use ($startDate) {
                        $q->where('created_at', '>=', $startDate);
                    })->count(),
                    'revenue' => $product->orderItems()->whereHas('order', function($q) use ($startDate) {
                        $q->where('created_at', '>=', $startDate);
                    })->sum('total_price'),
                    'stock_quantity' => $product->stock_quantity,
                    'low_stock_threshold' => $product->low_stock_threshold ?? 5,
                    'is_low_stock' => $product->stock_quantity <= ($product->low_stock_threshold ?? 5),
                    'category' => $product->category->name ?? 'Uncategorized',
                    'status' => $product->status ? 'Active' : 'Inactive',
                    'created_by_type' => $product->created_by_type,
                    'period' => $period
                ];
            } else {
                // Overall analytics
                $analytics = [
                    'total_products' => Product::count(),
                    'active_products' => Product::where('status', true)->count(),
                    'admin_products' => Product::where('created_by_type', 'admin')->count(),
                    'provider_products' => Product::where('created_by_type', 'provider')->count(),
                    'low_stock_products' => Product::whereRaw('stock_quantity <= COALESCE(low_stock_threshold, 5)')->count(),
                    'out_of_stock_products' => Product::where('stock_quantity', 0)->count(),
                    'featured_products' => Product::where('is_featured', true)->count(),
                    'total_revenue' => \DB::table('order_items')
                        ->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->where('orders.created_at', '>=', $startDate)
                        ->sum('order_items.total_price'),
                    'period' => $period
                ];
            }

            if($request->is('api/*')) {
                return comman_custom_response([
                    'message' => 'Analytics fetched successfully',
                    'data' => $analytics,
                    'status' => true
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => $analytics,
                'message' => 'Analytics fetched successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Product analytics error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            if($request->is('api/*')) {
                return comman_message_response('Failed to fetch analytics: ' . $e->getMessage());
            }
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch analytics: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update product stock
     */
    public function updateStock(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'stock_quantity' => 'required|integer|min:0',
                'low_stock_threshold' => 'nullable|integer|min:0',
                'track_inventory' => 'nullable|boolean'
            ]);

            $product = Product::findOrFail($request->product_id);

            $updateData = [
                'stock_quantity' => $request->stock_quantity
            ];

            if ($request->has('low_stock_threshold')) {
                $updateData['low_stock_threshold'] = $request->low_stock_threshold;
            }

            if ($request->has('track_inventory')) {
                $updateData['track_inventory'] = $request->track_inventory;
            }

            $product->update($updateData);

            $message = 'Product stock updated successfully';

            if($request->is('api/*')) {
                return comman_custom_response([
                    'message' => $message,
                    'data' => [
                        'product_id' => $product->id,
                        'stock_quantity' => $product->stock_quantity,
                        'low_stock_threshold' => $product->low_stock_threshold,
                        'track_inventory' => $product->track_inventory,
                        'is_low_stock' => $product->stock_quantity <= ($product->low_stock_threshold ?? 5)
                    ],
                    'status' => true
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => [
                    'product_id' => $product->id,
                    'stock_quantity' => $product->stock_quantity,
                    'low_stock_threshold' => $product->low_stock_threshold,
                    'track_inventory' => $product->track_inventory
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Product stock update error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            if($request->is('api/*')) {
                return comman_message_response('Failed to update stock: ' . $e->getMessage());
            }
            return response()->json([
                'status' => false,
                'message' => 'Failed to update stock: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Upload product images
     */
    public function uploadImages(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'product_images' => 'required|array',
                'product_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                'is_primary' => 'nullable|boolean'
            ]);

            $product = Product::findOrFail($request->product_id);
            $uploadedImages = [];

            if ($request->hasFile('product_images')) {
                // If this is marked as primary, clear existing primary images
                if ($request->is_primary) {
                    $product->clearMediaCollection('primary_image');
                }

                foreach ($request->file('product_images') as $index => $image) {
                    if ($request->is_primary && $index === 0) {
                        // First image as primary
                        $mediaItem = $product->addMedia($image)
                            ->toMediaCollection('primary_image');
                    } else {
                        // Additional images
                        $mediaItem = $product->addMedia($image)
                            ->toMediaCollection('gallery');
                    }

                    $uploadedImages[] = [
                        'id' => $mediaItem->id,
                        'url' => $mediaItem->getUrl(),
                        'name' => $mediaItem->name,
                        'collection' => $mediaItem->collection_name
                    ];
                }

                $message = 'Product images uploaded successfully';
            } else {
                $message = 'No images provided';
            }

            if($request->is('api/*')) {
                return comman_custom_response([
                    'message' => $message,
                    'data' => [
                        'product_id' => $product->id,
                        'uploaded_images' => $uploadedImages
                    ],
                    'status' => true
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => [
                    'product_id' => $product->id,
                    'uploaded_images' => $uploadedImages
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Product image upload error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            if($request->is('api/*')) {
                return comman_message_response('Failed to upload images: ' . $e->getMessage());
            }
            return response()->json([
                'status' => false,
                'message' => 'Failed to upload images: ' . $e->getMessage()
            ]);
        }
    }
}
