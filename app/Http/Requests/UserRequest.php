<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UserRequest extends FormRequest
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
                'username'          => 'required|max:255|unique:users,username,'.$id,
                'email'             => 'required|email|max:255|unique:users,email,'.$id,
                'contact_number'    => 'nullable', //unique:users,contact_number,'.$id,
                'profile_image'     => 'mimetypes:image/jpeg,image/png,image/jpg,image/gif',
                'sanad_job_title'   => 'nullable|string|max:255',
                'sanad_department'  => 'nullable|string|max:255',
                'sanad_employee_status' => 'nullable|string|in:available,busy,offline,on_leave,training',
                'sanad_permissions' => 'nullable|array',
                'sanad_permissions.*' => 'string',
                'sanad_working_hours' => 'nullable|string|max:255',
                'sanad_daily_capacity' => 'nullable|integer|min:0|max:100',
                'skills' => 'nullable|string',
                'partner_verification_document_ids' => 'required_if:user_type,provider|array|min:1',
                'partner_verification_document_ids.*' => 'integer|exists:documents,id',
        ];
    }

    public function messages()
    {
        return [
           'profile_image.*' => __('messages.image_png_gif')
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        if ( request()->is('api*')){
            $data = [
                'status' => 'false',
                'message' => $validator->errors()->first(),
                'all_message' =>  $validator->errors()
            ];

            throw new HttpResponseException(response()->json($data,406));
        }

        throw new HttpResponseException(redirect()->back()->withInput()->with('errors', $validator->errors()));
    }
}
