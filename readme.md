# AWSD ORM

**Data Mapper ORM** for **PHP 8.4**, **PostgreSQL-first**, built with **TDD** and strict **SRP**.
**MVP is PG-only.** No Active Record. No Repository. No hidden fallbacks.

> Status: WIP / API unstable until v0.1.0

---

## Goals

* **Data Mapper only** (entities ↔ rows) with explicit, testable behavior.
* **Deferred query generation**: IR → validate → render → bind → execute.
* **PostgreSQL as reference dialect**; others added later.
* **Type safety** and clear error messages; no “magic”.

## Non-Goals (for this project)

* Active Record, Repository, auto-relations “magic”, global caches.
* Silent emulation of unsupported SQL features.

## Constraints

* PHP **8.4**, strict types, **no implicitly nullable** params/returns.
* PHPDoc/comments in **English**.
* Prepared statements only (no value concatenation).
* **PostgreSQL-only** in MVP (placeholders `$1..$N`, quoted identifiers `"`).

---

## Roadmap

### MVP — Read-only (PostgreSQL)

* **Mapping core**

  * Minimal entity metadata (schema/table/columns/PK).
  * Base type mapping (int, string, bool, float, DateTimeImmutable).
  * Hydrator `row → entity` & extractor `entity → row`.

* **Query model & validation**

  * IR for `SELECT`: projections, `FROM`, `WHERE`, `ORDER BY`, `LIMIT/OFFSET`, `DISTINCT`.
  * Expression operators: `=`, `<>`, `IN`, `BETWEEN`, `LIKE`, `IS NULL`.
  * Deterministic alias register (fields/expressions).
  * Basic validations (FROM required, non-empty projections, stable param order).

* **SQL generation & execution**

  * **PostgreSQL renderer** (quoting, `$1..$N` placeholders).
  * Ordered param binding; execution via a small PDO abstraction.
  * SQL error → dedicated exceptions.
  * Golden tests (exact SQL comparison) + unit tests.

* **Platform & quality**

  * Minimal `ORMConfig` (dialect=PG, DSN, default schema).
  * TDD: PHPUnit + static analysis.
  * Simple logger (SQL + duration; secrets masked).

---

### CRUD-solid — Reliable writes

* **Mapping core**

  * Identifier strategies (IDENTITY/SEQUENCE/UUID).
  * DB defaults & generated columns (timestamps).
  * PHP enums / simple value objects mapping.

* **Query model & validation**

  * IR for `INSERT/UPDATE/DELETE`.
  * Write validations (valid columns, keys present).

* **SQL generation & execution**

  * PG renderers for I/U/D (+ `RETURNING`).
  * Simple batched executions (multi-insert).

* **In-memory state**

  * **Identity Map** (class + PK) → one instance per row per scope.
  * **Unit of Work**: `new/clean/dirty/deleted`, FK-aware **flush()** (transactional).
  * Change detection (snapshot).

* **Relations & loading**

  * Explicit **1–1 / 1–N / N–1** mapping.
  * Explicit fetching by default; optional eager via JOIN.
  * **Batch preloading** (anti N+1) with `IN (…)`.

* **Transactions & consistency**

  * Transaction manager tied to UoW.
  * **Optimistic locking** (version/timestamp in UPDATE’s WHERE).
  * Dedicated exceptions (unique/FK violations).

* **Schema & migrations**

  * Runner for existing migration scripts (apply/rollback) + journal.
  * Minimal fixtures pipeline for tests.

* **Platform & quality**

  * PG integration tests (container), per-test rollback.
  * Structured logs + basic metrics; targeted mutation testing.

---

### Complete — Production

* **Mapping core**

  * Advanced types: JSONB, UUID, arrays, bytea, time zones.
  * Extensible custom types.

* **Query model & validation**

  * **JOIN** (INNER/LEFT → RIGHT/FULL if supported).
  * **GROUP BY/HAVING** (strict), subqueries, **CTE/WITH**, **UNION**.
  * Deeper dialect-aware validations.

* **SQL generation & execution**

  * **MySQL/SQLite** renderers (subset) + dialect checks.
  * PG options: `ON CONFLICT`, bulk ops, optional hints.

* **In-memory state**

  * Cascades (persist/remove) in flush plan.
  * Savepoints & controlled re-entrance.

* **Relations & loading**

  * **Many-to-Many** (join tables), self-refs, orphan-removal.
  * Optional lazy loaders/proxies; fetch profiles.

* **Transactions & consistency**

  * **Pessimistic locking** (`FOR UPDATE`), savepoints.
  * Configurable retries for deadlocks/timeouts.

* **Schema & migrations**

  * **Introspection** (tables/columns/index/FK) + **diff → DDL** (`CREATE/ALTER/DROP`).
  * Indexes, uniques, sequences, constraints; idempotent scripts.

* **Platform & quality**

  * CLI (entity/mapper scaffolds, diff, migrations).
  * Docs & “recipes” (pagination, filters, transactions, relations).
  * Baseline benchmarks; APM/profiling hooks; extended config (limits, quoting policy).

---

## Development

* Install deps: `composer install`
* Run tests: `composer test`

**TDD loop:** write a failing test → make it pass → refactor (keep SRP).

---

## Design Tenets

* **Single source of truth** = IR (no stringly-typed query building).
* **Immutability** for builders to avoid half-baked state.
* **Dialect-aware** behavior (no silent fallbacks).
* **Security first**: prepared statements only; secrets masked in logs.

---

## License

TBD (e.g., MIT).
