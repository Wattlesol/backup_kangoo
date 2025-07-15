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

class ServicePackageController extends Controller
{
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
        return view('servicepackage.index', compact('pageTitle','auth_user','assets','filter'));
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
                if (auth()->user()->can('service list')) {
                    $link ='<a class="btn-link btn-link-hover"  href='.route('servicepackage.service',$query->id).'>'.$query->name.'</a>';
                } else {
                    $link = $query->name;
                }
                return $link;
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
                return ($query->price != null && isset($query->price)) ? getPriceFormat($query->price) : '-';
            })
            ->addColumn('action', function ($servicepackage) {
                return view('servicepackage.action', compact('servicepackage'))->render();
            })
            ->addIndexColumn()
            ->rawColumns(['action', 'status','name','check'])
            ->toJson();
    }

    public function bulk_action(Request $request)
    {
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
        $id = $request->id;
        $auth_user = authSession();
        $services = [];
        $selectedServiceId = [];
        $services_data = Service::pluck('name','id');
        $servicepackage = ServicePackage::find($id);
        $pageTitle = trans('messages.update_form_title', ['form' => trans('messages.package')]);
        if($servicepackage !== null){
            $serviceIds = $servicepackage->packageServices->pluck('service_id')->toArray();
            if (is_array($serviceIds)) {
            $services = Service::whereIn('id', $serviceIds)->get();
            $selectedServiceId = $serviceIds;
        }
    }
        if ($servicepackage == null) {
            $pageTitle = trans('messages.add_button_form', ['form' => trans('messages.package')]);
            $servicepackage = new ServicePackage;
        }
        $PriceList = PriceList::pluck('name','id');
        $AllServices = Service::pluck('name','id');
        $AllUser = User::pluck('contact_number','id');
        return view('servicepackage.create', compact('pageTitle', 'servicepackage', 'AllServices','auth_user','services',
            'selectedServiceId','services_data','PriceList','AllUser'));
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
        $provider_id = !empty($request->provider_id) ? $request->provider_id : \Auth::user()->id;
        $service_package = [
            'name' => $request->name,
            'description' => $request->description,
            'provider_id' => $provider_id,
            'status' => $request->status,
            'price' => $request->price,
            'start_at' => $request->start_at,
            'end_at' => $request->end_at,
            'category_id' => $request->category_id,
            'service_id' => $request->service_id,
            'duration' => $request->duration,
            'car_number' => $request->car_number,
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
        $result = ServicePackage::updateOrCreate(['id' => $data['id']], $service_package);
        if ($result->packageServices()->count() > 0) {
            $result->packageServices()->delete();
        }
        if (!empty($request->service_id)) {
            $service = $request->service_id;
            if(!$request->is('api/*')) {
//                $service = implode(",", $request->service_id);
            }
            foreach (explode(',', $service) as $key => $value) {
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


        if ($request->service_id_data) {
            foreach ($request->service_id_data as $key_data=> $values){
                package_service::create([
                    'service_id' => $values,
                    'package_id' =>$result->id,
                    'service_type_data' => $request->service_type_data[$key_data],
                    'count' => $request->count[$key_data],
                    'usage_times' => $request->usage_times[$key_data],
                    'duration_of_use' => $request->duration_of_use[$key_data],
                    'price' => $request->price_data[$key_data],
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
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
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
