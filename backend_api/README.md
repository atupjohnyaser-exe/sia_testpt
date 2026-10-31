# Rockport Records API

Laravel API for the Rockport Police Department records dashboard. It is the rebuilt backend:
people with a bounty, manhunts, infractions, known locations and a cost to the state,
all behind clearance levels and an audit log.

> **Status:** written and checked by reading and a static script, but **not run**.
> The first thing to do is `php artisan test`. If something fails, send me the output.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# create an empty MySQL database called rockport_records, then set DB_* in .env
php artisan migrate:fresh --seed     # wipes the tables, fine for the demo data
php artisan serve                     # API at http://localhost:8000/api/v1
php artisan test                      # needs the pdo_sqlite PHP extension
```

If `composer install` complains about the lock file, run `composer update` once.

The old database layout is gone (offenses became infractions, risk score became bounty).
Use `migrate:fresh`, do not try to migrate an old database in place.

### Demo officers

Created by the seeder in local environments. Password is `demo1234`, or whatever you put in
`SEED_OFFICER_PASSWORD`. In production the seeder refuses to create them without that variable.

| Badge | Name | Clearance |
|---|---|---|
| RP-1001 | Supt. R. Hale | 4 Supervisor |
| RP-1047 | Det. A. Cross | 3 Detective |
| RP-1102 | Ofc. T. Reyes | 2 Officer |
| RP-1200 | Clerk M. Ono | 1 Records Clerk |

Add real officers with `php artisan rpd:create-officer RP-2000 "Jane Doe" --clearance=3`.

## Clearance levels

| Level | Can do |
|---|---|
| 1 Records Clerk | Read everything except restricted notes and the advisory text |
| 2 Officer | Add infractions, manhunts, sightings and notes |
| 3 Detective | Create and edit records, change status, mark infractions served or fines paid |
| 4 Supervisor | Archive and restore, export, delete infractions and manhunts, threat override, cost rates, audit log |

Each level includes the ones below it. Routes are grouped by level in `routes/api.php`.

## How the numbers work

**Bounty** = bounty of every unserved infraction + bounty of every manhunt.
Serving an infraction removes its bounty. Fines have their own due and paid totals.

**Threat level (1 to 6)** comes from the bounty (`config/rockport.php`):
1 under 10,000, 2 from 10,000, 3 from 100,000, 4 from 500,000, 5 from 2,000,000, 6 from 10,000,000.
Each level has a label and a suggested response. A supervisor can pin a level with a reason.

**Status** (`WANTED`, `IN_CUSTODY`, `PAROLED`, `CLEARED`) is explicit and only changes in two ways:
a detective changes it with a reason, or a manhunt outcome does it
(apprehended means `IN_CUSTODY`, evaded means `WANTED`). Adding or deleting infractions never touches it.
Every change is kept in `status_changes`.

**Cost to state** = quantity x unit price for officers deployed, officers injured, units stood down,
checkpoints, K-9 units, helicopters, property damage incidents and insurance claims.
Prices are in `cost_rates`. A manhunt stores its own cost and bounty when it is logged,
so changing a price later does not rewrite history. Manhunt bounty = its cost x 0.4.

Fine and bounty on an infraction are copied from its type when it is recorded, for the same reason.

## Security

- Demo mode is off by default. When on, it only creates accounts for badges that do not exist yet,
  at clearance 2, and never skips the key check for an existing officer.
- Login: same 401 for unknown badge, wrong key or deactivated officer, rate limited per badge and IP,
  failed attempts written to the audit log, constant-time style dummy check for unknown badges.
- Tokens expire (`TOKEN_TTL_MINUTES`, default 8 hours) and are revoked on logout.
- Deactivating an officer (`is_active = false`) locks them out even if they hold a valid token.
- Status, bounty, threat level and case id cannot be set through create or update requests.
- `photo_url` only accepts http and https.
- Audit log is written by an observer, so new endpoints cannot forget it. Exports are logged.
  Opening a record is logged once per officer per 10 minutes. Note text is never copied into the log.
  Audit rows cannot be updated or deleted through the model.
- Responses send `Cache-Control: no-store` and `X-Content-Type-Options: nosniff`.
- Nothing secret is in this folder. `.env` is in `.gitignore`.

## Endpoints

Base path `/api/v1`. All except login need `Authorization: Bearer <token>`.
Lists are paginated (`?page=`, `?per_page=` up to 100).

| Method and path | Level | What it does |
|---|---|---|
| POST `/auth/login` | none | `badge_id`, `access_key`. Returns token, expiry, officer |
| POST `/auth/logout` | any | Revokes the token |
| GET `/auth/me` | 1 | Current officer |
| GET `/dashboard/stats` | 1 | Counts, total bounty, total cost to state |
| GET `/dashboard/priority` | 1 | Five highest bounties, cleared people left out |
| GET `/bulletins/current` | 1 | Rotating bulletin |
| GET `/subjects` | 1 | `search`, `status`, `threat_level`, `sort` (bounty, threat, name, recent) |
| GET `/subjects/{id}` | 1 | One record, with the five latest infractions |
| GET `/subjects/{id}/rap-sheet` | 1 | Header and summary numbers |
| GET `/subjects/{id}/infraction-summary` | 1 | Infractions by type, unserved and total |
| GET `/subjects/{id}/cost-to-state` | 1 | Quantity x cost table and total |
| GET `/subjects/{id}/locations` | 1 | Sightings by district, latest sightings |
| GET `/subjects/{id}/status-history` | 1 | Every status change with reason |
| GET `/subjects/{id}/infractions` | 1 | `served=true\|false` |
| GET `/subjects/{id}/manhunts` | 1 | `sort` (bounty, recent, length), `top=5` |
| GET `/subjects/{id}/notes` | 1 | Notes, restricted ones masked |
| GET `/infractions`, `/manhunts`, `/manhunts/{id}` | 1 | Global lists and manhunt detail |
| GET `/infraction-types`, `/cost-rates` | 1 | Reference data |
| GET `/rankings` | 1 | `scope` (single, lifetime), `category`, `limit` |
| POST `/subjects/{id}/infractions` | 2 | `infraction_type_id`, `occurred_on`, `location`, `disposition` |
| POST `/subjects/{id}/manhunts` | 2 | Duration, outcome, district, counts, optional `infractions[]` |
| POST `/subjects/{id}/sightings` | 2 | `district`, `seen_at`, optional address and source |
| POST `/subjects/{id}/notes` | 2 | `body`, optional `access_level` up to your own level |
| DELETE `/notes/{id}` | 2 | Author or supervisor |
| POST `/subjects` | 3 | Create. Optional starting `status` |
| PUT `/subjects/{id}` | 3 | Profile fields only |
| POST `/subjects/{id}/status` | 3 | `status`, `reason` |
| PATCH `/infractions/{id}` | 3 | `served` and/or `fine_paid` |
| POST `/subjects/{id}/case-file` | 3 | Everything for the printable case file in one response. Audited as `case_file_generated` |
| DELETE `/subjects/{id}` | 4 | Archive (soft delete) |
| POST `/subjects/{id}/restore` | 4 | Restore |
| GET `/subjects/export` | 4 | Streamed JSON of every record, audited |
| PUT `/subjects/{id}/threat-override` | 4 | `level` (1 to 6 or null), `reason` |
| DELETE `/infractions/{id}`, `/manhunts/{id}` | 4 | Remove |
| PUT `/cost-rates` | 4 | `rates: [{code, unit_cost}]` |
| GET `/audit-logs` | 4 | `user_id`, `subject_id`, `action`, `from`, `to` |

Ranking categories: `length`, `officers_involved`, `officers_injured`, `units_stood_down`,
`checkpoints`, `k9_units`, `helicopters`, `cost`, `bounty`, `infractions`.

## Layout

```
app/Enums          Clearance, SubjectStatus, ThreatLevel, ManhuntOutcome, Severity, AuditAction
app/Services       StatusService, SubjectStatsService, ManhuntService, InfractionService,
                   CostToStateCalculator, RankingService, AuditLogger, SubjectService
