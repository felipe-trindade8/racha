# MVP Backlog — Racha (Soccer Management System)

Derived from [product.md](product.md), [architecture.md](architecture.md) and [coding-standards.md](coding-standards.md).

## Conventions

- **Repo layout:** monorepo with `backend/` (Laravel 13 / PHP 8.4) and `frontend/` (Nuxt 4 / Nuxt UI / TS).
- **Issue ID:** `B-NN` is the backlog reference used for dependencies in this document. The GitHub issue number is assigned on creation.
- **Estimate:** every issue is scoped to **1–4 hours**. Larger work was split.
- **Track:** `INFRA`, `BE` (backend), `FE` (frontend), `DEVOPS`. Tracks signal who can pick the issue up in parallel.
- **Labels (suggested):** `epic:*`, `track:*`, `type:feature|chore|test|infra`, `priority:p0|p1|p2`.
- **Branch naming:** `feature/<issue-number>-description`.

### How to read dependencies

An issue can start once **all** its dependencies are merged. Issues with no shared dependency and different tracks are **parallelizable**. The "Parallelization" section per epic calls out the concurrent lanes.

---

## Epic Overview & Sequence

| # | Epic | Goal | Depends on |
|---|------|------|------------|
| E0 | Project Setup & Foundations | Both apps boot, CI green, API conventions in place | — |
| E1 | Authentication & Authorization | Login, Sanctum tokens, RBAC, policies | E0 |
| E2 | Player Management | CRUD players, positions, inactivate | E1 |
| E3 | Finances | Transactions, monthly payment, expenses, cash flow | E1 (E2 for player link) |
| E4 | Matches | Match creation, teams, score, history | E1, E2 |
| E5 | Attendance | Confirm attendance, list, status | E2, E4 |
| E6 | Deployment & Docs | App Runner, S3/CloudFront, Swagger | E0 (early CI), all features for prod |

**Critical path:** E0 → E1 → E2 → E4 → E5. E3 runs parallel to E4/E5 after E1/E2. E6 infra (B-60..B-62) can start right after E0.

---

## Epic 0 — Project Setup & Foundations

Goal: both apps run locally, lint + tests execute in CI, shared API conventions exist before feature work starts.

### B-01 — Initialize Laravel 13 backend skeleton `INFRA` `BE`
- **Estimate:** 2h
- **Description:** Create the `backend/` Laravel 13 app (PHP 8.4), configure `.env.example`, set timezone/locale, add `/api/v1` route group stub returning `{ "status": "ok" }` on `GET /api/v1/health`.
- **Acceptance criteria:**
  - `composer install` succeeds on PHP 8.4.
  - `php artisan serve` boots with no errors.
  - `GET /api/v1/health` returns `200` JSON `{ "status": "ok" }`.
  - `.env.example` committed; `.env` git-ignored.
- **Dependencies:** none
- **Parallel with:** B-02

### B-02 — Initialize Nuxt 4 frontend skeleton `INFRA` `FE`
- **Estimate:** 2h
- **Description:** Create the `frontend/` Nuxt 4 app with TypeScript and Nuxt UI installed/configured. Add a blank landing page and a `runtimeConfig.public.apiBase` pointing at the backend.
- **Acceptance criteria:**
  - `npm run dev` boots the app with no errors.
  - Nuxt UI components render (smoke: one `UButton` on the landing page).
  - TypeScript strict mode enabled in `tsconfig`.
  - `apiBase` read from env (`NUXT_PUBLIC_API_BASE`).
- **Dependencies:** none
- **Parallel with:** B-01

### B-03 — MySQL 8 + local dev environment `INFRA` `DEVOPS`
- **Estimate:** 2h
- **Description:** Provide a `docker-compose.yml` (or documented local setup) for MySQL 8, wire backend DB config, document setup in `README`.
- **Acceptance criteria:**
  - `docker compose up` starts MySQL 8 reachable by the backend.
  - `php artisan migrate` runs against it successfully (default migrations).
  - README documents how to start the DB and run migrations.
- **Dependencies:** B-01
- **Parallel with:** B-02

