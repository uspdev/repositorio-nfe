<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNfeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()?->can('admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'xml' => ['required', 'file', 'extensions:xml', 'max:5120'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'xml.required' => 'Selecione um arquivo XML da NF-e para enviar.',
            'xml.file' => 'O item enviado deve ser um arquivo válido.',
            'xml.extensions' => 'O arquivo deve possuir a extensão .xml.',
            'xml.max' => 'O arquivo XML não pode exceder 5 MB.',
        ];
    }
}
