<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use DataTables;

class ProductApprovalController extends Controller
{
    /**
     * Display pending products for approval
     */
    public function pending(Request $request)
    {
        $pageTitle = 'Pending Product Approvals';

        // Get list of providers who have pending products for filter
        $providers = User::where('user_type', 'provider')
                        ->whereHas('providerProducts', function($query) {
                            $query->where('approval_status', 'pending');
                        })
                        ->select('id', 'display_name', 'email')
                        ->orderBy('display_name')
                        ->get();

        if ($request->ajax()) {
            return $this->getPendingData($request);
        }

        return view('admin.product-approval.pending', compact('pageTitle', 'providers'));
    }

    private function getPendingData(Request $request)
    {
        $query = Product::with(['provider', 'category'])
                       ->where('approval_status', 'pending')
                       ->where('created_by_type', 'provider');

        // Apply filters
        if ($request->has('filter.provider_id') && $request->input('filter.provider_id')) {
            $query->where('provider_id', $request->input('filter.provider_id'));
        }

        return datatables($query)
            ->addColumn('check', function($product) {
                return '<input type="checkbox" class="form-check-input row-checkbox" value="'.$product->id.'">';
            })
            ->addColumn('name', function($product) {
                $imageUrl = null;
                if ($product->featured_image) {
                    // Handle different image storage methods
                    if (filter_var($product->featured_image, FILTER_VALIDATE_URL)) {
                        $imageUrl = $product->featured_image;
                    } else {
                        $imageUrl = asset('storage/' . $product->featured_image);
                    }
                }

                $image = $imageUrl
                    ? '<img src="'.$imageUrl.'" class="avatar-40 rounded me-2" style="object-fit: cover; width: 40px; height: 40px;">'
                    : '<div class="avatar-40 bg-soft-primary rounded me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;"><i class="fa fa-image text-primary"></i></div>';

                return '<div class="d-flex align-items-center">'.$image.'<div class="ms-2"><h6 class="mb-0 font-size-14">'.$product->name.'</h6><small class="text-muted">SKU: '.$product->sku.'</small></div></div>';
            })
            ->addColumn('provider', function($product) {
                return $product->provider ? $product->provider->display_name : 'N/A';
            })
            ->addColumn('category', function($product) {
                return $product->category ? '<span class="badge bg-soft-info text-info">'.$product->category->name.'</span>' : 'No Category';
            })
            ->addColumn('price', function($product) {
                return '$'.number_format($product->selling_price ?? $product->base_price, 2);
            })
            ->addColumn('submitted', function($product) {
                return $product->created_at->diffForHumans();
            })
            ->addColumn('status', function($product) {
                return '<span class="badge bg-soft-warning text-warning">Pending Review</span>';
            })
            ->addColumn('action', function($product) {
                return '<div class="d-flex gap-1">
                    <a href="'.route('product-approval.review', $product->id).'" class="btn btn-sm btn-soft-primary" title="Review"><i class="fa fa-eye"></i></a>
                    <button class="btn btn-sm btn-soft-success approve-product" data-product-id="'.$product->id.'" title="Approve"><i class="fa fa-check"></i></button>
                    <button class="btn btn-sm btn-soft-danger reject-product" data-product-id="'.$product->id.'" title="Reject"><i class="fa fa-times"></i></button>
                </div>';
            })
            ->rawColumns(['check', 'name', 'category', 'status', 'action'])
            ->make(true);
    }



    /**
     * Display rejected products
     */
    public function rejected(Request $request)
    {
        $pageTitle = 'Rejected Products';

        if ($request->ajax()) {
            return $this->getRejectedData($request);
        }

        return view('admin.product-approval.rejected', compact('pageTitle'));
    }

