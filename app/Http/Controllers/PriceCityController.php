<?php

namespace App\Http\Controllers;

use App\Http\Requests\PriceCityRequest;
use App\Models\CityRegion;
use App\Models\PriceCity;
use App\Models\PriceList;
use App\Models\Region;
use Illuminate\Http\Request;

class PriceCityController extends Controller
{
    public function index()
    {
//        abort_unless(Gate::allows($this->permssion.'index'),403);


        $query = PriceCity::orderby('id','DESC');
        if (\request()->search != ""){
            $query->where(function ($q){
                $q->Where('title', 'like', '%' . request()->search . '%');
            });

        }
        $data = $query->paginate(20);
        $title = __('pricecity');
        $route = route('pricecity.create');
        return view('pricecity.index',compact('data','title','route'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
//        abort_unless(Gate::allows($this->permssion.'create'),403);

        $title = __('create pricecity');

        $city = Region::get();

        return  view('pricecity.index',compact('title','city','price_list'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PriceCityRequest $request)
    {
//        abort_unless(Gate::allows($this->permssion.'create'),403);

        $data = $request->validated();

        $pricecity = PriceCity::create($data);
        session()->flash('success', trans('record added'));
        return redirect(route('pricecity.index'));
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

        $data = PriceCity::findorfail($id);
        $city = CityRegion::get();
        $price_list = PriceList::get();
        $title = __('edit', ['data_name' => $data->name]);

        return view('pricecity.index',compact('data','title','city','price_list'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(PriceCityRequest $request, $id)
    {

        $data = $request->validated();
        $pricecity =  PriceCity::findorfail($id);
        $pricecity->update($data);
        session()->flash('success', trans('record updated'));
        return redirect(route('pricecity.index'));
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
        $pricecity =  PriceCity::findorfail($id);
        $pricecity->delete();
        session()->flash('success', trans('record deleted'));
        return redirect(route('pricecity.index'));
    }
}
