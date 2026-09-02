<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class UserRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        if ($this->input('user_type') !== 'handyman' || $this->filled('country_id')) {
            return;
        }

        $saudiCountryId = DB::table('countries')
            ->where('code', 'SA')
            ->orWhere('name', 'Saudi Arabia')
            ->value('id');

        if ($saudiCountryId) {
            $this->merge(['country_id' => $saudiCountryId]);
        }
    }

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
        $isEmployee = request()->input('user_type') === 'handyman';
        $contactNumberRules = request()->input('user_type') === 'handyman'
            ? ['required', 'regex:/^(\+9665\d{8}|05\d{8}|5\d{8})$/']
            : ['nullable'];
        $emailRules = [
            'required',
            'email',
            'max:255',
            function ($attribute, $value, $fail) use ($id, $isEmployee) {
                if (!$isEmployee) {
                    return;
                }

                $existingCustomer = DB::table('users')
                    ->where('email', $value)
                    ->where('user_type', 'user')
                    ->when($id, function ($query) use ($id) {
                        $query->where('id', '!=', $id);
                    })
                    ->exists();

                if ($existingCustomer) {
                    $fail('This email is already registered as a customer. Please use a different email for employee access.');
                }
            },
            'unique:users,email,'.$id,
        ];

        return [
                'username'          => 'required|max:255|unique:users,username,'.$id,
                'email'             => $emailRules,
                'contact_number'    => $contactNumberRules,
                'country_id'        => $isEmployee ? 'required|exists:countries,id' : 'nullable',
                'state_id'          => $isEmployee ? 'required|exists:states,id' : 'nullable',
                'city_id'           => $isEmployee ? 'required|exists:cities,id' : 'nullable',
                'profile_image'     => 'mimetypes:image/jpeg,image/png,image/jpg,image/gif',
                'sanad_job_title'   => 'nullable|string|max:255',
                'sanad_department'  => 'nullable|string|max:255',
                'sanad_employee_status' => 'nullable|string|in:available,busy,offline,on_leave,training',
                'sanad_permissions' => 'nullable|array',
                'sanad_permissions.*' => 'string',
                'employee_permission_context' => 'nullable|string|in:admin,partner',
                'module_permissions' => 'nullable|array',
                'module_permissions.*' => 'nullable|array',
                'sanad_working_hours' => 'nullable|string|max:255',
                'sanad_work_schedule' => 'required_if:user_type,handyman|array',
                'sanad_work_schedule.start_day' => 'required_if:user_type,handyman|integer|between:0,6',
                'sanad_work_schedule.end_day' => 'required_if:user_type,handyman|integer|between:0,6',
                'sanad_work_schedule.start_time' => 'required_if:user_type,handyman|date_format:H:i',
                'sanad_work_schedule.end_time' => 'required_if:user_type,handyman|date_format:H:i|after:sanad_work_schedule.start_time',
                'sanad_daily_capacity' => 'nullable|integer|min:0|max:100',
                'skills' => 'nullable|string',
                'partner_verification_document_ids' => 'nullable|array',
                'partner_verification_document_ids.*' => 'integer|exists:documents,id',
                'custom_partner_verification_documents' => 'nullable|array',
                'custom_partner_verification_documents.*.text' => 'nullable|string|max:100',
        ];
    }

    public function messages()
    {
        return [
           'profile_image.*' => __('messages.image_png_gif'),
           'contact_number.regex' => 'Enter a valid Saudi mobile number, for example +9665XXXXXXXX, 05XXXXXXXX, or 5XXXXXXXX.'
        ];
    }

    protected function withValidator(Validator $validator)
    {
        $validator->after(function (Validator $validator) {
            if (request()->input('user_type') === 'provider') {
                $selectedDocuments = collect(request()->input('partner_verification_document_ids', []))->filter();
                $customDocuments = collect(request()->input('custom_partner_verification_documents', []))
                    ->filter(fn ($document) => filled($document['text'] ?? null));

                if ($selectedDocuments->isEmpty() && $customDocuments->isEmpty()) {
                    $validator->errors()->add(
                        'partner_verification_document_ids',
                        app()->getLocale() === 'ar'
                            ? 'اختر مستند تحقق واحداً على الأقل أو أضف مستنداً مخصصاً.'
                            : 'Select at least one verification document or add a custom document.'
                    );
                }
            }

            if (request()->input('user_type') !== 'handyman' || !request()->filled('country_id')) {
                return;
            }

            $isSaudi = DB::table('countries')
                ->where('id', request()->input('country_id'))
                ->where(function ($query) {
                    $query->where('code', 'SA')
                        ->orWhere('name', 'Saudi Arabia');
                })
                ->exists();

            if (!$isSaudi) {
                $validator->errors()->add('country_id', 'Employee country must be Saudi Arabia.');
            }
        });
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