    private function getRejectedData(Request $request)
    {
        $query = Product::with(['provider', 'category', 'rejectedBy'])
                       ->where('approval_status', 'rejected');

        return datatables($query)
            ->addColumn('check', function($product) {
                return '<input type="checkbox" class="form-check-input row-checkbox" value="'.$product->id.'">';
            })
            ->addColumn('name', function($product) {
                $imageUrl = null;
                if ($product->featured_image) {
                    // Handle different image storage methods
                    if (filter_var($product->featured_image, FILTER_VALIDATE_URL)) {
                        $imageUrl = $product->featured_image;
                    } else {
                        $imageUrl = asset('storage/' . $product->featured_image);
                    }
                }

                $image = $imageUrl
                    ? '<img src="'.$imageUrl.'" class="avatar-40 rounded me-2" style="object-fit: cover; width: 40px; height: 40px;">'
                    : '<div class="avatar-40 bg-soft-primary rounded me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;"><i class="fa fa-image text-primary"></i></div>';

                return '<div class="d-flex align-items-center">'.$image.'<div class="ms-2"><h6 class="mb-0 font-size-14">'.$product->name.'</h6><small class="text-muted">SKU: '.$product->sku.'</small></div></div>';
            })
            ->addColumn('provider', function($product) {
                return $product->provider ? $product->provider->display_name : 'N/A';
            })
            ->addColumn('category', function($product) {
                return $product->category ? '<span class="badge bg-soft-info text-info">'.$product->category->name.'</span>' : 'No Category';
            })
            ->addColumn('price', function($product) {
                return '$'.number_format($product->selling_price ?? $product->base_price, 2);
            })
            ->addColumn('rejected_date', function($product) {
                return $product->rejected_at ? $product->rejected_at->diffForHumans() : 'N/A';
            })
            ->addColumn('rejected_by', function($product) {
                return $product->rejectedBy ? $product->rejectedBy->first_name.' '.$product->rejectedBy->last_name : 'N/A';
            })
            ->addColumn('reason', function($product) {
                $reason = $product->rejection_reason ?? 'No reason provided';
                return '<span class="text-truncate" style="max-width: 150px;" title="'.$reason.'">'.$reason.'</span>';
            })
            ->addColumn('action', function($product) {
                return '<div class="d-flex gap-1">
                    <a href="'.route('product-approval.review', $product->id).'" class="btn btn-sm btn-soft-primary" title="View"><i class="fa fa-eye"></i></a>
                    <button class="btn btn-sm btn-soft-success reconsider-product" data-product-id="'.$product->id.'" title="Reconsider"><i class="fa fa-redo"></i></button>
                </div>';
            })
            ->rawColumns(['check', 'name', 'category', 'reason', 'action'])
            ->make(true);
    }

