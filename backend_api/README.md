# Bohol Police Department Records API

Laravel API for the Bohol Police Department records dashboard: people with a bounty, manhunts, infractions,
field reports, real Bohol places from PSGC, five clearance levels, and a Chief Admin who decides who gets in.

> **Status:** written and checked by reading and a static script, but **not run** on my side (no PHP there).
> The first thing to do is `php artisan test`. If something fails, send me the output.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# create an empty MySQL database (for example bohol_records), then set DB_* in .env
php artisan migrate:fresh --seed     # wipes the tables, fine for demo data
php artisan bpd:sync-places          # real municipalities and barangays of Bohol (needs internet)
php artisan serve                    # API at http://localhost:8000/api/v1
php artisan test                     # needs the pdo_sqlite PHP extension
```

### Updating an install that already works

```bash
php artisan migrate                  # adds new tables and columns, keeps your data
php artisan db:seed --class=OfficerSeeder       # adds the Chief Admin and the new ranks
php artisan db:seed --class=BulletinSeeder      # bulletin text with real places
php artisan db:seed --class=ReportSeeder        # a few demo field reports
php artisan bpd:sync-places          # real places
php artisan optimize:clear
```

`migrate` also renames badge IDs and case IDs from `RP-` to `BPD-` and swaps the old made-up districts for real
Bohol municipalities. Nothing else is touched.

### Demo officers (local only)

Password `demo1234`, or whatever `SEED_OFFICER_PASSWORD` says. In production the seeder refuses without it.

| Badge | Name | Rank | Level |
|---|---|---|---|
| BPD-1000 | Col. R. Villanueva | Provincial Director | 5 Chief Admin |
| BPD-1001 | PLTCOL R. Hale | Police Lieutenant Colonel | 4 Supervisor |
| BPD-1047 | Det. A. Cross | Detective | 3 Detective |
| BPD-1102 | PSSg T. Reyes | Police Staff Sergeant | 2 Senior Officer |
| BPD-1200 | Pat. M. Ono | Patrolman / Patrolwoman | 1 Patrol Officer |

Create real accounts with `php artisan bpd:create-officer BPD-2000 "Jane Doe" --clearance=3`.
Chief Admin accounts (level 5) can only be created and changed from the command line.

## Clearance levels

Each level includes everything below it.

| Level | Role | Can do |
|---|---|---|
| 1 | Patrol Officer | File field reports. See and edit only their own reports until they are reviewed. See no records of people. Delete nothing |
| 2 | Senior Officer | Read the database of people. Add infractions, manhunts, sightings and notes. See, edit and review every report. Needs a higher level to create or change records |
| 3 | Detective | Create and edit records. Change status. Mark infractions served or fines paid. Photos. Case files |
| 4 | Supervisor | Archive records. Export. Threat override. Cost rates. Audit log |
| 5 | Chief Admin | Everything above, plus: personnel and their personal information, review access applications, issue passwords, correct crime history (remove infractions and manhunts), clear or re-open a record, restore archived records, delete reports |

A refused request answers `403` with `required_level`, `required_label`, `your_level` and `your_label`, and writes
`access_denied` to the audit log. The app shows it as a warning.

## Getting access

1. An officer fills in the public application form (`POST /access-requests`): name, rank, birth date, mobile number,
   station, address, years of service, and why they need access. They choose neither a badge ID nor a password.
2. The **rank** decides the highest level. Detectives get level 3 on the application without a decision. Every
   other rank starts at what its rank allows and the Chief Admin picks the level. Nobody can apply for level 5.
3. The Chief Admin reviews it (`/admin/access-requests`). Approving creates the account with a **random made-up
   badge ID** (BPD-4821) and a **password issued by the Chief Admin** (a strong random one, or one they type).
   The password is shown once and is never stored in readable form.
4. The officer can check progress with their reference number (`ACC-XXXXXXXXXX`). After approval they see their badge ID.
   The password is handed over by the Chief Admin in person.

Rank ladder and limits are in `config/bpd.php`:

| Rank | Highest level |
|---|---|
| Patrolman / Patrolwoman, Police Corporal | 1 |
| Police Staff Sergeant to Police Executive Master Sergeant | 2 |
| Police Lieutenant, **Detective** (automatic) | 3 |
| Police Captain, Major, Lieutenant Colonel, Colonel | 4 |
| Provincial Director | 5 (command line only) |

## Places (PSGC)

`php artisan bpd:sync-places` reads the official Philippine Standard Geographic Code through PSGC Cloud
(`https://psgc.cloud/api/v2`): the municipalities of Bohol, the capital city of Tagbilaran (turn off with
`PSGC_INCLUDE_CITIES=false`), and the barangays of each. It is polite (a short pause between calls), safe to run
again, and reports any town it could not load. A built-in list of the 47 municipalities and the city is
loaded by the seeder so every dropdown works before the first sync.

## How the numbers work

**Bounty** = bounty of every unserved infraction + bounty of every manhunt.
**Threat level (1 to 6)** comes from the bounty (`config/bpd.php`). A supervisor can pin a level with a reason.
**Status** (`WANTED`, `IN_CUSTODY`, `PAROLED`, `CLEARED`) changes only when a detective changes it with a reason,
or when a manhunt outcome does it. Clearing a record, or re-opening a cleared one, needs the Chief Admin.
**Cost to state** = quantity x unit price. A manhunt stores its own cost and bounty when logged.

## Security

