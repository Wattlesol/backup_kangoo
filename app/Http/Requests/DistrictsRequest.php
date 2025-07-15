<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DistrictsRequest extends FormRequest
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
            'name'=>'required|string',
            'region_id'   =>'nullable|exists:region,id|integer',
        ];
    }
    public function onUpdate(){
        return [
            'name'=>'required|string',
            'region_id'   =>'nullable|exists:region,id|integer',
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
            'name'=>trans('Name'),
            'region_id'=>trans('messages.region'),
        ];
    }
}