### B-04 — Backend testing setup (Pest) `INFRA` `BE` `test`
- **Estimate:** 1h
- **Description:** Install Pest, configure `phpunit.xml`/`Pest.php`, add a sample passing test, configure `RefreshDatabase` with a SQLite/MySQL test connection.
- **Acceptance criteria:**
  - `./vendor/bin/pest` runs and passes a sample test.
  - Test DB isolated from dev DB.
- **Dependencies:** B-01
- **Parallel with:** B-05, B-06

### B-05 — Frontend testing setup (Vitest) `INFRA` `FE` `test`
- **Estimate:** 1h
- **Description:** Install/configure Vitest with a sample composable test.
- **Acceptance criteria:**
  - `npm run test` runs and passes a sample composable test.
  - Vue/Nuxt test utils configured for component tests.
- **Dependencies:** B-02
- **Parallel with:** B-04, B-06

### B-06 — E2E testing setup (Playwright) `INFRA` `FE` `test`
- **Estimate:** 2h
- **Description:** Install Playwright, configure base URL, add a smoke test that loads the landing page.
- **Acceptance criteria:**
  - `npx playwright test` passes a smoke test hitting the running frontend.
  - Config supports running against local dev URL.
- **Dependencies:** B-02
- **Parallel with:** B-04, B-05

### B-07 — Standard JSON response & error envelope `BE`
- **Estimate:** 3h
- **Description:** Implement consistent success/error JSON shape and a global exception handler producing the standard error format. Add a paginated response helper/resource wrapper.
- **Acceptance criteria:**
  - Validation errors return `{ "message": "...", "errors": {} }` with `422`.
  - Not-found/forbidden/unauthenticated return consistent JSON with correct status codes.
  - A documented helper exists for paginated list responses (data + meta).
  - Covered by Pest tests.
- **Dependencies:** B-01, B-04
- **Parallel with:** B-08

### B-08 — Backend lint/format + CI workflow `INFRA` `DEVOPS`
- **Estimate:** 3h
- **Description:** Add Pint (or PHP-CS-Fixer) and a GitHub Actions workflow that installs deps, runs lint, and runs Pest on push/PR.
- **Acceptance criteria:**
  - `composer lint` passes on the repo.
  - GitHub Actions runs lint + Pest on PRs to `main` and reports status.
  - Workflow caches Composer deps.
- **Dependencies:** B-04
- **Parallel with:** B-09

### B-09 — Frontend lint/format + CI workflow `INFRA` `DEVOPS`
- **Estimate:** 2h
- **Description:** Configure ESLint + Prettier for Nuxt/TS and a GitHub Actions workflow running lint, typecheck, and Vitest on PR.
- **Acceptance criteria:**
  - `npm run lint` and `npm run typecheck` pass.
  - GitHub Actions runs lint + typecheck + Vitest on PRs and reports status.
  - Workflow caches npm deps.
- **Dependencies:** B-05
- **Parallel with:** B-08

### B-10 — Frontend HTTP client + API service base `FE`
- **Estimate:** 3h
- **Description:** Create a typed `$fetch`-based API client (base URL, JSON, auth header injection hook, error normalization to the standard envelope) and a `BaseService` pattern. Add shared `*Type` definitions scaffold.
- **Acceptance criteria:**
  - A `useApi`/client composable injects base URL and (later) bearer token.
  - Errors are normalized to `{ message, errors }` for consumers.
  - Pagination response type defined in `types/`.
  - Unit test covers error normalization.
- **Dependencies:** B-02, B-07 (contract only — can stub if B-07 not merged)
- **Parallel with:** backend feature work

**Parallelization (E0):** Two lanes from the start — **Backend lane** (B-01 → B-03/B-04 → B-07/B-08) and **Frontend lane** (B-02 → B-05/B-06 → B-09/B-10). They converge only on shared conventions (B-07 contract feeds B-10).

---

## Epic 1 — Authentication & Authorization

Goal: users log in, receive Sanctum tokens, and RBAC + policies gate admin-only actions. Depends on **E0**.

