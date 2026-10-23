# Operations: backups, go-live import, scheduler

For whoever looks after the server. Staff instructions are in `user-guide.md`.

## Scheduler (one cron line)

Backups run from Laravel's scheduler. Add this cron job once in cPanel > Cron Jobs (every minute):

```
* * * * * /opt/alt/php83/usr/bin/php /home/<cpanel-user>/public_html/<site-folder>/artisan schedule:run >> /dev/null 2>&1
```

`php artisan schedule:list` shows what will run: the nightly backup at 02:00 Manila time and `model:prune` at 03:00, which deletes website traffic rows older than 13 months.

On the live server the cron line was added on 2026-10-23 (the crontab was backed up to `~/crontab.bak-*` first).

## Demo data on deploy

The deploy runs `db:seed`. The demo seeders are safe to repeat: the year of clinic history and the demo traffic each run once per database, the demo CRM people are skipped when already on file, and branch hours, photos and staff edits made in the office are kept.

## Encrypted backups

```
php artisan clinic:backup              # take one now
php artisan clinic:backup --keep=30    # keep more history
```

- Written to `storage/app/private/backups/<organization>-YYYYMMDD-HHMMSS.sql.gz.enc`, never inside `public/`.
- The dump is compressed and encrypted with the site's `APP_KEY`. **Keep a copy of `APP_KEY` off the server** (a password manager): without it no backup can be read.
- The last 14 are kept; older ones are deleted.
- If `mysqldump` is not on the PATH, set `MYSQLDUMP_PATH` in `.env` (on WAMP: `C:/wamp64/bin/mysql/mysql<version>/bin/mysqldump.exe`).
- Copy backups off the server regularly (cPanel backup, or download them). A backup on the same disk does not survive the disk.

### Restoring

```
php artisan clinic:backup --decrypt=storage/app/private/backups/<file>.sql.gz.enc
gunzip storage/app/private/backups/<file>.sql.gz
mysql -u <user> -p <database> < storage/app/private/backups/<file>.sql
```

Restore into an empty database first and check it before pointing the site at it.

## Go-live import of existing records

The clinic's existing client list and stock count come in from CSV (save from Excel as "CSV UTF-8"). Always run with `--dry-run` first: it checks every line and saves nothing. A real run is all or nothing: one bad line and nothing is saved.

### Clients

```
php artisan clinic:import clients clients.csv --dry-run
php artisan clinic:import clients clients.csv
```

Columns: `first_name` (required), `last_name`, `email`, `phone`, `branch` (slug or name, for example `cebu` or `Bacoor`), `notes`, `marketing_consent` (`yes` or `no`).

Clients become leads at the "Customer" stage. Anyone whose email or phone is already on file is skipped and listed. Do not put medical history in `notes`.

### Opening stock

```
php artisan clinic:import stock stock.csv --dry-run
php artisan clinic:import stock stock.csv --user=owner@glutarepubliq.com
```

Columns: `sku` or `product` (the name exactly as in Catalogue), `branch`, `quantity`, `lot`, `expires_on` (`YYYY-MM-DD`), `cost`.

Each line becomes a dated lot with an "Opening balance" line in the stock ledger, so first-expiry-first-out works from the first sale. Products must exist in Catalogue first.

The summary table at the end gives the record counts for the migration sign-off (milestone M5).

## Accounts and access

- Public sign-up is off. Accounts are created in **Staff accounts** by the owner or an administrator.
- A switched-off account is signed out on its next request, however it signed in.
- Sign-ins, sign-outs and failed attempts are in the audit log with the IP address.