    /**
     * Handle bulk actions for products
     */
    public function bulkAction(Request $request)
    {
        $action = $request->input('action_type');
        $productIds = $request->input('product_ids', []);

        if (empty($productIds)) {
            return response()->json(['status' => false, 'message' => 'No products selected']);
        }

        try {
            DB::beginTransaction();

            switch ($action) {
                case 'bulk-approve':
                    Product::whereIn('id', $productIds)->update([
                        'approval_status' => 'approved',
                        'approved_at' => now(),
                        'approved_by' => Auth::id(),
                        'is_available' => true,
                        'status' => true
                    ]);
                    $message = 'Products approved successfully';
                    break;

                case 'bulk-reject':
                    Product::whereIn('id', $productIds)->update([
                        'approval_status' => 'rejected',
                        'rejected_at' => now(),
                        'rejected_by' => Auth::id(),
                        'rejection_reason' => 'Bulk rejection'
                    ]);
                    $message = 'Products rejected successfully';
                    break;

                case 'reconsider':
                    Product::whereIn('id', $productIds)->update([
                        'approval_status' => 'pending',
                        'rejected_at' => null,
                        'rejected_by' => null,
                        'rejection_reason' => null
                    ]);
                    $message = 'Products moved to pending for reconsideration';
                    break;

                default:
                    return response()->json(['status' => false, 'message' => 'Invalid action']);
            }

            DB::commit();

            if ($request->is('api/*')) {
                return comman_custom_response([
                    'message' => $message,
                    'affected_count' => count($productIds),
                    'action' => $action,
                    'status' => true
                ]);
            }

            return response()->json(['status' => true, 'message' => $message]);

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('Bulk product action error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            $errorMessage = 'An error occurred: ' . $e->getMessage();

            if ($request->is('api/*')) {
                return comman_message_response($errorMessage);
            }

            return response()->json(['status' => false, 'message' => $errorMessage]);
        }
    }

    /**
     * Show detailed product review page
     */
    public function review($id)
    {
        $product = Product::with(['provider', 'category', 'approvedBy', 'rejectedBy'])
                         ->findOrFail($id);

        return view('admin.product-approval.review', compact('product'));
    }

    /**
     * Approve a product
     */
    public function approve(Request $request, $id)
    {
        try {
            $request->validate([
                'admin_notes' => 'nullable|string|max:1000'
            ]);

            DB::beginTransaction();

            $product = Product::findOrFail($id);

            // Check if product is eligible for approval
            if ($product->approval_status === 'approved') {
                $message = 'Product is already approved.';

                if ($request->is('api/*') || $request->ajax()) {
                    return comman_custom_response([
                        'message' => $message,
                        'status' => false
                    ]);
                }
                return redirect()->back()->with('warning', $message);
            }

            // Update product approval status
            $product->update([
                'approval_status' => 'approved',
                'approved_at' => now(),
                'approved_by' => Auth::id(),
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
                'admin_notes' => $request->admin_notes,
                'is_available' => true, // Make available in store
                'status' => true // Activate product
            ]);

            DB::commit();

            // Send notification to provider if it's a provider product
            if ($product->provider) {
                $this->sendApprovalNotification($product, 'approved');
            }

            $message = 'Product approved successfully and is now available in the store.';

            if ($request->is('api/*')) {
                return comman_custom_response([
                    'message' => $message,
                    'data' => [
                        'product_id' => $product->id,
                        'approval_status' => $product->approval_status,
                        'approved_at' => $product->approved_at,
                        'approved_by' => $product->approved_by,
                        'is_available' => $product->is_available,
                        'status' => $product->status
                    ],
                    'status' => true
                ]);
            }

            if ($request->ajax()) {
                return response()->json([
                    'status' => true,
                    'message' => $message
                ]);
            }

            return redirect()->route('admin.product-approval.pending')
                           ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('Product approval error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'product_id' => $id,
                'request_data' => $request->all()
            ]);

            $errorMessage = 'Failed to approve product: ' . $e->getMessage();

            if ($request->is('api/*')) {
                return comman_message_response($errorMessage);
            }

            if ($request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => $errorMessage
                ]);
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Reject a product
     */
    public function reject(Request $request, $id)
    {
        try {
            $request->validate([
                'rejection_reason' => 'required|string|max:1000',
                'admin_notes' => 'nullable|string|max:1000'
            ]);

            DB::beginTransaction();

            $product = Product::findOrFail($id);

            // Check if product is eligible for rejection
            if ($product->approval_status === 'rejected') {
                $message = 'Product is already rejected.';

                if ($request->is('api/*') || $request->ajax()) {
                    return comman_custom_response([
                        'message' => $message,
                        'status' => false
                    ]);
                }
                return redirect()->back()->with('warning', $message);
            }

            // Update product rejection status
            $product->update([
                'approval_status' => 'rejected',
                'rejected_at' => now(),
                'rejected_by' => Auth::id(),
                'rejection_reason' => $request->rejection_reason,
                'admin_notes' => $request->admin_notes,
                'approved_at' => null,
                'approved_by' => null,
                'is_available' => false, // Remove from store
                'status' => false // Deactivate product
            ]);

            DB::commit();

            // Send notification to provider if it's a provider product
            if ($product->provider) {
                $this->sendApprovalNotification($product, 'rejected');
            }

            $message = 'Product rejected successfully.';

            if ($request->is('api/*')) {
                return comman_custom_response([
                    'message' => $message,
                    'data' => [
                        'product_id' => $product->id,
                        'approval_status' => $product->approval_status,
                        'rejected_at' => $product->rejected_at,
                        'rejected_by' => $product->rejected_by,
                        'rejection_reason' => $product->rejection_reason,
                        'is_available' => $product->is_available,
                        'status' => $product->status
                    ],
                    'status' => true
                ]);
            }

            if ($request->ajax()) {
                return response()->json([
                    'status' => true,
                    'message' => $message
                ]);
            }

            return redirect()->route('admin.product-approval.pending')
                           ->with('success', $message);

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('Product rejection error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'product_id' => $id,
                'request_data' => $request->all()
            ]);

            $errorMessage = 'Failed to reject product: ' . $e->getMessage();

            if ($request->is('api/*')) {
                return comman_message_response($errorMessage);
            }

            if ($request->ajax()) {
                return response()->json([
                    'status' => false,
                    'message' => $errorMessage
                ]);
            }

            return redirect()->back()->with('error', $errorMessage);
        }
    }

