<?php

namespace App\Http\Requests;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ToggleLikeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'likeable_type' => ['required', 'string', Rule::in(['post', 'comment'])],
            'likeable_id' => [
                'required',
                'integer',
                Rule::exists(
                    $this->input('likeable_type') === 'comment' ? (new Comment)->getTable() : (new Post)->getTable(),
                    'id'
                ),
            ],
        ];
    }
}
