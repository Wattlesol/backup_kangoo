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
        $activeProducts = Product::where('status', true)->where('is_available', true)->count();
        $pendingProducts = Product::where('approval_status', 'pending')->count();
        $totalOrders = \App\Models\Order::count();

        return view('store.index', compact('pageTitle', 'auth_user', 'store', 'totalProducts', 'activeProducts', 'pendingProducts', 'totalOrders'));
    }

    // Removed index_data method - not needed for single store architecture

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
        $data = $request->all();

        // Validate required fields
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'address' => 'required|string',
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
            // Create new store (should only happen once)
            unset($data['id']);
            $result = Store::create($data);
            $message = 'Main store created successfully';
        }

        if($request->is('api/*')) {
            return comman_message_response($message);
		}

        return redirect(route('store.index'))->withSuccess($message);
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

    // Removed approval methods - not needed for single store architecture
}
