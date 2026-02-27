<?php

use App\Models\TransferEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makeEvent(array $overrides = []): array
{
    return array_merge([
        'event_id'   => (string) \Illuminate\Support\Str::uuid(),
        'station_id' => 'S1',
        'amount'     => 100.0,
        'status'     => 'approved',
        'created_at' => '2026-02-19T10:00:00Z',
    ], $overrides);
}

test('batch insert returns correct inserted and duplicates count', function () {
    $events = [
        makeEvent(['event_id' => 'evt-1']),
        makeEvent(['event_id' => 'evt-2']),
        makeEvent(['event_id' => 'evt-1']),
    ];

    $response = $this->postJson('/api/transfers', ['events' => $events]);

    $response->assertStatus(201)
             ->assertJson(['inserted' => 2, 'duplicates' => 1]);
});


test('sending duplicate event_id does not change summary totals', function () {
    $event = makeEvent(['event_id' => 'evt-dup', 'amount' => 200.0]);

    $this->postJson('/api/transfers', ['events' => [$event]])->assertStatus(201);
    $this->postJson('/api/transfers', ['events' => [$event]])->assertStatus(201);

    $response = $this->getJson('/api/stations/S1/summary');

    $response->assertJson([
        'station_id'            => 'S1',
        'total_approved_amount' => 200.0,
        'events_count'          => 1,
    ]);
});


test('out-of-order events produce correct totals regardless of arrival order', function () {
    $older  = makeEvent(['event_id' => 'evt-old', 'amount' => 50.0,  'created_at' => '2026-01-01T08:00:00Z']);
    $newer  = makeEvent(['event_id' => 'evt-new', 'amount' => 150.0, 'created_at' => '2026-02-01T08:00:00Z']);

    $this->postJson('/api/transfers', ['events' => [$newer, $older]])->assertStatus(201);

    $response = $this->getJson('/api/stations/S1/summary');

    $response->assertJson([
        'total_approved_amount' => 200.0,
        'events_count'          => 2,
    ]);
});


test('concurrent requests with same event_id do not double-insert', function () {
    $event = makeEvent(['event_id' => 'evt-concurrent', 'amount' => 300.0]);

    $responses = collect(range(1, 5))->map(
        fn() => $this->postJson('/api/transfers', ['events' => [$event]])
    );

    $totalInserted = $responses->sum(fn ($r) => $r->json('inserted'));

    expect($totalInserted)->toBe(1);

    $this->getJson('/api/stations/S1/summary')
         ->assertJson([
             'total_approved_amount' => 300.0,
             'events_count'          => 1,
         ]);
});


test('summary is isolated per station and only sums approved amounts', function () {
    $eventsS1 = [
        makeEvent(['event_id' => 's1-a', 'station_id' => 'S1', 'amount' => 100.0, 'status' => 'approved']),
        makeEvent(['event_id' => 's1-b', 'station_id' => 'S1', 'amount' => 50.0,  'status' => 'rejected']),
        makeEvent(['event_id' => 's1-c', 'station_id' => 'S1', 'amount' => 200.0, 'status' => 'approved']),
    ];

    $eventsS2 = [
        makeEvent(['event_id' => 's2-a', 'station_id' => 'S2', 'amount' => 999.0, 'status' => 'approved']),
    ];

    $this->postJson('/api/transfers', ['events' => array_merge($eventsS1, $eventsS2)]);

    $this->getJson('/api/stations/S1/summary')
         ->assertJson([
             'station_id'            => 'S1',
             'total_approved_amount' => 300.0,
             'events_count'          => 3,
         ]);

    $this->getJson('/api/stations/S2/summary')
         ->assertJson([
             'station_id'            => 'S2',
             'total_approved_amount' => 999.0,
             'events_count'          => 1,
         ]);
});


test('non-approved statuses are stored but excluded from total_approved_amount', function () {
    $events = [
        makeEvent(['event_id' => 'e1', 'amount' => 100.0, 'status' => 'approved']),
        makeEvent(['event_id' => 'e2', 'amount' => 500.0, 'status' => 'pending']),
        makeEvent(['event_id' => 'e3', 'amount' => 250.0, 'status' => 'rejected']),
        makeEvent(['event_id' => 'e4', 'amount' => 75.0,  'status' => 'unknown_status']),
    ];

    $this->postJson('/api/transfers', ['events' => $events])->assertStatus(201);

    $this->getJson('/api/stations/S1/summary')
         ->assertJson([
             'total_approved_amount' => 100.0,
             'events_count'          => 4,
         ]);
});


test('missing required field rejects entire batch with 400', function () {
    $events = [
        makeEvent(['event_id' => 'valid-1']),
        \Illuminate\Support\Arr::except(makeEvent(), ['station_id']),
    ];

    $this->postJson('/api/transfers', ['events' => $events])
         ->assertStatus(400)
         ->assertJsonPath('message', fn ($msg) => str_contains($msg, 'rejected'));

    expect(DB::table('transfer_events')->count())->toBe(0);
});


test('negative amount is rejected with 400', function () {
    $event = makeEvent(['amount' => -10.0]);

    $this->postJson('/api/transfers', ['events' => [$event]])
         ->assertStatus(400)
         ->assertJsonStructure(['errors' => ['events.0.amount']]);
});


test('invalid created_at format is rejected with 400', function () {
    $event = makeEvent(['created_at' => 'not-a-date']);

    $this->postJson('/api/transfers', ['events' => [$event]])
         ->assertStatus(400)
         ->assertJsonStructure(['errors' => ['events.0.created_at']]);
});


test('summary for station with no events returns zeros', function () {
    $this->getJson('/api/stations/UNKNOWN/summary')
         ->assertStatus(200)
         ->assertJson([
             'station_id'            => 'UNKNOWN',
             'total_approved_amount' => 0.0,
             'events_count'          => 0,
         ]);
});
