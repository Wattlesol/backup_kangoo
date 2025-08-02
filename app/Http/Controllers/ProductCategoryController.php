<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductCategory;
use App\Models\Product;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Str;

class ProductCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // If it's an API request, return API response
        if($request->is('api/*')) {
            return $this->getAllCategories($request);
        }

        $filter = [
            'status' => $request->status,
            'is_featured' => $request->is_featured,
        ];
        $pageTitle = trans('messages.list_form_title',['form' => trans('messages.product_category')] );
        $auth_user = authSession();
        $assets = ['datatable'];
        return view('productcategory.index', compact('pageTitle','auth_user','assets','filter'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        $query = ProductCategory::query();
        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['status']) && $filter['status'] !== '') {
                $query->where('status', $filter['status']);
            }
            if (isset($filter['is_featured']) && $filter['is_featured'] !== '') {
                $query->where('is_featured', $filter['is_featured']);
            }
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$row->id.'"  name="datatable_ids[]" value="'.$row->id.'" onclick="dataTableRowCheck('.$row->id.')">';
            })
            ->editColumn('name', function($query) {
                return '<a class="btn-link btn-link-hover" href='.route('productcategory.create', ['id' => $query->id]).'>'.$query->name.'</a>';
            })
            ->editColumn('status' , function ($query){
                $disabled = $query->trashed() ? 'disabled': '';
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input  change_status" data-type="product_category_status" '.($query->status ? "checked" : "").' '.$disabled.' value="'.$query->id.'" id="'.$query->id.'" data-id="'.$query->id.'">
                        <label class="custom-control-label" for="'.$query->id.'" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->addColumn('action', function($productCategory){
                return view('productcategory.action',compact('productCategory'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['action','status','check','name'])
            ->filterColumn('name', function($query, $keyword) {
                $query->where('name', 'like', "%{$keyword}%");
            })
            ->filterColumn('description', function($query, $keyword) {
                $query->where('description', 'like', "%{$keyword}%");
            })
            ->filterColumn('status', function($query, $keyword) {
                $query->where('status', 'like', "%{$keyword}%");
            })
            ->filterColumn('is_featured', function($query, $keyword) {
                $query->where('is_featured', 'like', "%{$keyword}%");
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
        $id = $request->id;
        $auth_user = authSession();

        $categorydata = ProductCategory::find($id);
        $pageTitle = trans('messages.update_form_title',['form'=>trans('messages.product_category')]);

        if($categorydata == null){
            $pageTitle = trans('messages.add_form_title',['form' => trans('messages.product_category')]);
            $categorydata = new ProductCategory;
        }

        $categories = ProductCategory::where('status', 1)->get();

        return view('productcategory.create', compact('pageTitle', 'auth_user', 'categorydata', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:product_categories,slug',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:product_categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $data = $request->all();

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/categories'), $imageName);
            $data['image'] = 'uploads/categories/' . $imageName;
        }

        $data['status'] = $request->has('status') ? 1 : 0;
        $data['is_featured'] = $request->has('is_featured') ? 1 : 0;

        $result = ProductCategory::create($data);

        $message = trans('messages.save_form',['form' => trans('messages.product_category')]);

        if($request->is('api/*')) {
            return comman_message_response($message);
		}

        return redirect(route('productcategory.index'))->withSuccess($message);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $productcategory = ProductCategory::findOrFail($id);
        $pageTitle = trans('messages.view_form_title',['form'=>trans('messages.product_category')]);
        $auth_user = authSession();
        return view('productcategory.view', compact('pageTitle','productcategory','auth_user'));
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
        $productCategory = ProductCategory::findOrFail($id);
        $pageTitle = trans('messages.update_form_title',['form' => trans('messages.product_category')]);
        $categories = ProductCategory::where('status', 1)->where('id', '!=', $id)->get();

        return view('productcategory.edit', compact('pageTitle', 'auth_user', 'productCategory', 'categories'));
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
        $productCategory = ProductCategory::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:product_categories,slug,' . $id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:product_categories,id',
            'sort_order' => 'nullable|integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $data = $request->all();

        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($productCategory->image && file_exists(public_path($productCategory->image))) {
                unlink(public_path($productCategory->image));
            }

            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/categories'), $imageName);
            $data['image'] = 'uploads/categories/' . $imageName;
        } elseif ($request->has('remove_image') && $request->remove_image) {
            // Remove current image
            if ($productCategory->image && file_exists(public_path($productCategory->image))) {
                unlink(public_path($productCategory->image));
            }
            $data['image'] = null;
        }

        $data['status'] = $request->has('status') ? 1 : 0;
        $data['is_featured'] = $request->has('is_featured') ? 1 : 0;

        $productCategory->update($data);

        $message = trans('messages.update_form',['form' => trans('messages.product_category')]);

        if($request->is('api/*')) {
            return comman_message_response($message);
		}

        return redirect(route('productcategory.index'))->withSuccess($message);
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
                $message = trans('messages.demo_permission_denied');
                if($request->is('api/*')) {
                    return comman_message_response($message);
                }
                return redirect()->back()->withErrors($message);
            }

            $productCategory = ProductCategory::findOrFail($id);

            // Check if category has products
            $productsCount = $productCategory->products()->count();
            if ($productsCount > 0) {
                $message = "Cannot delete category. It has {$productsCount} products associated with it.";

                if($request->is('api/*')) {
                    return comman_message_response($message);
                }
                return comman_custom_response(['message'=> $message , 'status' => false]);
            }

            $productCategory->delete();
            $message = __('messages.msg_deleted',['name' => __('messages.product_category')] );

            if($request->is('api/*')) {
                return comman_custom_response([
                    'message' => $message,
                    'status' => true
                ]);
            }

            return comman_custom_response(['message'=> $message , 'status' => true]);

        } catch (\Exception $e) {
            \Log::error('Delete category error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'category_id' => $id
            ]);

            $message = __('messages.msg_fail_to_delete',['item' => __('messages.product_category')] );

            if($request->is('api/*')) {
                return comman_message_response($message);
            }
            return comman_custom_response(['message'=> $message , 'status' => false]);
        }
    }

    public function action(Request $request){
        $id = $request->id;
        $productcategory  = ProductCategory::withTrashed()->where('id',$id)->first();
        $msg = __('messages.not_found_entry',['name' => __('messages.product_category')] );
        if($request->type == 'restore') {
            $productcategory->restore();
            $msg = __('messages.msg_restored',['name' => __('messages.product_category')] );
        }
        if($request->type == 'forcedelete') {
            $productcategory->forceDelete();
            $msg = __('messages.msg_forcedelete',['name' => __('messages.product_category')] );
        }
        return comman_custom_response(['message'=> $msg , 'status' => true]);
    }

    /**
     * Get all categories for API
     */
    public function getAllCategories(Request $request)
    {
        try {
            $perPage = $request->get('per_page', 15);
            $status = $request->get('status');
            $featured = $request->get('featured');
            $search = $request->get('search');

            $query = ProductCategory::query();

            // Apply filters
            if ($status !== null) {
                $query->where('status', $status);
            }

            if ($featured !== null) {
                $query->where('is_featured', $featured);
            }

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Order by sort_order and name
            $query->orderBy('sort_order')->orderBy('name');

            $categories = $query->paginate($perPage);

            // Transform data for API response
            $transformedCategories = $categories->getCollection()->map(function($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'slug' => $category->slug,
                    'status' => $category->status,
                    'is_featured' => $category->is_featured,
                    'sort_order' => $category->sort_order,
                    'color' => $category->color,
                    'products_count' => $category->products()->count(),
                    'image' => $category->image,
                    'created_at' => $category->created_at,
                    'updated_at' => $category->updated_at
                ];
            });

            $response = [
                'data' => $transformedCategories,
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
                'from' => $categories->firstItem(),
                'to' => $categories->lastItem()
            ];

            if($request->is('api/*')) {
                return comman_custom_response([
                    'message' => 'Categories fetched successfully',
                    'data' => $response,
                    'status' => true
                ]);
            }

            return response()->json([
                'status' => true,
                'data' => $response,
                'message' => 'Categories fetched successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Get categories error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            if($request->is('api/*')) {
                return comman_message_response('Failed to fetch categories: ' . $e->getMessage());
            }
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch categories: ' . $e->getMessage()
            ]);
        }
    }



    /**
     * Bulk action for categories
     */
    public function bulkAction(Request $request)
    {
        try {
            $request->validate([
                'action' => 'required|in:delete,activate,deactivate,feature,unfeature',
                'category_ids' => 'required|array',
                'category_ids.*' => 'exists:product_categories,id'
            ]);

            $action = $request->action;
            $categoryIds = $request->category_ids;
            $affectedCount = 0;

            switch ($action) {
                case 'delete':
                    // Check for categories with products
                    $categoriesWithProducts = ProductCategory::whereIn('id', $categoryIds)
                        ->whereHas('products')
                        ->pluck('name')
                        ->toArray();

                    if (!empty($categoriesWithProducts)) {
                        $message = 'Cannot delete categories with products: ' . implode(', ', $categoriesWithProducts);

                        if($request->is('api/*')) {
                            return comman_message_response($message);
                        }
                        return response()->json(['status' => false, 'message' => $message]);
                    }

                    $affectedCount = ProductCategory::whereIn('id', $categoryIds)->delete();
                    $message = "Successfully deleted {$affectedCount} categories";
                    break;

                case 'activate':
                    $affectedCount = ProductCategory::whereIn('id', $categoryIds)->update(['status' => true]);
                    $message = "Successfully activated {$affectedCount} categories";
                    break;

                case 'deactivate':
                    $affectedCount = ProductCategory::whereIn('id', $categoryIds)->update(['status' => false]);
                    $message = "Successfully deactivated {$affectedCount} categories";
                    break;

                case 'feature':
                    $affectedCount = ProductCategory::whereIn('id', $categoryIds)->update(['is_featured' => true]);
                    $message = "Successfully featured {$affectedCount} categories";
                    break;

                case 'unfeature':
                    $affectedCount = ProductCategory::whereIn('id', $categoryIds)->update(['is_featured' => false]);
                    $message = "Successfully unfeatured {$affectedCount} categories";
                    break;
            }

            if($request->is('api/*')) {
                return comman_custom_response([
                    'message' => $message,
                    'affected_count' => $affectedCount,
                    'status' => true
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => $message,
                'affected_count' => $affectedCount
            ]);

        } catch (\Exception $e) {
            \Log::error('Bulk action error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            $message = 'Failed to perform bulk action: ' . $e->getMessage();

            if($request->is('api/*')) {
                return comman_message_response($message);
            }
            return response()->json([
                'status' => false,
                'message' => $message
            ]);
        }
    }
}
