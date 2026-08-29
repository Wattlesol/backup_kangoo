<?php

namespace App\Http\Controllers;

use App\Models\ServiceAddon;
use App\Models\Category;
use App\Models\Service;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;
use App\Http\Requests\ServiceAddonRequest;

class ServiceAddonController extends Controller
{
    private function ensureSanadCatalogAdmin(Request $request): void
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole(['admin', 'demo_admin']), 403);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
        $filter = [
            'status' => $request->status,
        ];
        $pageTitle = __('messages.list_form_title',['form' => __('messages.service_addon')] );
        $auth_user = authSession();
        $assets = ['datatable'];
        return view('serviceaddon.index', compact('pageTitle','auth_user','assets','filter'));
    }
    public function index_data(DataTables $datatable,Request $request)
    {
        $query = ServiceAddon::query()->with(['categories', 'services', 'service'])->ServiceAddon();

        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
        }
        if (auth()->user()->hasAnyRole(['admin'])) {
            $query= $query;
        }
        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {

                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$row->id.'"  name="datatable_ids[]" value="'.$row->id.'" onclick="dataTableRowCheck('.$row->id.',this)">';
            })
            ->editColumn('status' , function ($query){
                return '<div class="custom-control custom-switch custom-switch-text custom-switch-color custom-control-inline">
                    <div class="custom-switch-inner">
                        <input type="checkbox" class="custom-control-input  change_status" data-type="serviceaddon_status" '.($query->status ? "checked" : "").'  value="'.$query->id.'" id="'.$query->id.'" data-id="'.$query->id.'">
                        <label class="custom-control-label" for="'.$query->id.'" data-on-label="" data-off-label=""></label>
                    </div>
                </div>';
            })
           ->editColumn('name', function($query){
               return '<a class="btn-link btn-link-hover"  href='.route('serviceaddon.create', ['id' => $query->id]).'>'.$query->name.'</a>';
           })
            ->editColumn('name_ar', function($query){
                if (!empty($query->name_ar)) {
                    return '<a class="btn-link btn-link-hover" href='.route('serviceaddon.create', ['id' => $query->id]).' dir="rtl">'.$query->name_ar.'</a>';
                }
                return '-';
            })

           ->addColumn('targets', function ($query) {
                $targets = [];

                if ($query->categories->isNotEmpty()) {
                    $targets[] = 'Categories: '.$query->categories->pluck('name')->implode(', ');
                }

                if ($query->services->isNotEmpty()) {
                    $targets[] = 'Services: '.$query->services->pluck('name')->implode(', ');
                } elseif ($query->service_id != null && isset($query->service)) {
                    $targets[] = 'Service: '.$query->service->name;
                }

                return !empty($targets) ? implode('<br>', $targets) : 'All Services';
            })
            ->editColumn('price', function ($query) {
                return ($query->price != null && isset($query->price)) ? getPriceFormat($query->price) : '-';
            })
            ->addColumn('action', function ($serviceaddon) {
                return view('serviceaddon.action', compact('serviceaddon'))->render();
            })
           ->addIndexColumn()
            ->rawColumns(['action', 'status','name','name_ar','check','price','targets'])
           ->toJson();
    }

    public function bulk_action(Request $request)
    {
        $this->ensureSanadCatalogAdmin($request);
        $ids = explode(',', $request->rowIds);

        $actionType = $request->action_type;

        $message = 'Bulk Action Updated';

        switch ($actionType) {
            case 'change-status':
                $branches = ServiceAddon::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = 'Bulk Category Status Updated';
                break;

            case 'delete':
                ServiceAddon::whereIn('id', $ids)->delete();
                $message = 'Bulk Category Deleted';
                break;

            default:
                return response()->json(['status' => false, 'message' => 'Action Invalid']);
                break;
        }

        return response()->json(['status' => true, 'message' => $message]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $this->ensureSanadCatalogAdmin($request);
        //
        $id = $request->id;
        $auth_user = authSession();
        $serviceaddon = ServiceAddon::with(['categories', 'services'])->find($id);
        $pageTitle = trans('messages.update_form_title',['form'=>trans('messages.service_addon')]);
        
        if($serviceaddon == null){
            $pageTitle = trans('messages.add_button_form',['form' => trans('messages.service_addon')]);
            $serviceaddon = new ServiceAddon;
        }
        
        $categories = Category::where('status', 1)->orderBy('name')->pluck('name', 'id');
        $services = Service::where('status', 1)->where('service_type', 'service')->orderBy('name')->pluck('name', 'id');
        $selectedCategoryIds = $serviceaddon->categories->pluck('id')->toArray();
        $selectedServiceIds = $serviceaddon->services->pluck('id')->toArray();
        if (empty($selectedServiceIds) && !empty($serviceaddon->service_id)) {
            $selectedServiceIds = [$serviceaddon->service_id];
        }

        return view('serviceaddon.create', compact('pageTitle' ,'serviceaddon' ,'auth_user', 'categories', 'services', 'selectedCategoryIds', 'selectedServiceIds'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ServiceAddonRequest $request)
    {
        $this->ensureSanadCatalogAdmin($request);
        //
        if(demoUserPermission()){
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $data = $request->except(['category_ids', 'service_ids']);
        $serviceIds = collect($request->input('service_ids', []))->filter()->unique()->values();
        $categoryIds = collect($request->input('category_ids', []))->filter()->unique()->values();
        $data['service_id'] = $serviceIds->count() === 1 ? $serviceIds->first() : null;

        $data['created_by'] = auth()->user()->id;
       
        $result = ServiceAddon::updateOrCreate(['id' => $data['id'] ?? null],$data);
        $result->categories()->sync($categoryIds->all());
        $result->services()->sync($serviceIds->all());
        
            storeMediaFile($result,$request->serviceaddon_image, 'serviceaddon_image');
        

        $message = trans('messages.update_form',['form' => trans('messages.service_addon')]);
        if($result->wasRecentlyCreated){
            $message = trans('messages.save_form',['form' => trans('messages.service_addon')]);
        }
        if($request->is('api/*')) {
            $response = [
                'message'=>$message,
            ];
            return comman_custom_response($response);
		}
        return redirect(route('serviceaddon.index'))->withSuccess($message); 
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ServiceAddon  $serviceAddon
     * @return \Illuminate\Http\Response
     */
    public function show(ServiceAddon $serviceAddon)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ServiceAddon  $serviceAddon
     * @return \Illuminate\Http\Response
     */
    public function edit(ServiceAddon $serviceAddon)
    {
        $this->ensureSanadCatalogAdmin(request());
        return redirect()->route('serviceaddon.create', ['id' => $serviceAddon->id]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ServiceAddon  $serviceAddon
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ServiceAddon $serviceAddon)
    {
        $this->ensureSanadCatalogAdmin($request);
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'service_id' => 'nullable|exists:services,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'exists:services,id',
            'price' => 'required|numeric|min:0',
            'status' => 'nullable|boolean',
            'serviceaddon_image' => 'nullable|mimes:jpg,jpeg,png,webp',
        ]);
        $serviceIds = collect($request->input('service_ids', []))->filter()->unique()->values();
        $categoryIds = collect($request->input('category_ids', []))->filter()->unique()->values();
        $data['service_id'] = $serviceIds->count() === 1 ? $serviceIds->first() : null;
        $data['created_by'] = $serviceAddon->created_by ?: auth()->id();
        unset($data['category_ids'], $data['service_ids']);
        $serviceAddon->update($data);
        $serviceAddon->categories()->sync($categoryIds->all());
        $serviceAddon->services()->sync($serviceIds->all());
        if ($request->hasFile('serviceaddon_image')) {
            storeMediaFile($serviceAddon, $request->file('serviceaddon_image'), 'serviceaddon_image');
        }
        return redirect()->route('serviceaddon.index')->withSuccess(
            trans('messages.update_form', ['form' => trans('messages.service_addon')])
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ServiceAddon  $serviceAddon
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->ensureSanadCatalogAdmin(request());
        //
        if(demoUserPermission()){
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $serviceaddon = ServiceAddon::find($id);
        $msg= __('messages.msg_fail_to_delete',['item' => __('messages.service_addon')] );
        
        if($serviceaddon != '') { 
            $serviceaddon->delete();
            $msg= __('messages.msg_deleted',['name' => __('messages.service_addon')] );
        }
        if(request()->is('api/*')){
            return comman_custom_response(['message'=> $msg , 'status' => true]);
        }
        return comman_custom_response(['message'=> $msg, 'status' => true]);
    }
}
