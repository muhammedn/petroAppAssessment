<?php

namespace App\Repositories;

use App\Models\TransferEvent;
use App\Repositories\Contracts\TransferEventRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentTransferEventRepository implements TransferEventRepositoryInterface
{
    private const BATCH_SIZE = 1000;

    public function insertBatch(array $events): array
    {
        return DB::transaction(function () use ($events) {
            $inserted   = 0;
            $duplicates = 0;
            $now        = now();

            foreach (array_chunk($events, self::BATCH_SIZE) as $chunk) {
                $attempted = count($chunk);

                $chunk = array_map(fn ($e) => array_merge($e, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ]), $chunk);

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
