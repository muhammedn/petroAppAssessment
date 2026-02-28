<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

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
            'events'                    => ['required', 'array', 'min:1', 'max:10000'],
            'events.*.event_id'         => ['required', 'string'],
            'events.*.station_id'       => ['required', 'string'],
            'events.*.amount'           => ['required', 'numeric', 'min:0'],
            'events.*.status'           => ['required', 'string'],
            'events.*.created_at'       => ['required', 'date'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Batch rejected due to validation errors.',
                'errors'  => $validator->errors(),
            ], 400)
        );
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'events' => collect($this->events)->map(function ($event) {
                $event['event_created_at'] = $event['created_at'];
                unset($event['created_at']);
                return $event;
            })->all(),
        ]);
    }
}
