<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCommentRequest extends FormRequest
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
        $post = $this->route('post');
        $postId = is_object($post) ? $post->id : $post;

        return [
            'body' => ['required', 'string', 'max:2000'],
            // Replies must target an existing TOP-LEVEL comment on this same post
            // (enforces the template's one-level-deep reply UI).
            'parent_id' => [
                'nullable',
                Rule::exists('comments', 'id')
                    ->where('post_id', $postId)
                    ->whereNull('parent_id'),
            ],
        ];
    }
}
