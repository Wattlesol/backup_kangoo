<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintEnums;
use App\Http\Requests\RegionRequest;
use App\Models\CityRegion;
use App\Models\complaints_comment;
use App\Models\Qualitycontrol;
use App\Models\QualitycontrolComment;
use App\Models\Region;
use App\Models\SanadCustomerComplaint;
use App\Models\Time;
use App\Models\TimeData;
use App\Traits\FileHandler;
use App\Models\User;
use Illuminate\Http\Request;

class QualityControlController extends Controller
{
    use FileHandler;
    public function index()
    {
//        abort_unless(Gate::allows($this->permssion.'index'),403);


        $query = Qualitycontrol::orderby('id','DESC');
        if (request()->provider_id) {
            $query->where('provider_id',  request()->provider_id );
        }
        if (request()->status) {
            $query->where('status', request()->status);
        }
        if (request()->issue_type) {
            $query->where('issue_type', request()->issue_type);
        }
        $data = $query->paginate(20);
        $sanadComplaints = collect();
        $sanadComplaintStats = ['total' => 0, 'open' => 0, 'urgent' => 0, 'resolved' => 0];

        if (auth()->user()->hasAnyRole(['admin', 'demo_admin'])) {
            $showSanadComplaints = !request()->issue_type || request()->issue_type === 'customer_complaint';
            $sanadComplaintQuery = SanadCustomerComplaint::with(['booking.service', 'booking.provider', 'customer'])
                ->when(request()->provider_id, function ($complaintQuery) {
                    $complaintQuery->whereHas('booking', fn ($bookingQuery) => $bookingQuery->where('provider_id', request()->provider_id));
                })
                ->when(request()->status, fn ($complaintQuery) => $complaintQuery->where('status', request()->status));

            if ($showSanadComplaints) {
                $sanadComplaints = (clone $sanadComplaintQuery)->latest()->paginate(20, ['*'], 'sanad_page')->withQueryString();
            }

            $statsQuery = SanadCustomerComplaint::query();
            $sanadComplaintStats = [
                'total' => (clone $statsQuery)->count(),
                'open' => (clone $statsQuery)->where('status', 'open')->count(),
                'urgent' => (clone $statsQuery)->where('priority', 'urgent')->count(),
                'resolved' => (clone $statsQuery)->where(function ($resolvedQuery) {
                    $resolvedQuery->whereNotNull('resolved_at')->orWhere('status', 'resolved');
                })->count(),
            ];
        }

       $title = "مراقبه الجوده";
        $route = '#';
       $providers = User::where('user_type', 'provider')->orderBy('display_name')->get();
        return view('QualityControl.index',compact('data','title','route', 'sanadComplaints', 'sanadComplaintStats', 'providers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
//        abort_unless(Gate::allows($this->permssion.'create'),403);

        $title = __('create region');
        $city = CityRegion::get();

        return  view('Time.create',compact('title','city'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
//        abort_unless(Gate::allows($this->permssion.'create'),403);


        $Time = Qualitycontrol::create([
            'title' => $request->title,
            'created_by' => auth()->user()->id,
            'provider_id' => $request->provider_id,
            'issue_type' => $request->issue_type ?: 'customer_complaint',
        ]);

        if ($request->filled('details') || $request->hasFile('file')) {
            $comment = new QualitycontrolComment();
            $comment->quality_control_id = $Time->id;
            $comment->comment = $request->details ?: $request->title;
            $comment->file = $request->hasFile('file') ? $this->UploadFile($request->file, 'files/') : '';
            $comment->created_by = auth()->user()->id;
            $comment->save();
        }


        session()->flash('success', trans('record added'));
        return redirect(route('complaint.show',$Time->id));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
//        abort_unless(Gate::allows($this->permssion.'edit'),403);

        $data = Qualitycontrol::findorfail($id);

        return  view('QualityControl.show',compact('data'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function reply_submitComplaint($id,Request $request)
    {
        $request->validate([
            'reply' => 'required|string',
        ]);
        // Find the complaint by ID and update the reply
        $complaint = New QualitycontrolComment();
        $complaint->comment = $request->input('reply');
        $complaint->quality_control_id = $id;
        $complaint->file =      (is_file($request->file)) ? $this->UploadFile($request->file,'files/'):"";;
        $complaint->created_by = auth()->user()->id;
        $complaint->save();
        $data = Qualitycontrol::findorfail($id);
        if (auth()->user()->user_type == "admin"){
            $data->status = ComplaintEnums::Waiting_reply_from_provider;
        }else{
            $data->status = ComplaintEnums::Waiting_reply_from_admin;
        }
        $data->save();

        return redirect()->back()->with('success', 'Your complaint has been submitted successfully.');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
//        abort_unless(Gate::allows($this->permssion.'delete'),403);
        $region =  Time::findorfail($id);
        $region->delete();
        session()->flash('success', trans('record deleted'));
        return redirect(route('time.index'));
    }
}