### B-11 — User migration, model & roles `BE`
- **Estimate:** 2h
- **Description:** Migration + Eloquent model for `users` (`email`, `password`, `role`, `player_id` nullable). Role as enum (`administrator`, `player`). Factory + seeder for an admin user.
- **Acceptance criteria:**
  - `users` table migrates with `role` and nullable `player_id`.
  - `User` model casts role to an enum; password hashed.
  - Seeder creates one administrator.
  - Factory + test asserting defaults.
- **Dependencies:** B-03, B-07
- **Parallel with:** —

### B-12 — Sanctum setup & login/logout endpoints `BE`
- **Estimate:** 3h
- **Description:** Install/configure Sanctum (token-based). Invokable controllers + Form Requests for `POST /api/v1/auth/login` and `POST /api/v1/auth/logout`, plus `GET /api/v1/auth/me`.
- **Acceptance criteria:**
  - Valid credentials return a bearer token + user payload.
  - Invalid credentials return `422`/`401` in the standard envelope.
  - `logout` revokes the current token.
  - `me` returns the authenticated user; `401` without token.
  - Pest tests cover success and failure paths.
- **Dependencies:** B-11
- **Parallel with:** B-13

### B-13 — RBAC roles, gates & base Policy scaffolding `BE`
- **Estimate:** 3h
- **Description:** Define an `administrator` gate/middleware and a Policy base convention. Add a `role:administrator` route middleware. Document the policy pattern in `coding-standards` follow-up.
- **Acceptance criteria:**
  - Middleware blocks non-administrators with `403` (standard envelope) on protected routes.
  - A sample admin-only route is reachable only by admins (Pest test).
  - Policy registration convention established for feature epics to follow.
- **Dependencies:** B-11
- **Parallel with:** B-12

### B-14 — Frontend auth service + token storage `FE`
- **Estimate:** 2h
- **Description:** `AuthService` calling login/logout/me; persist token securely; inject bearer into the B-10 client; expose `useAuth` composable (user, isAuthenticated, isAdmin, login, logout).
- **Acceptance criteria:**
  - Login stores token and hydrates user via `me`.
  - Token injected into all API calls.
  - Logout clears state and token.
  - `isAdmin` derived from role; unit test on the composable.
- **Dependencies:** B-10, B-12
- **Parallel with:** B-15

### B-15 — Login page + route middleware `FE`
- **Estimate:** 3h
- **Description:** Login page (Nuxt UI form, validation), global auth middleware redirecting unauthenticated users, and an admin-only middleware. App shell with logout.
- **Acceptance criteria:**
  - Login form validates and surfaces server errors from the standard envelope.
  - Authenticated users redirected away from login; unauthenticated redirected to login on protected routes.
  - Admin-only pages blocked for players.
  - Playwright test: login → land on home → logout.
- **Dependencies:** B-14, B-06
- **Parallel with:** —

**Parallelization (E1):** After B-11, **B-12** and **B-13** run in parallel (both BE). Frontend **B-14** starts once B-12 exists; **B-15** follows B-14. Backend devs can move on to E2 while frontend finishes B-15.

---

## Epic 2 — Player Management

Goal: admins manage players (create/update/inactivate, positions). Players view/edit their own info. Depends on **E1**.

### B-16 — Player migration, model & factory `BE`
- **Estimate:** 2h
- **Description:** Migration + model for `players` (`name`, `nickname`, `rating` 1–5, `status` active/inactive, `phone`). Positions stored relationally (see B-17) — this issue covers the core table. Factory + seeder.
- **Acceptance criteria:**
  - `players` table migrates with all core columns; `status` defaults to active.
  - `rating` constrained/validated to 1–5 at the model layer.
  - Factory + Pest test for defaults.
- **Dependencies:** B-11
- **Parallel with:** B-17

### B-17 — Player positions model & relationship `BE`
- **Estimate:** 2h
- **Description:** `positions` reference table (e.g. GK, DEF, MID, FWD) + `player_positions` pivot (many-to-many). Seed canonical positions.
- **Acceptance criteria:**
  - Positions seeded; `Player` has a `positions()` relationship.
  - A player can have multiple positions.
  - Pest test attaches/detaches positions.
- **Dependencies:** B-16
- **Parallel with:** —

