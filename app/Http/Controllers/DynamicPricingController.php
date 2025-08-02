<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StoreProduct;
use App\Models\Store;
use Yajra\DataTables\DataTables;

class DynamicPricingController extends Controller
{
    /**
     * Display dynamic pricing dashboard
     */
    public function index(Request $request)
    {
        $filter = [
            'category_id' => $request->category_id,
            'pricing_status' => $request->pricing_status,
        ];
        
        $pageTitle = trans('messages.dynamic_pricing_management');
        $auth_user = authSession();
        $assets = ['datatable'];
        $categories = ProductCategory::active()->get();
        
        return view('dynamic-pricing.index', compact('pageTitle', 'auth_user', 'assets', 'filter', 'categories'));
    }

    public function index_data(DataTables $datatable = null, Request $request)
    {
        // If it's an API request, return API response
        if($request->is('api/*')) {
            return $this->getApiList($request);
        }

        $query = Product::with(['category', 'provider'])
                       ->where('created_by_type', 'admin'); // Only admin products can have dynamic pricing

        $filter = $request->filter;
        if (isset($filter)) {
            if (isset($filter['category_id']) && $filter['category_id'] != '') {
                $query->where('product_category_id', $filter['category_id']);
            }
            if (isset($filter['pricing_status']) && $filter['pricing_status'] != '') {
                if ($filter['pricing_status'] == 'active') {
                    $query->where('admin_price_active', true);
                } else {
                    $query->where('admin_price_active', false);
                }
            }
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$row->id.'"  name="datatable_ids[]" value="'.$row->id.'" onclick="dataTableRowCheck('.$row->id.')">';
            })
            ->editColumn('name', function($query) {
                return '<a class="btn-link btn-link-hover" href="#" onclick="openPricingModal('.$query->id.')">'.$query->name.'</a>';
            })
            ->editColumn('category', function($query) {
                return $query->category ? $query->category->name : '-';
            })
            ->editColumn('base_price', function($query) {
                return getPriceFormat($query->base_price);
            })
            ->editColumn('admin_override_price', function($query) {
                return $query->admin_override_price ? getPriceFormat($query->admin_override_price) : '-';
            })
            ->editColumn('effective_price', function($query) {
                return getPriceFormat($query->effective_price);
            })
            ->editColumn('store_prices', function($query) {
                $storePrices = $query->storeProducts->map(function($sp) {
                    return $sp->store->name . ': ' . getPriceFormat($sp->store_price);
                })->take(3)->implode('<br>');
                
                if ($query->storeProducts->count() > 3) {
                    $storePrices .= '<br><small class="text-muted">+' . ($query->storeProducts->count() - 3) . ' more</small>';
                }
                
                return $storePrices ?: 'No store prices';
            })
            ->editColumn('pricing_status', function($query) {
                $badgeClass = $query->admin_price_active ? 'badge-success' : 'badge-secondary';
                $status = $query->admin_price_active ? 'Active' : 'Inactive';
                return '<span class="badge '.$badgeClass.'">'.$status.'</span>';
            })
            ->editColumn('price_override_type', function($query) {
                if (!$query->admin_price_active) return '-';
                
                $typeColors = [
                    'lowest' => 'info',
                    'highest' => 'warning', 
                    'fixed' => 'primary'
                ];
                $color = $typeColors[$query->price_override_type] ?? 'secondary';
                return '<span class="badge badge-'.$color.'">'.ucfirst($query->price_override_type).'</span>';
            })
            ->addColumn('action', function($product){
                return view('dynamic-pricing.action', compact('product'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'check', 'name', 'store_prices', 'pricing_status', 'price_override_type'])
            ->toJson();
    }

    /**
     * Get API list for dynamic pricing
     */
    public function getApiList(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $categoryId = $request->get('category_id');
            $pricingStatus = $request->get('pricing_status');
            $search = $request->get('search');

            $query = Product::with(['category', 'provider'])
                           ->where('created_by_type', 'admin');

            // Apply filters
            if ($categoryId) {
                $query->where('product_category_id', $categoryId);
            }

            if ($pricingStatus) {
                if ($pricingStatus == 'active') {
                    $query->where('admin_price_active', true);
                } else {
                    $query->where('admin_price_active', false);
                }
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $products = $query->paginate($perPage);

            // Transform data for API response
            $transformedProducts = $products->getCollection()->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category->name ?? 'Uncategorized',
                    'base_price' => $product->base_price,
                    'selling_price' => $product->selling_price,
                    'admin_price_active' => $product->admin_price_active,
                    'admin_override_price' => $product->admin_override_price,
                    'price_override_type' => $product->price_override_type,
                    'final_price' => $product->getFinalPrice(),
                    'provider' => $product->provider ? [
                        'id' => $product->provider->id,
                        'name' => $product->provider->display_name,
                        'email' => $product->provider->email
                    ] : null,
                    'stock_quantity' => $product->stock_quantity,
                    'is_available' => $product->is_available,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at
                ];
            });

