<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentRequest extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = request()->id;
        return [
            'name'              => 'required|string|max:100|unique:documents,name,'.$id,
            'name_ar'           => 'required|string|max:100|unique:documents,name_ar,'.$id,
            'status'            => 'required',
        ];
    }
}