- Demo mode is off by default and never skips the key check for an existing officer.
- Login: same 401 for every failure, rate limited, failed attempts logged. Tokens expire and are revoked on logout.
- Application form: public, so it is limited to 5 per hour per address, has a hidden bot trap, one waiting
  application per person, and only shows status (never personal details) to the applicant.
- The rank limit is enforced on the server for applications, approvals and edits. Level 5 is never handed out by the app.
- Only the Chief Admin issues passwords. A new password signs the person out everywhere. Deactivating does too.
- Every input has a limit (see below). Free text is checked for HTML, control characters and hidden direction characters.
- Portrait uploads: JPG, PNG or WebP only, up to 2 MB, type read from the file contents, random file names, private
  disk, signed expiring links, re-encoded when the GD extension is on.
- The audit log is written by observers and cannot be edited or deleted. It records access requests, approvals,
  password issues, refused requests, exports, reports and every change to a record.

## Input limits

| Field | Rule |
|---|---|
| Names | 2 to 120. Letters (any language), spaces, apostrophes, periods, hyphens. No digits. Officers need first and last name |
| Alias | 2 to 80. Letters, numbers, spaces, quotes, apostrophes, periods, hyphens |
| Age (subjects) | Whole number, 1 to 120 |
| Officer birth date | Age 21 to 65. Years of service cannot exceed age minus 18 |
| Mobile number | Philippine format, 09 plus nine digits (+63 and spaces are cleaned up) |
| Places | Municipality must be a real one from the list. Barangay must belong to it once barangays are loaded |
| Height, colours | Short formats (5'8", 173 cm. Letters only) |
| Reports | Narrative 20 to 3000. Person involved optional. Not in the future |
| Free text | Plain text only. Location 120, features 500, advisory 2000, notes 5000, reasons 300 |
| Manhunt | 1 second to 48 hours. Counts 0 to 5000 (checkpoints 500, K-9 200, helicopters 50) |
| Lists | `page` 1 to 100000, `per_page` 1 to 100, search up to 100, sort and filter values checked |
| Passwords | At least 12, with letters and numbers |

Uploads need PHP's `upload_max_filesize` and `post_max_size` at 3M or more. Turn on `extension=gd` to have images cleaned.

## Endpoints

Base path `/api/v1`. Level `-` means open to anyone.

| Method and path | Level | What it does |
|---|---|---|
| POST `/auth/login` | - | `badge_id`, `access_key` |
| GET `/places/municipalities`, `/places/municipalities/{id}/barangays` | - | Real Bohol places |
| GET `/access-requests/options` | - | Ranks and the level each one gets |
| POST `/access-requests` | - | Apply for access |
| GET `/access-requests/{reference}` | - | Status of an application |
| POST `/auth/logout`, GET `/auth/me` | 1 | Session |
| GET `/dashboard/stats`, `/bulletins/current`, `/infraction-types` | 1 | Numbers, bulletin, types |
| GET, POST `/reports`, GET, PUT `/reports/{id}` | 1 | File and read reports (own only at level 1) |
| GET `/dashboard/priority` | 2 | Five highest bounties |
| GET `/subjects`, `/subjects/{id}` and its sub pages (`rap-sheet`, `infraction-summary`, `cost-to-state`, `locations`, `analytics`, `status-history`, `infractions`, `manhunts`, `notes`) | 2 | The database of people |
| GET `/infractions`, `/manhunts`, `/manhunts/{id}`, `/rankings`, `/cost-rates` | 2 | Lists and rankings |
| POST `/subjects/{id}/infractions`, `/manhunts`, `/sightings`, `/notes`; DELETE `/notes/{id}` | 2 | Field records |
| POST `/reports/{id}/review` | 2 | Mark a report reviewed |
| POST `/subjects`, PUT `/subjects/{id}`, POST `/subjects/{id}/status`, `/case-file`, `/photo`, DELETE `/subjects/{id}/photo`, PATCH `/infractions/{id}` | 3 | Records |
| GET `/subjects/export`, DELETE `/subjects/{id}`, PUT `/subjects/{id}/threat-override`, PUT `/cost-rates`, GET `/audit-logs` | 4 | Supervisor |
| POST `/subjects/{id}/restore`, DELETE `/infractions/{id}`, `/manhunts/{id}`, `/reports/{id}` | 5 | Corrections |
| GET `/admin/summary`, `/admin/archived-subjects` | 5 | Chief Admin |
| GET `/admin/access-requests`, POST `.../{id}/approve`, `.../{id}/reject` | 5 | Review applications |
| GET, POST `/admin/personnel`, PUT `/admin/personnel/{id}`, POST `.../password`, `.../activate`, `.../deactivate` | 5 | Personnel |

## Layout

```
app/Enums          Clearance, SubjectStatus, ThreatLevel, ManhuntOutcome, Severity, AuditAction
app/Support        Ranks (rank ladder), Patterns (character whitelists), Denied (the 403 answer)
app/Services       AccessRequestService, PersonnelService, CredentialService, PlaceService, PsgcClient,
                   StatusService, SubjectStatsService, ManhuntService, InfractionService, AuditLogger ...
app/Observers      AuditObserver
app/Http/...       thin controllers (Admin/ for the Chief Admin), form requests, resources, clearance middleware
config/bpd.php     ranks, thresholds, PSGC settings, limits
database/seeders   places, officers, reference data, fictional demo people and reports
tests              clearance, reports, access requests, personnel, places, status, bounty, audit, rankings ...
```

## Frontend

The React app lives in its own folder (`bpd_ui`). It talks to this API at `/api/v1`.
Set `FRONTEND_URL` in `.env` if the app does not run on `http://localhost:5173`.
