<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
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
        $id = request()->route('category') ?: request()->id;
        return [
            'name_en'           => 'required|string|max:150|unique:categories,name_en,'.$id,
            'name_ar'           => 'required|string|max:150',
            'display_order'     => 'nullable|integer|min:0',
            'status'            => 'required',
        ];
    }
}
