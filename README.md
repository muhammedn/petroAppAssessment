# Station Transfers API

A Laravel 12 API for ingesting station transfer events with idempotent, concurrency-safe processing and reconciliation summaries.

---

## Tech Stack

| Layer | Choice |
|-------|--------|
| Framework | Laravel 12 |
| Language | PHP 8.3+ |
| Database | SQLite (swappable via repository pattern) |
| Testing | Pest 4 |
| API Docs | L5-Swagger (OpenAPI 3.0) |
| Container | Docker + Docker Compose |

---

## Requirements (local)

- PHP 8.3+
- Composer 2
- SQLite3

---

## How to Run Locally

```bash
# 1. Clone the repo
git clone <https://github.com/muhammedn/petroAppAssessment>
cd station-transfers

# 2. Install dependencies + setup DB (one command)
make install

# 3. Start the server
make run
# → http://localhost:8000
```

Or manually:
```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

---

## How to Run with Docker

```bash
# Build and start
docker compose up --build

# API available at http://localhost:8000
```

---

## How to Run Tests

```bash
# Local
make test

# Docker
make docker-test
# or:
docker compose run --rm app sh -c "composer install --dev && ./vendor/bin/pest"
```

Tests use an **in-memory SQLite** database (`:memory:`) — no setup needed, fully isolated per run.

---

## API Documentation

Interactive Swagger UI is available once the server is running:

```
http://localhost:8000/api/documentation
```

The OpenAPI 3.0 spec is generated from PHP attributes in `TransferEventController.php` and stored at `storage/api-docs/api-docs.json`.

---

## API Examples (curl)

### POST /transfers

```bash
curl -s -X POST http://localhost:8000/api/transfers \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "events": [
      {
        "event_id": "evt-001",
        "station_id": "S1",
        "amount": 150.50,
        "status": "approved",
        "created_at": "2026-02-19T10:00:00Z"
      },
      {
        "event_id": "evt-002",
        "station_id": "S1",
        "amount": 300.00,
        "status": "approved",
        "created_at": "2026-02-19T11:00:00Z"
      }
    ]
  }'

# Response 201:
# { "inserted": 2, "duplicates": 0 }
```


### GET /stations/{station_id}/summary

```bash
curl -s http://localhost:8000/api/stations/S1/summary \
  -H "Accept: application/json"

# Response 200:
# {
#   "station_id": "S1",
#   "total_approved_amount": 450.50,
#   "events_count": 2
# }
```

### Validation error (fail-fast)

```bash
curl -s -X POST http://localhost:8000/api/transfers \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "events": [
      {
        "event_id": "evt-bad",
        "station_id": "S1",
        "amount": -10,
        "status": "approved",
        "created_at": "not-a-date"
      }
    ]
  }'

# Response 400:
# {
#   "message": "Batch rejected due to validation errors.",
#   "errors": {
#     "events.0.amount": ["The events.0.amount field must be at least 0."],
#     "events.0.created_at": ["The events.0.created_at field must be a valid date."]
#   }
# }
```

---

## Design Decisions

### Architecture

The application follows a layered architecture (Repo-Service):

```
TransferEventController
        ↓
TransferEventService       (orchestration + logging)
        ↓
EloquentTransferEventRepository   (persistence)
```

Each layer depends on an interface and is bound via Laravel’s service container in AppServiceProvider.

This ensures:

- Swappable persistence layer

- Clear separation of concerns

- Testability of business logic

### Idempotency Strategy

A database level UNIQUE constraint on `event_id` is the single source of truth for deduplication.

```
$table->string('event_id')->unique();
```

Insertion is performed using:

Laravel's `insertOrIgnore()` This guarantees:

- Atomic duplicate rejection at the DB level.
- No race conditions from "check then insert".
- Accurate counting of inserted vs duplicates using affected rows.

### Concurrency Strategy

Concurrency safety is achieved entirely at the database layer.

If two concurrent requests attempt to insert the same event_id:

- One succeeds.
- The other is ignored.
- No duplicate rows are created.

Each batch is wrapped in a transaction to ensure:

- Summary queries never observe partial batch state.
- Writes are fully committed or not visible at all.

### Deterministic Reconciliation

Reconciliation queries must always produce the same result for the same underlying data.
This is guaranteed because:

- `event_id` is globally unique.
- Inserts are idempotent.
- No partial duplicates are possible.
- Summary queries use direct aggregation (SUM, COUNT) over committed rows.


### Validation Strategy: Fail-Fast

I intentionally chose a fail-fast batch validation strategy.
If any event in the batch is invalid:

- The entire batch is rejected.
- No events are stored.
- A 400 response is returned.

This will:

- Preserves batch atomicity.
- Simplifies retry logic.
- Guarantees deterministic reconciliation.
- Treats invalid payloads as contract violations.


### Summary Semantics

`total_approved_amount` includes only events where status == `approved`
`events_count` includes all stored events, regardless of status

This ensures:
- Financial totals reflect only approved transfers.
- Operational visibility reflects all ingested events.


### Storage Swappability

The repository layer abstracts persistence.
To switch from SQLite to any DB/Storage:

- Implement `StorageNameTransferEventRepository`.
- Update the binding in `AppServiceProvider`.
No changes are required in controllers or services.


### Test Coverage

The test validates:

- Correct inserted/duplicates counts
- Duplicate events do not change totals
- Out-of-order arrival produces identical summaries
- Concurrent ingestion does not double insert
- Summary correctness per station
- Validation failure behavior (fail-fast)

---

## Project Structure

```
app/
├── Http/
│   ├── Controllers/TransferEventController.php
│   └── Requests/TransferEventRequest.php
├── Models/TransferEvent.php
├── Providers/AppServiceProvider.php
├── Repositories/
│   ├── Contracts/TransferEventRepositoryInterface.php
│   └── EloquentTransferEventRepository.php
└── Services/
    ├── Contracts/TransferEventServiceInterface.php
    └── TransferEventService.php

database/
└── migrations/
routes/
└── api.php
storage/
└── api-docs/
    └── api-docs.json
tests/
└── Feature/TransferEventApiTest.php
Makefile
Dockerfile
docker-compose.yml
```
