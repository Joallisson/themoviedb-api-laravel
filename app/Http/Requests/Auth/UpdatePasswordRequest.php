<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class UpdatePasswordRequest extends FormRequest
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
            'password' => 'required|min:8|max:50|confirmed',
            //'password_confirmation' => 'required|min:8|max:50',
        ];
    }

    public function messages()
    {
        return [
                'required'          => 'O campo :attribute é obrigatório.',
                'min'               => 'O campo :attribute deve ter no mínimo :min caracteres',
                'max'               => 'O campo :attribute deve ter no máximo :max caracteres',
                'confirmed'         => 'As senhas não coincidem'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json( $validator->getMessageBag()->all(),
            JsonResponse::HTTP_UNPROCESSABLE_ENTITY )
        );
    }
}
