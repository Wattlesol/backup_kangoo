<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PriceCityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }


    public function onCreate(){
        return [
            'price'=>'required|string',
            'city_id'   =>'nullable|exists:city,id|integer',
            'price_list_id'   =>'nullable|exists:price_list,id|integer',
        ];
    }
    public function onUpdate(){
        return [
            'price'=>'required|string',
            'city_id'   =>'nullable|exists:city,id|integer',
            'price_list_id'   =>'nullable|exists:price_list,id|integer',
        ];
    }
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return request()->isMethod('put') ||request()->isMethod('patch') ? $this->onUpdate() :$this->onCreate();
    }

    public function attributes()
    {
        return [
            'name'=>trans('messages.name'),
            'city_id'=>trans('messages.city'),
            'price_id'=>trans('messages.price_list'),
        ];
    }
}
