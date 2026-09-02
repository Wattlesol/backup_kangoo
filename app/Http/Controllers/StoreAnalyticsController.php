<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Category;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StoreAnalyticsController extends Controller
{
    /**
     * Get comprehensive store analytics
     */
    public function getStoreAnalytics(Request $request)
    {
        try {
            $period = $request->get('period', '30days');
            $metrics = $request->get('metrics', 'sales,orders,revenue,products,customers');
            $metricsArray = explode(',', $metrics);

            // Calculate date range based on period
            $dateRange = $this->getDateRange($period);
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];

            $analytics = [];

            // Sales Analytics
            if (in_array('sales', $metricsArray)) {
                $analytics['sales'] = $this->getSalesAnalytics($startDate, $endDate);
            }

            // Orders Analytics
            if (in_array('orders', $metricsArray)) {
                $analytics['orders'] = $this->getOrdersAnalytics($startDate, $endDate);
            }

            // Revenue Analytics
            if (in_array('revenue', $metricsArray)) {
                $analytics['revenue'] = $this->getRevenueAnalytics($startDate, $endDate);
            }

            // Products Analytics
            if (in_array('products', $metricsArray)) {
                $analytics['products'] = $this->getProductsAnalytics($startDate, $endDate);
            }

            // Customers Analytics
            if (in_array('customers', $metricsArray)) {
                $analytics['customers'] = $this->getCustomersAnalytics($startDate, $endDate);
            }

            // Providers Analytics
            if (in_array('providers', $metricsArray)) {
                $analytics['providers'] = $this->getProvidersAnalytics($startDate, $endDate);
            }

            // Categories Analytics
            if (in_array('categories', $metricsArray)) {
                $analytics['categories'] = $this->getCategoriesAnalytics($startDate, $endDate);
            }

            // Add period info
            $analytics['period'] = [
                'period' => $period,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'days' => $startDate->diffInDays($endDate) + 1
            ];

            return comman_custom_response([
                'message' => 'Store analytics retrieved successfully',
                'data' => $analytics,
                'status' => true
            ]);

        } catch (\Exception $e) {
            Log::error('Store analytics error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);
            
            return comman_message_response('Failed to retrieve store analytics: ' . $e->getMessage());
        }
    }

    /**
     * Get date range based on period
     */
    private function getDateRange($period)
    {
        $endDate = now();
        
        $startDate = match($period) {
            '7days' => now()->subDays(6), // Include today
            '30days' => now()->subDays(29),
            '90days' => now()->subDays(89),
            '6months' => now()->subMonths(6),
            '1year' => now()->subYear(),
            'today' => now()->startOfDay(),
            'yesterday' => now()->subDay()->startOfDay(),
            'this_week' => now()->startOfWeek(),
            'last_week' => now()->subWeek()->startOfWeek(),
            'this_month' => now()->startOfMonth(),
            'last_month' => now()->subMonth()->startOfMonth(),
            default => now()->subDays(29)
        };

        if ($period === 'yesterday') {
            $endDate = now()->subDay()->endOfDay();
        } elseif ($period === 'last_week') {
            $endDate = now()->subWeek()->endOfWeek();
        } elseif ($period === 'last_month') {
            $endDate = now()->subMonth()->endOfMonth();
        }

        return ['start' => $startDate, 'end' => $endDate];
    }

    /**
     * Get sales analytics
     */
    private function getSalesAnalytics($startDate, $endDate)
    {
        $totalSales = OrderItem::whereHas('order', function($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate])
              ->where('payment_status', 'paid');
        })->sum('quantity');

        $previousPeriod = $startDate->diffInDays($endDate);
        $previousStart = $startDate->copy()->subDays($previousPeriod);
        $previousEnd = $startDate->copy()->subDay();

        $previousSales = OrderItem::whereHas('order', function($q) use ($previousStart, $previousEnd) {
            $q->whereBetween('created_at', [$previousStart, $previousEnd])
              ->where('payment_status', 'paid');
        })->sum('quantity');

        $growthRate = $previousSales > 0 ? (($totalSales - $previousSales) / $previousSales) * 100 : 0;

        return [
            'total_sales' => $totalSales,
            'previous_period_sales' => $previousSales,
            'growth_rate' => round($growthRate, 2),
            'average_daily_sales' => round($totalSales / ($startDate->diffInDays($endDate) + 1), 2)
        ];
    }

    /**
     * Get orders analytics
     */
    private function getOrdersAnalytics($startDate, $endDate)
    {
        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();
        $paidOrders = Order::whereBetween('created_at', [$startDate, $endDate])
                          ->where('payment_status', 'paid')->count();
        $pendingOrders = Order::whereBetween('created_at', [$startDate, $endDate])
                             ->where('payment_status', 'pending')->count();
        $cancelledOrders = Order::whereBetween('created_at', [$startDate, $endDate])
                                ->where('payment_status', 'cancelled')->count();

        $averageOrderValue = $paidOrders > 0 ? 
            Order::whereBetween('created_at', [$startDate, $endDate])
                 ->where('payment_status', 'paid')
                 ->avg('total_amount') : 0;

        return [
            'total_orders' => $totalOrders,
            'paid_orders' => $paidOrders,
            'pending_orders' => $pendingOrders,
            'cancelled_orders' => $cancelledOrders,
            'conversion_rate' => $totalOrders > 0 ? round(($paidOrders / $totalOrders) * 100, 2) : 0,
            'average_order_value' => round($averageOrderValue, 2)
        ];
    }

    /**
     * Get revenue analytics
     */
    private function getRevenueAnalytics($startDate, $endDate)
    {
        $totalRevenue = Order::whereBetween('created_at', [$startDate, $endDate])
                            ->where('payment_status', 'paid')
                            ->sum('total_amount');

        $previousPeriod = $startDate->diffInDays($endDate);
        $previousStart = $startDate->copy()->subDays($previousPeriod);
        $previousEnd = $startDate->copy()->subDay();

        $previousRevenue = Order::whereBetween('created_at', [$previousStart, $previousEnd])
                               ->where('payment_status', 'paid')
                               ->sum('total_amount');

        $growthRate = $previousRevenue > 0 ? (($totalRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;

        // Get daily revenue for chart data
        $dailyRevenue = Order::selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue')
                            ->whereBetween('created_at', [$startDate, $endDate])
                            ->where('payment_status', 'paid')
                            ->groupBy('date')
                            ->orderBy('date')
                            ->get();

        return [
            'total_revenue' => round($totalRevenue, 2),
            'previous_period_revenue' => round($previousRevenue, 2),
            'growth_rate' => round($growthRate, 2),
            'average_daily_revenue' => round($totalRevenue / ($startDate->diffInDays($endDate) + 1), 2),
            'daily_revenue' => $dailyRevenue
        ];
    }

    /**
     * Get products analytics
     */
    private function getProductsAnalytics($startDate, $endDate)
    {
        $totalProducts = Product::count();
        $activeProducts = Product::where('status', true)->where('is_available', true)->count();
        $newProducts = Product::whereBetween('created_at', [$startDate, $endDate])->count();
        $lowStockProducts = Product::whereRaw('stock_quantity <= COALESCE(low_stock_threshold, 5)')->count();
        $outOfStockProducts = Product::where('stock_quantity', 0)->count();

        // Top selling products
        $topSellingProducts = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
                                      ->whereHas('order', function($q) use ($startDate, $endDate) {
                                          $q->whereBetween('created_at', [$startDate, $endDate])
                                            ->where('payment_status', 'paid');
                                      })
                                      ->with('product:id,name,base_price')
                                      ->groupBy('product_id')
                                      ->orderBy('total_sold', 'desc')
                                      ->limit(5)
                                      ->get();

        return [
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'new_products' => $newProducts,
            'low_stock_products' => $lowStockProducts,
            'out_of_stock_products' => $outOfStockProducts,
            'top_selling_products' => $topSellingProducts
        ];
    }

    /**
     * Get customers analytics
     */
    private function getCustomersAnalytics($startDate, $endDate)
    {
        $totalCustomers = User::where('user_type', 'customer')->count();
        $newCustomers = User::where('user_type', 'customer')
                           ->whereBetween('created_at', [$startDate, $endDate])
                           ->count();
        
        $activeCustomers = User::where('user_type', 'customer')
                              ->whereHas('orders', function($q) use ($startDate, $endDate) {
                                  $q->whereBetween('created_at', [$startDate, $endDate]);
                              })
                              ->count();

        return [
            'total_customers' => $totalCustomers,
            'new_customers' => $newCustomers,
            'active_customers' => $activeCustomers,
            'customer_retention_rate' => $totalCustomers > 0 ? round(($activeCustomers / $totalCustomers) * 100, 2) : 0
        ];
    }

    /**
     * Get providers analytics
     */
    private function getProvidersAnalytics($startDate, $endDate)
    {
        $totalProviders = User::where('user_type', 'provider')->count();
        $activeProviders = User::where('user_type', 'provider')
                              ->where('status', 1)
                              ->count();
        
        $newProviders = User::where('user_type', 'provider')
                           ->whereBetween('created_at', [$startDate, $endDate])
                           ->count();

        return [
            'total_providers' => $totalProviders,
            'active_providers' => $activeProviders,
            'new_providers' => $newProviders
        ];
    }

    /**
     * Get categories analytics
     */
    private function getCategoriesAnalytics($startDate, $endDate)
    {
        $totalCategories = Category::count();
        $activeCategories = Category::where('status', 1)->count();

        // Top performing categories
        $topCategories = OrderItem::select('products.product_category_id', DB::raw('SUM(order_items.total_price) as revenue'))
                                 ->join('products', 'order_items.product_id', '=', 'products.id')
                                 ->whereHas('order', function($q) use ($startDate, $endDate) {
                                     $q->whereBetween('created_at', [$startDate, $endDate])
                                       ->where('payment_status', 'paid');
                                 })
                                 ->with('product.category:id,name')
                                 ->groupBy('products.product_category_id')
                                 ->orderBy('revenue', 'desc')
                                 ->limit(5)
                                 ->get();

        return [
            'total_categories' => $totalCategories,
            'active_categories' => $activeCategories,
            'top_performing_categories' => $topCategories
        ];
    }
}