    /**
     * Revoke approval for a product
     */
    public function revoke(Request $request, $id)
    {
        $request->validate([
            'revoke_reason' => 'required|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $product = Product::findOrFail($id);

            // Update product to pending status
            $product->update([
                'approval_status' => 'pending',
                'approved_at' => null,
                'approved_by' => null,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => 'Approval revoked: ' . $request->revoke_reason,
                'is_available' => false, // Remove from store
                'status' => false // Deactivate product
            ]);

            DB::commit();

            // Send notification to provider if it's a provider product
            if ($product->provider) {
                $this->sendApprovalNotification($product, 'revoked');
            }

            $message = 'Product approval revoked successfully.';

            return response()->json([
                'status' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'status' => false,
                'message' => 'Failed to revoke approval. Please try again.'
            ]);
        }
    }

    /**
     * Reconsider a rejected product
     */
    public function reconsider(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $product = Product::findOrFail($id);

            // Reset product to pending status
            $product->update([
                'approval_status' => 'pending',
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
                'approved_at' => null,
                'approved_by' => null
            ]);

            DB::commit();

            // Send notification to provider if it's a provider product
            if ($product->provider) {
                $this->sendApprovalNotification($product, 'reconsidered');
            }

            return response()->json([
                'status' => true,
                'message' => 'Product moved back to pending for reconsideration.'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            
            return response()->json([
                'status' => false,
                'message' => 'Failed to reconsider product. Please try again.'
            ]);
        }
    }

    /**
     * Get approval statistics for dashboard
     */
    public function getStats()
    {
        $stats = [
            'pending' => Product::where('approval_status', 'pending')->count(),
            'approved' => Product::where('approval_status', 'approved')->count(),
            'rejected' => Product::where('approval_status', 'rejected')->count(),
            'total' => Product::count()
        ];

        return response()->json($stats);
    }

    /**
     * Send approval notification to provider
     */
    private function sendApprovalNotification($product, $status)
    {
        // TODO: Implement email/push notification system
        // For now, we'll just log the notification
        \Log::info("Product approval notification", [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'provider_id' => $product->provider_id,
            'status' => $status
        ]);
    }

    /**
     * Get pending products for API
     */
    public function getPendingProducts(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $categoryId = $request->get('category_id');
            $providerId = $request->get('provider_id');
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $query = Product::with(['category', 'provider', 'approvedBy', 'rejectedBy'])
                           ->where('approval_status', 'pending');

            // Apply filters
            if ($categoryId) {
                $query->where('product_category_id', $categoryId);
            }

            if ($providerId) {
                $query->where('provider_id', $providerId);
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // Apply sorting
            $query->orderBy($sortBy, $sortOrder);

            $products = $query->paginate($perPage);

            // Transform data for API response
            $transformedProducts = $products->getCollection()->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'short_description' => $product->short_description,
                    'sku' => $product->sku,
                    'slug' => $product->slug,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name
                    ] : null,
                    'provider' => $product->provider ? [
                        'id' => $product->provider->id,
                        'name' => $product->provider->display_name,
                        'email' => $product->provider->email
                    ] : null,
                    'base_price' => $product->base_price,
                    'selling_price' => $product->selling_price,
                    'stock_quantity' => $product->stock_quantity,
                    'is_featured' => $product->is_featured,
                    'approval_status' => $product->approval_status,
                    'provider_notes' => $product->provider_notes,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                    'days_pending' => $product->created_at->diffInDays(now())
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
                'message' => 'Pending products fetched successfully',
                'data' => $response,
                'status' => true
            ]);

        } catch (\Exception $e) {
            \Log::error('Get pending products error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            return comman_message_response('Failed to fetch pending products: ' . $e->getMessage());
        }
    }

    /**
     * Get rejected products for API
     */
    public function getRejectedProducts(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $categoryId = $request->get('category_id');
            $providerId = $request->get('provider_id');
            $rejectedBy = $request->get('rejected_by');
            $search = $request->get('search');
            $sortBy = $request->get('sort_by', 'rejected_at');
            $sortOrder = $request->get('sort_order', 'desc');

            $query = Product::with(['category', 'provider', 'approvedBy', 'rejectedBy'])
                           ->where('approval_status', 'rejected');

            // Apply filters
            if ($categoryId) {
                $query->where('product_category_id', $categoryId);
            }

            if ($providerId) {
                $query->where('provider_id', $providerId);
            }

            if ($rejectedBy) {
                $query->where('rejected_by', $rejectedBy);
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('rejection_reason', 'like', "%{$search}%");
                });
            }

            // Apply sorting
            $query->orderBy($sortBy, $sortOrder);

            $products = $query->paginate($perPage);

            // Transform data for API response
            $transformedProducts = $products->getCollection()->map(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'short_description' => $product->short_description,
                    'sku' => $product->sku,
                    'slug' => $product->slug,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name
                    ] : null,
                    'provider' => $product->provider ? [
                        'id' => $product->provider->id,
                        'name' => $product->provider->display_name,
                        'email' => $product->provider->email
                    ] : null,
                    'base_price' => $product->base_price,
                    'selling_price' => $product->selling_price,
                    'stock_quantity' => $product->stock_quantity,
                    'is_featured' => $product->is_featured,
                    'approval_status' => $product->approval_status,
                    'rejection_reason' => $product->rejection_reason,
                    'rejected_at' => $product->rejected_at,
                    'rejected_by' => $product->rejectedBy ? [
                        'id' => $product->rejectedBy->id,
                        'name' => $product->rejectedBy->first_name . ' ' . $product->rejectedBy->last_name,
                        'email' => $product->rejectedBy->email
                    ] : null,
                    'admin_notes' => $product->admin_notes,
                    'provider_notes' => $product->provider_notes,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at,
                    'days_since_rejection' => $product->rejected_at ? $product->rejected_at->diffInDays(now()) : null
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
                'message' => 'Rejected products fetched successfully',
                'data' => $response,
                'status' => true
            ]);

        } catch (\Exception $e) {
            \Log::error('Get rejected products error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            return comman_message_response('Failed to fetch rejected products: ' . $e->getMessage());
        }
    }
}
