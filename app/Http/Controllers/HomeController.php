<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\User;
use App\Models\Payment;
use App\Models\Service;
use App\Models\Slider;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\ProviderDocument;
use App\Models\AppSetting;
use App\Models\Setting;
use App\Models\ProviderPayout;
use App\Models\HandymanPayout;
use App\Models\ServiceAddon;
use App\Models\AppDownload;
use App\Models\FrontendSetting;
use App\Models\Bank;
use App\Models\SanadAiInteraction;
use App\Models\SanadBuzzAlert;
use App\Models\SanadChatThread;
use App\Models\SanadDocumentVaultItem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use App\Models\BookingRating;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if (request()->ajax()) {
            $start = (!empty($_GET["start"])) ? date('Y-m-d', strtotime($_GET["start"])) : ('');
            $end = (!empty($_GET["end"])) ? date('Y-m-d', strtotime($_GET["end"])) : ('');
            $data =  Booking::myBooking()->where('status', 'pending')->whereDate('date', '>=', $start)->whereDate('date',   '<=', $end)->with('service')->get();
            return response()->json($data);
        }

        $data['dashboard'] = [
            'count_total_booking'               => Booking::myBooking()->count(),
            'count_total_service'               => Service::myService()->count(),
            'count_total_provider'              => User::myUsers('get_provider')->where('status', 1)->count(),
            'count_active_service'              => Service::myService()->where('status', 1)->count(),
            'count_pending_orders'               => Booking::myBooking()->where(function ($query) {
                $query->whereIn('sanad_stage', ['submitted', 'pending_review'])
                    ->orWhere(function ($legacy) { $legacy->whereNull('sanad_stage')->where('status', 'pending'); });
            })->count(),
            'count_in_progress_orders'           => Booking::myBooking()->where(function ($query) {
                $query->whereIn('sanad_stage', ['assigned_to_partner', 'assigned_to_employee', 'in_progress'])
                    ->orWhere(function ($legacy) { $legacy->whereNull('sanad_stage')->whereIn('status', ['accept', 'in_progress']); });
            })->count(),
            'count_completed_orders'            => Booking::myBooking()->where(function ($query) {
                $query->whereIn('sanad_stage', ['completed', 'closed'])
                    ->orWhere(function ($legacy) { $legacy->whereNull('sanad_stage')->where('status', 'completed'); });
            })->count(),
            'count_cancelled_orders'            => Booking::myBooking()->where('status', 'cancelled')->count(),
            'new_customer'                      => User::myUsers('get_customer')->orderBy('id', 'DESC')->take(5)->get(),
            'new_provider'                      => User::myUsers('get_provider')->with('getServiceRating')->orderBy('id', 'DESC')->take(5)->get(),
            'upcomming_booking'                 => Booking::myBooking()->with('customer')->where('status', 'pending')->orderBy('id', 'DESC')->take(5)->get(),
            'top_services_list'                 => Booking::myBooking()->showServiceCount()->take(5)->get(),
            'count_handyman_pending_booking'    => Booking::myBooking()->where('status', 'pending')->count(),
            'count_handyman_complete_booking'   => Booking::myBooking()->where('status', 'completed')->count(),
            'count_handyman_cancelled_booking'  => Booking::myBooking()->where('status', 'cancelled')->count()
        ];
        $data['sanad'] = $this->sanadDashboardData();

        $data['category_chart'] = [
            'chartdata'     => Booking::myBooking()->showServiceCount()->take(4)->get()->pluck('count_pid'),
            'chartlabel'    => Booking::myBooking()->showServiceCount()->take(4)->get()->pluck('service.category.name')
        ];

        $total_revenue  = Payment::where('payment_status', 'paid');
        if (auth()->user()->hasAnyRole(['admin', 'demo_admin'])) {
            $data['revenueData']    =  adminEarning();
        }
        if ($user->hasRole('provider')) {
            $monthExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "strftime('%m', created_at)"
                : 'DATE_FORMAT(created_at , "%m")';

            $revenuedata = ProviderPayout::selectRaw("sum(amount) as total , {$monthExpression} as month")
                ->where('provider_id', auth()->user()->id)
                ->whereYear('created_at', date('Y'))
                ->groupBy('month');
            $revenuedata = $revenuedata->get()->toArray();
            $data['revenueData']    =    [];
            $data['revenuelableData']    =    [];
            for ($i = 1; $i <= 12; $i++) {
                $revenueData = 0;

                foreach ($revenuedata as $revenue) {
                    if ((int)$revenue['month'] == $i) {
                        $data['revenueData'][] = (int)$revenue['total'];
                        $revenueData++;

                    }
                }
                if ($revenueData == 0) {
                    $data['revenueData'][] = 0;
                }
            }

            $data['currency_data']=currency_data();
            $data['sanad_partner'] = $this->sanadPartnerDashboardData($user);
        }


        $data['total_revenue']  =    $total_revenue->sum('total_amount');
        if ($user->hasRole('provider')) {
            $total_revenue  = ProviderPayout::where('provider_id', $user->id)->sum('amount') ?? 0;

            $data['total_revenue']=getPriceFormat($total_revenue);
        }
        if ($user->hasRole('handyman')) {
            $data['total_revenue']  = HandymanPayout::where('handyman_id', $user->id)->sum('amount') ?? 0;
            $data['sanad_employee'] = $this->sanadEmployeeDashboardData($user);


        }
        if ($user->hasRole('user')) {
            $data['sanad_customer'] = $this->sanadCustomerDashboardData($user);
        }

        if (auth()->user()->hasAnyRole(['admin', 'demo_admin'])) {
            return $this->adminDashboard($data);
        } else if (auth()->user()->hasAnyRole('provider')) {
            return $this->providerDashboard($data);
        } else if (auth()->user()->hasAnyRole('handyman')) {
            return $this->handymanDashboard($data);
        } else {
            return $this->userDashboard($data);
        }
    }

    /**
     * Admin Dashboard
     *
     * @param $data
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function adminDashboard($data)
    {
        return app(\App\Http\Controllers\SanadWebController::class)->dashboard();
    }
    public function providerDashboard($data)
    {
        return redirect()->route('provider.dashboard');
    }
    public function handymanDashboard($data)
    {

        return view('dashboard.handyman-dashboard', compact('data'));
    }
    public function userDashboard($data)
    {
        return redirect()->route('customer-portal.dashboard');
    }

    private function sanadEmployeeDashboardData(User $user)
    {
        $requestQuery = Booking::myBooking()->whereNotNull('sanad_stage');
        $today = now()->toDateString();

        $actionStages = [
            'assigned_to_employee',
            'in_progress',
            'awaiting_customer_action',
            'awaiting_quality_review',
            'escalated',
        ];

        return [
            'assigned_tasks' => (clone $requestQuery)->count(),
            'active_tasks' => (clone $requestQuery)->whereIn('sanad_stage', $actionStages)->count(),
            'in_progress_tasks' => (clone $requestQuery)->where('sanad_stage', 'in_progress')->count(),
            'awaiting_review_tasks' => (clone $requestQuery)->where('sanad_stage', 'awaiting_quality_review')->count(),
            'completed_tasks' => (clone $requestQuery)->whereIn('sanad_stage', ['completed', 'closed'])->count(),
            'today_tasks' => (clone $requestQuery)->whereDate('date', $today)->count(),
            'pending_evidence' => SanadDocumentVaultItem::whereIn('booking_id', (clone $requestQuery)->pluck('id'))
                ->where('verification_status', 'pending')
                ->count(),
            'unread_buzz' => SanadBuzzAlert::whereIn('booking_id', (clone $requestQuery)->pluck('id'))
                ->where('status', 'unread')
                ->where(function ($query) use ($user) {
                    $query->where('recipient_id', $user->id)
                        ->orWhere('recipient_role', $user->user_type);
                })
                ->count(),
            'open_chats' => SanadChatThread::whereIn('booking_id', (clone $requestQuery)->pluck('id'))
                ->where('status', 'open')
                ->count(),
            'paid_tasks' => (clone $requestQuery)->whereHas('payment', function ($paymentQuery) {
                $paymentQuery->where('payment_status', 'paid');
            })->count(),
            'pending_payment_tasks' => (clone $requestQuery)->whereHas('payment', function ($paymentQuery) {
                $paymentQuery->whereIn('payment_status', ['pending', 'advanced_paid', 'pending_by_admin', 'failed']);
            })->count(),
            'next_tasks' => Booking::myBooking()
                ->with(['customer', 'provider', 'service', 'payment'])
                ->whereNotNull('sanad_stage')
                ->whereIn('sanad_stage', $actionStages)
                ->orderByRaw('CASE WHEN sla_due_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('sla_due_at')
                ->orderBy('date')
                ->take(5)
                ->get(),
        ];
    }

    private function sanadCustomerDashboardData(User $user)
    {
        $requestQuery = Booking::myBooking()->whereNotNull('sanad_stage');
        $requestIds = (clone $requestQuery)->pluck('id');

        return [
            'total_requests' => (clone $requestQuery)->count(),
            'active_requests' => (clone $requestQuery)->whereIn('sanad_stage', [
                'submitted',
                'pending_review',
                'assigned_to_partner',
                'assigned_to_employee',
                'in_progress',
                'awaiting_customer_action',
                'awaiting_quality_review',
                'escalated',
            ])->count(),
            'awaiting_customer_action' => (clone $requestQuery)->where('sanad_stage', 'awaiting_customer_action')->count(),
            'completed_requests' => (clone $requestQuery)->whereIn('sanad_stage', ['completed', 'closed'])->count(),
            'pending_documents' => SanadDocumentVaultItem::whereIn('booking_id', $requestIds)
                ->where('verification_status', 'pending')
                ->count(),
            'approved_documents' => SanadDocumentVaultItem::whereIn('booking_id', $requestIds)
                ->where('verification_status', 'approved')
                ->count(),
            'unread_buzz' => SanadBuzzAlert::whereIn('booking_id', $requestIds)
                ->where('status', 'unread')
                ->where(function ($query) use ($user) {
                    $query->where('recipient_id', $user->id)
                        ->orWhere('recipient_role', $user->user_type);
                })
                ->count(),
            'open_chats' => SanadChatThread::whereIn('booking_id', $requestIds)
                ->where('status', 'open')
                ->count(),
            'paid_requests' => (clone $requestQuery)->whereHas('payment', function ($paymentQuery) {
                $paymentQuery->where('payment_status', 'paid');
            })->count(),
            'pending_payment_requests' => (clone $requestQuery)->whereHas('payment', function ($paymentQuery) {
                $paymentQuery->whereIn('payment_status', ['pending', 'advanced_paid', 'pending_by_admin', 'failed']);
            })->count(),
            'next_requests' => Booking::myBooking()
                ->with(['provider', 'service', 'payment'])
                ->whereNotNull('sanad_stage')
                ->orderByRaw('CASE WHEN sla_due_at IS NULL THEN 1 ELSE 0 END')
                ->orderBy('sla_due_at')
                ->latest()
                ->take(5)
                ->get(),
        ];
    }

    private function sanadPartnerDashboardData(User $user)
    {
        $requestQuery = Booking::myBooking()->whereNotNull('sanad_stage');

        $activeStages = [
            'submitted',
            'pending_review',
            'assigned_to_partner',
            'assigned_to_employee',
            'in_progress',
            'awaiting_customer_action',
            'awaiting_quality_review',
            'escalated',
        ];

        $completedStages = [
            'completed',
            'closed',
        ];

        $employees = User::where('provider_id', $user->id)
            ->where('user_type', 'handyman');

        $services = Service::myService();

        return [
            'assigned_requests' => (clone $requestQuery)->whereIn('sanad_stage', $activeStages)->count(),
            'completed_requests' => (clone $requestQuery)->whereIn('sanad_stage', $completedStages)->count(),
            'employee_count' => (clone $employees)->count(),
            'active_employee_count' => (clone $employees)->where('status', 1)->count(),
            'service_count' => (clone $services)->count(),
            'active_service_count' => (clone $services)->where('status', 1)->count(),
            'unassigned_employee_requests' => (clone $requestQuery)->whereDoesntHave('handymanAdded')->count(),
            'in_progress_requests' => (clone $requestQuery)->where('sanad_stage', 'in_progress')->count(),
            'awaiting_customer_requests' => (clone $requestQuery)->where('sanad_stage', 'awaiting_customer_action')->count(),
            'quality_review_requests' => (clone $requestQuery)->where('sanad_stage', 'awaiting_quality_review')->count(),
            'paid_requests' => (clone $requestQuery)->whereHas('payment', function ($paymentQuery) {
                $paymentQuery->where('payment_status', 'paid');
            })->count(),
            'pending_payment_requests' => (clone $requestQuery)->whereHas('payment', function ($paymentQuery) {
                $paymentQuery->whereIn('payment_status', ['pending', 'advanced_paid', 'pending_by_admin', 'failed']);
            })->count(),
            'recent_workload' => Booking::myBooking()
                ->with(['customer', 'service', 'handymanAdded.handyman'])
                ->whereNotNull('sanad_stage')
                ->latest()
                ->take(4)
                ->get(),
        ];
    }

    private function sanadDashboardData()
    {
        $user = auth()->user();
        $bookingQuery = Booking::myBooking();
        $now = now();
        $dueSoon = now()->addHours(config('sanad.sla.due_soon_hours', 24));

        $stageCounts = [];
        foreach (config('sanad.request_lifecycle', []) as $stage) {
            $stageCounts[$stage] = (clone $bookingQuery)->where('sanad_stage', $stage)->count();
        }

        $buzzQuery = Schema::hasTable('sanad_buzz_alerts') ? SanadBuzzAlert::query() : null;
        $documentQuery = Schema::hasTable('sanad_document_vault_items') ? SanadDocumentVaultItem::query() : null;
        $chatQuery = Schema::hasTable('sanad_chat_threads') ? SanadChatThread::query() : null;
        $aiQuery = Schema::hasTable('sanad_ai_interactions') ? SanadAiInteraction::query() : null;

        if (!$user->hasAnyRole(['admin', 'demo_admin'])) {
            if ($buzzQuery) {
                $buzzQuery->where(function ($q) use ($user) {
                    $q->where('recipient_id', $user->id)
                        ->orWhere('recipient_role', $user->user_type);
                });
            }
            if ($documentQuery) {
                $documentQuery->where(function ($q) use ($user) {
                    $q->where('owner_id', $user->id)
                        ->orWhere('uploaded_by', $user->id)
                        ->orWhere(function ($visibilityQuery) use ($user) {
                            $this->whereJsonArrayContains($visibilityQuery, 'visible_to', $user->user_type);
                        });
                });
            }
            if ($chatQuery) {
                $chatQuery->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                        ->orWhere(function ($visibilityQuery) use ($user) {
                            $this->whereJsonArrayContains($visibilityQuery, 'participant_roles', $user->user_type);
                        });
                });
            }
            if ($aiQuery) {
                $aiQuery->where('user_id', $user->id);
            }
        }

        $needsActionStages = [
            'pending_review',
            'awaiting_customer_action',
            'awaiting_quality_review',
            'escalated',
        ];

        $paymentPendingStatuses = [
            'pending',
            'advanced_paid',
            'pending_by_admin',
            'failed',
        ];

        $attentionRequests = Booking::myBooking()
            ->with(['customer', 'provider', 'service', 'payment'])
            ->whereNotNull('sanad_stage')
            ->where(function ($query) use ($needsActionStages, $paymentPendingStatuses, $now) {
                $query->whereIn('sanad_stage', $needsActionStages)
                    ->orWhereNull('provider_id')
                    ->orWhere(function ($slaQuery) use ($now) {
                        $slaQuery->whereNotNull('sla_due_at')->where('sla_due_at', '<', $now);
                    })
                    ->orWhereHas('sanadDocuments', function ($documentQuery) {
                        $documentQuery->where('verification_status', 'pending');
                    })
                    ->orWhereHas('sanadBuzzAlerts', function ($buzzAlertQuery) {
                        $buzzAlertQuery->where('status', 'unread');
                    })
                    ->orWhereHas('payment', function ($paymentQuery) use ($paymentPendingStatuses) {
                        $paymentQuery->whereIn('payment_status', $paymentPendingStatuses);
                    });
            })
            ->orderByRaw("CASE WHEN sanad_stage = 'escalated' THEN 0 ELSE 1 END")
            ->orderByRaw('CASE WHEN sla_due_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('sla_due_at')
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        $paidRevenue = Booking::myBooking()
            ->whereHas('payment', function ($paymentQuery) {
                $paymentQuery->where('payment_status', 'paid');
            })
            ->with('payment')
            ->get()
            ->sum(function ($booking) {
                return optional($booking->payment)->total_amount ?: 0;
            });

        return [
            'terminology' => config('sanad.terminology', []),
            'stage_counts' => $stageCounts,
            'active_requests' => (clone $bookingQuery)->whereIn('sanad_stage', [
                'submitted',
                'pending_review',
                'assigned_to_partner',
                'assigned_to_employee',
                'in_progress',
                'awaiting_customer_action',
                'awaiting_quality_review',
                'escalated',
            ])->count(),
            'unread_buzz' => $buzzQuery ? (clone $buzzQuery)->where('status', 'unread')->count() : 0,
            'pending_documents' => $documentQuery ? (clone $documentQuery)->where('verification_status', 'pending')->count() : 0,
            'open_chats' => $chatQuery ? (clone $chatQuery)->where('status', 'open')->count() : 0,
            'ai_escalations' => $aiQuery ? (clone $aiQuery)->where('requires_escalation', true)->count() : 0,
            'needs_action' => (clone $bookingQuery)->whereIn('sanad_stage', $needsActionStages)->count(),
            'unassigned_requests' => (clone $bookingQuery)->whereNotNull('sanad_stage')->whereNull('provider_id')->count(),
            'overdue_sla' => (clone $bookingQuery)->whereNotNull('sla_due_at')->where('sla_due_at', '<', $now)->count(),
            'due_soon_sla' => (clone $bookingQuery)->whereNotNull('sla_due_at')->whereBetween('sla_due_at', [$now, $dueSoon])->count(),
            'payment_pending' => Booking::myBooking()->whereHas('payment', function ($paymentQuery) use ($paymentPendingStatuses) {
                $paymentQuery->whereIn('payment_status', $paymentPendingStatuses);
            })->count(),
            'paid_requests' => Booking::myBooking()->whereHas('payment', function ($paymentQuery) {
                $paymentQuery->where('payment_status', 'paid');
            })->count(),
            'paid_revenue' => $paidRevenue,
            'attention_requests' => $attentionRequests,
            'recent_requests' => Booking::myBooking()
                ->with(['customer', 'provider', 'service', 'payment'])
                ->whereNotNull('sanad_stage')
                ->orderBy('updated_at', 'desc')
                ->take(5)
                ->get(),
        ];
    }

    private function whereJsonArrayContains(EloquentBuilder $query, $column, $value)
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return $query->where($column, 'like', '%"' . $value . '"%');
        }

        return $query->whereJsonContains($column, $value);
    }
    public function changeStatus(Request $request)
    {
        if (demoUserPermission()) {
            $message = __('messages.demo_permission_denied');
            $response = [
                'status'    => false,
                'message'   => $message
            ];

            return comman_custom_response($response);
        }
        $type = $request->type;
        $message_form = __('messages.item');
        $message = trans('messages.update_form', ['form' => trans('messages.status')]);
        switch ($type) {
            case 'role':
                $role = \App\Models\Role::find($request->id);
                $role->status = $request->status;
                $role->save();
                break;
            case 'category_status':
                $category = \App\Models\Category::find($request->id);
                $category->status = $request->status;
                $category->save();
                break;
            case 'category_featured':
                $message_form = __('messages.category');
                $category = \App\Models\Category::find($request->id);
                $category->is_featured = $request->status;
                $category->save();
                break;
            case 'service_status':
                $service = \App\Models\Service::find($request->id);
                $service->status = $request->status;
                $service->save();
                break;
            case 'service_featured':
                $message_form = __('messages.service');
                $service = \App\Models\Service::find($request->id);
                $service->is_featured = $request->status;
                $service->save();
                break;
            case 'coupon_status':
                $coupon = \App\Models\Coupon::find($request->id);
                $coupon->status = $request->status;
                $coupon->save();
                break;
            case 'document_status':
                $document = \App\Models\Documents::find($request->id);
                $document->status = $request->status;
                $document->save();
                break;
            case 'document_required':
                $message_form = __('messages.document');
                $document = \App\Models\Documents::find($request->id);
                $document->is_required = $request->status;
                $document->save();
                break;
            case 'provider_is_verified':
                $message_form = __('messages.providerdocument');
                $document = \App\Models\ProviderDocument::find($request->id);
                $document->is_verified = $request->status;
                $document->verification_status = $request->status ? 'approved' : 'pending';
                $document->review_reason = null;
                $document->reviewed_by = auth()->id();
                $document->reviewed_at = now();
                $document->save();
                break;
            case 'tax_status':
                $tax = \App\Models\Tax::find($request->id);
                $tax->status = $request->status;
                $tax->save();
                break;
            case 'provideraddress_status':
                $provideraddress = \App\Models\ProviderAddressMapping::find($request->id);
                $provideraddress->status = $request->status;
                $provideraddress->save();
                break;
            case 'slider_status':
                $slider = \App\Models\Slider::find($request->id);
                $slider->status = $request->status;
                $slider->save();
                break;
            case 'servicefaq_status':
                $servicefaq = \App\Models\ServiceFaq::find($request->id);
                $servicefaq->status = $request->status;
                $servicefaq->save();
                break;
            case 'wallet_status':
                $wallet = \App\Models\Wallet::find($request->id);
                $wallet->status = $request->status;
                $wallet->save();
                break;
            case 'subcategory_status':
                $subcategory = \App\Models\SubCategory::find($request->id);
                $subcategory->status = $request->status;
                $subcategory->save();
                break;
            case 'subcategory_featured':
                $message_form = __('messages.subcategory');
                $subcategory = \App\Models\SubCategory::find($request->id);
                $subcategory->is_featured = $request->status;
                $subcategory->save();
                break;
            case 'plan_status':
                $plans = \App\Models\Plans::find($request->id);
                $plans->status = $request->status;
                $plans->save();
                break;
            case 'bank_status':
                $banks = \App\Models\Bank::find($request->id);
                $banks->status = $request->status;
                $banks->save();
                break;
            case 'blog_status':
                $blog = \App\Models\Blog::find($request->id);
                $blog->status = $request->status;
                $blog->save();
                break;
            case 'servicepackage_status':
                $servicepackage = \App\Models\ServicePackage::find($request->id);
                $servicepackage->status = $request->status;
                $servicepackage->save();
                break;
            case 'notificationtemplate_status':
                $notificationtemplate = \App\Models\NotificationTemplate::find($request->id);
                $notificationtemplate->status = $request->status;
                $notificationtemplate->save();
            case 'serviceaddon_status':
                $serviceaddon = \App\Models\ServiceAddon::find($request->id);
                $serviceaddon->status = $request->status;
                $serviceaddon->save();
                break;
            case 'user_verify_email':
                $user = \App\Models\User::find($request->id);
                $user->is_email_verified = $request->status;
                $user->save();
                break;
            default:
                $message = 'error';
                break;
        }
        if ($request->has('is_email_verified') && $request->is_email_verified == 'is_email_verified') {
            $message =  __('messages.user_verified', ['form' => $message_form]);
            if ($request->status == 0) {
                $message = __('messages.remove_form', ['form' => $message_form]);
            }
        }
        if ($request->has('is_featured') && $request->is_featured == 'is_featured') {
            $message =  __('messages.added_form', ['form' => $message_form]);
            if ($request->status == 0) {
                $message = __('messages.remove_form', ['form' => $message_form]);
            }
        }
        if ($request->has('is_required') && $request->is_required == 'is_required') {
            $message =  __('messages.added_form', ['form' => $message_form]);
            if ($request->status == 0) {
                $message = __('messages.remove_form', ['form' => $message_form]);
            }
        }
        if ($request->has('provider_is_verified') && $request->provider_is_verified == 'provider_is_verified') {
            $message =  __('messages.added_form', ['form' => $message_form]);
            if ($request->status == 0) {
                $message = __('messages.remove_form', ['form' => $message_form]);
            }
        }
        return comman_custom_response(['message' => $message, 'status' => true]);
    }

    public function getAjaxList(Request $request)
    {
        $items = array();
        $value = $request->q;

        $auth_user = authSession();
        switch ($request->type) {
            case 'permission':
                $items = \App\Models\Permission::select('id', 'name as text')->whereNull('parent_id');
                if ($value != '') {
                    $items->where('name', 'LIKE', $value . '%');
                }
                $items = $items->get();
                break;
            case 'category':
                $isAr = app()->getLocale() === 'ar';
                $itemsQuery = \App\Models\Category::where('status', 1);
                if (isset($request->is_featured)) {
                    $itemsQuery->where('is_featured', $request->is_featured);
                }
                if ($value != '') {
                    $itemsQuery->where(function ($q) use ($value) {
                        $q->where('name', 'LIKE', '%' . $value . '%')
                          ->orWhere('name_ar', 'LIKE', '%' . $value . '%')
                          ->orWhere('name_en', 'LIKE', '%' . $value . '%');
                    });
                }
                $items = $itemsQuery->get()->map(function ($row) use ($isAr) {
                    return [
                        'id' => $row->id,
                        'text' => ($isAr && !empty($row->name_ar)) ? $row->name_ar : ($row->name_en ?: $row->name),
                    ];
                });
                break;
            case 'subcategory':
                $isAr = app()->getLocale() === 'ar';
                $itemsQuery = \App\Models\SubCategory::where('status', 1);
                if (isset($request->category_id)) {
                    $itemsQuery->where('category_id', $request->category_id);
                }
                if ($value != '') {
                    $itemsQuery->where(function ($q) use ($value) {
                        $q->where('name', 'LIKE', '%' . $value . '%')
                          ->orWhere('name_ar', 'LIKE', '%' . $value . '%')
                          ->orWhere('name_en', 'LIKE', '%' . $value . '%');
                    });
                }
                $items = $itemsQuery->get()->map(function ($row) use ($isAr) {
                    return [
                        'id' => $row->id,
                        'text' => ($isAr && !empty($row->name_ar)) ? $row->name_ar : ($row->name_en ?: $row->name),
                    ];
                });
                break;
            case 'sub_araeas':
                $items = \App\Models\Region::select('city_id', 'name as text');

                if ($value != '') {
                    $items->where('name', 'LIKE', '%' . $value . '%');
                }

                $items = $items->get();
                break;
            case 'provider':
                $items = \App\Models\User::select('id', 'display_name as text')
                    ->where('user_type', 'provider')
                    ->where('status', 1);

                if ($value != '') {
                    $items->where('display_name', 'LIKE', $value . '%');
                }

                $items = $items->get();
                break;

            case 'user':
                $items = \App\Models\User::select('id', \DB::raw("CONCAT(display_name, ' |', contact_number) as text"))
                ->where('user_type', 'user')
                    ->where('status', 1);

                if ($value != '') {
                    $items->where(function (Builder $query)use($value) {
                        $query->where('display_name', 'LIKE', $value . '%')->orwhere('contact_number','LIKE', $value . '%' );
                    });
                }

                $items = $items->get();
                break;

                case 'provider-user':
                    $items = \App\Models\User::select('id', 'display_name as text')
                        ->where('user_type', 'provider')->orWhere('user_type','user')
                        ->where('status', 1);

                    if ($value != '') {
                        $items->where('display_name', 'LIKE', $value . '%');
                    }

                    $items = $items->get();
                    break;

            case 'handyman':
                $items = \App\Models\User::select('id', 'display_name as text')
                    ->where('user_type', 'handyman')
                    ->where('status', 1);

                if (isset($request->provider_id)) {
                    $items->where('provider_id', $request->provider_id);
                }

                if (isset($request->booking_id)) {
                    $booking_data = Booking::find($request->booking_id);

                    $service_address = $booking_data->handymanByAddress;
                    if ($service_address != null) {
                        $items->where('service_address_id', $service_address->id);
                    }
                }

                if ($value != '') {
                    $items->where('display_name', 'LIKE', $value . '%');
                }

                $items = $items->get();
                break;
            case 'service':
                $items = \App\Models\Service::select('id', 'name as text')->where('status', 1);

                if ($value != '') {
                    $items->where('name', 'LIKE', '%' . $value . '%');
                }
                if (isset($request->provider_id)) {
                    $items->where('provider_id', $request->provider_id);
                }

                if (isset($request->top_rated)) {
                    $minRating = $request->top_rated['min'] ?? 0;
                    $maxRating = $request->top_rated['max'] ?? 5;

                    $topRatedServiceIds = BookingRating::select('service_id', \DB::raw('COALESCE(AVG(rating), 0) as avg_rating'))
                        ->groupBy('service_id')
                        ->havingRaw('avg_rating >= ?', [$minRating])
                        ->havingRaw('avg_rating <= ?', [$maxRating])
                        ->orderByDesc('avg_rating')
                        ->pluck('service_id')
                        ->toArray();

                    $items->whereIn('id', $topRatedServiceIds)
                        ->orderByRaw(\DB::raw("FIELD(id, " . implode(',', $topRatedServiceIds) . ")"));
                }

                if(isset($request->is_featured)){
                    $items->where('is_featured', 1);
                }


                $items = $items->get();


                break;
            case 'service-list':
                    $items = \App\Models\Service::select('id', 'name as text')->where('status', 1)->where('service_type','service');

                    if ($value != '') {
                        $items->where('name', 'LIKE', '%' . $value . '%');
                    }
                    if (isset($request->provider_id)) {
                        $items->where('provider_id', $request->provider_id);
                    }

                    $items = $items->get();
                    break;
            case 'providertype':
                $items = \App\Models\ProviderType::select('id', 'name as text')
                    ->where('status', 1);

                if ($value != '') {
                    $items->where('name', 'LIKE', $value . '%');
                }

                $items = $items->get();
                break;
            case 'coupon':
                $items = \App\Models\Coupon::select('id', 'code as text')->where('status', 1);

                if ($value != '') {
                    $items->where('code', 'LIKE', '%' . $value . '%');
                }

                $items = $items->where('status',1)->get();
                break;

                case 'bank':
                    $items = \App\Models\Bank::select('id', 'bank_name as text')->where('provider_id',$request->provider_id)->where('status',1);

                    if ($value != '') {
                        $items->where('name', 'LIKE', $value . '%');
                    }
                    $items = $items->get();
                    break;

            case 'country':
                $items = \App\Models\Region::select('id', 'name as text');

                if ($value != '') {
                    $items->where('name', 'LIKE', $value . '%');
                }
                $items = $items->get();
                break;
            case 'state':
                $items = \App\Models\State::select('id', 'name as text');
                if (isset($request->country_id)) {
                    $items->where('country_id', $request->country_id);
                }
                if ($value != '') {
                    $items->where('name', 'LIKE', $value . '%');
                }
                $items = $items->get();
                break;
            case 'city':
                $items = \App\Models\City::select('id', 'name as text');
                if (isset($request->state_id)) {
                    $items->where('state_id', $request->state_id);
                }
                if ($value != '') {
                    $items->where('name', 'LIKE', $value . '%');
                }
                $items = $items->get();
                break;
            case 'booking_status':
                $items = \App\Models\BookingStatus::select('id', 'label as text');

                if ($value != '') {
                    $items->where('label', 'LIKE', $value . '%');
                }
                $items = $items->get();
                break;
            case 'currency':
                $items = \DB::table('countries')->select(\DB::raw('id id,CONCAT(name , " ( " , symbol ," ) ") text'));

                $items->whereNotNull('symbol')->where('symbol', '!=', '');
                if ($value != '') {
                    $items->where('name', 'LIKE', $value . '%')->orWhere('currency_code', 'LIKE', $value . '%');
                }
                $items = $items->get();
                break;
            case 'country_code':
                $items = \DB::table('countries')->select(\DB::raw('code id,name text'));
                if ($value != '') {
                    $items->where('name', 'LIKE', $value . '%')->orWhere('code', 'LIKE', $value . '%');
                }
                $items = $items->get();
                break;

            case 'time_zone':
                $items = timeZoneList();

                foreach ($items as $k => $v) {

                    if ($value != '') {
                        if (strpos($v, $value) !== false) {
                        } else {
                            unset($items[$k]);
                        }
                    }
                }

                $data = [];
                $i = 0;
                foreach ($items as $key => $row) {
                    $data[$i] = [
                        'id'    => $key,
                        'text'  => $row,
                    ];
                    $i++;
                }
                $items = $data;
                break;
            case 'provider_address':
                $provider_id = !empty($request->provider_id) ? $request->provider_id : $auth_user->id;
                $items = \App\Models\ProviderAddressMapping::select('id', 'address as text', 'latitude', 'longitude', 'status')->where('provider_id', $provider_id)->where('status', 1);
                $items = $items->get();
                break;

            case 'provider_tax':
                $provider_id = !empty($request->provider_id) ? $request->provider_id : $auth_user->id;
                $items = \App\Models\Tax::select('id', 'title as text')->where('status', 1);
                $items = $items->get();
                break;

            case 'documents':
                $items = \App\Models\Documents::select('id', 'name', 'name_ar', 'status', 'is_required')->where('status', 1);
                if ($value != '') {
                    $items->where(function ($query) use ($value) {
                        $query->where('name', 'LIKE', $value . '%')
                            ->orWhere('name_ar', 'LIKE', $value . '%');
                    });
                }
                $items = $items->get()->map(function ($document) {
                    $document->text = $document->localized_name . ($document->is_required ? ' * ' : '');
                    return $document;
                });
                break;
            case 'handymantype':
                $items = \App\Models\HandymanType::select('id', 'name as text')
                    ->where('status', 1);

                if ($value != '') {
                    $items->where('name', 'LIKE', $value . '%');
                }

                $items = $items->get();
                break;
            case 'subcategory_list':
                $category_id = !empty($request->category_id) ? $request->category_id : '';
                $items = \App\Models\SubCategory::select('id', 'name as text')->where('category_id', $category_id)->where('status', 1);
                $items = $items->get();
                break;
            case 'service_package':
                $service_id = !empty($request->service_id) ? $request->service_id : $auth_user->id;
                $items = \App\Models\ServicePackage::select('id', 'description as text', 'status')->where('provider_id', $service_id)->where('status', 1);
                $items = $items->get();
                break;
            case 'all_user':
                $items = \App\Models\User::select('id', 'display_name as text')
                    ->where('status', 1);

                if ($value != '') {
                    $items->where('display_name', 'LIKE', $value . '%');
                }

                $items = $items->get();
                break;
            default:
                break;
        }
        return response()->json(['status' => 'true', 'results' => $items]);
    }

    public function removeFile(Request $request)
    {
        $allowedTypes = [
            'slider_image', 'profile_image', 'service_attachment', 'category_image', 'category_icon',
            'subcategory_image', 'provider_document', 'booking_attachment', 'bank_attachment',
            'app_image', 'app_image_full', 'package_attachment', 'blog_attachment',
            'serviceaddon_image', 'section5_attachment', 'main_image', 'google_play',
            'app_store', 'vimage', 'login_register_image', 'logo', 'favicon', 'footer_logo', 'loader',
        ];
        $validated = $request->validate([
            'id' => 'required|integer',
            'type' => 'required|string|in:'.implode(',', $allowedTypes),
        ]);
        $this->authorizeFileRemoval($validated['type'], $validated['id']);

        // Demo admin is allowed to add/remove catalog media during QA. Keep the
        // legacy demo restriction for unrelated destructive settings/actions.
        $demoMediaTypes = [
            'category_image',
            'category_icon',
            'subcategory_image',
            'service_attachment',
            'package_attachment',
            'serviceaddon_image',
        ];

        if (demoUserPermission() && !in_array($request->type, $demoMediaTypes, true)) {
            $message = __('messages.demo_permission_denied');
            $response = [
                'status'    => false,
                'message'   => $message
            ];

            return comman_custom_response($response);
        }

        $type = $validated['type'];
        $data = null;
        switch ($type) {
            case 'slider_image':
                $data = Slider::find($request->id);
                $message = __('messages.msg_removed', ['name' => __('messages.slider')]);
                break;
            case 'profile_image':
                $data = User::find($request->id);
                $message = __('messages.msg_removed', ['name' => __('messages.profile_image')]);
                break;
            case 'service_attachment':
                $media = Media::findOrFail($request->id);
                $media->delete();
                $message = __('messages.msg_removed', ['name' => __('messages.attachments')]);
                break;
            case 'category_image':
                $data = Category::find($request->id);
                $message = __('messages.msg_removed', ['name' => __('messages.category') . ' image']);
                break;
            case 'category_icon':
                $data = Category::find($request->id);
                $message = __('messages.msg_removed', ['name' => __('messages.category') . ' icon']);
                break;
            case 'subcategory_image':
                $data = SubCategory::find($request->id);
                $message = __('messages.msg_removed', ['name' => __('messages.subcategory') . ' image']);
                break;
            case 'provider_document':
                $data = ProviderDocument::find($request->id);
                $message = __('messages.msg_removed', ['name' => __('messages.providerdocument')]);
                break;
            case 'booking_attachment':
                $media = Media::findOrFail($request->id);
                $media->delete();
                $message = __('messages.msg_removed', ['name' => __('messages.attachments')]);
                break;
            case 'bank_attachment':
                $media = Media::findOrFail($request->id);
                $media->delete();
                $message = __('messages.msg_removed', ['name' => __('messages.attachments')]);
                break;
            case 'app_image':
                $data = AppDownload::find($request->id);
                $message = __('messages.msg_removed', ['name' => __('messages.attachments')]);
                break;
            case 'app_image_full':
                $data = AppDownload::find($request->id);
                $message = __('messages.msg_removed', ['name' => __('messages.attachments')]);
                break;
            case 'package_attachment':
                $media = Media::findOrFail($request->id);
                $media->delete();
                $message = __('messages.msg_removed', ['name' => __('messages.attachments')]);
                break;
            case 'blog_attachment':
                $media = Media::findOrFail($request->id);
                $media->delete();
                $message = __('messages.msg_removed', ['name' => __('messages.attachments')]);
                break;
            case 'serviceaddon_image':
                $data = ServiceAddon::find($request->id);
                $message = __('messages.msg_removed', ['name' => __('messages.service_addon')]);
                break;
            case 'section5_attachment':
                $media = Media::findOrFail($request->id);
                $media->delete();
                $message = __('messages.msg_removed', ['name' => __('messages.attachments')]);
                break;
            case 'main_image':
                $data = FrontendSetting::find($request->id);
                $message = __('messages.msg_removed', ['name' => __('messages.main_image')]);
                break;
            case 'google_play':
                $data = FrontendSetting::find($request->id);
                $message = __('messages.msg_removed', ['name' => __('messages.google_image')]);
                break;
            case 'app_store':
                $data = FrontendSetting::find($request->id);
                $message = __('messages.msg_removed', ['name' => __('messages.app_store')]);
                break;
            case 'vimage':
                $data = FrontendSetting::find($request->id);
                $message = __('messages.msg_removed', ['name' => __('messages.app_store')]);
                break;
            case 'login_register_image':
                $data = FrontendSetting::find($request->id);
                $message = __('messages.msg_removed', ['name' => __('messages.app_store')]);
                break;
            case 'logo':
                $data = Setting::find($request->id);
                $message = __('messages.msg_removed', ['name' => __('messages.app_store')]);
                break;
            case 'favicon':
                $data = Setting::find($request->id);
                $message = __('messages.msg_removed', ['name' => __('messages.app_store')]);
                break;
            case 'footer_logo':
                $data = Setting::find($request->id);
                $message = __('messages.msg_removed', ['name' => __('messages.app_store')]);
                break;
            case 'loader':
                $data = Setting::find($request->id);
                $message = __('messages.msg_removed', ['name' => __('messages.app_store')]);
                break;
            default:
                $data = AppSetting::find($request->id);
                $message = __('messages.msg_removed', ['name' => __('messages.image')]);
                break;
        }

        if ($data != null) {
            $data->clearMediaCollection($type);
        }

        $response = [
            'status'    => true,
            'image'     => getSingleMedia($data, $type),
            'id'        => $request->id,
            'preview'   => $type . "_preview",
            'message'   => $message
        ];

        return comman_custom_response($response);
    }

    private function authorizeFileRemoval(string $type, int $id): void
    {
        $user = auth()->user();
        abort_unless($user, 401);

        if ($user->hasAnyRole(['admin', 'demo_admin'])) {
            return;
        }

        if ($type === 'profile_image') {
            abort_unless((int) $id === (int) $user->id, 403);
            return;
        }

        if ($type === 'provider_document') {
            abort_unless(
                $user->hasRole('provider')
                && ProviderDocument::where('provider_id', $user->id)->whereKey($id)->exists(),
                403
            );
            return;
        }

        if (in_array($type, ['booking_attachment', 'bank_attachment'], true)) {
            $media = Media::findOrFail($id);
            $model = $media->model;
            if ($model instanceof Booking) {
                abort_unless(Booking::query()->myBooking()->whereKey($model->id)->exists(), 403);
                return;
            }
            if ($model instanceof Bank) {
                abort_unless($user->hasRole('provider') && (int) $model->provider_id === (int) $user->id, 403);
                return;
            }
            if (isset($model->booking_id)) {
                abort_unless(Booking::query()->myBooking()->whereKey($model->booking_id)->exists(), 403);
                return;
            }
        }

        abort(403);
    }

    public function lang($locale)
    {
        abort_unless(in_array($locale, ['ar', 'en'], true), 404);

        \App::setLocale($locale);
        session()->put('locale', $locale);
        \Artisan::call('cache:clear');
        $dir = 'ltr';
        if (in_array($locale, ['ar', 'dv', 'ff', 'ur', 'he', 'ku', 'fa'])) {
            $dir = 'rtl';
        }

        session()->put('dir',  $dir);
        if (auth()->check()) {
            $user = auth()->user();
            $user->language_option = $locale;
            $user->save();
        }

        $prev = url()->previous();
        if (str_contains($prev, '.css') || str_contains($prev, '.js') || str_contains($prev, 'switch-language') || str_contains($prev, '/lang/')) {
            return redirect()->route('frontend.index');
        }

        return redirect()->back()->withCookie(cookie('quick_locale', $locale, 60 * 24 * 365, '/', null, false, false, false, 'lax'));
    }

    function authLogin(\Illuminate\Http\Request $request)
    {
        if ($request->hasAny(['email', 'password'])) {
            return redirect()->route('auth.login');
        }

        return view('auth.login');
    }
    function authRegister()
    {
        return view('auth.register');
    }

    function authRecoverPassword()
    {
        return view('auth.forgot-password');
    }

    function authConfirmEmail()
    {
        return view('auth.verify-email');
    }
    function getAjaxServiceList(Request $request){
        $items = \App\Models\Service::select('id', 'name as text')->where('status', 1)->where('type', 'fixed');

        $provider_id = !empty($request->provider_id) ? $request->provider_id : auth()->user()->id;
        $items->where('provider_id', $provider_id );
        if (isset($request->category_id)) {
            $items->where('category_id', $request->category_id);
        }
        if (isset($request->subcategory_id)) {
            $items->where('subcategory_id', $request->subcategory_id);
        }
        $items = $items->get();
        return response()->json(['status' => 'true', 'results' => $items]);
    }
}
