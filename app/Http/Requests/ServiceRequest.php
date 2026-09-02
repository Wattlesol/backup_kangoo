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
        $id = request()->route('service') ?: request()->id;
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
            'name_en'                        => 'required|string|max:255|unique:services,name_en,'.$id,
            'name_ar'                        => 'required|string|max:255',
            'category_id'                    => 'required',
            'type'                           => 'nullable',
            'price'                          => 'nullable|numeric|min:0',
            'status'                         => 'required',
            'government_entity'              => 'nullable|string|max:255',
            'required_documents'             => 'nullable',
            'required_documents.*.name'      => 'required|string|max:255',
            'required_documents.*.name_ar'   => 'required|string|max:255',
            'required_documents.*.key'       => 'required|string|max:255',
            'required_documents.*.required'  => 'nullable|in:0,1',
            'required_documents.*.approval_required' => 'nullable|in:0,1',
            'required_documents.*.mime_types' => 'nullable|string|max:500',
            'required_documents.*.max_size_mb' => 'nullable|integer|min:1|max:100',
            'estimated_completion_time'      => 'nullable|string|max:255',
            'government_fee'                 => 'nullable|numeric|min:0',
            'service_fee'                    => 'nullable|numeric|min:0',
            'service_instructions'           => 'nullable',
            'service_instructions.*.title'   => 'nullable|string|max:255',
            'service_instructions.*.instruction' => 'nullable|string|max:4000',
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
