<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferEventRequest;
use App\Services\Contracts\TransferEventServiceInterface;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TransferEventInput',
    required: ['event_id', 'station_id', 'amount', 'status', 'created_at'],
    properties: [
        new OA\Property(property: 'event_id',    type: 'string', example: 'EVT-001'),
        new OA\Property(property: 'station_id',  type: 'string', example: 'STN-42'),
        new OA\Property(property: 'amount',      type: 'number', format: 'float',     example: 150.75),
        new OA\Property(property: 'status',      type: 'string', example: 'approved'),
        new OA\Property(property: 'created_at',  type: 'string', format: 'date-time', example: '2026-02-27T10:00:00Z'),
    ]
)]
#[OA\Schema(
    schema: 'BatchInsertResponse',
    properties: [
        new OA\Property(property: 'inserted',   type: 'integer', example: 95),
        new OA\Property(property: 'duplicates', type: 'integer', example: 5),
    ]
)]
#[OA\Schema(
    schema: 'StationSummaryResponse',
    properties: [
        new OA\Property(property: 'station_id',            type: 'string',  example: 'STN-42'),
        new OA\Property(property: 'total_approved_amount', type: 'number',  format: 'float', example: 14275.50),
        new OA\Property(property: 'events_count',          type: 'integer', example: 100),
    ]
)]
#[OA\Schema(
    schema: 'ValidationErrorResponse',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The events field is required.'),
        new OA\Property(property: 'errors',  type: 'object'),
    ]
)]
class TransferEventController extends Controller
{
    protected TransferEventServiceInterface $transferEventService;

    public function __construct(TransferEventServiceInterface $transferEventService) 
    {
        $this->transferEventService = $transferEventService;
    }

    #[OA\Post(
        path: '/transfers',
        summary: 'Batch insert transfer events',
        description: 'Accepts an array of transfer events and inserts them in bulk. Duplicate event_ids are silently ignored.',
        tags: ['Transfer Events'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['events'],
                properties: [
                    new OA\Property(
                        property: 'events',
                        type: 'array',
                        minItems: 1,
                        items: new OA\Items(ref: '#/components/schemas/TransferEventInput')
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Batch processed successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/BatchInsertResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function store(TransferEventRequest $request)
    {
        $result = $this->transferEventService->insertBatch($request->events);
        return response()->json($result, 201);
    }

    #[OA\Get(
        path: '/stations/{station_id}/summary',
        summary: 'Get station summary',
        description: 'Returns the total approved transfer amount and total event count for a given station.',
        tags: ['Stations'],
        parameters: [
            new OA\Parameter(
                name: 'station_id',
                in: 'path',
                required: true,
                description: 'The unique identifier of the station',
                schema: new OA\Schema(type: 'string', example: 'STN-42')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Station summary retrieved successfully',
                content: new OA\JsonContent(ref: '#/components/schemas/StationSummaryResponse')
            ),
        ]
    )]
    public function summary(string $stationId)
    {
        $summary = $this->transferEventService->getSummary($stationId);
        return response()->json($summary);
    }
}
