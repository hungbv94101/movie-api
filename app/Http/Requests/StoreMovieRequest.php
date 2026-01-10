<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMovieRequest extends FormRequest
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
        return [
            'title' => 'required|string|max:255',
            'year' => 'required|integer|min:1900|max:' . date('Y'),
            'genre' => 'nullable|string|max:255',
            'director' => 'nullable|string|max:255',
            'actors' => 'nullable|string|max:500',
            'plot' => 'nullable|string|max:1000',
            'poster' => 'nullable|url|max:500',
            'runtime' => 'nullable|string|max:50',
            'imdb_rating' => 'nullable|numeric|between:0,10',
            'imdb_id' => 'nullable|string|max:20|unique:movies,imdb_id',
            'language' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'title.required' => __('Movie title is required'),
            'year.required' => __('Movie year is required'),
            'year.integer' => __('Year must be a valid number'),
            'year.min' => __('Year must be at least 1900'),
            'year.max' => __('Year cannot be in the future'),
            'imdb_rating.between' => __('IMDB rating must be between 0 and 10'),
            'poster.url' => __('Poster must be a valid URL'),
            'imdb_id.unique' => __('A movie with this IMDB ID already exists'),
        ];
    }
}
