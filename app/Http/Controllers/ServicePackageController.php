<?php

namespace App\Http\Controllers;


use App\Enums\BookingEnums;
use App\Models\complaints_comment;
use App\Models\HanyManRateingService;
use App\Models\package_service;
use App\Models\package_service_booking;
use App\Models\PackageComplaint;
use App\Models\PriceList;
use App\Models\User;
use App\Models\UsersFeedback;
use App\Traits\FileHandler;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\ServicePackage;
use App\Models\Service;
use App\Models\PackageServiceMapping;
use Yajra\DataTables\DataTables;
use App\Models\BookingPackageMapping;
use Illuminate\Validation\ValidationException;

class ServicePackageController extends Controller
{
    private function ensureSanadCatalogAdmin(Request $request): void
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole(['admin', 'demo_admin']), 403);
    }
    use FileHandler;
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
        $pageTitle = __('messages.list_form_title',['form' => __('messages.service_package')] );
        $auth_user = authSession();
        $assets = ['datatable'];

        $packageSummary = [
            'total' => ServicePackage::count(),
            'active' => ServicePackage::where('status', 1)->count(),
            'inactive' => ServicePackage::where('status', 0)->count(),
            'featured' => ServicePackage::where('is_featured', 1)->count(),
            'services' => Service::count(),
        ];

        return view('servicepackage.index', compact('pageTitle', 'auth_user', 'assets', 'filter', 'packageSummary'));
    }

    public function index_data(DataTables $datatable,Request $request)
    {
        $query = ServicePackage::query();

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
                    <input type="checkbox" class="custom-control-input  change_status" data-type="servicepackage_status" '.($query->status ? "checked" : "").'  value="'.$query->id.'" id="'.$query->id.'" data-id="'.$query->id.'">
                    <label class="custom-control-label" for="'.$query->id.'" data-on-label="" data-off-label=""></label>
                </div>
            </div>';
        })


            ->editColumn('name', function($query){
                $image = getSingleMedia($query, 'package_attachment', null);
                $nameEn = $query->name;
                $nameAr = $query->name_ar ?: '';

                $thumb = $image 
                    ? '<img src="'.$image.'" alt="'.e($nameEn).'" class="quick-category-avatar" style="width:38px;height:38px;object-fit:cover;border-radius:10px;border:1px solid var(--quick-shell-line);flex-shrink:0;background:var(--quick-shell-surface);">'
                    : '<div class="quick-category-avatar-placeholder" style="width:38px;height:38px;border-radius:10px;background:rgba(31,107,255,.09);color:var(--quick-blue);display:grid;place-items:center;font-weight:900;font-size:14px;border:1px solid rgba(31,107,255,.15);flex-shrink:0;">'.mb_substr($nameEn, 0, 1).'</div>';

                if (auth()->user()->can('service list')) {
                    $link = '<a class="quick-category-title-link" style="font-weight:800;font-size:13px;color:var(--quick-shell-ink);text-decoration:none;" href="'.route('servicepackage.create', ['id' => $query->id]).'">'.e($nameEn).'</a>';
                } else {
                    $link = '<span style="font-weight:800;font-size:13px;color:var(--quick-shell-ink);">'.e($nameEn).'</span>';
                }

                $subtext = $nameAr ? '<span style="display:block;font-size:11px;color:var(--quick-shell-muted);margin-top:2px;">'.e($nameAr).'</span>' : '';

                return '<div style="display:flex;align-items:center;gap:12px;">'.$thumb.'<div style="min-width:0;">'.$link.$subtext.'</div></div>';
            })
            ->editColumn('name_ar', function($query){
                return '<span style="font-weight:700;font-size:13px;color:var(--quick-shell-ink);">'.e($query->name_ar ?: '-').'</span>';
            })

            ->editColumn('category_id', function ($query) {
                return ($query->category_id != null && isset($query->category)) ? $query->category->name : '-';
            })
            ->editColumn('pricelist_id', function ($query) {
                return ($query->pricelist_id != null && isset($query->pricelist)) ? $query->pricelist->name : '-';
            })
            ->editColumn('package_type', function ($query) {
                return ($query->package_type != null && isset($query->package_type)) ? ucfirst($query->package_type) : '-';
            })
            ->editColumn('price', function ($query) {
                return ($query->price != null && isset($query->price)) ? '<span class="quick-order-badge" style="display:inline-flex;align-items:center;padding:4px 10px;border-radius:8px;background:rgba(16,185,129,.09);color:#10b981;font-weight:800;font-size:12px;border:1px solid rgba(16,185,129,.18);">' . getPriceFormat($query->price) . '</span>' : '-';
            })
            ->addColumn('action', function ($servicepackage) {
                return view('servicepackage.action', compact('servicepackage'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'status','name','name_ar','check','price'])
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
                $branches = ServicePackage::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = 'Bulk Service Status Updated';
                break;

            case 'delete':
                ServicePackage::whereIn('id', $ids)->delete();
                $message = 'Bulk Service Deleted';
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
        $id = $request->id;
        $auth_user = authSession();
        $services = [];
        $selectedServiceId = [];
        $services_data = Service::pluck('name','id');
        $servicePrices = Service::pluck('price', 'id')->map(fn ($price) => (float) $price);
        $servicepackage = ServicePackage::find($id);
        $pageTitle = trans('messages.update_form_title', ['form' => trans('messages.service_package')]);
        if($servicepackage !== null){
            $serviceIds = $servicepackage->packageServices->pluck('service_id')->toArray();
            if (is_array($serviceIds)) {
            $services = Service::whereIn('id', $serviceIds)->get();
            $selectedServiceId = $serviceIds;
        }
    }
        if ($servicepackage == null) {
            $pageTitle = trans('messages.add_button_form', ['form' => trans('messages.service_package')]);
            $servicepackage = new ServicePackage;
        }
        $PriceList = PriceList::pluck('name','id');
        $AllServices = Service::pluck('name','id');
        $AllUser = User::pluck('contact_number','id');
        return view('servicepackage.create', compact('pageTitle', 'servicepackage', 'AllServices','auth_user','services',
            'selectedServiceId','services_data','servicePrices','PriceList','AllUser'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->ensureSanadCatalogAdmin($request);
        $request->validate([
            'name' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
            'service_id' => 'nullable',
            'service_id_data' => 'required_without:service_id|array|min:1',
            'service_id_data.*' => 'integer|distinct|exists:services,id',
            'status' => 'required|boolean',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:4000',
            'package_attachment' => 'nullable',
            'package_attachment.*' => 'file|mimes:jpg,jpeg,png,webp|max:10240',
        ]);
        $data = $request->all();
        $provider_id = admin_id();
        $serviceIds = $request->service_id;
        if (empty($serviceIds) && is_array($request->service_id_data)) {
            $serviceIds = $request->service_id_data;
        }
        $serviceIds = is_array($serviceIds) ? $serviceIds : (empty($serviceIds) ? [] : explode(',', $serviceIds));
        $serviceIds = collect($serviceIds)->filter()->unique()->values()->all();
        $servicePrices = Service::whereIn('id', $serviceIds)->pluck('price', 'id')->map(fn ($price) => (float) $price);
        if ($servicePrices->count() !== count($serviceIds)) {
            throw ValidationException::withMessages([
                'service_id_data' => __('validation.exists', ['attribute' => __('messages.service')]),
            ]);
        }
        $bundlePrice = $request->filled('price') ? (float) $request->price : (float) $servicePrices->sum();

        if ($bundlePrice <= 0) {
            return redirect()->back()->withInput()->withErrors('Bundle price is required when selected services do not have prices.');
        }

        $service_package = [
            'name' => $request->name,
            'name_ar' => $request->name_ar,
            'description' => $request->description,
            'provider_id' => $provider_id,
            'status' => $request->status,
            'price' => $bundlePrice,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
            'category_id' => $request->category_id,
            'service_id' => implode(',', $serviceIds),
            'duration' => $request->duration ?? 0,
            'car_number' => $request->car_number ?? '',
            'pricelist_id' => $request->pricelist_id,
            'subcategory_id' => $request->subcategory_id,
            'package_type' => $request->package_type,
        ];
        if(!$request->is('api/*')) {
            if($request->id == null ){
                if(!isset($data['package_attachment'])){
                    return  redirect()->back()->withErrors(__('validation.required',['attribute' =>'attachments']));
                }
            }
        }


        if(!$request->is('api/*')) {
            $service_package['is_featured'] = 0;
            if($request->has('is_featured')){
                $service_package['is_featured'] = 1;
            }
        }
        $result = ServicePackage::updateOrCreate(['id' => $data['id'] ?? null], $service_package);
        if ($result->packageServices()->count() > 0) {
            $result->packageServices()->delete();
        }
        package_service::where('package_id', $result->id)->delete();
        if (!empty($serviceIds)) {
            foreach ($serviceIds as $value) {
                $mapping_array = [
                    'service_package_id' => $result->id,
                    'service_id' => $value
                ];
                $result->packageServices()->create($mapping_array);
            }
        }
        if ($request->is('api/*')) {
            if ($request->has('attachment_count')) {
                for ($i = 0; $i < $request->attachment_count; $i++) {
                    $attachment = "package_attachment_" . $i;
                    if ($request->$attachment != null) {
                        $file[] = $request->$attachment;
                    }
                }
                storeMediaFile($result, $file, 'package_attachment');
            }
        } else {
            storeMediaFile($result, $request->package_attachment, 'package_attachment');
        }

        $message = trans('messages.update_form', ['form' => trans('messages.package')]);
        if ($result->wasRecentlyCreated) {
            $message = trans('messages.save_form', ['form' => trans('messages.package')]);
        }
        if ($request->is('api/*')) {
            return comman_message_response($message);
        }


        if (!empty($serviceIds)) {
            foreach ($serviceIds as $values){
                package_service::create([
                    'service_id' => $values,
                    'package_id' =>$result->id,
                    'service_type_data' => 'limited',
                    'count' => 1,
                    'usage_times' => 1,
                    'duration_of_use' => null,
                    'price' => $servicePrices[$values] ?? 0,
                ]);
            }
        }
        return redirect(route('servicepackage.index'))->withSuccess($message);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $this->ensureSanadCatalogAdmin(request());
        return redirect()->route('servicepackage.create', ['id' => $id]);
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
        $this->ensureSanadCatalogAdmin($request);
        $request->merge(['id' => $id]);
        return $this->store($request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->ensureSanadCatalogAdmin(request());
        if (demoUserPermission()) {
            if (request()->is('api/*')) {
                return comman_message_response(__('messages.demo_permission_denied'));
            }
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $service_package = ServicePackage::find($id);
        $msg = __('messages.msg_fail_to_delete', ['item' => __('messages.package')]);

        if ($service_package != '') {

            $service_package->delete();
            $msg = __('messages.msg_deleted', ['name' => __('messages.package')]);
        }
        if (request()->is('api/*')) {
            return comman_custom_response(['message' => $msg, 'status' => true]);
        }
        return redirect()->back()->withSuccess($msg);
    }

    public function action(Request $request){
        $this->ensureSanadCatalogAdmin($request);
        $id = $request->id;
        $servicepackage = ServicePackage::where('id',$id)->first();
        $msg = __('messages.not_found_entry',['name' => __('messages.service_package')] );
        if($request->type === 'forcedelete'){
            $bookingPackageMappings = $servicepackage->bookingPackageMappings;
            foreach ($bookingPackageMappings as $bookingPackageMapping) {
                $booking = $bookingPackageMapping->bookings;
                if ($booking) {
                    $booking->delete();
                }
                $bookingPackageMapping->delete();
            }
            $servicepackage->forceDelete();
            $msg = __('messages.msg_forcedelete',['name' => __('messages.service_package')] );
        }

        return comman_custom_response(['message'=> $msg , 'status' => true]);
    }
    public function servicepackage_booking(){
       $package_service_booking = package_service_booking::orderBy('id','DESC')->get();
       $handyman = User::where('user_type', 'handyman')->pluck('id','display_name');
    return view('booking.packagebooking',compact('package_service_booking','handyman'));
    }
    public function proiver_booking(){
       $package_service_booking = package_service_booking::where('provider_id',auth()->user()->id)->orderBy('id','DESC')->get();
       $handyman = User::where('provider_id',auth()->user()->id)->where('user_type', 'handyman')->pluck('id','display_name');
    return view('booking.Proiverpackagebooking',compact('package_service_booking','handyman'));
    }

    public function Handyman_booking(){
        $package_service_booking = package_service_booking::orderBy('id','DESC')->where('handyman_id',auth()->user()->id)->get();
        $handyman = User::where('user_type', 'handyman')->pluck('id','display_name');
        return view('booking.HandymanBbooking',compact('package_service_booking','handyman'));
    }

    public function change_status ($booking_id,$status){
       $package_service_booking = package_service_booking::orderBy('id','DESC')->findOrFail($booking_id);

       $package_service_booking->status = $status;

       if ($status == BookingEnums::finished){
           $package_service_booking->end_at = Carbon::now();
       }
       $package_service_booking->save();
        return redirect()->back()->with('success','Confirmed');


    }
    public function start_service ($booking_id){
       $package_service_booking = package_service_booking::orderBy('id','DESC')->findOrFail($booking_id);
       if ($package_service_booking->start_at == null){
        $package_service_booking->start_at = Carbon::now();
       $package_service_booking->save();
       }
        return redirect()->back()->with('success','Confirmed');


    }
    public function change_status_user ($booking_id){
       $package_service_booking = package_service_booking::orderBy('id','DESC')->findOrFail($booking_id);

       $package_service_booking->status = BookingEnums::approved;
       $package_service_booking->save();
        return redirect()->back()->with('success','Confirmed');


    }
    public function ChangeData ($booking_id,Request  $request){
       $package_service_booking = package_service_booking::orderBy('id','DESC')->findOrFail($booking_id);

       $package_service_booking->status = BookingEnums::reschedule;
       $package_service_booking->date = $request->booking_date;
       $package_service_booking->save();
        return redirect()->back()->with('success','Change Date Done');


    }
    public function AssignHandyman ($booking_id,Request  $request){
       $package_service_booking = package_service_booking::orderBy('id','DESC')->findOrFail($booking_id);

       $package_service_booking->status = BookingEnums::handyman_assign;
       $package_service_booking->handyman_id = $request->handyman;
       $package_service_booking->save();
        return redirect()->back()->with('success','Handyman assign confirmed');


    }
    public function rate ($booking_id,Request  $request){
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string',
        ]);
        $package_service_booking = package_service_booking::orderBy('id','DESC')->findOrFail($booking_id);

        $HanyManRateingService = HanyManRateingService::where([
            'handyman_id' => auth()->user()->id,
            'booking_id' => $booking_id
        ])->count();
       if ($HanyManRateingService != 0 ){
           return redirect()->back()->with('error', 'You have already rated this booking');
       }
        HanyManRateingService::create([
            'handyman_id' => auth()->user()->id,
            'booking_id' => $booking_id,
            'rate' => $request->rating,
            'subscription_id'=>$package_service_booking->subscription_id,
            'feedback' => $request->feedback,
        ]);

        return redirect()->back()->with('success', 'Thank you for your feedback!');


    }
    public function user_booking_service ($booking_id,Request  $request){
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string',
        ]);
        $package_service_booking = package_service_booking::orderBy('id','DESC')->findOrFail($booking_id);

        $HanyManRateingService = UsersFeedback::where([
            'user_id' => auth()->user()->id,
            'booking_id' => $booking_id
        ])->count();
       if ($HanyManRateingService != 0 ){
           return redirect()->back()->with('error', 'You have already rated this booking');
       }
        UsersFeedback::create([
            'user_id' => auth()->user()->id,
            'booking_id' => $booking_id,
            'rate' => $request->rating,
            'subscription_id'=>$package_service_booking->subscription_id,
            'feedback' => $request->feedback,
        ]);

        return redirect()->back()->with('success', 'Thank you for your feedback!');


    }
    public function view_rate ($subscription_id){
        $HanyManRateingService = HanyManRateingService::orderBy('id', 'DESC')->where('subscription_id',$subscription_id)->get();
        return view('booking.packagebookingrate', compact('HanyManRateingService'));
    }


    public function submitComplaint( $id,Request $request)
    {
        $request->validate([
            'complaint_type' => 'required|string|max:255',
            'complaint_details' => 'required|string',
        ]);
        $package_service_booking = package_service_booking::orderBy('id','DESC')->findOrFail($id);

        // Assuming you have a Complaint model and a related table in your database
        PackageComplaint::create([
            'subscription_id' => $package_service_booking->subscription_id,
            'booking_id' => $package_service_booking->id,
            'service_id' => $package_service_booking->service_id,
            'complaint_type' => $request->complaint_type,
            'complaint_details' => $request->complaint_details,
            'file'=>$this->UploadFile($request->complaint_file),
            'user_id' => auth()->user()->id, // Assuming the user is authenticated
        ]);

        return redirect()->back()->with('success', 'Your complaint has been submitted successfully.');
    }

    public function complaint()
    {
      $PackageComplaint = PackageComplaint::where('user_id',auth()->user()->id)->get();
      return view('booking.complaint', compact('PackageComplaint'));
    }
    public function complaint_show($id)
    {
      $PackageComplaint = PackageComplaint::where('user_id',auth()->user()->id)->findOrFail($id);
      return view('booking.complaint_show', compact('PackageComplaint'));
    }



    public function complaint_provider()
    {
        $PackageComplaint = PackageComplaint::get();
        return view('booking.complaint_proiver', compact('PackageComplaint'));
    }
    public function complaint_show_provider($id)
    {
        $PackageComplaint = PackageComplaint::findOrFail($id);
        return view('booking.complaint_show', compact('PackageComplaint'));
    }
    public function reply_submitComplaint($id,Request $request)
    {
        $request->validate([
            'reply' => 'required|string',
        ]);

        // Find the complaint by ID and update the reply
        $complaint = New complaints_comment();
        $complaint->comment = $request->input('reply');
       $complaint->complaint_id = $id;
        $complaint->file =      (is_file($request->file)) ? $this->UploadFile($request->file,'files/'):"";;
;
        $complaint->user_id = auth()->user()->id;
        $complaint->save();
        return redirect()->back()->with('success', 'Your complaint has been submitted successfully.');

    }
}