### B-18 — PlayerService + create/update business rules `BE`
- **Estimate:** 3h
- **Description:** `PlayerService` encapsulating create/update (incl. positions sync). Controllers stay thin/invokable; validation via Form Requests. No DB queries in controllers.
- **Acceptance criteria:**
  - Service creates/updates a player with positions in one call.
  - Rating/status/positions validated; invalid input → standard `422`.
  - Pest tests cover create + update incl. position sync.
- **Dependencies:** B-17, B-07
- **Parallel with:** B-19 (Policy)

### B-19 — Player policy & authorization `BE`
- **Estimate:** 2h
- **Description:** `PlayerPolicy`: admins manage all; a player may view/update only their own linked player record (`users.player_id`).
- **Acceptance criteria:**
  - Admin can CRUD any player.
  - Player can `view`/`update` own record only; `403` otherwise.
  - Pest tests for both roles.
- **Dependencies:** B-13, B-16
- **Parallel with:** B-18

### B-20 — Player REST endpoints (list/show/create/update) `BE`
- **Estimate:** 3h
- **Description:** Invokable controllers for `GET /players` (paginated), `GET /players/{id}`, `POST /players`, `PUT /players/{id}`. Wire Form Requests, Policy, Service. API Resource for response shape (camelCase out).
- **Acceptance criteria:**
  - List endpoint paginated (data + meta).
  - Create/update guarded by policy + Form Request.
  - Responses camelCase; DB columns snake_case.
  - Pest feature tests for each endpoint incl. authz.
- **Dependencies:** B-18, B-19
- **Parallel with:** B-21

### B-21 — Inactivate player endpoint `BE`
- **Estimate:** 1h
- **Description:** `PATCH /players/{id}/inactivate` (and reactivate) toggling status via the service. Admin only.
- **Acceptance criteria:**
  - Status flips to inactive/active; admin-only.
  - Inactive players excluded from default active listings (filter param).
  - Pest test for transition + authz.
- **Dependencies:** B-20
- **Parallel with:** —

### B-22 — PlayerService (FE) + types `FE`
- **Estimate:** 2h
- **Description:** `PlayerService` (list/show/create/update/inactivate) + `PlayerType`, `PositionType`. `usePlayers` composable for state.
- **Acceptance criteria:**
  - Service methods typed against API contract; pagination handled.
  - `usePlayers` exposes list/loading/error + mutations.
  - Unit test on the composable (mocked service).
- **Dependencies:** B-10, B-20
- **Parallel with:** backend remainder

### B-23 — Player list page `FE`
- **Estimate:** 3h
- **Description:** Mobile-first list with search/status filter, pagination, position badges. Admin sees create button.
- **Acceptance criteria:**
  - Paginated, searchable, filter by status.
  - Mobile-first layout (Nuxt UI).
  - Admin-only create action hidden from players.
- **Dependencies:** B-22
- **Parallel with:** —

### B-24 — Player create/edit form `FE`
- **Estimate:** 3h
- **Description:** Create/edit form (name, nickname, rating, positions multi-select, phone, status). Players editing own record see a restricted form.
- **Acceptance criteria:**
  - Validation + server-error surfacing from standard envelope.
  - Positions multi-select bound to reference data.
  - Player role can edit only own profile fields.
  - Playwright happy-path test (admin creates a player).
- **Dependencies:** B-23, B-21
- **Parallel with:** —

**Parallelization (E2):** Backend lane (B-16→B-17→B-18/B-19→B-20→B-21) runs while frontend waits for B-20, then frontend lane (B-22→B-23→B-24) proceeds. E3 backend can start as soon as E1 is done — it does not block on E2.

---

## Epic 3 — Finances

Goal: monthly payments, expenses, and cash flow. Depends on **E1**; player linkage uses **E2** (B-16). Runs **in parallel with E4**.

### B-25 — FinancialTransaction migration & model `BE`
- **Estimate:** 2h
- **Description:** Migration + model for `financial_transactions` (`player_id` nullable, `description`, `amount`, `type` income/expense, `date`, `status` open/paid). Enums + factory.
- **Acceptance criteria:**
  - Table migrates; `type` and `status` as enums; `amount` stored as decimal/integer cents (document choice).
  - Belongs-to `player` (nullable) relationship.
  - Factory + Pest test.
