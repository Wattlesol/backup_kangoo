<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegionRequest;
use App\Models\CityRegion;
use App\Models\Region;
use App\Models\Time;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    public function index()
    {
//        abort_unless(Gate::allows($this->permssion.'index'),403);


        $query = Region::orderby('id','DESC');
        if (\request()->search != ""){
            $query->where(function ($q){
                $q->Where('title', 'like', '%' . request()->search . '%');
            });

        }
        $data = $query->paginate(20);
        $title = __('region');
        $route = route('region.create');
        return view('region.index',compact('data','title','route'));
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
        $time = Time::get();
        return  view('region.create',compact('title','city','time'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RegionRequest $request)
    {
//        abort_unless(Gate::allows($this->permssion.'create'),403);

        $data = $request->validated();

        $region = Region::create($data);
        session()->flash('success', trans('record added'));
        return redirect(route('region.index'));
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

        $data = Region::findorfail($id);
        $city = CityRegion::get();
        $title = __('edit', ['data_name' => $data->name]);
        $time = Time::get();

        return view('region.edit',compact('data','title','city','time'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(RegionRequest $request, $id)
    {

        $data = $request->validated();
        $region =  Region::findorfail($id);
        $region->update($data);
        session()->flash('success', trans('record updated'));
        return redirect(route('region.index'));
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
        $region =  Region::findorfail($id);
        $region->delete();
        session()->flash('success', trans('record deleted'));
        return redirect(route('region.index'));
    }
}
