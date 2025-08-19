<?php
$config = require __DIR__ . '/../config.php';
$db = $config['db'];
$dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
$pdo = new PDO($dsn, $db['user'], $db['pass'], [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

$rows = [];
$hasStandings = true;
try {
  $rows = $pdo->query("SELECT * FROM standings LIMIT 1000")->fetchAll();
} catch (Throwable $e) {
  $hasStandings = false;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>League HQ</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="/assets/app.css">
  <script defer src="/assets/app.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body{font-family: system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,Arial,sans-serif; margin:0; background:#0a0a0a; color:#f5f5f5;}
    header{padding:16px 20px; border-bottom:1px solid #222; display:flex; align-items:center; gap:12px}
    .nwo {font-weight:800; background:#000; color:#fff; padding:4px 8px; border:2px solid #fff; display:inline-block}
    main{max-width:1100px; margin:24px auto; padding:0 16px;}
    .card{background:#101010; border:1px solid #1f1f1f; border-radius:16px; padding:16px; box-shadow:0 1px 0 #111;}
    table{width:100%; border-collapse: collapse;}
    th, td{padding:10px; border-bottom:1px solid #222; text-align:left; font-size:14px}
    th{color:#ccc; text-transform:uppercase; letter-spacing:.08em; font-size:12px}
    .grid{display:grid; gap:16px}
    @media(min-width:900px){ .grid{grid-template-columns: 1.2fr .8fr} }
    a{color:#9dd1ff}
  </style>
</head>
<body>
  <header>
    <div class="nwo">nWo</div>
    <div style="font-weight:700; font-size:18px">League HQ</div>
  </header>
  <main class="grid">
    <section class="card">
      <h2 style="margin:0 0 12px">Standings</h2>
      <?php if (!$hasStandings): ?>
        <p>No <code>standings</code> table found yet. Put CSVs in <code>tools/data/</code> and run the importer.</p>
      <?php elseif (!$rows): ?>
        <p>No data yet. Run the importer and refresh.</p>
      <?php else: ?>
        <div style="overflow:auto">
          <table>
            <thead>
              <tr>
                <?php foreach (array_keys($rows[0]) as $h): ?>
                  <th><?=htmlspecialchars($h)?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <?php foreach ($r as $val): ?>
                    <td><?=htmlspecialchars((string)$val)?></td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <section class="card">
      <h2 style="margin:0 0 12px">Wins by Team</h2>
      <canvas id="winsChart" width="400" height="300"></canvas>
      <script>
        (async () => {
          try{
            const res = await fetch('/api/standings.php');
            if (!res.ok) return;
            const data = await res.json();
            if (!data || !data.length) return;
            const cols = Object.keys(data[0]);
            const teamCol = cols.find(c=>/team|owner|manager|franchise/i.test(c)) || cols[1] || cols[0];
            const winsCol = cols.find(c=>/win/i.test(c)) || null;
            if (!winsCol) return;
            const labels = data.map(r => r[teamCol]);
            const wins = data.map(r => Number(r[winsCol]) || 0);
            const ctx = document.getElementById('winsChart').getContext('2d');
            new Chart(ctx, {
              type: 'bar',
              data: { labels, datasets: [{ label: 'Wins', data: wins }]},
              options: { responsive: true, plugins: { legend: { display: true }}, scales: { y: { beginAtZero: true } } }
            });
          } catch(e){}
        })();
      </script>
      <p style="font-size:12px;color:#aaa;margin-top:8px">If the chart is empty, we couldn’t guess your wins column. You can hardcode fields in <code>/public/api/standings.php</code>.</p>
    </section>
  </main>
</body>
</html>
