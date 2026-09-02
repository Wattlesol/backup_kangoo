<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubCategoryRequest extends FormRequest
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
        $id = request()->route('subcategory') ?: request()->id;

        return [
            'name_en'           => 'required|string|max:150|unique:sub_categories,name_en,'.$id,
            'name_ar'           => 'required|string|max:150',
            'status'            => 'required',
            'category_id'       => 'required',
        ];
    }
}
