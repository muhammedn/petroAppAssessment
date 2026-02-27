<?php

namespace App\Http\Controllers;

use App\Http\Requests\TransferEventRequest;
use App\Services\Contracts\TransferEventServiceInterface;

class TransferEventController extends Controller
{
    protected TransferEventServiceInterface $transferEventService;

    public function __construct(TransferEventServiceInterface $transferEventService) 
    {
        $this->transferEventService = $transferEventService;
    }

    public function store(TransferEventRequest $request)
    {
        $result = $this->transferEventService->insertBatch($request->events);
        return response()->json($result, 201);
    }

    public function summary(string $stationId)
    {
        $summary = $this->transferEventService->getSummary($stationId);
        return response()->json($summary);
    }
}
