<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Booking;
use App\Models\Wallet;
use App\Models\PaymentHistory;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Http\Resources\API\PaymentResource;
use App\Http\Resources\API\PaymentHistoryResource;
use App\Http\Resources\API\GetCashPaymentHistoryResource;
use App\Traits\NotificationTrait;
use App\Http\Resources\API\PaymentGatewayResource;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    use NotificationTrait;

    public function savePayment(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|integer',
            'payment_type' => 'required|string|max:50',
            'payment_status' => 'required|string|in:pending,failed,paid,advanced_paid,pending_by_admin',
            'txn_id' => 'nullable|string|max:255',
            'other_transaction_detail' => 'nullable',
            'datetime' => 'nullable|date',
            'advance_payment_amount' => 'nullable|numeric|min:0',
        ]);
        $booking = Booking::query()->myBooking()->findOrFail($validated['booking_id']);
        if ($validated['payment_type'] === 'wallet') {
            abort_unless((int) auth()->id() === (int) $booking->customer_id, 403);
        }

        $data = collect($validated)->only([
            'booking_id',
            'payment_type',
            'payment_status',
            'txn_id',
            'other_transaction_detail',
        ])->all();
        $data['customer_id'] = $booking->customer_id;
        $data['total_amount'] = (float) $booking->total_amount;
        $data['datetime'] = isset($request->datetime) ? date('Y-m-d H:i:s',strtotime($request->datetime)) : date('Y-m-d H:i:s');
        $wallet = null;
        if ($validated['payment_type'] === 'wallet') {
            $wallet = Wallet::where('user_id', $booking->customer_id)->first();
            if (!$wallet || (float) $wallet->amount < $data['total_amount']) {
                return comman_message_response(__('messages.wallent_balance_error'), 422);
            }
        }
        $result = Payment::create($data);
        if(!empty($result) && $result->payment_status == 'advanced_paid'){
            $booking->advance_paid_amount  = min((float) ($validated['advance_payment_amount'] ?? 0), (float) $booking->total_amount);
            $booking->status  = 'pending';
        }
        $booking->payment_id = $result->id;
        $booking->update();
        $status_code = 200;
        if($request->payment_type == 'wallet'){
            if($wallet !== null){
                $wallet_amount = $wallet->amount;
                if($wallet_amount >= $data['total_amount']){
                    $wallet->amount = $wallet->amount - $data['total_amount'];
                    $wallet->update();
                    $activity_data = [
                        'activity_type' => 'paid_for_booking',
                        'wallet' => $wallet,
                        'booking_id'=>$request->booking_id,
                        'booking_amount'=>$data['total_amount'],
                    ];
                    $this->sendNotification($activity_data);

                }
            }
        }
        $message = __('messages.payment_completed');
        $activity_data = [
            'activity_type' => 'payment_message_status',
            'payment_status'=>  str_replace("_"," ",ucfirst($data['payment_status'])),
            'booking_id' => $booking->id,
            'booking' => $booking,
        ];
        $this->sendNotification($activity_data);

        if($result->payment_status == 'failed')
        {
            $status_code = 400;
        }
        return comman_message_response($message,$status_code);
    }

    public function paymentList(Request $request)
    {
        $payment = Payment::myPayment()->with('booking');
        if($request->has('booking_id') && !empty($request->booking_id)){
            $payment->where('booking_id',$request->booking_id);
        }
        if($request->has('payment_type') && !empty($request->payment_type)){

            if($request->payment_type == 'cash'){
                $payment->where('payment_type',$request->payment_type);
            }
        }
        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page)){
                $per_page = $request->per_page;
            }
            if($request->per_page === 'all' ){
                $per_page = $payment->count();
            }
        }

        $payment = $payment->orderBy('id','desc')->paginate($per_page);
        $items = PaymentResource::collection($payment);

        $response = [
            'pagination' => [
                'total_items' => $items->total(),
                'per_page' => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'totalPages' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
                'next_page' => $items->nextPageUrl(),
                'previous_page' => $items->previousPageUrl(),
            ],
            'data' => $items,
        ];

        return comman_custom_response($response);
    }

    public function transferPayment(Request $request){
        $validated = $request->validate([
            'booking_id' => 'required|integer',
            'action' => 'required|in:handyman_send_provider,provider_approved_cash,provider_send_admin',
            'p_id' => 'nullable|integer',
            'parent_id' => 'nullable|integer',
            'txn_id' => 'nullable|string|max:255',
            'other_transaction_detail' => 'nullable',
        ]);
        $actor = auth()->user();
        $booking = Booking::query()->myBooking()->with(['payment', 'handymanAdded'])->findOrFail($validated['booking_id']);
        abort_unless($booking->payment, 422);

        $action = $validated['action'];
        if ($action === 'handyman_send_provider') {
            abort_unless(
                $actor->user_type === 'handyman'
                && $booking->handymanAdded->contains('handyman_id', $actor->id)
                && $booking->provider_id,
                403
            );
            $receiverId = $booking->provider_id;
            $status = config('constant.PAYMENT_HISTORY_STATUS.PENDING_PROVIDER');
        } else {
            abort_unless(
                $actor->user_type === 'provider' && (int) $booking->provider_id === (int) $actor->id,
                403
            );
            $receiverId = $action === 'provider_send_admin' ? admin_id() : $actor->id;
            $status = $action === 'provider_send_admin'
                ? config('constant.PAYMENT_HISTORY_STATUS.PENDING_ADMIN')
                : config('constant.PAYMENT_HISTORY_STATUS.APPROVED_PROVIDER');
        }

        $parentHistory = !empty($validated['p_id'])
            ? PaymentHistory::where('booking_id', $booking->id)->findOrFail($validated['p_id'])
            : null;
        $mainHistory = !empty($validated['parent_id'])
            ? PaymentHistory::where('booking_id', $booking->id)->findOrFail($validated['parent_id'])
            : null;
        $amount = (float) $booking->payment->total_amount;
        $text = $action === 'provider_approved_cash'
            ? __('messages.cash_approved', ['amount' => getPriceFormat($amount), 'name' => get_user_name($receiverId)])
            : __('messages.payment_transfer', [
                'from' => get_user_name($actor->id),
                'to' => get_user_name($receiverId),
                'amount' => getPriceFormat($amount),
            ]);

        DB::transaction(function () use ($validated, $booking, $actor, $receiverId, $status, $action, $amount, $text, $parentHistory, $mainHistory) {
            PaymentHistory::create([
                'payment_id' => $booking->payment->id,
                'booking_id' => $booking->id,
                'action' => $action,
                'text' => $text,
                'type' => $booking->payment->payment_type,
                'sender_id' => $actor->id,
                'receiver_id' => $receiverId,
                'datetime' => now(),
                'status' => $status,
                'total_amount' => $amount,
                'txn_id' => $validated['txn_id'] ?? null,
                'other_transaction_detail' => $validated['other_transaction_detail'] ?? null,
                'parent_id' => $mainHistory?->id,
            ]);

            if ($parentHistory) {
                $parentHistory->update(['status' => $action === 'handyman_send_provider' ? 'send_to_provider' : $status]);
            }
            if ($mainHistory && $action === 'provider_approved_cash') {
                $mainHistory->update(['status' => $status]);
            }
        });
        $message = trans('messages.transfer');
        if($request->is('api/*')) {
            return comman_message_response($message);
		}
    }

    public function paymentHistory(Request $request){
        $validated = $request->validate([
            'booking_id' => 'required|integer',
        ]);
        $booking = Booking::query()->myBooking()->findOrFail($validated['booking_id']);
        $payment = PaymentHistory::where('booking_id', $booking->id);

        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page)){
                $per_page = $request->per_page;
            }
            if($request->per_page === 'all' ){
                $per_page = $payment->count();
            }
        }

        $payment = $payment->orderBy('id','desc')->paginate($per_page);
        $items = PaymentHistoryResource::collection($payment);

        $response = [
            'pagination' => [
                'total_items' => $items->total(),
                'per_page' => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'totalPages' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
                'next_page' => $items->nextPageUrl(),
                'previous_page' => $items->previousPageUrl(),
            ],
            'data' => $items,
        ];

        return comman_custom_response($response);

    }

    public function getCashPaymentHistory(Request $request){
        $validated = $request->validate([
            'payment_id' => 'required|integer',
        ]);
        $ownedPayment = Payment::query()->myPayment()->findOrFail($validated['payment_id']);
        $payment = PaymentHistory::where('payment_id', $ownedPayment->id)->with('booking');

        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page)){
                $per_page = $request->per_page;
            }
            if($request->per_page === 'all' ){
                $per_page = $payment->count();
            }
        }

        $payment = $payment->orderBy('id','desc')->paginate($per_page);
        $items = GetCashPaymentHistoryResource::collection($payment);

        $response = [
            'pagination' => [
                'total_items' => $items->total(),
                'per_page' => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'totalPages' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
                'next_page' => $items->nextPageUrl(),
                'previous_page' => $items->previousPageUrl(),
            ],
            'data' => $items,
        ];

        return comman_custom_response($response);

    }


    public function paymentDetail(Request $request){
        $auth_user = authSession();
        $user_id = $auth_user->id;

        $get_all_payments = PaymentHistory::where('receiver_id',$user_id);
        if(!empty($request->status)){
            $get_all_payments = $get_all_payments->where('status',$request->status);
        }

        if(!empty($request->from) && !empty($request->to)){
            $get_all_payments = $get_all_payments->whereDate('datetime', '>=', $request->from)->whereDate('datetime', '<=',  $request->to);
        }
        if (auth()->user()->hasAnyRole(['handyman'])) {
            $get_all_payments = $get_all_payments->where('action' ,'handyman_approved_cash')->where('receiver_id',$user_id);
        }

        if (auth()->user()->hasAnyRole(['provider'])) {
            $get_all_payments = $get_all_payments->where('action' ,'handyman_send_provider')->where('receiver_id',$user_id);
        }


        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page)){
                $per_page = $request->per_page;
            }
            if($request->per_page === 'all' ){
                $per_page = $get_all_payments->count();
            }
        }

        $get_all_payments = $get_all_payments->orderBy('id','desc')->paginate($per_page);


        $items = PaymentHistoryResource::collection($get_all_payments);

        $response = [
            'today_cash' => today_cash_total($user_id,$request->to,$request->from),
            'total_cash' => total_cash($user_id),
            'cash_detail' => $items
        ];

        return comman_custom_response($response);
    }

    public function getCashPayment(Request $request)
    {
        $payment = Payment::where('payment_type', 'cash');

        $per_page = config('constant.PER_PAGE_LIMIT');
        if( $request->has('per_page') && !empty($request->per_page)){
            if(is_numeric($request->per_page)){
                $per_page = $request->per_page;
            }
            if($request->per_page === 'all' ){
                $per_page = $payment->count();
            }
        }

        $payment = $payment->orderBy('id','desc')->paginate($per_page);
        $items = PaymentResource::collection($payment);

        $response = [
            'pagination' => [
                'total_items' => $items->total(),
                'per_page' => $items->perPage(),
                'currentPage' => $items->currentPage(),
                'totalPages' => $items->lastPage(),
                'from' => $items->firstItem(),
                'to' => $items->lastItem(),
                'next_page' => $items->nextPageUrl(),
                'previous_page' => $items->previousPageUrl(),
            ],
            'data' => $items,
        ];

        return comman_custom_response($response);
    }
    public function paymentGateways(Request $request){
        $payment = PaymentGateway::where('status',1)->where('type', '!=', 'razorPayX')->get();
        $payment = PaymentGatewayResource::collection($payment);

        return comman_custom_response(['data' => $payment]);
    }
}
