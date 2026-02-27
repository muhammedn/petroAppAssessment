<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferEventRequest extends FormRequest
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
            'events'                        => ['required', 'array', 'min:1'],
            'events.*.event_id'             => ['required', 'string'],
            'events.*.station_id'           => ['required', 'string'],
            'events.*.amount'               => ['required', 'numeric', 'min:0'],
            'events.*.status'               => ['required', 'string'],
            'events.*.event_created_at'     => ['required', 'date'],   
        ];
    }
}
