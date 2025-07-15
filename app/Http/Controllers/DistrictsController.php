<?php

namespace App\Http\Controllers;

use App\Http\Requests\DistrictsRequest;
use App\Models\Districts;
use App\Models\Region;
use Illuminate\Http\Request;

class DistrictsController extends Controller
{
    public function index()
    {
//        abort_unless(Gate::allows($this->permssion.'index'),403);


        $query = Districts::orderby('id','DESC');
        if (\request()->search != ""){
            $query->where(function ($q){
                $q->Where('title', 'like', '%' . request()->search . '%');
            });

        }
        $data = $query->paginate(20);
        $title = __('districts');
        $route = route('districts.create');
        return view('districts.index',compact('data','title','route'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
//        abort_unless(Gate::allows($this->permssion.'create'),403);

        $title = __('create districts');
        $region = Region::get();

        return  view('districts.create',compact('title','region'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(DistrictsRequest $request)
    {
//        abort_unless(Gate::allows($this->permssion.'create'),403);

        $data = $request->validated();

        $districts = Districts::create($data);
        session()->flash('success', trans('record added'));
        return redirect(route('districts.index'));
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

        $data = Districts::findorfail($id);
        $region = Region::get();
        $title = __('edit', ['data_name' => $data->name]);

        return view('districts.edit',compact('data','title','region'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(DistrictsRequest $request, $id)
    {

        $data = $request->validated();
        $districts =  Districts::findorfail($id);
        $districts->update($data);
        session()->flash('success', trans('record updated'));
        return redirect(route('districts.index'));
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
        $districts =  Districts::findorfail($id);
        $districts->delete();
        session()->flash('success', trans('record deleted'));
        return redirect(route('districts.index'));
    }
}
