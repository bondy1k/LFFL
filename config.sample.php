<?php
// config.sample.php
return [
  'db' => [
    'host' => 'DB_HOST',   // e.g., localhost or your IONOS DB host
    'name' => 'DB_NAME',
    'user' => 'DB_USER',
    'pass' => 'DB_PASS',
    'charset' => 'utf8mb4',
  ],
  'data_dir' => __DIR__ . '/tools/data',
  'csv_files' => [
    'league_overview.csv',
    'league_stat_settings.csv',
    'rosters_settings.csv',
    'standings.csv',
    'trades.csv',
    'matchups.csv',
    'transactions.csv',
    'weekly_rosters.csv',
    'weekly_player_stats.csv',
  ],
];