- **Dependencies:** B-11 (B-16 for player FK)
- **Parallel with:** E4 backend

### B-26 — FinanceService + transaction rules `BE`
- **Estimate:** 3h
- **Description:** Service for creating income/expense transactions and marking paid/open. Encapsulate sign/`type` consistency rules.
- **Acceptance criteria:**
  - Create income/expense with validation of amount > 0 and type.
  - Mark transaction paid/open transitions.
  - Pest tests for create + status transitions.
- **Dependencies:** B-25, B-07
- **Parallel with:** B-27

### B-27 — Finance policy (admin-only) `BE`
- **Estimate:** 1h
- **Description:** Policy restricting all finance management to administrators.
- **Acceptance criteria:**
  - Non-admins get `403` on all finance write/read management endpoints.
  - Pest test for both roles.
- **Dependencies:** B-13, B-25
- **Parallel with:** B-26

### B-28 — Monthly payment generation `BE`
- **Estimate:** 3h
- **Description:** Endpoint/command to generate monthly payment transactions (income, status open) for active players for a given month, idempotent per player/month.
- **Acceptance criteria:**
  - Generates one open income transaction per active player for a month.
  - Re-running the same month does not duplicate.
  - Admin-only; Pest test for idempotency + authz.
- **Dependencies:** B-26, B-27, B-16
- **Parallel with:** B-29

### B-29 — Finance REST endpoints (CRUD + mark paid) `BE`
- **Estimate:** 3h
- **Description:** `GET /financial-transactions` (paginated, filter by type/status/month/player), `POST`, `PUT`, `PATCH /{id}/pay`. Resources + Form Requests + Policy.
- **Acceptance criteria:**
  - List paginated + filterable; camelCase responses.
  - Create/update/pay guarded by policy + Form Request.
  - Pest feature tests incl. filters and authz.
- **Dependencies:** B-26, B-27
- **Parallel with:** B-28

### B-30 — Cash flow summary endpoint `BE`
- **Estimate:** 3h
- **Description:** `GET /finances/cash-flow` returning totals (income, expense, balance) and a period breakdown (by month). Use joins, not whereHas, per standards.
- **Acceptance criteria:**
  - Returns current balance = paid income − paid expense (document open vs paid handling).
  - Monthly breakdown for a requested range.
  - Admin-only; Pest test asserting computed totals.
- **Dependencies:** B-29
- **Parallel with:** —

### B-31 — FinanceService (FE) + types `FE`
- **Estimate:** 2h
- **Description:** `FinanceService` (list/create/update/pay/cashFlow/generateMonthly) + `TransactionType`, `CashFlowType`. `useFinances` composable.
- **Acceptance criteria:**
  - Typed methods + pagination/filter support.
  - `useFinances` exposes list, summary, mutations.
  - Unit test (mocked service).
- **Dependencies:** B-10, B-29, B-30
- **Parallel with:** —

### B-32 — Transactions list & create/edit page `FE`
- **Estimate:** 4h
- **Description:** Admin page listing transactions (filters: type/status/month), create/edit modal, mark-paid action. Mobile-first.
- **Acceptance criteria:**
  - Filterable paginated list; create/edit with validation.
  - Mark-paid updates row state.
  - Admin-only route.
- **Dependencies:** B-31
- **Parallel with:** B-33

### B-33 — Cash flow dashboard page `FE`
- **Estimate:** 3h
- **Description:** Cash flow view: balance card, income/expense totals, monthly breakdown (simple chart or table). Mobile-first.
- **Acceptance criteria:**
  - Shows current balance + totals from the cash-flow endpoint.
  - Monthly breakdown rendered.
  - Admin-only; Playwright smoke test.
- **Dependencies:** B-31
- **Parallel with:** B-32

**Parallelization (E3):** Entire epic runs concurrently with E4. Within E3: backend B-25→(B-26∥B-27)→(B-28∥B-29)→B-30; frontend B-31→(B-32∥B-33).

---

## Epic 4 — Matches

