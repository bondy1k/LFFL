# Fantasy League Site (IONOS PHP + MySQL)

This is a minimal starter to get your league site live fast on IONOS shared hosting.

## What you get
- **`tools/import_csv.php`**: one-time importer to create tables (from CSV headers) and load rows.
- **`public/index.php`**: dynamic standings page (table + chart) from the DB.
- **`public/api/standings.php`**: JSON endpoint used by the chart.
- **`config.sample.php`**: copy to `config.php` and fill in your DB credentials and CSV directory.

## Quick start

1. **Create the MySQL DB** in IONOS (done). Make note of host, db name, user, password.
2. **Copy `config.sample.php` → `config.php`** and put your real credentials in it.
3. **Upload your CSVs to the server** into `tools/data/`.
4. **Run the importer once**:
   - Visit: `https://YOURDOMAIN/tools/import_csv.php?token=change-this-token`
   - You should see a list of imported files.
   - Then delete or rename `tools/import_csv.php` or change the token.
5. **Visit the site**: `https://YOURDOMAIN/` to see your standings table + chart.

## CSV file names expected

You can change these in `config.php` later, but the default list is:
- league_overview.csv
- league_stat_settings.csv
- rosters_settings.csv
- standings.csv
- trades.csv
- matchups.csv
- transactions.csv
- weekly_rosters.csv
- weekly_player_stats.csv

## Notes
- `public/.htaccess` routes everything to `index.php` and sets sane headers.
- `config.php` is ignored by git via `.gitignore` to keep credentials private.
- The importer guesses column types; you can refine schemas later (add indexes, FKs).
- Theme is dark with a subtle nWo nod—you can tweak `app.css`.
