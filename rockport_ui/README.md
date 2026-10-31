# Rockport Records UI

React front end for the Rockport Records API (`rockport_api`). Same green terminal look as before,
rebuilt around people, bounties, manhunts and cost to the state.

> **Status:** linted with your project's ESLint config (0 errors, 0 warnings) and every import and
> style class was checked by script, but the app has **not been opened in a browser**.
> Run it, click through every tab once, and send me a screenshot of anything that looks wrong.

## Setup

```bash
npm install
cp .env.example .env     # copy .env.example .env on Windows cmd
npm run dev              # http://localhost:5173
```

The API must be running (`php artisan serve` in `rockport_api`). Set `VITE_API_URL` in `.env`
if it is not at `http://localhost:8000/api/v1`. Demo login on a local build: `RP-1047` / `demo1234`.

Other scripts: `npm run lint`, `npm run build`.

## Screens

| Route | Screen | Needs level |
|---|---|---|
| `/command` | Dashboard: stats, priority subjects (bounty and heat level), bulletin, shortcuts | 1 |
| `/database` | Subject list with search, status, threat and sort filters, real pagination | 1 |
| `/database/:id` | Overview: rap sheet summary, heat level meter with suggested response, advisory | 1 |
| `/database/:id/infractions` | Infractions by type, full list, mark served or fine paid | 1 (edit from 2 or 3) |
| `/database/:id/manhunts` | Manhunts, top bounty first, log a new one | 1 (log from 2) |
| `/database/:id/manhunts/:id` | Manhunt detail and cost breakdown. Esc goes back | 1 |
| `/database/:id/locations` | Known districts, recent sightings, log a sighting | 1 (log from 2) |
| `/database/:id/cost` | Cost to state table | 1 |
| `/database/:id/notes` | Case notes with access levels | 1 (add from 2) |
| `/database/:id/history` | Every status change with its reason | 1 |
| `/case-file/:id` | Printable case file (Print, then Save as PDF) | 3 |
| `/infractions` | Infraction index across all files | 1 |
| `/rankings` | Leaderboard, single manhunt or lifetime, 10 categories. Key `1` switches | 1 |
| `/audit` | Audit log with filters | 4 |

Buttons you are not cleared for are hidden. The server still enforces every level.

## Keys

| Key | Does |
|---|---|
| Arrow up and down | Move through the subject list (not while typing or in a dialog) |
| Ctrl or Cmd + K | Focus the search box |
| Esc | Back from a manhunt detail |
| 1 | Rankings: single manhunt or lifetime |

## Layout

```
src/api          axios client (token, 401 handling) and one function per endpoint
src/context      auth and toast providers
src/hooks        queries.js, mutations.js, useHotkeys.js
src/lib          number, time and date formatting, error helpers, demo number generator
src/components   ui.jsx (shared pieces), ThreatMeter.jsx
src/features     auth, dashboard, subjects, manhunts, infractions, rankings, audit, casefile
```

## What changed from the first version

| Problem found in review | Now |
|---|---|
| Arrow keys hijacked inside forms and jumped to a stale item | Only works outside inputs and dialogs, moves from the open record |
| A token that died after a fresh login did not send you to login | The 401 listener is always registered |
| Loading showed `0—` in the number boxes | Shows `—` |
| Archive reloaded the whole page and hid its own message | Normal navigation, one message, errors shown |
| Lists stopped at 50 with no way to see more | Pagination everywhere |
| Hard-coded bulletin time and a made-up fallback officer name | Real values only |
| Every save forced "risk override" and posted the whole record back | Sends only editable fields. Bounty and threat come from the server |
| One 120 line page with dense one-line components | Feature folders, small components |
| Export failed silently | Shows a message either way |
| Tiny 5 to 7px labels | Raised to 9 to 11px |

## Add-ons included

- **Log a manhunt** form with a "generate demo numbers" button and a live cost estimate
- **Heat levels** 1 to 6 on every list, the overview meter, and supervisor override
- **Printable case file** at `/case-file/:id`. Every one generated is written to the audit log
