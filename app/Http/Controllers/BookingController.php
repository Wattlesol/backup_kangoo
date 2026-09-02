<?php

namespace App\Http\Controllers;

use App\Enums\BookingEnums;
use App\Models\package_service_booking;
use App\Models\ProviderRegion;
use App\Models\ServicePacakgeServiceSubscription;
use App\Models\ServicePacakgeSubscription;
use App\Models\ServicePackage;
use App\Models\SubscriptionAddress;
use App\Models\SubscriptionCar;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Service;
use App\Models\Payment;
use App\Models\User;
use App\Models\SanadPartnerServicePerformance;
use App\Models\SanadRequestAction;
use App\Exports\BookingsExport;
use App\Models\BookingStatus;
use App\Models\PostJobRequest;
use App\Models\ProviderAddressMapping;
use App\Http\Requests\BookingUpdateRequest;
use App\Models\Notification;
use Yajra\DataTables\DataTables;
use PDF;
use App\Models\AppSetting;
use Carbon\Carbon;
use App\Traits\NotificationTrait;

use App\Models\ServiceAddon;
use App\Models\BookingServiceAddonMapping;
use App\Models\BookingRating;
use App\Models\Setting;
use App\Models\Country;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
class BookingController extends Controller
{
    use NotificationTrait;

    /**
     * Add an Additional Service to a customer's active request.
     */
    public function addServiceAddon(Request $request, int $id)
    {
        $request->validate(['service_addon_id' => 'required|exists:service_addons,id']);

        $booking = Booking::where('id', $id)
            ->where('customer_id', auth()->id())
            ->whereIn('status', ['pending', 'accept', 'accepted', 'in_progress', 'assigned_to_partner', 'assigned_to_employee'])
            ->firstOrFail();

        $service = Service::findOrFail($booking->service_id);
        $addon = ServiceAddon::query()
            ->where('id', $request->service_addon_id)
            ->forService($service)
            ->firstOrFail();

        $mapping = BookingServiceAddonMapping::firstOrCreate(
            ['booking_id' => $booking->id, 'service_addon_id' => $addon->id],
            ['name' => $addon->name, 'price' => $addon->price, 'status' => 0]
        );

        return comman_custom_response([
            'status' => true,
            'message' => 'Additional Service added to the request.',
            'data' => $mapping,
        ]);
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
        $pageTitle = __('messages.orders');
        $auth_user = authSession();
        $assets = ['datatable'];

        return view('booking.index', compact('pageTitle','auth_user','assets','filter'));
    }


