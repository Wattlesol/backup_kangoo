<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\User;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Str;

class StoreController extends Controller
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
            'provider_id' => $request->provider_id,
            'location' => $request->location,
        ];
        $pageTitle = trans('messages.list_form_title',['form' => trans('messages.store')] );
        $auth_user = authSession();
        $assets = ['datatable'];
        $providers = User::where('user_type', 'provider')->get();
        return view('store.index', compact('pageTitle','auth_user','assets','filter','providers'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        $query = Store::with(['provider', 'approvedBy'])->withCount('products');
        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['status']) && $filter['status'] != '') {
                $query->where('status', $filter['status']);
            }
            if (isset($filter['provider_id']) && $filter['provider_id'] != '') {
                $query->where('provider_id', $filter['provider_id']);
            }
            if (isset($filter['location']) && $filter['location'] != '') {
                $query->where(function($q) use ($filter) {
                    $q->where('city', 'like', '%' . $filter['location'] . '%')
                      ->orWhere('state', 'like', '%' . $filter['location'] . '%')
                      ->orWhere('address', 'like', '%' . $filter['location'] . '%');
                });
            }
        }
        // Apply permission-based filtering
        $user = auth()->user();
        if ($user->user_type === 'admin') {
            $query = $query->withTrashed();
        } elseif ($user->user_type === 'provider') {
            // Providers can only see their own stores
            $query->where('provider_id', $user->id);
        } else {
            // Regular users shouldn't access this endpoint, but if they do, show nothing
            $query->whereRaw('1 = 0');
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$row->id.'"  name="datatable_ids[]" value="'.$row->id.'" onclick="dataTableRowCheck('.$row->id.')">';
            })
            ->editColumn('name', function($query) {
                return '<a class="btn-link btn-link-hover" href='.route('store.show', $query->id).'>'.$query->name.'</a>';
            })
            ->addColumn('provider', function($query) {
                return $query->provider ? $query->provider->display_name : '-';
            })
            ->addColumn('products_count', function($query) {
                return $query->products_count ?? 0;
            })
            ->editColumn('status', function($query) {
                $statusColors = [
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'suspended' => 'secondary'
                ];
                $color = $statusColors[$query->status] ?? 'secondary';
                return '<span class="badge badge-'.$color.'">'.ucfirst($query->status).'</span>';
            })
            ->editColumn('is_active' , function ($query){
                $disabled = $query->trashed() ? 'disabled': '';
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input  change_status" data-type="store_status" '.($query->is_active ? "checked" : "").' '.$disabled.' value="'.$query->id.'" id="'.$query->id.'" data-id="'.$query->id.'">
                        <label class="custom-control-label" for="'.$query->id.'" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
            ->editColumn('created_at', function($query) {
                return dateAgoFormate($query->created_at, true);
            })
            ->addColumn('action', function($store){
                return view('store.action',compact('store'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['action','status','is_active','check','name'])
            ->filterColumn('name', function($query, $keyword) {
                $query->where('name', 'like', "%{$keyword}%");
            })
            ->filterColumn('provider', function($query, $keyword) {
                $query->whereHas('provider', function($q) use ($keyword) {
                    $q->where('display_name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('status', function($query, $keyword) {
                $query->where('status', 'like', "%{$keyword}%");
            })
            ->filterColumn('is_active', function($query, $keyword) {
                $query->where('is_active', 'like', "%{$keyword}%");
            })
            ->filterColumn('created_at', function($query, $keyword) {
                $query->where('created_at', 'like', "%{$keyword}%");
            })
            ->filterColumn('products_count', function($query, $keyword) {
                $query->having('products_count', 'like', "%{$keyword}%");
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

        $storedata = Store::with(['provider', 'country', 'state', 'city'])->find($id);
        $pageTitle = trans('messages.update_form_title',['form'=>trans('messages.store')]);

        if($storedata == null){
            $pageTitle = trans('messages.add_form_title',['form' => trans('messages.store')]);
            $storedata = new Store;
        }

        $providers = User::where('user_type', 'provider')->get();
        $countries = Country::get();

        return view('store.create', compact('pageTitle' ,'storedata','auth_user','providers','countries'));
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

        // Set provider_id from authenticated user if not provided
        if (!isset($data['provider_id']) || !$data['provider_id']) {
            $data['provider_id'] = auth()->id();
        }

        $data['slug'] = Str::slug($data['name'] . '-' . $data['provider_id']);

        // Handle business_hours as JSON
        if (isset($data['business_hours'])) {
            $data['business_hours'] = json_encode($data['business_hours']);
        }

        // Create or update store
        if (isset($data['id']) && $data['id']) {
            // Update existing store
            $result = Store::updateOrCreate(['id' => $data['id']], $data);
        } else {
            // Create new store
            unset($data['id']); // Remove id if it exists but is null/empty
            $result = Store::create($data);
        }

        $message = trans('messages.update_form',['form' => trans('messages.store')]);
        if($result->wasRecentlyCreated){
            $message = trans('messages.save_form',['form' => trans('messages.store')]);
        }

        if($request->is('api/*')) {
            return comman_message_response($message);
		}

        return redirect(route('store.index'))->withSuccess($message);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $store = Store::with(['provider', 'country', 'state', 'city', 'approvedBy', 'products'])->withCount('products')->findOrFail($id);
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

    public function approve(Request $request)
    {
        $data = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'action' => 'required|in:approve,reject',
            'rejection_reason' => 'required_if:action,reject|string|max:500'
        ]);

        $store = Store::findOrFail($data['store_id']);

        if ($data['action'] == 'approve') {
            $store->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
                'rejection_reason' => null
            ]);
            $message = trans('messages.store_approved_successfully');
        } else {
            $store->update([
                'status' => 'rejected',
                'rejection_reason' => $data['rejection_reason'],
                'approved_at' => null,
                'approved_by' => null
            ]);
            $message = trans('messages.store_rejected_successfully');
        }

        return comman_custom_response(['message'=> $message , 'status' => true]);
    }

    public function pending()
    {
        $pageTitle = trans('messages.pending_stores');
        $auth_user = authSession();
        $assets = ['datatable'];
        return view('store.pending', compact('pageTitle','auth_user','assets'));
    }

    public function pending_data(DataTables $datatable, Request $request)
    {
        $query = Store::with(['provider'])->where('status', 'pending');

        return $datatable->eloquent($query)
            ->editColumn('name', function($query) {
                return '<a class="btn-link btn-link-hover" href='.route('store.show', $query->id).'>'.$query->name.'</a>';
            })
            ->editColumn('provider', function($query) {
                return $query->provider ? $query->provider->display_name : '-';
            })
            ->editColumn('created_at', function($query) {
                return dateAgoFormate($query->created_at, true);
            })
            ->addColumn('action', function($store){
                return view('store.pending_action',compact('store'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['action','name'])
            ->toJson();
    }
}
