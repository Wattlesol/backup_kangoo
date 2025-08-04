<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\User;
use App\Models\Product;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class StoreController extends Controller
{
    /**
     * Display the main store configuration.
     * In single-store architecture, there's only one store to manage.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $pageTitle = 'Store Management';
        $auth_user = authSession();

        // Get the main store (there should only be one)
        $store = Store::where('store_type', 'main')->first();

        if (!$store) {
            // If no store exists, redirect to create
            return redirect()->route('store.create');
        }

        // Get store statistics
        $totalProducts = Product::count();
        $activeProducts = Product::where('status', true)->count();
        $pendingProducts = Product::where('approval_status', 'pending')->count();
        $totalOrders = \App\Models\Order::count();

        return view('store.index', compact('pageTitle', 'auth_user', 'store', 'totalProducts', 'activeProducts', 'pendingProducts', 'totalOrders'));
    }

    /**
     * Display a listing of stores in DataTables format
     *
     * @return \Illuminate\Http\Response
     */
    public function list()
    {
        $pageTitle = __('messages.list_form_title', ['form' => __('messages.store')]);
        $auth_user = authSession();

        return view('store.list', compact('pageTitle', 'auth_user'));
    }

    /**
     * Get stores data for DataTables
     * Returns data for the stores listing table
     *
     * @param DataTables $datatable
     * @param Request $request
     * @return mixed
     */
    public function index_data(DataTables $datatable, Request $request)
    {
        $query = Store::query()->with(['createdBy', 'country', 'state', 'city']);

        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
            if (isset($filter['store_type'])) {
                $query->where('store_type', $filter['store_type']);
            }
        }

        if (auth()->user()->hasAnyRole(['admin'])) {
            $query->withTrashed();
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row" id="datatable-row-'.$row->id.'" name="datatable_ids[]" value="'.$row->id.'" onclick="dataTableRowCheck('.$row->id.')">';
            })
            ->addColumn('action', function ($row) {
                return view('store.action', compact('row'))->render();
            })
            ->addColumn('products_count', function ($row) {
                // In single-store architecture, all products belong to the main store
                if ($row->store_type === 'main') {
                    return Product::count();
                }
                return 0;
            })
            ->addColumn('store_type', function ($row) {
                $badgeClass = $row->store_type == 'main' ? 'badge-primary' : 'badge-secondary';
                return '<span class="badge ' . $badgeClass . '">' . ucfirst($row->store_type) . '</span>';
            })
            ->editColumn('name', function ($row) {
                return '<div class="d-flex align-items-center">
                    <div class="avatar-40 rounded mr-3">
                        <i class="fa fa-store text-primary"></i>
                    </div>
                    <div>
                        <h6 class="mb-0">' . $row->name . '</h6>
                        <small class="text-muted">' . ($row->description ? Str::limit($row->description, 50) : '-') . '</small>
                    </div>
                </div>';
            })
            ->editColumn('email', function ($row) {
                return $row->email ?? '-';
            })
            ->editColumn('status', function ($row) {
                $disabled = $row->trashed() ? 'disabled' : '';
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input bg-success change_status" data-type="store_status" ' . ($row->status ? "checked" : "") . ' ' . $disabled . ' value="' . $row->id . '" id="' . $row->id . '" data-id="' . $row->id . '">
                        <label class="custom-control-label" for="' . $row->id . '" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->editColumn('created_at', function ($row) {
                return date('Y-m-d H:i:s', strtotime($row->created_at));
            })
            ->editColumn('updated_at', function ($row) {
                return date('Y-m-d H:i:s', strtotime($row->updated_at));
            })
            ->filterColumn('name', function ($query, $keyword) {
                $query->where('name', 'like', '%' . $keyword . '%');
            })
            ->rawColumns(['action', 'status', 'check', 'store_type', 'name'])
            ->toJson();
    }

    /**
     * Show the form for creating/editing the main store.
     * In single-store architecture, we only create/edit one store.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $auth_user = authSession();

        // Check if main store already exists
        $store = Store::where('store_type', 'main')->first();

        if ($store) {
            // If store exists, redirect to edit
            return redirect()->route('store.edit', $store->id);
        }

        $pageTitle = 'Create Store';

        return view('store.create', compact('pageTitle', 'auth_user'));
    }

    /**
     * Store the main store configuration.
     * In single-store architecture, only admins can create/update the store.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $data = $request->all();

        // Validate required fields
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
        ]);

        // Set admin as creator
        $data['created_by'] = auth()->id();
        $data['store_type'] = 'main';
        $data['slug'] = Str::slug($data['name']);
        $data['is_active'] = true;

        // Handle business_hours as JSON
        if (isset($data['business_hours'])) {
            $data['business_hours'] = json_encode($data['business_hours']);
        }

        // Handle store settings as JSON
        if (isset($data['store_settings'])) {
            $data['store_settings'] = json_encode($data['store_settings']);
        }

        // Handle payment methods as JSON
        if (isset($data['payment_methods'])) {
            $data['payment_methods'] = json_encode($data['payment_methods']);
        }

        // Handle shipping methods as JSON
        if (isset($data['shipping_methods'])) {
            $data['shipping_methods'] = json_encode($data['shipping_methods']);
        }

        // Create or update the main store
        if (isset($data['id']) && $data['id']) {
            // Update existing store
            $result = Store::updateOrCreate(['id' => $data['id']], $data);
            $message = 'Store updated successfully';
        } else {
            // Check if main store already exists (prevent multiple main stores)
            $existingMainStore = Store::where('store_type', 'main')->first();
            if ($existingMainStore) {
                if($request->is('api/*')) {
                    return comman_message_response('Main store already exists. Only one main store is allowed in single-store architecture.');
                }
                return redirect()->route('store.edit', $existingMainStore->id)
                    ->withError('Main store already exists. Only one main store is allowed.');
            }

            // Create new store (should only happen once)
            unset($data['id']);
            $result = Store::create($data);
            $message = 'Main store created successfully';
        }

        if($request->is('api/*')) {
            return comman_message_response($message);
		}

            return redirect(route('store.index'))->withSuccess($message);

        } catch (\Exception $e) {
            \Log::error('Store save error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            if($request->is('api/*')) {
                return comman_message_response('Store operation failed: ' . $e->getMessage());
            }
            return redirect()->back()->withError('Store operation failed: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the main store.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $auth_user = authSession();
        $store = Store::where('store_type', 'main')->findOrFail($id);

        $pageTitle = 'Edit Store';

        return view('store.edit', compact('pageTitle', 'store', 'auth_user'));
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
            // Validate required fields
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'address' => 'nullable|string',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'delivery_radius' => 'nullable|numeric|min:0',
                'minimum_order_amount' => 'nullable|numeric|min:0',
                'delivery_fee' => 'nullable|numeric|min:0',
            ]);

            $store = Store::findOrFail($id);
            $data = $request->all();

            // Update slug if name changed
            if (isset($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Handle business_hours as JSON
            if (isset($data['business_hours'])) {
                $data['business_hours'] = json_encode($data['business_hours']);
            }

            // Handle store_settings as JSON
            if (isset($data['store_settings'])) {
                $data['store_settings'] = json_encode($data['store_settings']);
            }

            $store->update($data);

            $message = 'Store updated successfully';

            if($request->is('api/*')) {
                return comman_message_response($message);
            }

            return redirect(route('store.index'))->withSuccess($message);

        } catch (\Exception $e) {
            Log::error('Store update error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            if($request->is('api/*')) {
                return comman_message_response('Store update failed: ' . $e->getMessage());
            }
            return redirect()->back()->withError('Store update failed: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $store = Store::with(['createdBy', 'country', 'state', 'city'])->findOrFail($id);
        $pageTitle = trans('messages.view_form_title',['form'=>trans('messages.store')]);
        $auth_user = authSession();
        return view('store.view', compact('pageTitle','store','auth_user'));
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
        $store = Store::find($id);
        $msg= __('messages.msg_fail_to_delete',['item' => __('messages.store')] );

        if($store != '') {
            $store->delete();
            $msg= __('messages.msg_deleted',['name' => __('messages.store')] );
        }
        if(request()->is('api/*')) {
            return comman_message_response($msg);
        }
        return comman_custom_response(['message'=> $msg , 'status' => true]);
    }

    public function action(Request $request){
        $id = $request->id;
        $store  = Store::withTrashed()->where('id',$id)->first();
        $msg = __('messages.not_found_entry',['name' => __('messages.store')] );
        if($request->type == 'restore') {
            $store->restore();
            $msg = __('messages.msg_restored',['name' => __('messages.store')] );
        }
        if($request->type == 'forcedelete') {
            $store->forceDelete();
            $msg = __('messages.msg_forcedelete',['name' => __('messages.store')] );
        }
        return comman_custom_response(['message'=> $msg , 'status' => true]);
    }

    public function updateStatus(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:stores,id',
                'is_active' => 'sometimes|boolean',
                'status' => 'sometimes|in:pending,approved,rejected,suspended'
            ]);

            $store = Store::findOrFail($request->id);

            $updateData = [];
            if ($request->has('is_active')) {
                $updateData['is_active'] = $request->is_active;
            }
            if ($request->has('status')) {
                $updateData['status'] = $request->status;
            }

            $store->update($updateData);

            $message = 'Store status updated successfully';

            if($request->is('api/*')) {
                return comman_message_response($message);
            }
            return redirect()->back()->withSuccess($message);

        } catch (\Exception $e) {
            \Log::error('Store status update error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            if($request->is('api/*')) {
                return comman_message_response('Store status update failed: ' . $e->getMessage());
            }
            return redirect()->back()->withError('Store status update failed: ' . $e->getMessage());
        }
    }

    public function uploadLogo(Request $request)
    {
        try {
            $request->validate([
                'store_id' => 'required|exists:stores,id',
                'logo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            $store = Store::findOrFail($request->store_id);

            if ($request->hasFile('logo')) {
                // Delete existing logo if any
                $store->clearMediaCollection('logo');

                // Upload new logo
                $store->addMediaFromRequest('logo')
                    ->toMediaCollection('logo');

                $message = 'Store logo uploaded successfully';
            } else {
                $message = 'No logo file provided';
            }

            if($request->is('api/*')) {
                return comman_message_response($message);
            }
            return redirect()->back()->withSuccess($message);

        } catch (\Exception $e) {
            \Log::error('Store logo upload error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'request_data' => $request->all()
            ]);

            if($request->is('api/*')) {
                return comman_message_response('Store logo upload failed: ' . $e->getMessage());
            }
            return redirect()->back()->withError('Store logo upload failed: ' . $e->getMessage());
        }
    }

    // Removed approval methods - not needed for single store architecture
}
