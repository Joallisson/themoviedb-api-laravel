<?php

namespace App\Http\Requests\User;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

class StoreUserRequest extends FormRequest
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
            'name'     => 'required|min:6|max:255|string',
            'email'    => 'max:255|required|email|unique:users',
            'username' => 'required|min:4|max:50|unique:users|string',
            'password' => 'required|min:8|max:50|string'
        ];
    }

    public function messages()
    {
        return [
                'required'          => 'O campo :attribute é obrigatório.',
                'min'               => 'O campo :attribute deve ter no mínimo :min caracteres',
                'max'               => 'O campo :attribute deve ter no máximo :max caracteres',
                'string'            => 'O campo :attribute deve ser um texto',
                'email'             => 'O email fornecido não está no formato correto',
                'email.unique'      => 'O email fornecido já está em uso',
                'username.unique'   => 'O username fornecido já está em uso'
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