Goal: create matches with two teams, record score/result, view history. Depends on **E1**, **E2**.

### B-34 — Match + MatchTeam migrations & models `BE`
- **Estimate:** 3h
- **Description:** Migrations/models for `matches` (`date`, `team_a_id`, `team_b_id`, `status`) and `match_teams` (`match_id`, `team_name`, `result`). Enforce exactly two teams per match at the service layer. Relationships + factories.
- **Acceptance criteria:**
  - Both tables migrate with FKs; `Match` relates to its two `MatchTeam`s.
  - `status` enum (e.g. planned/finished).
  - Factories + Pest test for relationships.
- **Dependencies:** B-16
- **Parallel with:** B-35

### B-35 — TeamPlayer migration & model `BE`
- **Estimate:** 2h
- **Description:** Migration/model for `team_players` (`match_team_id`, `player_id`, `position`, `game_rating`, `is_starter`). Relationships to `MatchTeam` and `Player`.
- **Acceptance criteria:**
  - Table migrates with FKs and `is_starter` boolean.
  - `MatchTeam hasMany TeamPlayer`; `TeamPlayer belongsTo Player`.
  - Factory + Pest test.
- **Dependencies:** B-34
- **Parallel with:** —

### B-36 — MatchService: create match with teams `BE`
- **Estimate:** 4h
- **Description:** Service to create a match with its two teams and roster assignments in a transaction. Validate exactly two teams and no duplicate player across teams.
- **Acceptance criteria:**
  - Creating a match persists match + two teams + team players atomically.
  - Rejects ≠2 teams or a player on both teams (standard `422`).
  - Pest tests for happy path + validation failures.
- **Dependencies:** B-35, B-07
- **Parallel with:** B-37

### B-37 — Match policy (admin-only writes, player reads) `BE`
- **Estimate:** 1h
- **Description:** Policy: admins create/update matches; players can view.
- **Acceptance criteria:**
  - Player can `view`/list; cannot create/update (`403`).
  - Pest tests for both roles.
- **Dependencies:** B-13, B-34
- **Parallel with:** B-36

### B-38 — Record match score & result `BE`
- **Estimate:** 3h
- **Description:** `PATCH /matches/{id}/score` setting each `MatchTeam.result` and moving match to finished. Optional per-player `game_rating`.
- **Acceptance criteria:**
  - Sets both teams' results and finishes the match.
  - Cannot score an already-finished match without explicit re-open (document rule).
  - Admin-only; Pest test for transition + validation.
- **Dependencies:** B-36, B-37
- **Parallel with:** B-39

### B-39 — Match REST endpoints (list/show/create/update) `BE`
- **Estimate:** 3h
- **Description:** `GET /matches` (paginated, filter by status/date), `GET /matches/{id}` (with teams + rosters), `POST /matches`, `PUT /matches/{id}`. Resources + Form Requests + Policy.
- **Acceptance criteria:**
  - List paginated + filterable; detail includes teams and players.
  - Writes guarded by policy + Form Request; camelCase out.
  - Pest feature tests incl. authz.
- **Dependencies:** B-36, B-37
- **Parallel with:** B-38

### B-40 — Match history endpoint `BE`
- **Estimate:** 2h
- **Description:** `GET /matches/history` returning finished matches with results, ordered by date desc, paginated. Use joins per standards.
- **Acceptance criteria:**
  - Returns only finished matches with both teams' results.
  - Paginated, newest first.
  - Pest test asserts ordering + filtering.
- **Dependencies:** B-39
- **Parallel with:** —

### B-41 — MatchService (FE) + types `FE`
- **Estimate:** 2h
- **Description:** `MatchService` (list/show/create/update/score/history) + `MatchType`, `MatchTeamType`, `TeamPlayerType`. `useMatches` composable.
- **Acceptance criteria:**
  - Typed methods + pagination/filters.
  - `useMatches` exposes list/detail/history + mutations.
  - Unit test (mocked service).
- **Dependencies:** B-10, B-39, B-40
- **Parallel with:** —

