<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingHandymanMapping;
use App\Models\Payment;
use App\Models\ProviderPayout;
use App\Models\SanadDocumentRequest;
use App\Models\SanadDocumentVaultItem;
use App\Models\SanadPartnerServiceWorkflow;
use App\Models\SanadPartnerServiceAvailability;
use App\Models\SanadPartnerWorkflowStage;
use App\Models\SanadPartnerWorkflowTemplate;
use App\Models\SanadPartnerWorkflowTemplateStep;
use App\Models\SanadRequestAction;
use App\Models\Service;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Yajra\DataTables\DataTables;

class OrderController extends Controller
{
    private array $kanbanStages = [
        'submitted',
        'pending_review',
        'assigned_to_partner',
        'assigned_to_employee',
        'in_progress',
        'waiting_for_documents',
        'government_processing',
        'legal_review',
        'accounting',
        'quality_review',
        'ready_for_delivery',
        'awaiting_customer_action',
        'awaiting_quality_review',
        'completed',
    ];

    public function dashboard()
    {
        $auth_user = auth()->user();
        $query = $this->assignedBookingsQuery($auth_user);

        $pageTitle = app()->getLocale() === 'ar' ? 'لوحة تحكم عمليات الشريك' : 'Partner Operations Dashboard';
        $dashboard = $this->dashboardData($query, $auth_user);

        return view('provider.dashboard', compact('pageTitle', 'auth_user', 'dashboard'));
    }

    public function index(Request $request)
    {
        $pageTitle = app()->getLocale() === 'ar' ? 'الطلبات المسندة' : 'Assigned Orders';
        $assets = ['datatable'];
        $filter = [
            'sanad_stage' => $request->sanad_stage,
            'sanad_priority' => $request->sanad_priority,
            'sla_state' => $request->sla_state,
        ];

        return view('provider.order.index', compact('pageTitle', 'assets', 'filter'));
    }

    public function index_data(DataTables $datatable, Request $request)
    {
        $query = $this->assignedBookingsQuery(auth()->user())->with(['customer', 'service', 'handymanAdded.handyman']);

        $filter = $request->filter ?: [];
        if (!empty($filter['sanad_stage'])) {
            $query->where('sanad_stage', $filter['sanad_stage']);
        }
        if (!empty($filter['sanad_priority'])) {
            $query->where('sanad_priority', $filter['sanad_priority']);
        }
        if (($filter['sla_state'] ?? null) === 'overdue') {
            $query->whereNotNull('sla_due_at')->where('sla_due_at', '<', now())->whereNotIn('sanad_stage', ['completed', 'closed']);
        }

        return $datatable->eloquent($query)
            ->editColumn('sanad_reference', function ($booking) {
                $label = e($booking->quick_reference);
                return '<a class="btn-link btn-link-hover" href="' . route('provider.order.show', $booking->id) . '">' . $label . '</a>';
            })
            ->addColumn('customer', fn ($booking) => e(optional($booking->customer)->display_name ?: '-'))
            ->addColumn('service', function ($booking) {
                if (!$booking->service) {
                    return '-';
                }
                $isAr = app()->getLocale() === 'ar';
                return e($isAr && !empty($booking->service->name_ar) ? $booking->service->name_ar : ($booking->service->name_en ?: $booking->service->name));
            })
            ->editColumn('sanad_priority', function ($booking) {
                $priority = strtolower($booking->sanad_priority ?: 'normal');
                $label = ucfirst($priority);
                if (app()->getLocale() === 'ar') {
                    $map = [
                        'low' => 'منخفض',
                        'normal' => 'عادي',
                        'high' => 'مرتفع',
                        'urgent' => 'طارئ',
                        'critical' => 'حرج',
                    ];
                    $label = $map[$priority] ?? $label;
                }
                return '<span class="badge badge-' . $this->priorityColor($booking->sanad_priority) . '">' . e($label) . '</span>';
            })
            ->editColumn('sanad_stage', function ($booking) {
                $stage = $booking->sanad_stage ?: $booking->status ?: 'submitted';

                return '<span class="badge badge-primary">' . e(quick_status_label($stage)) . '</span>';
            })
            ->addColumn('assigned_employees', function ($booking) {
                return e($booking->handymanAdded->pluck('handyman.display_name')->filter()->implode(', ') ?: '-');
            })
            ->addColumn('sla', function ($booking) {
                if (!$booking->sla_due_at) {
                    return '-';
                }
                $class = $booking->sla_due_at->isPast() && !in_array($booking->sanad_stage, ['completed', 'closed'], true) ? 'text-danger' : 'text-muted';
                return '<span class="' . $class . '">' . e($booking->sla_due_at->format('Y-m-d H:i')) . '</span>';
            })
            ->editColumn('updated_at', fn ($booking) => e(dateAgoFormate($booking->updated_at, true)))
            ->addColumn('action', fn ($booking) => '<a href="' . route('provider.order.show', $booking->id) . '" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></a>')
            ->addIndexColumn()
            ->rawColumns(['sanad_reference', 'sanad_priority', 'sanad_stage', 'sla', 'action'])
            ->toJson();
    }

