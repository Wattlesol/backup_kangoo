<?php

namespace App\Http\Controllers;

use App\Http\Requests\PriceListRequest;
use App\Models\CityRegion;
use App\Models\PriceCity;
use App\Models\PriceList;
use App\Models\Region;
use Illuminate\Http\Request;

class PriceListController extends Controller
{
    public function index()
    {
//        abort_unless(Gate::allows($this->permssion.'index'),403);


        $query = PriceList::orderby('id','DESC');
        if (\request()->search != ""){
            $query->where(function ($q){
                $q->Where('title', 'like', '%' . request()->search . '%');
            });

        }
        $data = $query->paginate(20);
        $title = __('pricelist');
        $route = route('pricelist.create');
        return view('pricelist.index',compact('data','title','route'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
//        abort_unless(Gate::allows($this->permssion.'create'),403);

        $title = __('create pricelist');
        $city = Region::get();

        return  view('pricelist.create',compact('title','city'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(PriceListRequest $request)
    {
//        abort_unless(Gate::allows($this->permssion.'create'),403);

        $data = $request->validated();

        $pricelist = PriceList::create(['name'=>$data['name']]);

        foreach ($request->price as $key => $value){
            if ($value){
            PriceCity::create([
                'price_list_id' => $pricelist->id,
                'city_id' => $request->city_id[$key],
                'price' => $value
            ]);
            }
        }
        session()->flash('success', trans('record added'));
        return redirect(route('pricelist.index'));
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

        $data = PriceList::findorfail($id);
        $title = __('edit', ['data_name' => $data->name]);
        $PriceCity = PriceCity::where('price_list_id', $id)->get();
        $city = Region::get();

        return view('pricelist.edit',compact('data','title','PriceCity','city'));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(PriceListRequest $request, $id)
    {

        $data = $request->validated();
        $pricelist =  PriceList::findorfail($id);
        $pricelist->update($data);
        PriceCity::where('price_list_id', $id)->delete();
        foreach ($request->price as $key => $value){
            if ($value){
                PriceCity::create([
                    'price_list_id' => $id,
                    'city_id' => $request->city_id[$key],
                    'price' => $value
                ]);
            }
        }
        session()->flash('success', trans('record updated'));
        return redirect(route('pricelist.index'));
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
        $pricelist =  PriceList::findorfail($id);
        $pricelist->delete();
        session()->flash('success', trans('record deleted'));
        return redirect(route('pricelist.index'));
    }
}