### B-42 — Match list & history page `FE`
- **Estimate:** 3h
- **Description:** List upcoming/planned matches and a history tab with results. Mobile-first. Visible to all roles.
- **Acceptance criteria:**
  - Planned + history views with pagination and date/status filter.
  - Results displayed for finished matches.
  - Playwright smoke test.
- **Dependencies:** B-41
- **Parallel with:** B-43, B-44

### B-43 — Match create form (teams + roster) `FE`
- **Estimate:** 4h
- **Description:** Admin form: date, two teams, assign players to each with position + starter flag. Prevent assigning a player to both teams client-side.
- **Acceptance criteria:**
  - Builds a valid create payload with two teams + rosters.
  - Client guards against duplicate player; server errors surfaced.
  - Admin-only route.
- **Dependencies:** B-41, B-24 (player picker reuse)
- **Parallel with:** B-42, B-44

### B-44 — Record score UI `FE`
- **Estimate:** 3h
- **Description:** Admin screen on a planned match to enter each team's result (and optional player ratings) and finish it.
- **Acceptance criteria:**
  - Enter both results + finish; UI reflects finished state.
  - Admin-only; validation errors surfaced.
  - Playwright happy path (create match → record score → appears in history).
- **Dependencies:** B-43, B-38
- **Parallel with:** B-42

**Parallelization (E4):** Backend B-34→B-35→(B-36∥B-37)→(B-38∥B-39)→B-40. Frontend B-41→(B-42∥B-43)→B-44. E4 backend and E3 run concurrently across the team.

---

## Epic 5 — Attendance

Goal: players confirm attendance per match with a status; admins/players see the confirmation list. Depends on **E2**, **E4**.

### B-45 — Attendance migration & model `BE`
- **Estimate:** 2h
- **Description:** Migration/model for `attendances` (`player_id`, `match_id`, `status` available/injured/... + confirmed flag). Unique per player/match. Relationships + factory.
- **Acceptance criteria:**
  - Table migrates with unique `(player_id, match_id)` and a `status` enum.
  - Relationships to `Player` and `Match`.
  - Factory + Pest test for uniqueness.
- **Dependencies:** B-34, B-16
- **Parallel with:** —

### B-46 — AttendanceService + confirm rules `BE`
- **Estimate:** 3h
- **Description:** Service to confirm/update a player's attendance for a match (upsert), enforcing match is not finished and player is active.
- **Acceptance criteria:**
  - Upserts attendance; updating status overwrites prior entry.
  - Rejects confirming a finished match or an inactive player (standard `422`).
  - Pest tests for confirm, update, and rejection paths.
- **Dependencies:** B-45, B-07
- **Parallel with:** B-47

### B-47 — Attendance policy `BE`
- **Estimate:** 1h
- **Description:** Policy: a player confirms only their own attendance; admins manage any. All roles can view the list.
- **Acceptance criteria:**
  - Player confirming someone else → `403`.
  - Admin can confirm/override for any player.
  - Pest tests for both roles.
- **Dependencies:** B-13, B-45
- **Parallel with:** B-46

### B-48 — Attendance endpoints (confirm + list) `BE`
- **Estimate:** 3h
- **Description:** `POST /matches/{id}/attendance` (confirm own/specified), `GET /matches/{id}/attendance` (confirmation list with status, paginated). Resources + Form Requests + Policy.
- **Acceptance criteria:**
  - Confirm endpoint validates status + applies policy.
  - List returns confirmed players with status; paginated; camelCase.
  - Pest feature tests incl. authz.
- **Dependencies:** B-46, B-47
- **Parallel with:** —

### B-49 — AttendanceService (FE) + types `FE`
- **Estimate:** 2h
- **Description:** `AttendanceService` (confirm/list) + `AttendanceType`. `useAttendance` composable.
- **Acceptance criteria:**
  - Typed confirm/list methods.
  - `useAttendance` exposes list + confirm mutation with optimistic state.
  - Unit test (mocked service).
- **Dependencies:** B-10, B-48
- **Parallel with:** —

### B-50 — Attendance confirmation UI on match page `FE`
- **Estimate:** 3h
- **Description:** On a match detail page: a player confirms attendance + sets status (available/injured); the confirmation list shows who's in with status. Mobile-first.
- **Acceptance criteria:**
  - Player can confirm/update own status; reflected immediately.
  - Confirmation list visible to all roles, grouped/labeled by status.
  - Playwright: player confirms attendance → appears in list.
