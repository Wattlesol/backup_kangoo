<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\PaymentHistory;
use App\Models\Payment;
use App\Models\ProviderPayout;
use App\Models\Setting;
use App\Models\Wallet;
use Yajra\DataTables\DataTables;

class PaymentController extends Controller
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
        ];
        $pageTitle = __('messages.list_form_title',['form' => __('messages.payment')] );
        $assets = ['datatable'];
        $sanadPaymentSummary = $this->sanadPaymentSummary();

        return view('payment.index', compact('pageTitle','assets','filter','sanadPaymentSummary'));
    }

    private function sanadPaymentSummary()
    {
        $user = auth()->user();
        $paymentQuery = Payment::query()->myPayment();
        $paidPaymentQuery = (clone $paymentQuery)->where('payment_status', 'paid');
        $pendingStatuses = ['pending', 'advanced_paid', 'pending_by_admin'];
        $refundStatuses = ['refund', 'refunded'];
        $paidPayments = (clone $paidPaymentQuery)->with('booking.provider.providertype')->get();
        $payoutQuery = ProviderPayout::query()->myPayout();
        $walletQuery = Wallet::query();

        if (! $this->isSanadFinanceAdmin($user)) {
            $walletQuery->where('user_id', $user->id);
        }

        $paidAmount = $paidPayments->sum('total_amount');
        $settledAmount = (clone $payoutQuery)->whereIn('status', ['paid', 'completed', 'approved'])->sum('amount') ?? 0;
        $commissionAmount = $this->sanadPlatformCommissionAmount($paidPayments);
        $vatAmount = $paidPayments->sum(function ($payment) {
            return optional($payment->booking)->final_total_tax ?? 0;
        });
        $pendingAmount = (clone $paymentQuery)->whereIn('payment_status', $pendingStatuses)->sum('total_amount') ?? 0;
        $refundAmount = (clone $paymentQuery)->whereIn('payment_status', $refundStatuses)->sum('total_amount') ?? 0;
        $pendingSettlement = max(($paidAmount - $settledAmount), 0);

        return [
            'total_payments' => (clone $paymentQuery)->count(),
            'paid_payments' => (clone $paymentQuery)->where('payment_status', 'paid')->count(),
            'pending_payments' => (clone $paymentQuery)->whereIn('payment_status', $pendingStatuses)->count(),
            'failed_payments' => (clone $paymentQuery)->where('payment_status', 'failed')->count(),
            'cash_payments' => (clone $paymentQuery)->where('payment_type', 'cash')->count(),
            'total_amount' => (clone $paymentQuery)->sum('total_amount') ?? 0,
            'paid_amount' => $paidAmount,
            'pending_amount' => $pendingAmount,
            'customer_payments' => (clone $paymentQuery)->whereNotNull('customer_id')->count(),
            'settlements_count' => (clone $payoutQuery)->count(),
            'settled_amount' => $settledAmount,
            'pending_settlement' => $pendingSettlement,
            'platform_commission' => $commissionAmount,
            'vat_amount' => $vatAmount,
            'refunds_count' => (clone $paymentQuery)->whereIn('payment_status', $refundStatuses)->count(),
            'refund_amount' => $refundAmount,
            'wallet_current_balance' => (clone $walletQuery)->sum('amount') ?? 0,
            'wallet_pending_balance' => $pendingAmount,
            'wallet_released_balance' => $settledAmount,
            'upcoming_settlement' => $pendingSettlement,
            'recent_settlements' => (clone $payoutQuery)->with('providers')->orderBy('id', 'desc')->take(5)->get(),
            'recent_transactions' => (clone $paymentQuery)->with('customer', 'booking.service')->orderBy('id', 'desc')->take(5)->get(),
            'role_scope' => $this->sanadFinanceRoleScope($user),
        ];
    }

    private function sanadFinanceRoleScope($user)
    {
        if ($this->isSanadFinanceAdmin($user)) {
            return [
                'label' => 'Admin finance scope',
                'description' => 'Admins can review all customer payments, invoices, refunds, wallet balances, partner settlements, VAT, and platform commission. Admin-only bulk actions are enabled.',
                'can_bulk_manage' => true,
            ];
        }

        if ($this->isSanadFinancePartner($user)) {
            return [
                'label' => 'Partner finance scope',
                'description' => 'Partners see only payments and settlements connected to their assigned Quick requests. Admin-only delete and bulk actions are hidden.',
                'can_bulk_manage' => false,
            ];
        }

        if ($this->isSanadFinanceEmployee($user)) {
            return [
                'label' => 'Employee finance scope',
                'description' => 'Employees see only payment context for assigned Sanad work when finance access is granted. Partner settlement and admin wallet controls are not exposed.',
                'can_bulk_manage' => false,
            ];
        }

        return [
            'label' => 'Customer finance scope',
            'description' => 'Customers see only their own Quick payment, invoice, refund, and wallet context. Internal partner settlement and commission data are not exposed.',
            'can_bulk_manage' => false,
        ];
    }

    private function isSanadFinanceAdmin($user)
    {
        return $user->hasAnyRole(['admin', 'demo_admin']) || in_array($user->user_type, ['admin', 'demo_admin'], true);
    }

    private function isSanadFinancePartner($user)
    {
        return $user->hasRole('provider') || $user->user_type === 'provider';
    }

    private function isSanadFinanceEmployee($user)
    {
        return $user->hasRole('handyman') || $user->user_type === 'handyman';
    }

    private function sanadPlatformCommissionAmount($payments)
    {
        return $payments->sum(function ($payment) {
            $booking = $payment->booking;
            $providerType = optional(optional($booking)->provider)->providertype;
            $commission = (float) optional($providerType)->commission;
            $commissionType = optional($providerType)->type;

            if ($commission <= 0) {
                return 0;
            }

            if ($commissionType === 'percent') {
                return ((float) $payment->total_amount) * $commission / 100;
            }

            return $commission;
        });
    }

    public function cashIndex($id)
    {
        $pageTitle = __('messages.list_form_title',['form' => __('messages.cash_history')] );
        $auth_user = authSession();
        $assets = ['datatable'];
        return view('paymenthistory.index', compact('pageTitle','assets','auth_user','id'));
    }

     public function paymenthistory_index_data(DataTables $datatable,$id){
        $query = PaymentHistory::where('payment_id',$id);

        if (auth()->user()->hasAnyRole(['admin'])) {
            $query->newquery();
        }

        return $datatable  ->eloquent($query)
        ->editColumn('booking_id', function($payment) {
            return ($payment->booking_id != null && isset($payment->booking->service)) ? $payment->booking->service->name :'-';
        })
        ->filterColumn('booking_id',function($query,$keyword){
            $query->whereHas('booking.service',function ($q) use($keyword){
                $q->where('name','like','%'.$keyword.'%');
            });
        })
        ->editColumn('customer_id', function($payment) {
            return ($payment->booking != null && isset($payment->booking->customer)) ? $payment->booking->customer->display_name : '-';
        })
        ->filterColumn('customer_id', function ($query, $keyword) {
            $query->whereHas('booking', function ($q) use ($keyword) {
                $q->whereHas('customer', function ($c) use ($keyword) {
                    $c->where('display_name', 'like', '%' . $keyword . '%');
                });
            });
        })
        ->addIndexColumn()
        ->toJson();
     }

    public function cashDatatable(Request $request){
        $filter = [
            'status' => $request->status,
        ];
        $pageTitle = __('messages.list_form_title',['form' => __('messages.cash_payment')] );
        $assets = ['datatable'];
        return view('payment.cash', compact('pageTitle','assets','filter'));
    }

    public function cash_index_data(DataTables $datatable,Request $request)
    {
        $query = Payment::query()->myPayment();
        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
        }
        if (auth()->user()->hasAnyRole(['admin'])) {
            $query= $query->orderBy('id','desc')->where('payment_type','cash')->newQuery();
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                if (! $this->isSanadFinanceAdmin(auth()->user())) {
                    return '';
                }

                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$row->id.'"  name="datatable_ids[]" value="'.$row->id.'" onclick="dataTableRowCheck('.$row->id.')">';
            })
            ->editColumn('id', function($payment) {
                if(isset($payment->booking) && $payment->booking !== null){
                    return '<a class="btn-link btn-link-hover" href='.route('booking.show', $payment->booking->id).'> #'.$payment->booking->id.'</a>';
                }
            })
            ->editColumn('booking_id', function($payment) {
                if($payment->customer_id != null && isset($payment->booking->service)){
                    return $payment->booking->service->name;
                }else{
                    return '-';
                }
            })
            ->filterColumn('booking_id',function($query,$keyword){
                $query->whereHas('booking.service',function ($q) use($keyword){
                    $q->where('name','like','%'.$keyword.'%');
                });
            })
            ->editColumn('customer_id', function ($payment) {
                return view('payment.user', compact('payment'));
            })
            ->filterColumn('customer_id',function($query,$keyword){
                $query->whereHas('customer',function ($q) use($keyword){
                    $q->where('display_name','like','%'.$keyword.'%');
                });
            })
            ->editColumn('datetime' , function ($query){
                $sitesetup = Setting::where('type','site-setup')->where('key', 'site-setup')->first();
                $datetime = json_decode($sitesetup->value);
                $date = date("$datetime->date_format / $datetime->time_format", strtotime($query->datetime));
                return $date;
            })
            ->editColumn('total_amount', function($payment) {
                return getPriceFormat($payment->total_amount);
            })
            ->editColumn('history', function($payment) {
                $action = '<a class="btn-link btn-link-hover"   href='.route('cash.index',$payment->id).'>View</a>';
                return $action;
            })
            ->editColumn('status', function($payment) {
                return last_status($payment->id);
            })
            ->filterColumn('status',function($query,$keyword){
                $query->whereHas('paymentHistory',function ($q) use($keyword){
                    $q->where('status','like','%'.$keyword.'%');
                });
            })

            ->editColumn('action', function($payment) {
                $action = set_admin_approved_cash($payment->id). ' ' .view('payment.cashaction',compact('payment'))->render();
                return $action;

            })
            ->addIndexColumn()->rawColumns(['check','history','action','id','status'])
            ->toJson();
    }



    public function index_data(DataTables $datatable,Request $request)
    {
        $query = Payment::query()->myPayment();
        $filter = $request->filter;

        if (isset($filter)) {
            if (isset($filter['column_status'])) {
                $query->where('status', $filter['column_status']);
            }
        }
        if (auth()->user()->hasAnyRole(['admin'])) {
            $query->newQuery();
        }

        return $datatable->eloquent($query)
            ->addColumn('check', function ($row) {
                return '<input type="checkbox" class="form-check-input select-table-row"  id="datatable-row-'.$row->id.'"  name="datatable_ids[]" value="'.$row->id.'" onclick="dataTableRowCheck('.$row->id.')">';
            })
        ->editColumn('booking_id', function($query) {


            return ($query->customer_id != null &&isset($query->booking->service)) ? $query->booking->service->name :'-';
        })
        ->filterColumn('booking_id',function($query,$keyword){
            $query->whereHas('booking.service',function ($q) use($keyword){
                $q->where('name','like','%'.$keyword.'%');
            });
        })
        ->editColumn('customer_id', function ($payment) {
            return view('payment.user', compact('payment'));
        })
        ->filterColumn('customer_id',function($query,$keyword){
            $query->whereHas('customer',function ($q) use($keyword){
                $q->where('display_name','like','%'.$keyword.'%');
            });
        })
        ->editColumn('datetime' , function ($query){
            $sitesetup = Setting::where('type','site-setup')->where('key', 'site-setup')->first();
            $datetime = json_decode($sitesetup->value);
            $date = date("$datetime->date_format / $datetime->time_format", strtotime($query->datetime));
            return $date;
        })
        ->editColumn('payment_status', function($query) {
            $payment = $query->payment_status;
            if($payment !== null){
                $payment_status = '<span class="text-center badge badge-primary1">'.str_replace('_'," ",ucfirst($payment)).'</span>';
            }else{
                $payment_status = '<span class="text-center d-block">-</span>';
            }
            return $payment_status;
        })


        ->editColumn('total_amount', function($query) {
            return getPriceFormat($query->total_amount);
        })
        ->addColumn('action', function($payment){
            return view('payment.action',compact('payment'))->render();
        })
        ->addIndexColumn()
        ->rawColumns(['action','check','payment_status'])
            ->toJson();
    }

    /* bulck action method */
    public function bulk_action(Request $request)
    {
        $ids = explode(',', $request->rowIds);

        $actionType = $request->action_type;

        $message = 'Bulk Action Updated';
        switch ($actionType) {
            case 'change-status':
                $branches = Payment::whereIn('id', $ids)->update(['status' => $request->status]);
                $message = 'Bulk Payment Status Updated';
                break;

            case 'delete':
                Payment::whereIn('id', $ids)->delete();
                $message = 'Bulk Payment Deleted';
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
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
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
        if(demoUserPermission()){
            return  redirect()->back()->withErrors(trans('messages.demo_permission_denied'));
        }
        $document = Payment::find($id);
        $msg= __('messages.msg_fail_to_delete',['name' => __('messages.payment')] );

        if( $document!='') {

            $document->delete();
            $msg= __('messages.msg_deleted',['name' => __('messages.payment')] );
        }
        if(request()->is('api/*')) {
            return comman_message_response($msg);
		}
        return comman_custom_response(['message'=> $msg, 'status' => true]);
    }

    public function cashApprove($id){
        // $admin = AppSetting::first();
        $sitesetup = Setting::where('type','site-setup')->where('key', 'site-setup')->first();
        $admin = json_decode($sitesetup->value);
        $paymentdata = Payment::where('id',$id)->first();
        $parent_payment_history = PaymentHistory::where('status','pending_by_admin')
        ->where('payment_id',$id)->first();



        $payment_history = [
            'payment_id' => $id,
            'booking_id' => $paymentdata->booking_id,
            'action' => config('constant.PAYMENT_HISTORY_ACTION.ADMIN_APPROVED_CASH'),
            'type' => $parent_payment_history->payment_type,
            'sender_id' => $parent_payment_history->sender_id,
            'receiver_id' => admin_id(),
            'total_amount' => $paymentdata->total_amount,
            'text' =>  __('messages.cash_approved',['amount' => getPriceFormat((float)$paymentdata->total_amount),'name' => get_user_name(admin_id())]),
            'status' => config('constant.PAYMENT_HISTORY_STATUS.APPROVED_ADMIN'),
            'parent_id' => $parent_payment_history->parent_id
        ];

        date_default_timezone_set( $admin->time_zone ?? 'UTC');
        $payment_history['datetime'] = date('Y-m-d H:i:s');

        if(!empty($paymentdata->txn_id)){
            $payment_history['txn_id'] =$paymentdata->txn_id;
        }
        if(!empty($paymentdata->other_transaction_detail)){
            $payment_history['other_transaction_detail'] =$paymentdata->other_transaction_detail;
        }
        $res = PaymentHistory::create($payment_history);

        $parent_record = PaymentHistory::where('parent_id',$parent_payment_history->parent_id)->first();

        $parent_payment_history->status = 'approved_by_admin';
        $parent_payment_history->update();

        $parent_record->status = 'approved_by_admin';
        $parent_record->update();


        $paymentdata->payment_status = 'paid';
        $paymentdata->update();

        $msg = __('messages.approve_successfully');
        return redirect()->back()->withSuccess($msg);
    }

}
