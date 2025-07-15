<?php

namespace App\Http\Controllers;

use App\Http\Requests\CityRequest;
use App\Models\CityRegion;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class CityController extends Controller
{
    public function index()
    {
//        abort_unless(Gate::allows($this->permssion.'index'),403);


        $query = CityRegion::orderby('id','DESC');
        if (\request()->search != ""){
            $query->where(function ($q){
                $q->Where('title', 'like', '%' . request()->search . '%');
            });

        }
        $data = $query->paginate(20);
        $title = __('city');
        $route = route('city.create');
        return view('city.index',compact('data','title','route'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
//        abort_unless(Gate::allows($this->permssion.'create'),403);

        $title = __('create city');

        return  view('city.create',compact('title'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CityRequest $request)
    {
//        abort_unless(Gate::allows($this->permssion.'create'),403);

        $data = $request->validated();

        $city = CityRegion::create($data);
        session()->flash('success', trans('record added'));
        return redirect(route('city.index'));
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

        $data = CityRegion::findorfail($id);
        $title = __('edit', ['data_name' => $data->name]);

        return view('city.edit',compact('data','title'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(CityRequest $request, $id)
    {

        $data = $request->validated();
        $city =  CityRegion::findorfail($id);
        $city->update($data);
        session()->flash('success', trans('record updated'));
        return redirect(route('city.index'));
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
        $city =  CityRegion::findorfail($id);
        $city->delete();
        session()->flash('success', trans('record deleted'));
        return redirect(route('city.index'));
    }
}