    public function show($id)
    {
        $booking = $this->assignedBookingsQuery(auth()->user())
            ->with([
                'customer',
                'service.category',
                'payment.paymentHistory',
                'handymanAdded.handyman',
                'sanadDocuments',
                'sanadDocumentRequests.document',
                'sanadRequestActions.actor',
                'sanadWorkflowStages.employee',
                'sanadChatThreads.messages.sender',
            ])
            ->findOrFail($id);

        $pageTitle = (app()->getLocale() === 'ar' ? 'طلب مسند #' : 'Assigned Order #') . $booking->quick_reference;
        $currentlyAssignedIds = $booking->handymanAdded->pluck('handyman_id')->map(fn ($id) => (int) $id);
        $employees = $this->employeesQuery()->get()->filter(function ($employee) use ($currentlyAssignedIds) {
            return $employee->isScheduledToWorkAt(now()) || $currentlyAssignedIds->contains((int) $employee->id);
        })->values();
        $recommendations = $this->employeeRecommendations($booking, $employees);
        $workflowTemplates = SanadPartnerWorkflowTemplate::with(['steps', 'serviceLinks.service'])
            ->where('provider_id', auth()->id())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $privateThread = $booking->sanadChatThreads->firstWhere('thread_type', 'partner_internal');
        $chatMessages = $privateThread ? $privateThread->messages()->with('sender')->latest()->take(30)->get()->reverse()->values() : collect();
        $serviceDocumentOptions = $this->serviceDocumentOptions($booking->service);

        return view('provider.order.view', compact(
            'pageTitle',
            'booking',
            'employees',
            'recommendations',
            'chatMessages',
            'workflowTemplates',
            'serviceDocumentOptions'
        ));
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|integer',
            'action' => 'required|string|in:accept_order,reject_order,request_missing_documents,add_internal_note,complete_current_stage,request_admin_review,mark_completed',
            'reason' => 'nullable|string|max:1000',
            'internal_note' => 'nullable|string|max:2000',
        ]);

        if (in_array($request->action, ['reject_order', 'request_missing_documents', 'request_admin_review'], true) && !$request->filled('reason')) {
            return $this->statusResponse($request, false, 'A reason is required for this action.', 422);
        }

        $booking = $this->assignedBookingsQuery(auth()->user())->findOrFail($request->booking_id);
        $previousStatus = $booking->status;
        $previousStage = $booking->sanad_stage;
        [$status, $stage] = $this->stageForAction($request->action, $booking);

        $booking->status = $status;
        $booking->sanad_stage = $stage;
        if ($request->filled('reason')) {
            $booking->reason = $request->reason;
        }
        if ($stage === 'completed' && empty($booking->closed_at)) {
            $booking->closed_at = now();
        }
        if ($stage === 'assigned_to_partner' && empty($booking->assigned_at)) {
            $booking->assigned_at = now();
        }
        $booking->save();

        $this->recordAction($booking, $request->action, $previousStatus, $previousStage, $request->reason, $request->internal_note);

        return $this->statusResponse($request, true, 'Partner order action recorded.');
    }

    public function statistics()
    {
        $dashboard = $this->dashboardData($this->assignedBookingsQuery(auth()->user()), auth()->user());

        return response()->json([
            'status' => true,
            'kpis' => $dashboard['kpis'],
        ]);
    }

    public function assignEmployees(Request $request, $id)
    {
        $booking = $this->assignedBookingsQuery(auth()->user())->findOrFail($id);
        $request->validate([
            'handyman_id' => 'nullable|array',
            'handyman_id.*' => 'integer',
            'assignment_mode' => 'required|string|in:manual,sequential,parallel,automatic_next_stage',
            'workflow_template_id' => 'nullable|integer',
        ]);

        $allowed = $this->employeesQuery()->get()
            ->filter(fn ($employee) => $employee->dailyAvailableCapacity(now()) > 0)
            ->pluck('id')->map(fn ($id) => (int) $id);
        $employeeIds = collect($request->handyman_id ?: [])->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($employeeIds->diff($allowed)->isNotEmpty()) {
            return redirect()->back()->withErrors('One or more selected employees cannot be assigned to this order.');
        }

        $template = null;
        if ($request->filled('workflow_template_id')) {
            $template = SanadPartnerWorkflowTemplate::with('steps')
                ->where('provider_id', auth()->id())
                ->where('is_active', true)
                ->findOrFail($request->workflow_template_id);
        }

        $booking->handymanAdded()->delete();
        $booking->sanadWorkflowStages()->delete();

        foreach ($employeeIds as $index => $employeeId) {
            BookingHandymanMapping::create(['booking_id' => $booking->id, 'handyman_id' => $employeeId]);
        }

        $steps = $template ? $template->steps : collect();
        if ($steps->isEmpty()) {
            $steps = $employeeIds->map(function ($employeeId, $index) {
                return (object) [
                    'stage_name' => 'Assigned Work',
                    'role' => null,
                    'execution_order' => $index + 1,
                    'parallel_group' => null,
                    'estimated_duration_minutes' => null,
                ];
            });
        }

        foreach ($steps as $index => $step) {
            $employeeId = $employeeIds->get($index) ?: $employeeIds->first();
            SanadPartnerWorkflowStage::create([
                'booking_id' => $booking->id,
                'workflow_template_id' => optional($template)->id,
                'employee_id' => $employeeId,
                'stage_name' => $step->stage_name,
                'role' => $step->role,
                'execution_order' => $request->assignment_mode === 'parallel' ? 1 : ($step->execution_order ?: $index + 1),
                'parallel_group' => $request->assignment_mode === 'parallel' ? 1 : $step->parallel_group,
                'estimated_duration_minutes' => $step->estimated_duration_minutes,
                'assignment_mode' => $request->assignment_mode,
                'status' => $index === 0 || $request->assignment_mode === 'parallel' ? 'active' : 'pending',
            ]);
        }

        $previousStage = $booking->sanad_stage;
        $previousStatus = $booking->status;
        if ($employeeIds->isNotEmpty()) {
            $booking->status = 'accept';
            $booking->sanad_stage = 'assigned_to_employee';
            $booking->assigned_by = auth()->id();
            $booking->assigned_at = now();
            $booking->save();
        }

        $this->recordAction($booking, 'reassign_employees', $previousStatus, $previousStage, null, 'Partner employee workflow updated.', [
            'employee_ids' => $employeeIds->all(),
            'assignment_mode' => $request->assignment_mode,
            'workflow_template_id' => optional($template)->id,
        ]);

        return redirect()->back()->withSuccess('Employee assignment updated.');
    }

    public function workflows()
    {
        $pageTitle = app()->getLocale() === 'ar' ? 'مسارات عمل الموظفين' : 'Employee Workflows';
        $workflows = SanadPartnerWorkflowTemplate::with(['steps', 'serviceLinks.service'])
            ->where('provider_id', auth()->id())
            ->latest()
            ->get();

        return view('provider.workflows.index', compact('pageTitle', 'workflows'));
    }

    public function workflowForm($id = null)
    {
        $workflow = $id
            ? SanadPartnerWorkflowTemplate::with(['steps', 'serviceLinks'])->where('provider_id', auth()->id())->findOrFail($id)
            : new SanadPartnerWorkflowTemplate(['is_active' => true]);
        $pageTitle = $workflow->exists ? 'Update Employee Workflow' : 'Create Employee Workflow';
        $services = Service::where('service_type', 'service')->where('status', 1)->orderBy('name')->get();
        $linkedServiceIds = $workflow->exists ? $workflow->serviceLinks->pluck('service_id')->all() : [];

        return view('provider.workflows.create', compact('pageTitle', 'workflow', 'services', 'linkedServiceIds'));
    }

    public function storeWorkflow(Request $request, $id = null)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'integer',
            'is_active' => 'nullable|boolean',
            'stage_name' => 'required|array|min:1',
            'stage_name.*' => 'required|string|max:255',
            'role' => 'nullable|array',
            'role.*' => 'nullable|string|max:255',
            'estimated_duration_minutes' => 'nullable|array',
            'estimated_duration_minutes.*' => 'nullable|integer|min:1',
            'stage_mode' => 'nullable|array',
            'stage_mode.*' => 'nullable|string|in:sequential,parallel',
        ]);

        $workflow = $id
            ? SanadPartnerWorkflowTemplate::where('provider_id', auth()->id())->findOrFail($id)
            : new SanadPartnerWorkflowTemplate(['provider_id' => auth()->id()]);

        $workflow->fill([
            'name' => $request->name,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ])->save();

        $workflow->steps()->delete();
        foreach ($request->stage_name as $index => $stageName) {
            $stageMode = $request->stage_mode[$index] ?? 'sequential';
            SanadPartnerWorkflowTemplateStep::create([
                'workflow_template_id' => $workflow->id,
                'stage_name' => $stageName,
                'role' => $request->role[$index] ?? null,
                'execution_order' => $index + 1,
                'parallel_group' => $stageMode === 'parallel' ? 1 : null,
                'estimated_duration_minutes' => $request->estimated_duration_minutes[$index] ?? null,
            ]);
        }

        SanadPartnerServiceWorkflow::where('provider_id', auth()->id())->where('workflow_template_id', $workflow->id)->delete();
        foreach (array_filter($request->service_ids ?: []) as $serviceId) {
            if (!Service::where('service_type', 'service')->where('status', 1)->where('id', $serviceId)->exists()) {
                continue;
            }
            SanadPartnerServiceWorkflow::create([
                'provider_id' => auth()->id(),
                'service_id' => $serviceId,
                'workflow_template_id' => $workflow->id,
                'is_default' => false,
            ]);
        }

        return redirect()->route('provider.workflows.index')->withSuccess('Employee workflow saved.');
    }

    public function destroyWorkflow($id)
    {
        $workflow = SanadPartnerWorkflowTemplate::where('provider_id', auth()->id())->findOrFail($id);
        $workflow->delete();

        return redirect()->route('provider.workflows.index')->withSuccess('Employee workflow removed.');
    }

    public function completeWorkflowStage(Request $request, $id, $stageId)
    {
        $booking = $this->assignedBookingsQuery(auth()->user())->findOrFail($id);
        $stage = $booking->sanadWorkflowStages()->findOrFail($stageId);

        $stage->status = 'completed';
        $stage->completed_at = now();
        $stage->save();

        $this->activateNextWorkflowStages($booking);

        $previousStage = $booking->sanad_stage;
        if (!$booking->sanadWorkflowStages()->where('status', '!=', 'completed')->exists()) {
            $booking->sanad_stage = 'awaiting_quality_review';
            $booking->save();
        }

        $this->recordAction($booking, 'complete_current_stage', $booking->status, $previousStage, null, 'Workflow stage completed.', [
            'workflow_stage_id' => $stage->id,
        ]);

        return redirect()->back()->withSuccess('Workflow stage completed.');
    }

    public function services()
    {
        $pageTitle = app()->getLocale() === 'ar' ? 'توفر خدمات كويك' : 'Quick Services Availability';
        $services = Service::where('service_type', 'service')->where('status', 1)->orderBy('name')->get();
        $availability = SanadPartnerServiceAvailability::where('provider_id', auth()->id())->get()->keyBy('service_id');

        return view('provider.services', compact('pageTitle', 'services', 'availability'));
    }

    public function updateServices(Request $request)
    {
        $request->validate([
            'services' => 'nullable|array',
            'services.*.is_enabled' => 'nullable|boolean',
            'services.*.availability' => 'nullable|string|max:255',
            'services.*.estimated_execution_time' => 'nullable|string|max:255',
            'services.*.required_employee_skills' => 'nullable|string|max:1000',
            'services.*.internal_notes' => 'nullable|string|max:2000',
        ]);

        foreach ($request->services ?: [] as $serviceId => $data) {
            $service = Service::where('service_type', 'service')->where('status', 1)->find($serviceId);
            if (!$service) {
                continue;
            }
            SanadPartnerServiceAvailability::updateOrCreate(
                ['provider_id' => auth()->id(), 'service_id' => $service->id],
                [
                    'is_enabled' => !empty($data['is_enabled']),
                    'availability' => $data['availability'] ?? null,
                    'estimated_execution_time' => $data['estimated_execution_time'] ?? null,
                    'required_employee_skills' => collect(explode(',', $data['required_employee_skills'] ?? ''))->map(fn ($item) => trim($item))->filter()->values()->all(),
                    'internal_notes' => $data['internal_notes'] ?? null,
                ]
            );
        }

        return redirect()->back()->withSuccess('Sanad service availability updated.');
    }

    public function kanban()
    {
        $pageTitle = app()->getLocale() === 'ar' ? 'لوحة العمليات' : 'Operations Board';
        $columns = collect($this->kanbanStages)->mapWithKeys(function ($stage) {
            return [$stage => $this->assignedBookingsQuery(auth()->user())
                ->with(['customer', 'service', 'handymanAdded.handyman'])
                ->where('sanad_stage', $stage)
                ->latest()
                ->get()];
        });

        return view('provider.kanban', compact('pageTitle', 'columns'));
    }

    public function moveKanban(Request $request, $id)
    {
        $request->validate(['sanad_stage' => 'required|string|in:' . implode(',', $this->kanbanStages)]);
        $booking = $this->assignedBookingsQuery(auth()->user())->findOrFail($id);
        $previousStage = $booking->sanad_stage;
        $booking->sanad_stage = $request->sanad_stage;
        if ($request->sanad_stage === 'completed') {
            $booking->status = 'completed';
            $booking->closed_at = $booking->closed_at ?: now();
        }
        $booking->save();
        $this->recordAction($booking, 'kanban_stage_moved', $booking->status, $previousStage, null, 'Kanban stage updated.');

        return response()->json(['status' => true, 'message' => 'Kanban stage updated.']);
    }

    public function employees()
    {
        $pageTitle = app()->getLocale() === 'ar' ? 'الموظفون' : 'Employees';
        $employees = $this->employeesQuery()
            ->withCount(['handyman as assigned_orders_count' => function ($query) {
                $query->whereHas('bookings', fn ($booking) => $booking->whereNotIn('sanad_stage', ['completed', 'closed'])->where('status', '!=', 'cancelled'));
            }])
            ->orderBy('display_name')
            ->get();

        return view('provider.employees', compact('pageTitle', 'employees'));
    }

    public function destroyEmployee($id)
    {
        $employee = User::query()
            ->where('user_type', 'handyman')
            ->where('provider_id', auth()->id())
            ->findOrFail($id);

        $hasActiveOrders = BookingHandymanMapping::where('handyman_id', $employee->id)
            ->whereHas('bookings', function ($query) {
                $query->whereNotIn('sanad_stage', ['completed', 'closed'])
                    ->where('status', '!=', 'cancelled');
            })
            ->exists();

        if ($hasActiveOrders) {
            return redirect()->back()->withErrors(
                app()->getLocale() === 'ar'
                    ? 'لا يمكن إزالة موظف لديه طلبات نشطة. أعد إسناد الطلبات أولاً.'
                    : 'This employee has active orders. Reassign those orders before removing the employee.'
            );
        }

        $employee->delete();

        return redirect()->route('provider.employees.index')->withSuccess(
            app()->getLocale() === 'ar' ? 'تمت إزالة الموظف بنجاح.' : 'Staff member removed successfully.'
        );
    }

    public function performance()
    {
        $pageTitle = app()->getLocale() === 'ar' ? 'أداء الموظفين' : 'Employee Performance';
        $employees = $this->employeesQuery()->get()->map(function ($employee) {
            $assigned = Booking::whereHas('handymanAdded', fn ($query) => $query->where('handyman_id', $employee->id));
            $completed = (clone $assigned)->whereIn('sanad_stage', ['completed', 'closed'])->count();
            $delayed = (clone $assigned)->whereNotNull('sla_due_at')->where('sla_due_at', '<', now())->whereNotIn('sanad_stage', ['completed', 'closed'])->count();
            $total = (clone $assigned)->count();

            $employee->sanad_metrics = [
                'completed_orders' => $completed,
                'average_completion_time' => (float) ($employee->sanad_average_completion_minutes ?: 0),
                'delayed_orders' => $delayed,
                'customer_rating' => 0,
                'quality_score' => (float) ($employee->sanad_quality_score ?: 0),
                'reopened_orders' => (clone $assigned)->where('reason', 'like', '%rework%')->count(),
                'sla_compliance' => (float) ($employee->sanad_sla_compliance_rate ?: 0),
                'productivity' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            ];

            return $employee;
        });

        return view('provider.performance', compact('pageTitle', 'employees'));
    }

    public function financial()
    {
        $pageTitle = app()->getLocale() === 'ar' ? 'المركز المالي' : 'Financial Center';
        $bookings = $this->assignedBookingsQuery(auth()->user())->with('payment')->get();
        $payments = Payment::whereIn('booking_id', $bookings->pluck('id'))->latest()->get();
        $paid = $payments->where('payment_status', 'paid')->sum('total_amount');
        $pending = $payments->whereIn('payment_status', ['pending', 'pending_by_admin', 'advanced_paid'])->sum('total_amount');
        $settlements = ProviderPayout::where('provider_id', auth()->id())->latest()->get();
        $wallet = Wallet::where('user_id', auth()->id())->where('status', 1)->first();
        $commission = $paid - $settlements->sum('amount');

        return view('provider.financial', compact('pageTitle', 'payments', 'paid', 'pending', 'settlements', 'wallet', 'commission'));
    }

    public function notifications()
    {
        $pageTitle = app()->getLocale() === 'ar' ? 'مركز الإشعارات' : 'Notification Center';
        $notifications = auth()->user()->notifications()->latest()->paginate(20);

        return view('provider.notifications', compact('pageTitle', 'notifications'));
    }

    public function profile()
    {
        $pageTitle = app()->getLocale() === 'ar' ? 'الملف التعريفي للشريك' : 'Partner Profile';
        $provider = auth()->user()->load(['providerDocument.document', 'providerbank', 'providerslotsmapping']);
        $services = SanadPartnerServiceAvailability::with('service')->where('provider_id', auth()->id())->get();

        return view('provider.profile', compact('pageTitle', 'provider', 'services'));
    }

    public function uploadProfileDocument(Request $request, $id)
    {
        $providerDocument = auth()->user()->providerDocument()->where('id', $id)->firstOrFail();
        $request->validate([
            'provider_document' => 'required|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:10240',
        ]);

        storeMediaFile($providerDocument, $request->file('provider_document'), 'provider_document');
        $providerDocument->is_verified = 0;
        $providerDocument->verification_status = 'pending';
        $providerDocument->review_reason = null;
        $providerDocument->reviewed_by = null;
        $providerDocument->reviewed_at = null;
        $providerDocument->save();

        return redirect()->back()->withSuccess('Verification document uploaded for Sanad review.');
    }

    private function assignedBookingsQuery(User $user)
    {
        abort_unless($user->user_type === 'provider', 403);

        return Booking::query()->where('provider_id', $user->id);
    }

    private function employeesQuery()
    {
        return User::where('user_type', 'handyman')->where('provider_id', auth()->id())->where('status', 1);
    }

    private function dashboardData($query, User $provider)
    {
        $total = (clone $query)->count();
        $new = (clone $query)->whereIn('sanad_stage', ['submitted', 'pending_review', 'assigned_to_partner'])->count();
        $inProgress = (clone $query)->whereIn('sanad_stage', ['assigned_to_employee', 'in_progress', 'government_processing', 'legal_review', 'accounting', 'quality_review'])->count();
        $completed = (clone $query)->whereIn('sanad_stage', ['completed', 'closed'])->count();
        $delayed = (clone $query)->whereNotNull('sla_due_at')->where('sla_due_at', '<', now())->whereNotIn('sanad_stage', ['completed', 'closed'])->count();
        $waitingCustomer = (clone $query)->whereIn('sanad_stage', ['waiting_for_documents', 'awaiting_customer_action'])->count();
        $waitingGovernment = (clone $query)->where('sanad_stage', 'government_processing')->count();
        $payments = Payment::whereIn('booking_id', (clone $query)->pluck('id'))->get();
        $settled = ProviderPayout::where('provider_id', $provider->id)->sum('amount');
        $revenue = $payments->where('payment_status', 'paid')->sum('total_amount');

        $isAr = app()->getLocale() === 'ar';
        return [
            'kpis' => [
                ($isAr ? 'إجمالي الطلبات' : 'Total Orders') => $total,
                ($isAr ? 'الطلبات الجديدة' : 'New Orders') => $new,
                ($isAr ? 'طلبات قيد التنفيذ' : 'Orders In Progress') => $inProgress,
                ($isAr ? 'الطلبات المكتملة' : 'Completed Orders') => $completed,
                ($isAr ? 'الطلبات المتأخرة' : 'Delayed Orders') => $delayed,
                ($isAr ? 'بانتظار العميل' : 'Waiting for Customer') => $waitingCustomer,
                ($isAr ? 'بانتظار الجهة الحكومية' : 'Waiting for Government') => $waitingGovernment,
                ($isAr ? 'متوسط اتفاقية مستوى الخدمة' : 'Average SLA') => $this->averageSlaLabel($query),
                ($isAr ? 'رضا العملاء' : 'Customer Satisfaction') => '0%',
                ($isAr ? 'الموظفون النشطون' : 'Active Employees') => $this->employeesQuery()->where('sanad_employee_status', 'available')->count(),
                ($isAr ? 'حجم العمل الحالي' : 'Current Workload') => $inProgress,
                ($isAr ? 'الإيرادات الشهرية' : 'Monthly Revenue') => getPriceFormat($revenue),
                ($isAr ? 'تسويات معلقة' : 'Pending Settlement') => getPriceFormat(max($revenue - $settled, 0)),
                ($isAr ? 'عمولة المنصة' : 'Platform Commission') => getPriceFormat(max($revenue - $settled, 0)),
            ],
            'today_tasks' => (clone $query)->whereDate('updated_at', now()->toDateString())->latest()->take(6)->get(),
            'recent_orders' => (clone $query)->with(['customer', 'service'])->latest()->take(8)->get(),
            'sla_alerts' => (clone $query)->whereNotNull('sla_due_at')->where('sla_due_at', '<=', now()->addDay())->whereNotIn('sanad_stage', ['completed', 'closed'])->latest()->take(6)->get(),
            'employee_workload' => $this->employeesQuery()->withCount(['handyman as active_orders_count' => function ($workloadQuery) {
                $workloadQuery->whereHas('bookings', fn ($bookingQuery) => $bookingQuery->whereNotIn('sanad_stage', ['completed', 'closed']));
            }])->orderByDesc('active_orders_count')->take(8)->get(),
        ];
    }

    private function employeeRecommendations(Booking $booking, $employees)
    {
        return $employees->map(function ($employee) use ($booking) {
            $capacity = max((int) ($employee->sanad_daily_capacity ?: 1), 1);
            $availableCapacity = $employee->dailyAvailableCapacity(now());
            $active = max(0, $capacity - $availableCapacity);
            $skills = collect(json_decode($employee->skills ?: '[]', true) ?: []);
            $serviceSkills = collect(optional($booking->service)->required_employee_skills ?: []);
            $skillScore = $serviceSkills->isEmpty() ? 20 : $serviceSkills->intersect($skills)->count() * 20;
            $isOnShift = $employee->isScheduledToWorkAt(now());
            $availabilityScore = $isOnShift && ($employee->sanad_employee_status === 'available' || $employee->is_available) ? 20 : 0;
            $workloadScore = max(0, 20 - (($active / $capacity) * 20));
            $qualityScore = min(20, ((float) $employee->sanad_quality_score / 100) * 20);
            $slaScore = min(20, ((float) $employee->sanad_sla_compliance_rate / 100) * 20);

            $employee->recommendation_score = round($skillScore + $availabilityScore + $workloadScore + $qualityScore + $slaScore, 2);
            $employee->recommendation_breakdown = [
                'skills' => round($skillScore, 2),
                'availability' => round($availabilityScore, 2),
                'is_on_shift' => $isOnShift,
                'scheduled_daily_hours' => $employee->scheduledDailyHours(),
                'available_capacity_today' => $availableCapacity,
                'workload' => round($workloadScore, 2),
                'quality' => round($qualityScore, 2),
                'sla' => round($slaScore, 2),
            ];
            $employee->active_orders_count = $active;

            return $employee;
        })->sortByDesc('recommendation_score')->values();
    }

    private function stageForAction(string $action, Booking $booking): array
    {
        $status = $booking->status;
        $stage = $booking->sanad_stage ?: 'submitted';

        if ($action === 'accept_order') {
            return ['accept', 'assigned_to_partner'];
        }
        if ($action === 'reject_order') {
            return ['rejected', 'rejected'];
        }
        if ($action === 'request_missing_documents') {
            return [$status, 'waiting_for_documents'];
        }
        if ($action === 'complete_current_stage') {
            $next = $this->nextLifecycleStage($stage);
            return [$next === 'completed' ? 'completed' : $status, $next];
        }
        if ($action === 'request_admin_review') {
            return [$status, 'awaiting_quality_review'];
        }
        if ($action === 'mark_completed') {
            return ['completed', 'completed'];
        }

        return [$status, $stage];
    }

    private function nextLifecycleStage(string $currentStage): string
    {
        $lifecycle = config('sanad.request_lifecycle', []);
        $index = array_search($currentStage, $lifecycle, true);

        return $index === false ? 'in_progress' : ($lifecycle[$index + 1] ?? 'completed');
    }

    private function activateNextWorkflowStages(Booking $booking): void
    {
        $stages = $booking->sanadWorkflowStages()->get();
        foreach ($stages->where('status', 'pending') as $stage) {
            $dependencies = collect($stage->depends_on_stage_ids ?: []);
            $dependencyComplete = $dependencies->isEmpty()
                ? !$stages->where('execution_order', '<', $stage->execution_order)->where('status', '!=', 'completed')->count()
                : !$stages->whereIn('id', $dependencies)->where('status', '!=', 'completed')->count();

            if ($dependencyComplete) {
                $stage->status = 'active';
                $stage->started_at = now();
                $stage->save();
            }
        }
    }

    private function recordAction(Booking $booking, string $action, ?string $previousStatus, ?string $previousStage, ?string $reason = null, ?string $note = null, array $metadata = []): void
    {
        SanadRequestAction::create([
            'booking_id' => $booking->id,
            'actor_id' => auth()->id(),
            'actor_role' => 'provider',
            'action' => $action,
            'previous_status' => $previousStatus,
            'current_status' => $booking->status,
            'previous_stage' => $previousStage,
            'current_stage' => $booking->sanad_stage,
            'reason' => $reason,
            'internal_note' => $note,
            'metadata' => array_merge(['source' => 'partner_portal'], $metadata),
        ]);
    }

    private function statusResponse(Request $request, bool $status, string $message, int $code = 200)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => $status, 'message' => $message], $code);
        }

        return $status
            ? redirect()->back()->withSuccess($message)
            : redirect()->back()->withErrors($message);
    }

    private function averageSlaLabel($query): string
    {
        $orders = (clone $query)->whereNotNull('assigned_at')->whereNotNull('closed_at')->get();
        if ($orders->isEmpty()) {
            return '-';
        }

        $minutes = $orders->avg(fn ($order) => $order->assigned_at->diffInMinutes($order->closed_at));

        return round($minutes / 60, 1) . 'h';
    }

    private function priorityColor(?string $priority): string
    {
        return [
            'urgent' => 'danger',
            'high' => 'warning',
            'normal' => 'primary',
            'low' => 'secondary',
        ][$priority ?: 'normal'] ?? 'primary';
    }

    private function serviceDocumentOptions(?Service $service)
    {
        return collect(optional($service)->required_documents ?: [])
            ->map(function ($document, $index) {
                if (is_array($document)) {
                    $storedName = trim((string) ($document['name'] ?? $document['label'] ?? $document['title'] ?? $document['key'] ?? ''));
                    $name = localized_service_document_name($document, '');
                    $key = trim((string) ($document['key'] ?? \Str::slug($storedName ?: 'document-'.$index, '_')));

                    return $name ? ['key' => $key, 'name' => $name] : null;
                }

                $name = trim((string) $document);

                return $name ? ['key' => \Str::slug($name, '_'), 'name' => $name] : null;
            })
            ->filter()
            ->values();
    }
}
