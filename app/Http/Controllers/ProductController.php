<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductVariant;
use App\Models\User;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Str;

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

    public function index_data(DataTables $datatable, Request $request)
    {
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
            $query->where('created_by_id', $user->id)
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
        $id = $request->id;
        $auth_user = authSession();

        $productdata = Product::with(['category', 'variants'])->find($id);
        $pageTitle = trans('messages.update_form_title',['form'=>trans('messages.product')]);

        if($productdata == null){
            $pageTitle = trans('messages.add_form_title',['form' => trans('messages.product')]);
            $productdata = new Product;
        }

        $categories = ProductCategory::active()->get();
        $providers = User::where('user_type', 'provider')->get();

        return view('product.create', compact('pageTitle' ,'productdata','auth_user','categories','providers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
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

        // Create or update product
        if (isset($data['id']) && $data['id']) {
            // Update existing product
            $result = Product::updateOrCreate(['id' => $data['id']], $data);
        } else {
            // Create new product
            unset($data['id']); // Remove id if it exists but is null/empty
            $result = Product::create($data);
        }

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

        $message = trans('messages.update_form',['form' => trans('messages.product')]);
        if($result->wasRecentlyCreated){
            $message = trans('messages.save_form',['form' => trans('messages.product')]);
        }

        if($request->is('api/*')) {
            return comman_message_response($message);
		}

        return redirect(route('product.index'))->withSuccess($message);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $product = Product::with(['category', 'creator', 'variants'])->findOrFail($id);
        $pageTitle = trans('messages.view_form_title',['form'=>trans('messages.product')]);
        $auth_user = authSession();
        return view('product.view', compact('pageTitle','product','auth_user'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(demoUserPermission()){
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $product = Product::find($id);
        $msg= __('messages.msg_fail_to_delete',['item' => __('messages.product')] );

        if($product != '') {
            $product->delete();
            $msg= __('messages.msg_deleted',['name' => __('messages.product')] );
        }
        if(request()->is('api/*')) {
            return comman_message_response($msg);
        }
        return comman_custom_response(['message'=> $msg , 'status' => true]);
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

    public function updatePricing(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'admin_override_price' => 'nullable|numeric|min:0',
            'admin_price_active' => 'boolean',
            'price_override_type' => 'in:lowest,highest,fixed'
        ]);

        $product = Product::findOrFail($data['product_id']);
        $product->update($data);

        $message = trans('messages.pricing_updated_successfully');
        return comman_custom_response(['message'=> $message , 'status' => true]);
    }
}
