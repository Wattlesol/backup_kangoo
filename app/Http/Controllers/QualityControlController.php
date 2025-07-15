<?php

namespace App\Http\Controllers;

use App\Enums\ComplaintEnums;
use App\Http\Requests\RegionRequest;
use App\Models\CityRegion;
use App\Models\complaints_comment;
use App\Models\Qualitycontrol;
use App\Models\QualitycontrolComment;
use App\Models\Region;
use App\Models\Time;
use App\Models\TimeData;
use App\Traits\FileHandler;
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
        $data = $query->paginate(20);
        $title = "مراقبه الجوده";
        $route = route('time.create');
        return view('QualityControl.index',compact('data','title','route'));
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
        ]);


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