            $response = [
                'data' => $transformedProducts,
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem()
            ];

            return comman_custom_response([
                'message' => 'Dynamic pricing list fetched successfully',
                'data' => $response,
                'status' => true
            ]);

        } catch (\Exception $e) {
            \Log::error('Dynamic pricing list error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            return comman_message_response('Failed to fetch dynamic pricing list: ' . $e->getMessage());
        }
    }

    /**
     * Show pricing details for a product
     */
    public function show(Request $request, $id)
    {
        try {
            $product = Product::with(['category', 'provider'])->findOrFail($id);

            // Only admin products can have dynamic pricing
            if ($product->created_by_type !== 'admin') {
                $message = 'Dynamic pricing is only available for admin products';

                if($request->is('api/*')) {
                    return comman_message_response($message);
                }
                return response()->json(['status' => false, 'message' => $message]);
            }

            // Calculate price analysis for single-store architecture
            $analysis = [
                'base_price' => $product->base_price,
                'selling_price' => $product->selling_price,
                'admin_override_price' => $product->admin_override_price,
                'final_price' => $product->getFinalPrice(),
                'price_difference' => $product->admin_override_price ?
                    ($product->admin_override_price - $product->base_price) : 0,
                'stock_quantity' => $product->stock_quantity,
                'is_available' => $product->is_available,
                'track_inventory' => $product->track_inventory
            ];

            $responseData = [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'category' => $product->category->name ?? 'Uncategorized',
                    'base_price' => $product->base_price,
                    'admin_price_active' => $product->admin_price_active,
                    'admin_override_price' => $product->admin_override_price,
                    'price_override_type' => $product->price_override_type,
                    'final_price' => $product->getFinalPrice(),
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at
                ],
                'analysis' => $analysis,
                'provider_info' => $product->provider ? [
                    'id' => $product->provider->id,
                    'name' => $product->provider->display_name,
                    'email' => $product->provider->email,
                    'phone' => $product->provider->contact_number
                ] : null
            ];

            if($request->is('api/*')) {
                return comman_custom_response([
                    'message' => 'Product pricing details fetched successfully',
                    'data' => $responseData,
                    'status' => true
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => $responseData,
                'message' => 'Product pricing details fetched successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Show product pricing error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'product_id' => $id
            ]);

            $message = 'Failed to fetch product pricing details: ' . $e->getMessage();

            if($request->is('api/*')) {
                return comman_message_response($message);
            }
            return response()->json(['status' => false, 'message' => $message]);
        }
    }

    /**
     * Update dynamic pricing for a product
     */
    public function updatePricing(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'admin_price_active' => 'boolean',
                'admin_override_price' => 'nullable|numeric|min:0',
                'price_override_type' => 'required_if:admin_price_active,true|in:lowest,highest,fixed'
            ]);

            $product = Product::findOrFail($request->product_id);

            // Only admin products can have dynamic pricing
            if ($product->created_by_type !== 'admin') {
                $message = 'Dynamic pricing is only available for admin products';

                if($request->is('api/*')) {
                    return comman_message_response($message);
                }
                return comman_custom_response(['message' => $message, 'status' => false]);
            }

            $updateData = [
                'admin_price_active' => $request->admin_price_active ?? false,
                'price_override_type' => $request->price_override_type ?? 'lowest'
            ];

            if ($request->admin_price_active && $request->admin_override_price) {
                $updateData['admin_override_price'] = $request->admin_override_price;
            } else {
                $updateData['admin_override_price'] = null;
            }

            $product->update($updateData);
            $message = 'Dynamic pricing updated successfully';

            if($request->is('api/*')) {
                return comman_custom_response([
                    'message' => $message,
                    'data' => [
                        'product_id' => $product->id,
                        'admin_price_active' => $product->admin_price_active,
                        'admin_override_price' => $product->admin_override_price,
                        'price_override_type' => $product->price_override_type,
                        'final_price' => $product->getFinalPrice()
                    ],
                    'status' => true
                ]);
            }

            return comman_custom_response(['message' => $message, 'status' => true]);

        } catch (\Exception $e) {
            \Log::error('Update pricing error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            $message = 'Failed to update dynamic pricing: ' . $e->getMessage();

            if($request->is('api/*')) {
                return comman_message_response($message);
            }
            return comman_custom_response(['message' => $message, 'status' => false]);
        }
    }

    /**
     * Bulk update pricing
     */
    public function bulkUpdatePricing(Request $request)
    {
        try {
            $request->validate([
                'product_ids' => 'required|array',
                'product_ids.*' => 'exists:products,id',
                'action' => 'required|in:activate,deactivate,set_type',
                'price_override_type' => 'required_if:action,set_type|in:lowest,highest,fixed'
            ]);

            $products = Product::whereIn('id', $request->product_ids)
                              ->where('created_by_type', 'admin')
                              ->get();

            if ($products->count() === 0) {
                $message = 'No admin products found for the given IDs';

                if($request->is('api/*')) {
                    return comman_message_response($message);
                }
                return comman_custom_response(['message' => $message, 'status' => false]);
            }

            $updateData = [];

            switch ($request->action) {
                case 'activate':
                    $updateData = [
                        'admin_price_active' => true,
                        'price_override_type' => $request->price_override_type ?? 'lowest'
                    ];
                    break;
                case 'deactivate':
                    $updateData = [
                        'admin_price_active' => false,
                        'admin_override_price' => null
                    ];
                    break;
                case 'set_type':
                    $updateData = [
                        'price_override_type' => $request->price_override_type
                    ];
                    break;
            }

            $affectedCount = 0;
            foreach ($products as $product) {
                $product->update($updateData);
                $affectedCount++;
            }

            $message = "Bulk pricing update completed successfully for {$affectedCount} products";

            if($request->is('api/*')) {
                return comman_custom_response([
                    'message' => $message,
                    'data' => [
                        'affected_count' => $affectedCount,
                        'action' => $request->action,
                        'update_data' => $updateData
                    ],
                    'status' => true
                ]);
            }

            return comman_custom_response(['message' => $message, 'status' => true]);

        } catch (\Exception $e) {
            \Log::error('Bulk update pricing error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            $message = 'Failed to perform bulk pricing update: ' . $e->getMessage();

            if($request->is('api/*')) {
                return comman_message_response($message);
            }
            return comman_custom_response(['message' => $message, 'status' => false]);
        }
    }

    /**
     * Get pricing analytics
     */
    public function analytics(Request $request)
    {
        try {
            $stats = [
                'total_products' => Product::where('created_by_type', 'admin')->count(),
                'active_dynamic_pricing' => Product::where('created_by_type', 'admin')
                                                  ->where('admin_price_active', true)
                                                  ->count(),
                'products_with_providers' => Product::where('created_by_type', 'admin')
                                                      ->whereNotNull('provider_id')
                                                      ->count(),
                'average_price_difference' => $this->calculateAveragePriceDifference(),
                'pricing_types' => [
                    'lowest' => Product::where('admin_price_active', true)
                                      ->where('price_override_type', 'lowest')
                                      ->count(),
                    'highest' => Product::where('admin_price_active', true)
                                       ->where('price_override_type', 'highest')
                                       ->count(),
                    'fixed' => Product::where('admin_price_active', true)
                                 ->where('price_override_type', 'fixed')
                                 ->count(),
                ]
            ];

            if($request->is('api/*')) {
                return comman_custom_response([
                    'message' => 'Pricing analytics fetched successfully',
                    'data' => $stats,
                    'status' => true
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => $stats,
                'message' => 'Pricing analytics fetched successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Pricing analytics error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            $message = 'Failed to fetch pricing analytics: ' . $e->getMessage();

            if($request->is('api/*')) {
                return comman_message_response($message);
            }
            return response()->json(['status' => false, 'message' => $message]);
        }
    }

    /**
     * Calculate average price difference between admin and store prices
     */
    private function calculateAveragePriceDifference()
    {
        $products = Product::where('created_by_type', 'admin')
                          ->where('admin_price_active', true)
                          ->whereNotNull('admin_override_price')
                          ->get();

        if ($products->isEmpty()) {
            return 0;
        }

        $totalDifference = 0;
        $count = 0;

        foreach ($products as $product) {
            if ($product->admin_override_price && $product->base_price) {
                $difference = abs($product->admin_override_price - $product->base_price);
                $totalDifference += $difference;
                $count++;
            }
        }

        return $count > 0 ? round($totalDifference / $count, 2) : 0;
    }

    /**
     * Export pricing data
     */
    public function export(Request $request)
    {
        // This would typically use Laravel Excel or similar package
        // For now, return a simple response
        
        return comman_custom_response([
            'message' => 'Export feature will be implemented with Laravel Excel package',
            'status' => false
        ]);
    }

    /**
     * Price comparison tool
     */
    public function priceComparison(Request $request)
    {
        try {
            $request->validate([
                'product_ids' => 'nullable|array',
                'product_ids.*' => 'exists:products,id',
                'category_id' => 'nullable|exists:product_categories,id',
                'date_from' => 'nullable|date',
                'date_to' => 'nullable|date|after_or_equal:date_from'
            ]);

            $query = Product::with(['category', 'provider']);

            // Filter by specific products or category
            if ($request->product_ids) {
                $query->whereIn('id', $request->product_ids);
            } elseif ($request->category_id) {
                $query->where('product_category_id', $request->category_id);
            } else {
                // Default to admin products with dynamic pricing
                $query->where('created_by_type', 'admin')
                      ->where('admin_price_active', true);
            }

            // Date filtering (if needed for historical data)
            if ($request->date_from && $request->date_to) {
                $query->whereBetween('updated_at', [$request->date_from, $request->date_to]);
            }

            $products = $query->limit(50)->get(); // Limit for performance

            $comparison = [];

            foreach ($products as $product) {
                $comparison[] = [
                    'product' => [
                        'id' => $product->id,
                        'name' => $product->name,
                        'category' => $product->category->name ?? 'Uncategorized',
                        'base_price' => $product->base_price,
                        'selling_price' => $product->selling_price,
                        'admin_override_price' => $product->admin_override_price,
                        'admin_price_active' => $product->admin_price_active,
                        'price_override_type' => $product->price_override_type,
                        'final_price' => $product->getFinalPrice(),
                        'stock_quantity' => $product->stock_quantity,
                        'is_available' => $product->is_available
                    ],
                    'provider_info' => $product->provider ? [
                        'id' => $product->provider->id,
                        'name' => $product->provider->display_name,
                        'email' => $product->provider->email
                    ] : null,
                    'price_analysis' => [
                        'base_vs_selling' => $product->selling_price - $product->base_price,
                        'admin_override_difference' => $product->admin_override_price ?
                            ($product->admin_override_price - $product->base_price) : 0,
                        'effective_price' => $product->getFinalPrice(),
                        'pricing_strategy' => $product->price_override_type ?? 'none'
                    ]
                ];
            }

            if($request->is('api/*')) {
                return comman_custom_response([
                    'message' => 'Price comparison data fetched successfully',
                    'data' => $comparison,
                    'status' => true
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => $comparison,
                'message' => 'Price comparison data fetched successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Price comparison error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            $message = 'Failed to fetch price comparison data: ' . $e->getMessage();

            if($request->is('api/*')) {
                return comman_message_response($message);
            }
            return response()->json(['status' => false, 'message' => $message]);
        }
    }
}