app/Observers      AuditObserver
app/Http/...       thin controllers, form requests, resources, clearance middleware
database/seeders   reference data, demo officers, fictional demo people (no outside calls)
tests              auth, clearance, status, bounty and cost, audit, rankings, lists
```

## What changed from the first version

| Problem found in review | Now |
|---|---|
| Demo login accepted any key for existing officers, on by default | Off by default, never skips the key check |
| `.env`, log and database file inside the zip | Not included |
| Seeder always created `RP-1047` / `demo1234` | Local only, or needs `SEED_OFFICER_PASSWORD` |
| Export was not audited, loaded everything at once | Audited, streamed in chunks |
| Tokens never expired, failed logins not logged | Expire, failed logins logged |
| Status guessed from outcome text, blank meant WANTED | Explicit status with history and reasons |
| Deleting the last offense reset status to WANTED | Status is never touched by infractions |
| Risk override always on | Bounty and threat are calculated, with a supervisor override |
| Dashboard numbers cached 30 seconds | No cache, always current |
| Audit log full of repeat "viewed" rows | One view per officer per 10 minutes |
| Deletions logged as "updated" | Own actions, written by the observer |
| Clearance 4 and restricted notes unreachable | Four real levels, restricted notes work |
| Dead and duplicate code, one-line controllers | Services, thin controllers, enums |
| Welcome page test needed Vite build | No web routes, `/up` health check only |

## Frontend

The React app lives in its own folder (`rockport_ui`). It talks to this API at `/api/v1`.
Set `FRONTEND_URL` in `.env` if the app does not run on `http://localhost:5173`.

Times are stored in UTC. The app sends timestamps in UTC and shows them in your own time zone.
Infraction dates may be up to one day ahead of the server date, so officers ahead of UTC can still enter today.
