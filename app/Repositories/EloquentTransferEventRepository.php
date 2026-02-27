<?php

namespace App\Repositories;

use App\Models\TransferEvent;
use App\Repositories\Contracts\TransferEventRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentTransferEventRepository implements TransferEventRepositoryInterface
{

    public function insertBatch(array $events, int $batchSize = 1000): array
    {
        return DB::transaction(function () use ($events, $batchSize) {
            $inserted   = 0;
            $duplicates = 0;

            foreach (array_chunk($events, $batchSize) as $chunk) {
                $attempted = count($chunk);

                $affected = TransferEvent::insertOrIgnore($chunk);

                $inserted += $affected;
                $duplicates += $attempted - $affected;
            }

            return compact('inserted', 'duplicates');
        });
    }


    public function getSummary(string $stationId): array
    {
        $row = TransferEvent::forStation($stationId)
            ->selectRaw(
                'COUNT(*) AS events_count,
                 COALESCE(SUM(CASE WHEN status = ? THEN amount ELSE 0 END), 0) AS total_approved_amount',
                ['approved']
            )
            ->first();

        return [
            'station_id'            => $stationId,
            'total_approved_amount' => (float) $row->total_approved_amount,
            'events_count'          => (int) $row->events_count,
        ];
    }
}