- **Dependencies:** B-49, B-42
- **Parallel with:** —

**Parallelization (E5):** Backend B-45→(B-46∥B-47)→B-48; frontend B-49→B-50. Can start once E4 match endpoints (B-39/B-42) exist.

---

## Epic 6 — Deployment & Documentation

Goal: reproducible deploys to AWS within cost goals + API docs. Infra issues (B-60–B-62) can start right after **E0**; production cutover (B-63) waits on feature epics.

### B-60 — Backend deploy to AWS App Runner `DEVOPS`
- **Estimate:** 4h
- **Description:** Containerize the Laravel API, configure App Runner service, env/secrets, connect to MySQL 8 (RDS or compatible within cost goal). GitHub Actions deploy job on `main`.
- **Acceptance criteria:**
  - Pushing to `main` builds + deploys the API to App Runner.
  - `GET /api/v1/health` returns `200` on the deployed URL.
  - Secrets injected via App Runner config (no secrets in repo).
  - Documented; stays within the USD 10/month goal (note actual estimate).
- **Dependencies:** B-08
- **Parallel with:** B-61

### B-61 — Frontend deploy to S3 + CloudFront `DEVOPS`
- **Estimate:** 4h
- **Description:** Build Nuxt for static/SSR-compatible hosting, sync to S3, serve via CloudFront with cache invalidation. GitHub Actions deploy job on `main`.
- **Acceptance criteria:**
  - Pushing to `main` builds + deploys the frontend; CloudFront serves it.
  - `apiBase` points at the deployed API.
  - Cache invalidated on deploy.
  - Documented; within cost goal.
- **Dependencies:** B-09
- **Parallel with:** B-60

### B-62 — Swagger/OpenAPI documentation `BE` `DEVOPS`
- **Estimate:** 3h
- **Description:** Generate OpenAPI docs (annotations or spec) served at a docs route, covering auth + all feature endpoints as they land. Wire into CI.
- **Acceptance criteria:**
  - Swagger UI reachable (e.g. `/api/documentation`).
  - Auth, players, finances, matches, attendance endpoints documented.
  - Docs build verified in CI.
- **Dependencies:** B-12 (grows with each epic; finalize after E5)
- **Parallel with:** feature work (annotate as endpoints land)

### B-63 — Production environment config & smoke `DEVOPS`
- **Estimate:** 3h
- **Description:** Wire prod env vars/secrets, run migrations on deploy, seed the initial administrator, end-to-end smoke against the deployed stack (login → list players).
- **Acceptance criteria:**
  - Migrations run automatically on backend deploy.
  - Initial admin seeded (idempotent).
  - Playwright smoke against prod URLs (login → players) passes.
  - Runbook documented in README.
- **Dependencies:** B-60, B-61, B-15, B-23
- **Parallel with:** —

**Parallelization (E6):** B-60 ∥ B-61 right after E0 CI exists. B-62 grows alongside features. B-63 is the final gate.

---

## Cross-cutting parallel lanes (team view)

| Phase | Backend lane | Frontend lane | DevOps lane |
|-------|--------------|---------------|-------------|
| 1 | B-01,B-03,B-04,B-07 | B-02,B-05,B-06,B-10 | B-08,B-09 |
| 2 | B-11,B-12,B-13 | B-14,B-15 | B-60,B-61 (start) |
| 3 | B-16..B-21 (Players) + B-25..B-30 (Finances) | B-22..B-24 | B-62 (start) |
| 4 | B-34..B-40 (Matches) | B-31..B-33, B-41..B-44 | — |
| 5 | B-45..B-48 (Attendance) | B-49,B-50 | — |
| 6 | finalize B-62 | — | B-63 |

**Summary:** 50 issues across 6 epics, every issue scoped to 1–4h. Backend and frontend run as two persistent parallel lanes after E0; Finances (E3) and Matches (E4) are independent backend streams that run concurrently. Critical path: E0 → E1 → E2 → E4 → E5 → B-63.
