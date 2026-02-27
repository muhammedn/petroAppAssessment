<?php

namespace App\Services;

use App\Repositories\Contracts\TransferEventRepositoryInterface;
use App\Services\Contracts\TransferEventServiceInterface;
use Illuminate\Support\Facades\Log;

class TransferEventService implements TransferEventServiceInterface
{

    protected TransferEventRepositoryInterface $transferEventRepository;

    public function __construct(TransferEventRepositoryInterface $transferEventRepository) 
    {
        $this->transferEventRepository = $transferEventRepository;
    }

    public function insertBatch(array $events): array
    {
        $result = $this->transferEventRepository->insertBatch($events);

        Log::info('Transfer batch processed', [
            'inserted'   => $result['inserted'],
            'duplicates' => $result['duplicates'],
        ]);

        return $result;
    }

    public function getSummary(string $stationId): array
    {
        return $this->transferEventRepository->getSummary($stationId);
    }
}
