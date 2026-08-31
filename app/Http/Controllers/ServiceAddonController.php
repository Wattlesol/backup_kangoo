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
        $filter = [
            'status' => $request->status,
        ];
        $pageTitle = __('messages.list_form_title',['form' => __('messages.service_addon')] );
        $auth_user = authSession();
        $assets = ['datatable'];

        $addonSummary = [
            'total' => ServiceAddon::count(),
            'active' => ServiceAddon::where('status', 1)->count(),
            'inactive' => ServiceAddon::where('status', 0)->count(),
            'categories' => Category::count(),
            'services' => Service::count(),
        ];

        return view('serviceaddon.index', compact('pageTitle', 'auth_user', 'assets', 'filter', 'addonSummary'));
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
                $image = getSingleMedia($query, 'serviceaddon_image', null);
                $nameEn = $query->name;
                $nameAr = $query->name_ar ?: '';

                $thumb = $image 
                    ? '<img src="'.$image.'" alt="'.e($nameEn).'" class="quick-category-avatar" style="width:38px;height:38px;object-fit:cover;border-radius:10px;border:1px solid var(--quick-shell-line);flex-shrink:0;background:var(--quick-shell-surface);">'
                    : '<div class="quick-category-avatar-placeholder" style="width:38px;height:38px;border-radius:10px;background:rgba(31,107,255,.09);color:var(--quick-blue);display:grid;place-items:center;font-weight:900;font-size:14px;border:1px solid rgba(31,107,255,.15);flex-shrink:0;">'.mb_substr($nameEn, 0, 1).'</div>';

                if (auth()->user()->can('service list') || auth()->user()->hasAnyRole(['admin', 'demo_admin'])) {
                    $link = '<a class="quick-category-title-link" style="font-weight:800;font-size:13px;color:var(--quick-shell-ink);text-decoration:none;" href="'.route('serviceaddon.create', ['id' => $query->id]).'">'.e($nameEn).'</a>';
                } else {
                    $link = '<span style="font-weight:800;font-size:13px;color:var(--quick-shell-ink);">'.e($nameEn).'</span>';
                }

                $subtext = $nameAr ? '<span style="display:block;font-size:11px;color:var(--quick-shell-muted);margin-top:2px;">'.e($nameAr).'</span>' : '';

                return '<div style="display:flex;align-items:center;gap:12px;">'.$thumb.'<div style="min-width:0;">'.$link.$subtext.'</div></div>';
           })
            ->editColumn('name_ar', function($query){
                return '<span style="font-weight:700;font-size:13px;color:var(--quick-shell-ink);">'.e($query->name_ar ?: '-').'</span>';
            })

           ->addColumn('targets', function ($query) {
                $isAr = app()->getLocale() === 'ar';
                $targets = [];

                if ($query->categories->isNotEmpty()) {
                    $catNames = $query->categories->map(function($c) use ($isAr) {
                        return $isAr && !empty($c->name_ar) ? $c->name_ar : ($c->name_en ?: $c->name);
                    })->implode(', ');
                    $targets[] = '<span class="quick-order-badge" style="display:inline-block;padding:3px 8px;border-radius:6px;background:rgba(31,107,255,.08);color:var(--quick-blue);font-size:11px;font-weight:700;margin-bottom:2px;">'.($isAr ? 'القطاعات: ' : 'Categories: ').e($catNames).'</span>';
                }

                if ($query->services->isNotEmpty()) {
                    $srvNames = $query->services->map(function($s) use ($isAr) {
                        return $isAr && !empty($s->name_ar) ? $s->name_ar : ($s->name_en ?: $s->name);
                    })->implode(', ');
                    $targets[] = '<span class="quick-order-badge" style="display:inline-block;padding:3px 8px;border-radius:6px;background:rgba(139,92,246,.08);color:#8b5cf6;font-size:11px;font-weight:700;">'.($isAr ? 'الخدمات: ' : 'Services: ').e($srvNames).'</span>';
                } elseif ($query->service_id != null && isset($query->service)) {
                    $srv = $query->service;
                    $srvName = $isAr && !empty($srv->name_ar) ? $srv->name_ar : ($srv->name_en ?: $srv->name);
                    $targets[] = '<span class="quick-order-badge" style="display:inline-block;padding:3px 8px;border-radius:6px;background:rgba(139,92,246,.08);color:#8b5cf6;font-size:11px;font-weight:700;">'.($isAr ? 'الخدمة: ' : 'Service: ').e($srvName).'</span>';
                }

                return !empty($targets) ? implode('<div style="height:3px;"></div>', $targets) : '<span style="font-size:12px;color:var(--quick-shell-muted);font-weight:600;">'.($isAr ? 'جميع الخدمات' : 'All Services').'</span>';
            })
            ->editColumn('price', function ($query) {
                return ($query->price != null && isset($query->price)) ? '<span class="quick-order-badge" style="display:inline-flex;align-items:center;padding:4px 10px;border-radius:8px;background:rgba(16,185,129,.09);color:#10b981;font-weight:800;font-size:12px;border:1px solid rgba(16,185,129,.18);">' . getPriceFormat($query->price) . '</span>' : '-';
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
