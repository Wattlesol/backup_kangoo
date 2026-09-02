<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\HandymanType;

class HandymanTypeRequest extends FormRequest
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
        return [
            'name'              => 'required',
            'commission'        => 'required',
            'status'            => 'required',
        ];
    }

    protected function withValidator(Validator $validator)
    {
        $validator->after(function (Validator $validator) {
            $name = trim((string) $this->input('name'));

            if ($name === '') {
                return;
            }

            $query = HandymanType::withTrashed()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]);

            if ($this->filled('id')) {
                $query->where('id', '!=', $this->input('id'));
            }

            if ($query->exists()) {
                $validator->errors()->add('name', 'Employee type name already exists.');
            }
        });
    }
}
