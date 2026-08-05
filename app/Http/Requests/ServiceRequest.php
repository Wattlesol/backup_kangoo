<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ServiceRequest extends FormRequest
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
        if (auth()->check() && auth()->user()->hasRole('provider') && !request()->is('api/*')) {
            return [
                'id' => 'required|exists:services,id',
                'status' => 'required',
                'duration' => 'nullable',
                'partner_availability_notes' => 'nullable|string',
                'required_employee_skills' => 'nullable|string',
            ];
        }

        return [
            'name'                           => 'required|unique:services,name,'.$id,
            'category_id'                    => 'required',
            'type'                           => 'required',
            'price'                          => 'required|min:0',
            'status'                         => 'required',
            'name_ar'                        => 'nullable|string|max:255',
            'name_en'                        => 'nullable|string|max:255',
            'government_entity'              => 'nullable|string|max:255',
            'required_documents'             => 'nullable|string',
            'estimated_completion_time'      => 'nullable|string|max:255',
            'government_fee'                 => 'nullable|numeric|min:0',
            'service_fee'                    => 'nullable|numeric|min:0',
            'service_instructions'           => 'nullable|string',
            'terms_and_conditions'           => 'nullable|string',
            'partner_availability_notes'     => 'nullable|string',
            'required_employee_skills'       => 'nullable|string',
        ];
    }
    public function messages()
    {
        return [];
    }

    protected function failedValidation(Validator $validator)
    {
        if ( request()->is('api*')){
            $data = [
                'status' => 'false',
                'message' => $validator->errors()->first(),
                'all_message' =>  $validator->errors()
            ];

            throw new HttpResponseException(response()->json($data,422));
        }

        throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
    }
}
