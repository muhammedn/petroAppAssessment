<?php

namespace App\Services\Contracts;

interface TransferEventServiceInterface
{
    public function insertBatch(array $events): array;
    public function getSummary(string $stationId): array;
}