    public function index_data(DataTables $datatable,Request $request)
    {
        $query = Booking::query()->myBooking()->with(['customer', 'service', 'provider', 'payment', 'handymanAdded.handyman']);
        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
        }
        if (auth()->user()->hasAnyRole(['admin'])) {
            $query->withTrashed();
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$row->id.'"  name="datatable_ids[]" value="'.$row->id.'" data-type="booking" onclick="dataTableRowCheck('.$row->id.',this)">';
            })
            ->editColumn('id' , function ($query){
                return "<a class='btn-link btn-link-hover' href=" .route('booking.show', $query->id).">#".$query->id ."</a>";
            })
            ->editColumn('customer_id' , function ($query){
                return view('booking.customer', compact('query'));
            })
            ->filterColumn('customer_id',function($query,$keyword){
                $query->whereHas('customer',function ($q) use($keyword){
                    $q->where('display_name','like','%'.$keyword.'%');
                });
            })
            ->editColumn('service_id' , function ($query){
                if ($query->service_id != null && isset($query->service)) {
                    $isAr = app()->getLocale() === 'ar';
                    $service_name = $isAr && !empty($query->service->name_ar)
                        ? $query->service->name_ar
                        : ($query->service->name_en ?: $query->service->name);
                } else {
                    $service_name = "";
                }
                return "<a class='btn-link btn-link-hover' href=" .route('booking.show', $query->id).">".e($service_name)."</a>";
            })
            ->filterColumn('service_id',function($query,$keyword){
                $query->whereHas('service',function ($q) use($keyword){
                    $q->where('name','like','%'.$keyword.'%');
                });
            })
            ->editColumn('date' , function ($query){
                $sitesetup = Setting::where('type','site-setup')->where('key', 'site-setup')->first();
                $datetime = $sitesetup ? json_decode($sitesetup->value) : null;

                $date = optional($datetime)->date_format && optional($datetime)->time_format
                ? date(optional($datetime)->date_format, strtotime($query->date)) . ' / ' . date(optional($datetime)->time_format, strtotime($query->date))
                : $query->date;

                return $date;
            })
            ->editColumn('provider_id' , function ($query){
                return view('booking.provider', compact('query'));
            })
            ->filterColumn('provider_id',function($query,$keyword){
                $query->whereHas('provider',function ($q) use($keyword){
                    $q->where('display_name','like','%'.$keyword.'%');
                });
            })
            ->addColumn('handyman_id', function ($query) {
                $employees = $query->handymanAdded
                    ->pluck('handyman.display_name')
                    ->filter()
                    ->unique()
                    ->values();

                return $employees->isNotEmpty() ? e($employees->join(', ')) : '-';
            })
            ->filterColumn('handyman_id', function ($query, $keyword) {
                $query->whereHas('handymanAdded.handyman', function ($employeeQuery) use ($keyword) {
                    $employeeQuery->where('display_name', 'like', '%'.$keyword.'%');
                });
            })
            ->editColumn('status' , function ($query){
                return bookingstatus(BookingStatus::bookingStatus($query->status));
            })
            ->editColumn('payment_id' , function ($query){
                $payment_status = optional($query->payment)->payment_status;
                if($payment_status !== 'paid'){
                    $status = '<span class="badge badge-pay-pending">'.__('messages.pending').'</span>';
                }else{
                    $status = '<span class="badge badge-paid">'.__('messages.paid').'</span>';
                }
                return  $status;
            })
            ->filterColumn('payment_id',function($query,$keyword){
                $query->whereHas('payment',function ($q) use($keyword){
                    $q->where('payment_status','like',$keyword.'%');
                });
            })
            ->editColumn('total_amount' , function ($query){
                return $query->total_amount ? getPriceFormat($query->total_amount) : '-';
            })

            ->addColumn('action', function($booking){
                return view('booking.action',compact('booking'))->render();
            })

            ->editColumn('updated_at', function ($query) {
                $diff = Carbon::now()->diffInHours($query->updated_at);
                if ($diff < 25) {
                    return $query->updated_at->diffForHumans();
                } else {
                    return $query->updated_at->isoFormat('llll');
                }
            })
            ->addColumn('order_number', function ($query) {
                return e($query->quick_reference);
            })
            ->addColumn('priority', function ($query) {
                $priority = strtolower($query->sanad_priority ?: 'normal');
                if (app()->getLocale() === 'ar') {
                    $map = [
                        'low' => 'منخفض',
                        'normal' => 'عادي',
                        'high' => 'مرتفع',
                        'urgent' => 'طارئ',
                        'critical' => 'حرج',
                    ];
                    return $map[$priority] ?? ucfirst($priority);
                }
                return e(ucfirst($priority));
            })
            ->addColumn('expected_completion_at', function ($query) {
                return optional($query->expected_completion_at)->format('Y-m-d H:i') ?: '-';
            })
            ->addIndexColumn()
            ->rawColumns(['action','status','payment_id','service_id','id','check'])
            ->toJson();
    }

    public function export(Request $request, string $format)
    {
        abort_unless(in_array($format, ['pdf', 'excel']), 404);

        $bookings = $this->bookingExportQuery($request)->get();
        $summary = $this->bookingSummary($bookings);
        $filename = 'orders-report-'.now()->format('Y-m-d-His');

        if ($format === 'excel') {
            return Excel::download(new BookingsExport($bookings, $summary), $filename.'.xlsx');
        }

        $pdf = PDF::loadView('booking.exports.pdf', [
            'bookings' => $bookings,
            'summary' => $summary,
            'generatedAt' => now(),
        ]);

        return $pdf->download($filename.'.pdf');
    }

    /* bulck action method */
    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);

        $actionType = $request->action_type;

        $message = 'Bulk Action Updated';
        switch ($actionType) {
            case 'change-status':
                $branches = Booking::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = 'Bulk Booking Status Updated';
                break;

            case 'delete':
                Booking::whereIn('id', $ids)->delete();
                $message = 'Bulk Booking Deleted';
                break;

            case 'restore':
                Booking::whereIn('id', $ids)->restore();
                $message = 'Bulk Booking Restored';
                break;

            case 'permanently-delete':
                Booking::whereIn('id', $ids)->forceDelete();
                $message = 'Bulk Booking Permanently Deleted';
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

        $bookingdata = Booking::find($id);
        $pageTitle = __('messages.update_form_title',['form'=> __('messages.booking')]);

        if($bookingdata == null){
            $pageTitle = __('messages.add_button_form',['form' => __('messages.booking')]);
            $bookingdata = new Booking;
        }

        return view('booking.create', compact('pageTitle' ,'bookingdata' ,'auth_user' ));
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
            'service_id' => 'required|exists:services,id',
            'customer_id' => 'nullable|exists:users,id',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_address' => 'nullable|string|max:1000',
            'customer_password' => 'nullable|string|min:8|max:255|confirmed',
            'date' => 'required',
            'address' => 'nullable|string|max:2000',
            'description' => 'nullable|string|max:4000',
            'sanad_priority' => 'nullable|in:normal,high,urgent',
            'service_addon_id' => 'nullable|array',
            'service_addon_id.*' => 'integer|distinct|exists:service_addons,id',
        ]);

        $data = $request->all();
        $data['coupon_id'] = $data['coupon_id'] ?? null;

        $data['tax'] = null;

        if($request->id == null)
        {
            $data['status'] = !empty($data['status']) ? $data['status'] :'pending';
        }

        if(isset($data['booking_slot'])){
            $date = isset($request->date) ? date('Y-m-d', strtotime($request->date)) : date('Y-m-d');
            $time = date('H:i:s', strtotime($data['booking_slot']));
            $data['date'] = $date . ' ' . $time;
        }
        else{
            $data['date'] = isset($request->date) ? date('Y-m-d H:i:s',strtotime($request->date)) : date('Y-m-d H:i:s');
        }
        $service_data = Service::find($data['service_id']);
        $requestedAddonIds = collect($request->input('service_addon_id', []))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        if ($requestedAddonIds->isNotEmpty()) {
            $eligibleAddonIds = ServiceAddon::query()
                ->forService($service_data)
                ->whereIn('id', $requestedAddonIds)
                ->pluck('id');

            if ($eligibleAddonIds->count() !== $requestedAddonIds->count()) {
                throw ValidationException::withMessages([
                    'service_addon_id' => 'One or more selected add-ons are not available for this service.',
                ]);
            }
        }
        $customer = $this->resolveBookingCustomer($request);
        $plainPassword = $customer->wasRecentlyCreated ? $customer->plain_password_for_admin ?? null : null;
        $data['customer_id'] = $customer->id;
        $data['address'] = $request->input('address') ?: $request->input('customer_address') ?: optional($customer)->address;
        unset($data['customer_mode'], $data['customer_name'], $data['customer_phone'], $data['customer_email'], $data['customer_address'], $data['customer_password'], $data['customer_password_confirmation']);

        // Customers never select or inherit a Partner. Assignment belongs to
        // the Sanad Operations workflow and starts as Suggested/Unassigned.
        $data['provider_id'] = null;
        $data['status'] = $data['status'] ?? 'pending';
        $data['sanad_stage'] = $data['sanad_stage'] ?? 'submitted';
        $data['sanad_priority'] = $data['sanad_priority'] ?? 'normal';
        $data['expected_completion_at'] = $data['expected_completion_at'] ?? $this->expectedCompletion($service_data);
        $data['sla_due_at'] = $data['sla_due_at'] ?? $data['expected_completion_at'];
        $data['amount'] = (float) ($service_data->service_fee ?? $service_data->price ?? 0);

        if($request->has('tax') && $request->tax != null) {
            $data['tax'] = json_encode($request->tax);
        }

        if($request->coupon_id != null) {
            $coupons = Coupon::with('serviceAdded')->where('code',$request->coupon_id)
                ->where('expire_date','>',date('Y-m-d H:i'))->where('status',1)
                ->whereHas('serviceAdded', function($coupon) use($service_data){
                    $coupon->where('service_id', $service_data->id );
                })->first();
            if( $coupons == null ) {
                return comman_message_response( __('messages.invalid_coupon_code'),406);
            } else {
                $data['coupon_id'] = $coupons->id;
            }
        }
        $data['final_total_tax'] = 0;
        $data['total_amount'] = (float) (($service_data->service_fee ?? $service_data->price ?? 0) + ($service_data->government_fee ?? 0));
        $result = Booking::updateOrCreate(['id' => $request->id], $data);
        if (empty($result->sanad_reference)) {
            $result->sanad_reference = $this->nextSanadReference($result->id);
            $result->save();
        }

        $activity_data = [
            'activity_type' => 'add_booking',
            'booking_id' => $result->id,
            'booking' => $result,
        ];
        $this->sendNotification($activity_data);


        if($data['coupon_id'] != null) {
            $coupons = Coupon::find($data['coupon_id']);

            $coupon_data = [
                'booking_id'    => $result->id,
                'code'          => $coupons->code,
                'discount'      => $coupons->discount,
                'discount_type' => $coupons->discount_type,
            ];

            $result->couponAdded()->create($coupon_data);
        }
        if($request->has('booking_address_id') && $request->booking_address_id != null) {
            $booking_address_mapping = ProviderAddressMapping::find($data['booking_address_id']);

            $booking_address_data = [
                'booking_id'    => $result->id,
                'address'          => $booking_address_mapping->address,
                'latitude'      => $booking_address_mapping->latitude,
                'longitude' => $booking_address_mapping->longitude,
            ];

            $result->addressAdded()->create($booking_address_data);
        }

        if ($requestedAddonIds->isNotEmpty()) {
            foreach ($requestedAddonIds as $serviceaddon) {
                $booking_serviceaddon_mapping = ServiceAddon::query()->forService($service_data)->find($serviceaddon);
                if ($booking_serviceaddon_mapping) {
                    $booking_serviceaddon_data = [
                        'booking_id' => $result->id,
                        'service_addon_id' => $booking_serviceaddon_mapping->id,
                        'name' => $booking_serviceaddon_mapping->name,
                        'price' => $booking_serviceaddon_mapping->price,
                    ];

                   $result->bookingAddonService()->create($booking_serviceaddon_data);
                }
            }
        }


        if($request->has('booking_package') && $request->booking_package != null) {
            $booking_package = [
               'booking_id' => $result->id,
               'service_package_id' => $data['booking_package']['id'],
               'provider_id' => $data['provider_id'],
               'name' => $data['booking_package']['name'],
               'is_featured' => $data['booking_package']['is_featured'],
               'package_type' => $data['booking_package']['package_type'],
               'price' => $data['booking_package']['price'],
            ];
            if(!empty($data['booking_package']['start_at'])){
                $booking_package['start_at'] = $data['booking_package']['start_at'];
            }
            if(!empty($data['booking_package']['end_at'])){
                $booking_package['end_at'] = $data['booking_package']['end_at'];
            }
            if(!empty($data['booking_package']['subcategory_id'])){
                $booking_package['subcategory_id'] = $data['booking_package']['subcategory_id'];
            }
            if(!empty($data['booking_package']['category_id'])){
                $booking_package['category_id'] = $data['booking_package']['category_id'];
            }
            $result->bookingPackage()->create($booking_package);
       }
        if(!empty($data['type']) && $data['type'] === 'user_post_job'){
            $post_request = PostJobRequest::where('id',$data['post_request_id'])->first();
            $post_request->date = isset($request->date) ? date('Y-m-d H:i:s',strtotime($request->date)) : date('Y-m-d H:i:s');
            $post_request->update();
        }
        $message = $result->wasRecentlyCreated
            ? __('messages.save_form',[ 'form' => __('messages.booking') ] )
            : __('messages.update_form',[ 'form' => __('messages.booking') ] );

        if($request->is('api/*')) {
            $response = [
                'message'=>$message,
                'booking_id' => $result->id
            ];
            return comman_custom_response($response);
		}
        $redirect = redirect(route('booking.index'))->withSuccess($message);
        if ($plainPassword) {
            $redirect->with('created_customer_credentials', [
                'name' => $customer->display_name,
                'email' => $customer->email,
                'password' => $plainPassword,
            ]);
        }
		return  $redirect;

    }

    private function resolveBookingCustomer(Request $request): User
    {
        if (auth()->check() && in_array(auth()->user()->user_type, ['user', 'customer'], true)) {
            return auth()->user();
        }

        if ($request->filled('customer_id')) {
            $customer = User::whereIn('user_type', ['user', 'customer'])->findOrFail($request->customer_id);
            $updates = [];
            if ($request->filled('customer_phone')) {
                $updates['contact_number'] = $request->customer_phone;
            }
            if ($request->filled('customer_address')) {
                $updates['address'] = $request->customer_address;
            }
            if ($updates) {
                $customer->fill($updates)->save();
            }
            return $customer;
        }

        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required_without:customer_email|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_password' => 'nullable|string|min:8|max:255|confirmed',
        ]);

        $customer = null;
        if ($request->filled('customer_email')) {
            $emailOwner = User::where('email', $request->customer_email)->first();
            if ($emailOwner && !in_array($emailOwner->user_type, ['user', 'customer'], true)) {
                throw ValidationException::withMessages([
                    'customer_email' => 'This email belongs to a non-customer account.',
                ]);
            }
            $customer = $emailOwner;
        }
        if (!$customer && $request->filled('customer_phone')) {
            $customer = User::where('contact_number', $request->customer_phone)->whereIn('user_type', ['user', 'customer'])->first();
        }

        if ($customer) {
            $customer->fill([
                'display_name' => $request->customer_name ?: $customer->display_name,
                'contact_number' => $request->customer_phone ?: $customer->contact_number,
                'address' => $request->customer_address ?: $customer->address,
            ])->save();

            return $customer;
        }

        $nameParts = preg_split('/\s+/', trim($request->customer_name), 2);
        $firstName = $nameParts[0] ?: 'Customer';
        $lastName = $nameParts[1] ?? '';
        $email = $request->customer_email;
        $plainPassword = $request->filled('customer_password') ? $request->customer_password : Str::random(12);

        $customer = User::create([
            'username' => $this->uniqueCustomerUsername($firstName),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'display_name' => trim($request->customer_name),
            'email' => $email,
            'password' => Hash::make($plainPassword),
            'user_type' => 'user',
            'contact_number' => $request->customer_phone,
            'address' => $request->customer_address,
            'status' => 1,
        ]);
        $customer->plain_password_for_admin = $plainPassword;

        if (method_exists($customer, 'assignRole')) {
            try {
                $customer->assignRole('user');
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $customer;
    }

    public function customerDetails($id)
    {
        $customer = User::where('user_type', 'user')->where('status', 1)->findOrFail($id);

        return response()->json([
            'id' => $customer->id,
            'display_name' => $customer->display_name,
            'contact_number' => $customer->contact_number,
            'email' => $customer->email,
            'address' => $customer->address,
        ]);
    }

    private function uniqueCustomerUsername(string $name): string
    {
        $base = Str::slug($name ?: 'customer', '_') ?: 'customer';
        $username = $base;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $base . '_' . $counter;
            $counter++;
        }

        return $username;
    }

    private function nextSanadReference(int $id): string
    {
        return 'QUICK-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    private function expectedCompletion(Service $service)
    {
        preg_match('/\d+/', (string) $service->estimated_completion_time, $matches);
        $days = isset($matches[0]) ? max(1, (int) $matches[0]) : 3;
        return now()->addDays($days);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
      $auth_user = authSession();

         $user = auth()->user();
         $user->last_notification_seen = now();
         $user->save();

         if(count($user->unreadNotifications) > 0 ) {

           foreach($user->unreadNotifications as $notifications){

              if($notifications['data']['id'] == $id){

                 $notification = $user->unreadNotifications->where('id', $notifications['id'])->first();
                if($notification){
                     $notification->markAsRead();
                       }
                  }

             }

        }


        $bookingdata = Booking::with('bookingExtraCharge','payment')->myBooking()->find($id);


        $tabpage = 'info';
        if (empty($bookingdata)) {
            $msg = __('messages.not_found_entry', ['name' => __('messages.booking')]);
            return redirect(route('booking.index'))->withError($msg);
        }
        if (count($auth_user->unreadNotifications) > 0) {
            $auth_user->unreadNotifications->where('data.id', $id)->markAsRead();
        }

        $pageTitle = __('messages.view_form_title', ['form' => __('messages.booking')]);
        return view('booking.view', compact('pageTitle', 'bookingdata', 'auth_user', 'tabpage'));
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

        $bookingdata = Booking::myBooking()->find($id);

        $pageTitle = __('messages.update_form_title',['form'=> __('messages.booking')]);
        $relation = [
            'status' => BookingStatus::where('status',1)->orderBy('sequence','ASC')->get()->pluck('label', 'value'),
        ];
        return view('booking.edit', compact('pageTitle' ,'bookingdata' ,'auth_user' )+$relation);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(BookingUpdateRequest $request, $id)
    {
        if(demoUserPermission()){
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $data = $request->all();



        $data['date'] = isset($request->date) ? date('Y-m-d H:i:s',strtotime($request->date)) : date('Y-m-d H:i:s');
        $data['start_at'] = isset($request->start_at) ? date('Y-m-d H:i:s',strtotime($request->start_at)) : null;
        $data['end_at'] = isset($request->end_at) ? date('Y-m-d H:i:s',strtotime($request->end_at)) : null;


        $bookingdata = Booking::find($id);
        $paymentdata = Payment::where('booking_id',$id)->first();
        if($data['status'] === 'hold'){
            if($bookingdata->start_at == null && $bookingdata->end_at == null){
                $duration_diff = duration($data['start_at'] ,$data['end_at'] ,'in_minute');
                $data['duration_diff'] = $duration_diff;
            }else{
                if($bookingdata->status == $data['status']){
                    $booking_start_date = $bookingdata->start_at;
                    $request_start_date = $data['start_at'];
                    if($request_start_date > $booking_start_date){
                        $msg = __('messages.already_in_status',[ 'status' => $data['status'] ] );
                        return redirect()->back()->withSuccess($msg);
                    }
                }else{
                    $duration_diff = $bookingdata->duration_diff;
                    $new_diff = duration($bookingdata->start_at ,$bookingdata->end_at ,'in_minute');
                    $data['duration_diff'] = $duration_diff + $new_diff;
                }
            }
        }
        if($bookingdata->status != $data['status']) {
            $activity_type = 'update_booking_status';
        }
        if($data['status'] == 'cancelled'){
            $activity_type = 'cancel_booking';
        }
        $data['reason'] = isset($data['reason']) ? $data['reason'] : null;
        $old_status = $bookingdata->status;
        $bookingdata->update($data);
        if($old_status != $data['status']){
            $bookingdata->old_status = $old_status;

            $activity_data = [
                'activity_type' => $activity_type,
                'booking_id' => $id,
                'booking' => $bookingdata,
            ];
            $this->sendNotification($activity_data);

        }
        if($bookingdata->payment_id != null){
            $data['payment_status'] = isset($data['payment_status']) ? $data['payment_status'] : 'pending';
            $paymentdata->update($data);

            if($bookingdata->payment_id != null){
                $data['payment_status'] = isset($data['payment_status']) ? $data['payment_status'] : 'pending';
                $paymentdata->update($data);

                $activity_data = [
                    'activity_type' => 'payment_message_status',
                    'payment_status'=> $data['payment_status'],
                    'booking_id' => $id,
                    'booking' => $bookingdata,
                ];
                $this->sendNotification($activity_data);

            }
        }
        $message = __('messages.update_form',[ 'form' => __('messages.booking') ] );

        if($request->is('api/*')) {

            return comman_message_response($message);
		}

		return  redirect(route('booking.index'))->withSuccess($message);
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
        $booking = Booking::find($id);

        $msg = __('messages.msg_fail_to_delete',['item' => __('messages.booking')] );

        if($booking != '') {
            Notification::whereJsonContains('data->id',$booking->id)->delete();
            $booking->delete();
            $msg = __('messages.msg_deleted',['name' => __('messages.booking')] );
        }
        return comman_custom_response(['message'=> $msg, 'status' => true]);
    }

    public function  bookingAssignForm(Request $request){
        abort_unless(auth()->check() && auth()->user()->hasAnyRole(['admin', 'demo_admin', 'provider']), 403);
        $bookingdata = Booking::query()->myBooking()->findOrFail($request->id);
        $pageTitle = __('messages.assign_form_title',['form'=> __('messages.booking')]);
        return view('booking.assigned_form',compact('bookingdata','pageTitle'));
    }

    public function bookingAssigned(Request $request)
    {
        abort_unless(auth()->check() && auth()->user()->hasAnyRole(['admin', 'demo_admin', 'provider']), 403);
        $bookingdata = Booking::query()->myBooking()->findOrFail($request->id);
        $previousProviderId = $bookingdata->provider_id;
        $previousStatus = $bookingdata->status;
        $previousStage = $bookingdata->sanad_stage;

        $request->merge(['assignment_mode' => $request->assignment_mode ?: 'suggested']);
        $request->validate([
            'assignment_mode' => 'required|in:suggested,auto,manual',
            'assignment_reason' => 'nullable|string|max:2000',
            'partner_id' => 'nullable|integer|exists:users,id',
            'handyman_id' => 'nullable|array',
            'handyman_id.*' => 'integer|distinct|exists:users,id',
        ]);
        if (auth()->user()->hasRole('provider')) {
            $request->merge([
                'partner_id' => auth()->id(),
                'assignment_mode' => 'manual',
            ]);
        }
        if ($request->assignment_mode === 'manual' && empty($request->partner_id)) {
            return response()->json(['status' => false, 'message' => 'A Partner is required for manual assignment.'], 422);
        }
        if ($bookingdata->provider_id && $request->partner_id && (int) $bookingdata->provider_id !== (int) $request->partner_id && empty($request->assignment_reason)) {
            return response()->json(['status' => false, 'message' => __('messages.assignment_reason_required')], 422);
        }

        $partnerId = $request->partner_id ?: $bookingdata->provider_id;
        if ($request->partner_id && !User::where('id', $partnerId)->where('user_type', 'provider')->where('status', 1)->exists()) {
            return response()->json(['status' => false, 'message' => 'The selected Partner is not active.'], 422);
        }
        if ($partnerId && !User::where('id', $partnerId)->where('user_type', 'provider')->where('status', 1)->exists()) {
            $partnerId = null;
        }
        if (!$partnerId) {
            abort_unless(auth()->user()->hasAnyRole(['admin', 'demo_admin']), 403);
            $candidates = User::query()
                ->where('user_type', 'provider')
                ->where('status', 1)
                ->get();
            $partnerId = $candidates->map(function ($candidate) use ($bookingdata) {
                $servicePerformance = SanadPartnerServicePerformance::where('provider_id', $candidate->id)
                    ->where('service_id', $bookingdata->service_id)
                    ->first();
                $activeOrders = Booking::where('provider_id', $candidate->id)
                    ->whereNotIn('sanad_stage', ['completed', 'closed'])
                    ->where('status', '!=', 'cancelled')->count();
                $serviceExperience = $servicePerformance?->completed_orders ?? Booking::where('provider_id', $candidate->id)
                    ->where('service_id', $bookingdata->service_id)
                    ->whereIn('sanad_stage', ['completed', 'closed'])->count();
                $capacity = (int) ($candidate->sanad_daily_capacity ?: 0);
                $capacityScore = $capacity > 0 ? max(0, 100 - (($activeOrders / $capacity) * 100)) : 50;
                $averageCompletion = $servicePerformance?->average_completion_minutes ?? $candidate->sanad_average_completion_minutes;
                $qualityScore = $servicePerformance?->quality_score ?? $candidate->sanad_quality_score;
                $slaCompliance = $servicePerformance?->sla_compliance_rate ?? $candidate->sanad_sla_compliance_rate;
                $acceptanceRate = $servicePerformance?->acceptance_rate ?? $candidate->sanad_acceptance_rate;
                $cancellationRate = $servicePerformance?->cancellation_rate ?? $candidate->sanad_cancellation_rate;
                $speedScore = $averageCompletion
                    ? max(0, 100 - min(100, ((float) $averageCompletion / 1440) * 100))
                    : 50;
                $score = ($serviceExperience * 5)
                    + ((float) ($qualityScore ?: 0) * 0.25)
                    + ((float) ($slaCompliance ?: 0) * 0.2)
                    + ((float) ($acceptanceRate ?: 0) * 0.1)
                    + ($capacityScore * 0.2)
                    + ($speedScore * 0.1)
                    - ((float) ($cancellationRate ?: 0) * 0.1)
                    - ($activeOrders * 2);
                $candidate->assignment_score = $score;
                return $candidate;
            })->sortByDesc('assignment_score')->first();
            $partnerId = $partnerId?->id;
        }
        if (!$partnerId) {
            return response()->json(['status' => false, 'message' => 'No active Partner is available for assignment.'], 422);
        }

        $assigned_handyman_ids = [];
        $isPartnerTransfer = $previousProviderId && $partnerId && (int) $previousProviderId !== (int) $partnerId;

        if($bookingdata->handymanAdded()->count() > 0){
            $assigned_handyman_ids = $bookingdata->handymanAdded()->pluck('handyman_id')->toArray();
            $bookingdata->handymanAdded()->delete();
            $message = __('messages.transfer_to_handyman');
            $activity_type = 'transfer_booking';
        } else {
            $message = __('messages.assigned_to_handyman');
            $activity_type = 'assigned_booking';
        }

        $remove_notification_id = [];
        if($request->handyman_id != null) {
            $validEmployeeIds = User::query()
                ->whereIn('id', $request->handyman_id)
                ->where('user_type', 'handyman')
                ->where('provider_id', $partnerId)
                ->where('status', 1)
                ->pluck('id');
            if ($validEmployeeIds->count() !== count(array_unique($request->handyman_id))) {
                return response()->json(['status' => false, 'message' => 'Every selected Employee must belong to the assigned Partner and be active.'], 422);
            }
            foreach($validEmployeeIds as $handyman) {
                $assign_to_handyman = [
                    'booking_id'   => $bookingdata->id,
                    'handyman_id'  => $handyman
                ];
                $remove_notification_id = removeArrayValue($assigned_handyman_ids,$handyman);
                $bookingdata->handymanAdded()->insert($assign_to_handyman);
            }
        }

        if(!empty($remove_notification_id)){
            $search = "id".'":'.$bookingdata->id;

            Notification::whereIn('notifiable_id',$remove_notification_id)
                ->whereJsonContains('data->id',$bookingdata->id)
                ->delete();
        }

        $bookingdata->status = 'accept';
        $bookingdata->provider_id = $partnerId;
        $bookingdata->assignment_mode = $request->assignment_mode;
        $bookingdata->assignment_reason = $request->assignment_reason;
        $bookingdata->assigned_by = auth()->id();
        $bookingdata->assigned_at = now();
        if ($isPartnerTransfer) {
            $bookingdata->sanad_stage = 'assigned_to_partner';
            $bookingdata->chat_owner_type = 'partner_team';
            $bookingdata->chat_owner_user_id = $partnerId;
            $bookingdata->chat_assigned_by = auth()->id();
            $bookingdata->chat_assigned_at = now();
            $bookingdata->chat_assignment_note = $request->assignment_reason;
            $bookingdata->sanadWorkflowStages()->delete();
        }
        $bookingdata->save();

        if ($isPartnerTransfer) {
            SanadRequestAction::create([
                'booking_id' => $bookingdata->id,
                'actor_id' => auth()->id(),
                'actor_role' => optional(auth()->user())->user_type,
                'action' => 'transfer_partner',
                'previous_status' => $previousStatus,
                'current_status' => $bookingdata->status,
                'previous_stage' => $previousStage,
                'current_stage' => $bookingdata->sanad_stage,
                'reason' => $request->assignment_reason,
                'internal_note' => 'Order transferred to a different partner. Documents, chats, add-ons, payments, and request history remain attached to the same order.',
                'metadata' => [
                    'previous_provider_id' => $previousProviderId,
                    'new_provider_id' => $partnerId,
                ],
            ]);
        }

        $activity_data = [
            'activity_type' => $activity_type,
            'booking_id' => $bookingdata->id,
            'booking' => $bookingdata,
        ];
        $this->sendNotification($activity_data);

        $message = __('messages.save_form',[ 'form' => __('messages.booking') ] );
        if($request->is('api/*')) {
            return comman_message_response($message);
		}

        return response()->json(['status' => true,'event' => 'callback' , 'message' => $message]);
    }

    public function action(Request $request)
    {
        $id = $request->id;
        $type = $request->type;
        $booking_data = Booking::withTrashed()->where('id',$id)->first();
        $msg = __('messages.not_found_entry',['name' => __('messages.booking')] );
        if($request->type === 'restore'){
            if($booking_data != ''){
                $booking_data->restore();
                $msg = __('messages.msg_restored',['name' => __('messages.booking')] );
            }
        }
        if($request->type === 'forcedelete'){
            $booking_data->forceDelete();
            $msg = __('messages.msg_forcedelete',['name' => __('messages.booking')] );
        }

        return comman_custom_response(['message'=> $msg , 'status' => true]);
    }
    public function bookingDetails(Request $request, $id)
    {
        $auth_user = authSession();
        $providerdata = User::with('providerBooking')->where('user_type', 'provider')->where('id', $id)->first();
        $earningData = array();
        foreach ($providerdata->providerBooking as $booking) {
            $booking_id = $booking->id;
            $provider_name = optional($booking->provider)->display_name ?? '-';
            $provider_image = getSingleMedia(optional($booking->provider),'profile_image', null);
            $provider_contact = optional($booking->provider)->contact_number ?? '-';
            $provider_email = optional($booking->provider)->email ?? '-';
            $amount = $booking->amount;
            $payment_status = optional($booking->payment)->payment_status ?? '-';
            $start_at = $booking->start_at;
            $end_at = $booking->end_at;
            $earningData[] = [
                'provider_id' => $providerdata->id,
                'booking_id' => $booking->id,
                'provider_name' => $provider_name,
                'provider_image' => $provider_image,
                'provider_email' => $provider_email,
                'provider_contact' => $provider_contact,
                'amount' => $amount,
                'payment_status' => $payment_status,
                'start_at' => $start_at,
                'end_at' => $end_at,
            ];
        }
        if ($request->ajax()) {
            return Datatables::of($earningData)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '-';
                    $booking_id = $row['booking_id'];
                    $btn = "<a href=" . route('booking.show', $booking_id) . "><i class='fas fa-eye'></i></a>";
                    return $btn;
                })
                ->editColumn('provider_name' , function ($row){
                    return view('booking.provider', compact('row'));
                })
                ->editColumn('payment_status' , function ($row){
                    $payment_status = $row['payment_status'];
                    if($payment_status == 'pending'){
                        $status = '<span class="badge badge-danger">'.__('messages.pending').'</span>';
                    }else{
                        $status = '<span class="badge badge-success">'.__('messages.paid').'</span>';
                    }
                    return  $status;
                })
                ->editColumn('start_at', function ($row) {
                    if (is_array($row)) {
                        $row = (object)$row;
                    }
                    $startAt = isset($row->start_at) ? $row->start_at : null;
                    if ($startAt !== null) {
                        $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
                        $datetime = $sitesetup ? json_decode($sitesetup->value) : null;
                        $date = optional($datetime)->date_format && optional($datetime)->time_format
                        ? date(optional($datetime)->date_format, strtotime($startAt)) . ' / ' . date(optional($datetime)->time_format, strtotime($startAt))
                        : $startAt;
                        return $date;
                    }
                    return null;
                })
                ->editColumn('end_at', function ($row) {
                    if (is_array($row)) {
                        $row = (object)$row;
                    }
                    $endAt = isset($row->end_at) ? $row->end_at : null;
                    if ($endAt !== null) {
                        $sitesetup = Setting::where('type', 'site-setup')->where('key', 'site-setup')->first();
                        $datetime = $sitesetup ? json_decode($sitesetup->value) : null;
                        $date = optional($datetime)->date_format && optional($datetime)->time_format
                        ? date(optional($datetime)->date_format, strtotime($endAt)) . ' / ' . date(optional($datetime)->time_format, strtotime($endAt))
                        : $endAt;
                        return $date;
                    }
                    return null;
                })
                ->editColumn('amount' , function ($row){
                    return $row['amount'] ? getPriceFormat($row['amount']) : '-';
                })
                ->rawColumns(['action','payment_status','amount','check'])
                ->make(true);
        }
        if (empty($providerdata)) {
            $msg = __('messages.not_found_entry', ['name' => __('messages.provider')]);
            return redirect(route('provider.index'))->withError($msg);
        }
        $pageTitle = __('messages.booking');
        return view('booking.details', compact('pageTitle', 'earningData', 'auth_user', 'providerdata'));
    }
    public function bookingstatus(Request $request, $id)
    {
        $tabpage = $request->tabpage;
        $auth_user = authSession();
        $user_id = $auth_user->id;
        $user_data = User::find($user_id);
        $bookingdata = Booking::with('handymanAdded', 'payment', 'bookingExtraCharge', 'bookingAddonService')->myBooking()->find($id);
        switch ($tabpage) {
            case 'info':
                $data  = view('booking.' . $tabpage, compact('user_data', 'tabpage', 'auth_user', 'bookingdata'))->render();
                break;
            case 'status':
                $data  = view('booking.' . $tabpage, compact('user_data', 'tabpage', 'auth_user', 'bookingdata'))->render();
                break;
            default:
                $data  = view('booking.' . $tabpage, compact('tabpage', 'auth_user', 'bookingdata'))->render();
                break;
        }
        return response()->json($data);
    }
    public function createPDF($id)
    {
        $data =AppSetting::take(1)->first();
    $bookingdata = Booking::with('handymanAdded', 'payment', 'bookingExtraCharge')->myBooking()->find($id);
    $pdf = Pdf::loadView('booking.invoice',['bookingdata'=>$bookingdata ,'data'=> $data]);
    return $pdf->download('invoice.pdf');
    }

    public function updateStatus(Request $request)
    {

        switch ($request->type) {
            case 'payment':
                $data = Payment::where('booking_id',$request->bookingId)->update(['payment_status'=>$request->status]);
                break;
                default:

                $data = Booking::find($request->bookingId)->update(['status'=>$request->status]);
                break;
        }

        return comman_custom_response(['message'=> 'Status Updated' , 'status' => true]);
    }

    public function saveBookingRating(Request $request)
    {
        $rating_data = $request->all();
        $result = BookingRating::updateOrCreate(['id' => $request->id], $rating_data);

        $message = __('messages.update_form',[ 'form' => __('messages.rating') ] );
		if($result->wasRecentlyCreated){
			$message = __('messages.save_form',[ 'form' => __('messages.rating') ] );
		}

        return  redirect()->back()->withSuccess($message);
    }
    public function getPaymentMethod(Request $request)
    {
        $data = $request->all();
        $data['datetime'] = now();

        $data['payment_status'] = 'failed';

        $payment_data = Payment::where('booking_id', $data['booking_id'])->first();

        if (!empty($payment_data)) {
            $payment_data->update($data);
        } else {
            $payment_data = Payment::create($data);
        }
        $sitesetup = Setting::where('type','site-setup')->where('key', 'site-setup')->first();
        $sitesetupdata = $sitesetup ? json_decode($sitesetup->value, true) : null;

        $country_id = $sitesetupdata['default_currency'] ?? null;
        $country = Country::find($country_id);

        $data['currency_code'] = $country ? $country->currency_code : "USD";

        switch ($data['payment_type']) {
            case 'stripe':
                $data['payment_geteway_data'] = getPaymentMethodkey($data['payment_type']);
                break;

            default:

                break;
        }

        return comman_custom_response($data);
    }


     public function createStripePayment(Request $request){

        $data = $request->all();

        $checkout_session = getstripepayments($data);

        if(isset($checkout_session['message'])) {

            return comman_custom_response($checkout_session);

        }else{
             Payment::where('booking_id', $data['booking_id'])->update(['other_transaction_detail' => $checkout_session['id']]);

             return comman_custom_response($checkout_session);
        }

     }

     public function saveStripePayment(Request $request, $id){

        $type = $request->type;

        $result = Payment::where('booking_id',$id)->first();

        $stripe_session_id= $result->other_transaction_detail;
        $payment_type= $result->payment_type;

        $session_object=getstripePaymnetId($stripe_session_id,$payment_type);

        if($session_object['payment_intent'] !== '' && $session_object['payment_status'] == 'paid') {

           $result->txn_id =$session_object['payment_intent'];

            if($type == 'advance_payment'){

                $result->payment_status='advanced_paid';
            }else{

                $result->payment_status='paid';
            }

        };

        $result->update();

        $booking = Booking::find($id);
        if(!empty($result) && $result->payment_status == 'advanced_paid'){
            $booking->advance_paid_amount  = $result->total_amount;
            $booking->status  = 'pending';
        }
        $booking->payment_id = $result->id;
        $booking->update();

        $activity_data = [
            'activity_type' => 'payment_message_status',
            'payment_status'=>  str_replace("_"," ",ucfirst($result->payment_status)),
            'booking_id' => $booking->id,
            'booking' => $booking,
        ];
        $this->sendNotification($activity_data);

        return redirect('/booking-list');

     }
     public function package_service_detail(){
         $packages = ServicePacakgeSubscription::where('user_id',auth()->user()->id)->get();
         return view('booking.package_booking',compact('packages'));
}
    public function package_service_show($id){
        $package = ServicePacakgeSubscription::findOrFail($id);
        $package_service = package_service_booking::where([
            'subscription_id' => $id,

        ])->get();
        return view('booking.package_booking_show', compact('package','package_service'));
    }
    public function package_service_renew_data($id){
        $package = ServicePacakgeSubscription::findOrFail($id)->toArray();
        $package_service = ServicePacakgeServiceSubscription::where('subscription_id',$id)->get();

        $packs_data = ServicePackage::where('id',$package['package_id'])->first();
        $package['end_at'] = Carbon::now()->addDays($packs_data->duration)->toDateString();
        $package['start_at'] =Carbon::now()->toDateString() ;


        $ServicePacakgeSubscription = ServicePacakgeSubscription::create($package);
        foreach ($package_service as $item){

            $item['subscription_id'] = $ServicePacakgeSubscription->id;

            ServicePacakgeServiceSubscription::create($item->toArray());
        }
        return redirect()->back()->with('success', 'Package Renew');
    }
    public function add_new_car($id,Request $request){
        $package = ServicePacakgeSubscription::findOrFail($id);

if ($package->car_number > $package->Cars->count()) {
    # code...

        SubscriptionCar::create([
            'car_number'=>$request->car_number,
            'car_year'=>$request->car_year,
            'car_model'=>$request->car_model,
            'subscription_id'=>$id,
        ]);
        return redirect()->back()->with('success','Car added');

            }
        return redirect()->back()->with('error','u cant add more car');

    }
    public function add_new_address($id,Request $request){
        $package = ServicePacakgeSubscription::findOrFail($id);


if ( in_array($package->package_type,['Breaks','specific_place']) && count($package->address_data) != 1) {
    # code...

    SubscriptionAddress::create([
            'region_id' => $package->region_id,
            'city_id' => $package->city_id,
            'area_id' => $request->area_id,
            'address'=>$request->address,
            'subscription_id'=>$id,
        ]);
        return redirect()->back()->with('success','Address added');

            }
        return redirect()->back()->with('error','u cant add more Address');

    }

    public function booking_service_data($serviec_id, $booking_id){
        $package = ServicePacakgeSubscription::findOrFail($booking_id);
        $ServicePackage  = ServicePacakgeServiceSubscription::where([
            'service_id' => $serviec_id,
            'subscription_id' => $package->id,
        ])->first();

        if (!$ServicePackage){
            return redirect()->back()->with('error','u cant found');

        }


        if ($ServicePackage->count == 0){
            return redirect()->back()->with('error','U Cant Request More now');

        }

        if (in_array($package->package_type,['Breaks'])){


        }else{
        if ($package->Cars->count() == 0 && in_array($package->package_type,['single','family'])){
            return redirect()->back()->with('error','Please add car first');

        }

        return  view('booking.package_booking_cars', compact('ServicePackage', 'package'));

        }
}
        public function booking_car_data($car_id,$booking_id,$serviec_id,Request $request){
        $time = Carbon::parse($request->booking_date)->format('Y-m-d H:i');

            $package = ServicePacakgeSubscription::findOrFail($booking_id);
        $ServicePackage  = ServicePacakgeServiceSubscription::where([
            'service_id' => $serviec_id,
            'subscription_id' => $package->id,
        ])->first();

        if (!$ServicePackage){
            return redirect()->back()->with('error','u cant found');

        }


        if ($ServicePackage->count == 0){
            return redirect()->back()->with('error','U Cant Request More now');

        }
        if ($package->Cars->count() == 0 && in_array($package->package_type,['single','family'])){
            return redirect()->back()->with('error','Please add car first');

        }
            $get_Provider = ProviderRegion::where('region_id',$package->region_id)->inRandomOrder()->first();

        $package_service = package_service_booking::create([
            'service_id' => $serviec_id,
            'subscription_id' => $booking_id,
            'date' => $time,
            'provider_id' => @$get_Provider->provider_id,

            'car_id'=>$car_id,
            'user_id' => auth()->user()->id

        ]);
            return redirect(route('booking.package_service_show',$package->id))->with('success','Booking Successfully Please Wait for Approval From The Administrator');


        }
        public function booking_breaks_data($booking_id,$serviec_id,Request $request){
        $time = Carbon::parse($request->booking_date)->format('Y-m-d H:i');

            $package = ServicePacakgeSubscription::findOrFail($booking_id);
        $ServicePackage  = ServicePacakgeServiceSubscription::where([
            'service_id' => $serviec_id,
            'subscription_id' => $package->id,
        ])->first();

        if (!$ServicePackage){
            return redirect()->back()->with('error','u cant found');

        }
            if (count($package->address_data) == 0){
                return redirect()->back()->with('error','Please add address');

            }

        if ($ServicePackage->count == 0){
            return redirect()->back()->with('error','U Cant Request More now');

        }

            $package->date_breaks = $time ;
            $package->save();
            $get_Provider = ProviderRegion::where('region_id',$package->region_id)->inRandomOrder()->first();

        $package_service = package_service_booking::create([
            'service_id' => $serviec_id,
            'subscription_id' => $booking_id,
            'provider_id' => @$get_Provider->provider_id,

            'date' => $time,
            'user_id' => auth()->user()->id

        ]);
            return redirect(route('booking.package_service_show',$package->id))->with('success','Booking Successfully Please Wait for Approval From The Administrator');


        }
        public function booking_breaks_data_with_out_Data($booking_id,$serviec_id,Request $request){

            $package = ServicePacakgeSubscription::findOrFail($booking_id);
        $ServicePackage  = ServicePacakgeServiceSubscription::where([
            'service_id' => $serviec_id,
            'subscription_id' => $package->id,
        ])->first();

        if (!$ServicePackage){
            return redirect()->back()->with('error','u cant found');

        }
            if (count($package->address_data) == 0){
                return redirect()->back()->with('error','Please add address');

            }

        if ($ServicePackage->count == 0){
            return redirect()->back()->with('error','U Cant Request More now');

        }
        $get_Provider = ProviderRegion::where('region_id',$package->region_id)->inRandomOrder()->first();


        $package_service = package_service_booking::create([
            'service_id' => $serviec_id,
            'subscription_id' => $booking_id,
            'date' => $package->date_breaks,
            'provider_id' => @$get_Provider->provider_id,
            'user_id' => auth()->user()->id,
            'address_id'=>@$package->address_data->first()->id,

        ]);
            return redirect(route('booking.package_service_show',$package->id))->with('success','Booking Successfully Please Wait for Approval From The Administrator');


        }


    public function ChangeData ($booking_id,Request  $request){
        $package_service_booking = package_service_booking::orderBy('id','DESC')->findOrFail($booking_id);

        $package_service_booking->status = BookingEnums::reschedule;
        $package_service_booking->date = $request->booking_date;
        $package_service_booking->save();
        return redirect()->back()->with('success','Change Date Done');


    }

    private function bookingExportQuery(Request $request)
    {
        $query = Booking::query()
            ->myBooking()
            ->with(['customer', 'service', 'provider', 'payment']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery->where('sanad_reference', 'like', "%{$search}%")
                    ->orWhere('id', $search)
                    ->orWhereHas('service', fn ($serviceQuery) => $serviceQuery->where('name', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('display_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('provider', fn ($providerQuery) => $providerQuery->where('display_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        if (auth()->user()->hasAnyRole(['admin', 'demo_admin'])) {
            $query->withTrashed();
        }

        return $query->latest();
    }

    private function bookingSummary($bookings): array
    {
        return [
            'total_orders' => $bookings->count(),
            'unassigned_orders' => $bookings->whereNull('provider_id')->count(),
            'high_priority_orders' => $bookings->where('sanad_priority', 'high')->count(),
            'completed_orders' => $bookings->whereIn('sanad_stage', ['completed', 'closed'])->count(),
            'cancelled_orders' => $bookings->where('status', 'cancelled')->count(),
            'pending_orders' => $bookings->whereIn('sanad_stage', ['submitted', 'pending_review'])->count(),
            'in_progress_orders' => $bookings->whereIn('sanad_stage', ['assigned_to_partner', 'assigned_to_employee', 'in_progress'])->count(),
            'overdue_orders' => $bookings->filter(fn ($booking) => $booking->expected_completion_at && $booking->expected_completion_at->isPast() && !in_array($booking->sanad_stage, ['completed', 'closed']))->count(),
            'total_value' => $bookings->sum(fn ($booking) => (float) ($booking->total_amount ?: $booking->amount)),
        ];
    }
}
