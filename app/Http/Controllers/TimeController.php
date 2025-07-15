<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegionRequest;
use App\Models\CityRegion;
use App\Models\Region;
use App\Models\Time;
use App\Models\TimeData;
use Illuminate\Http\Request;

class TimeController extends Controller
{
    public function index()
    {
//        abort_unless(Gate::allows($this->permssion.'index'),403);


        $query = Time::orderby('id','DESC');
        if (\request()->search != ""){
            $query->where(function ($q){
                $q->Where('name', 'like', '%' . request()->search . '%');
            });

        }
        $data = $query->paginate(20);
        $title = __('Time');
        $route = route('time.create');
        return view('Time.index',compact('data','title','route'));
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


        $Time = Time::create([
            'name' => $request->name
        ]);

        foreach ($request->start_at as $key => $value){
           TimeData::create([
               'time_id' => $Time->id,
               'start_at' => $value,
               'end_at' => @$request->end_at[$key],
               'day' => $key,
               'off' => @$request->off[$key],
           ]);
        }
        session()->flash('success', trans('record added'));
        return redirect(route('time.index'));
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
//        abort_unless(Gate::allows($this->permssion.'edit'),403);

        $data = Time::findorfail($id);
        $title = __('edit', ['data_name' => $data->name]);

        return view('Time.edit',compact('data','title'));

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

        $data = $request->validated();
        $region =  Time::findorfail($id);
        $region->name = $request->name;
        $region->save();
        TimeData::where('time_id',$id)->delete();
        foreach ($request->start_at as $key => $value){
            TimeData::create([
                'time_id' => $id,
                'start_at' => $value,
                'end_at' => @$request->end_at[$key],
                'day' => $key,
                'off' => @$request->off[$key],
            ]);
        }
        session()->flash('success', trans('record updated'));
        return redirect(route('time.index'));
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
