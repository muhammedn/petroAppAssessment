<?php

namespace App\Repositories\Contracts;

interface TransferEventRepositoryInterface
{
    public function insertBatch(array $events): array;
    public function getSummary(string $stationId): array;
}
