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

    public function index_data(DataTables $datatable, Request $request)
    {
        $query = Product::with(['category', 'storeProducts.store'])
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
     * Show pricing details for a product
     */
    public function show($id)
    {
        $product = Product::with(['category', 'storeProducts.store'])->findOrFail($id);
        
        // Calculate price analysis
        $storePrices = $product->storeProducts->pluck('store_price')->toArray();
        $analysis = [
            'lowest_store_price' => $storePrices ? min($storePrices) : null,
            'highest_store_price' => $storePrices ? max($storePrices) : null,
            'average_store_price' => $storePrices ? array_sum($storePrices) / count($storePrices) : null,
            'store_count' => count($storePrices),
            'final_price' => $product->getFinalPrice()
        ];
        
        return response()->json([
            'status' => true,
            'data' => [
                'product' => $product,
                'analysis' => $analysis
            ]
        ]);
    }

    /**
     * Update dynamic pricing for a product
     */
    public function updatePricing(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'admin_price_active' => 'boolean',
            'admin_override_price' => 'nullable|numeric|min:0',
            'price_override_type' => 'required_if:admin_price_active,true|in:lowest,highest,fixed'
        ]);

        $product = Product::findOrFail($request->product_id);
        
        // Only admin products can have dynamic pricing
        if ($product->created_by_type !== 'admin') {
            return comman_custom_response([
                'message' => 'Dynamic pricing is only available for admin products',
                'status' => false
            ]);
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

        return comman_custom_response([
            'message' => 'Dynamic pricing updated successfully',
            'status' => true
        ]);
    }

    /**
     * Bulk update pricing
     */
    public function bulkUpdatePricing(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'action' => 'required|in:activate,deactivate,set_type',
            'price_override_type' => 'required_if:action,set_type|in:lowest,highest,fixed'
        ]);

        $products = Product::whereIn('id', $request->product_ids)
                          ->where('created_by_type', 'admin')
                          ->get();

        $updateData = [];
        
        switch ($request->action) {
            case 'activate':
                $updateData = [
                    'admin_price_active' => true,
                    'price_override_type' => 'lowest'
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

        foreach ($products as $product) {
            $product->update($updateData);
        }

        return comman_custom_response([
            'message' => 'Bulk pricing update completed successfully',
            'status' => true
        ]);
    }

    /**
     * Get pricing analytics
     */
    public function analytics()
    {
        $stats = [
            'total_products' => Product::where('created_by_type', 'admin')->count(),
            'active_dynamic_pricing' => Product::where('created_by_type', 'admin')
                                              ->where('admin_price_active', true)
                                              ->count(),
            'products_with_store_prices' => Product::where('created_by_type', 'admin')
                                                  ->whereHas('storeProducts')
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

        return response()->json($stats);
    }

    /**
     * Calculate average price difference between admin and store prices
     */
    private function calculateAveragePriceDifference()
    {
        $products = Product::where('created_by_type', 'admin')
                          ->where('admin_price_active', true)
                          ->whereHas('storeProducts')
                          ->with('storeProducts')
                          ->get();

        if ($products->isEmpty()) {
            return 0;
        }

        $totalDifference = 0;
        $count = 0;

        foreach ($products as $product) {
            $adminPrice = $product->effective_price;
            $storePrices = $product->storeProducts->pluck('store_price');
            
            foreach ($storePrices as $storePrice) {
                $difference = abs($adminPrice - $storePrice);
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
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id'
        ]);

        $products = Product::with(['storeProducts.store'])
                          ->whereIn('id', $request->product_ids)
                          ->get();

        $comparison = [];
        
        foreach ($products as $product) {
            $storePrices = $product->storeProducts->map(function($sp) {
                return [
                    'store_name' => $sp->store->name,
                    'store_price' => $sp->store_price,
                    'final_price' => $sp->final_price,
                    'stock' => $sp->stock_quantity
                ];
            });

            $comparison[] = [
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'base_price' => $product->base_price,
                    'admin_override_price' => $product->admin_override_price,
                    'effective_price' => $product->effective_price,
                    'admin_price_active' => $product->admin_price_active,
                    'price_override_type' => $product->price_override_type
                ],
                'store_prices' => $storePrices,
                'price_analysis' => [
                    'lowest_price' => $storePrices->min('store_price'),
                    'highest_price' => $storePrices->max('store_price'),
                    'average_price' => $storePrices->avg('store_price'),
                    'final_customer_price' => $product->getFinalPrice()
                ]
            ];
        }

        return response()->json([
            'status' => true,
            'data' => $comparison
        ]);
    }
}
