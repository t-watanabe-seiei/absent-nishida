<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $parentId = $this->route('id');
        
        return [
            'seito_id' => 'required|exists:students,seito_id',
            'parent_name' => 'required|string|max:255',
            'parent_relationship' => 'required|in:父,母,その他',
            'parent_tel' => 'nullable|string|max:20',
            'parent_email' => 'required|email|unique:parents,parent_initial_email,' . $parentId,
            'parent_password' => 'nullable|string|min:8',
        ];
    }
}
