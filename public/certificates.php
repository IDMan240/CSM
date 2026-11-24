<?php
/*
 * Calibration Management System — Certificates List
 * Upload this file to: htdocs/public/certificates.php
 */
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

function e($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function firstValue(array $row, array $keys, string $fallback = '—'): string {
    foreach ($keys as $key) {
        if (array_key_exists($key, $row) && $row[$key] !== null && trim((string)$row[$key]) !== '') {
            return (string)$row[$key];
        }
    }
    return $fallback;
}

function dateValue(array $row, array $keys): string {
    $value = firstValue($row, $keys, '');
    if ($value === '') return '—';
    $time = strtotime($value);
    return $time ? date('d M Y', $time) : $value;
}

$catalog = [
    'Gauge' => [
        'table' => 'gauge_calibrations',
        'item_keys' => ['test_item', 'instrument_name', 'equipment_name'],
        'date_keys' => ['calibration_date', 'test_date', 'created_at'],
        'due_keys' => ['due_date', 'next_due_date'],
        'link' => static fn(array $r): string => '../certificate.php?id=' . rawurlencode((string)($r['id'] ?? '')),
    ],
    'Recorder' => [
        'table' => 'recorder_calibrations',
        'item_keys' => ['test_item', 'instrument_name', 'equipment_name'],
        'date_keys' => ['calibration_date', 'test_date', 'created_at'],
        'due_keys' => ['due_date', 'next_due_date'],
        'link' => static fn(array $r): string => 'recorder-certificate.php?certificate_no=' . rawurlencode((string)($r['certificate_no'] ?? '')),
    ],
    'Valve' => [
        'table' => 'valve_certificates',
        'item_keys' => ['tested_item', 'test_item', 'valve_class'],
        'date_keys' => ['test_date', 'calibration_date', 'created_at'],
        'due_keys' => ['due_date', 'next_due_date'],
        'link' => static fn(array $r): string => 'valve-certificate.php?id=' . rawurlencode((string)($r['id'] ?? '')),
    ],
];

$records = [];
$dbErrors = [];

foreach ($catalog as $type => $config) {
    try {
        // Query the fixed, allow-listed table name directly. A missing table is
        // handled by the catch below; avoid prepared SHOW TABLES syntax, which
        // is not supported consistently by shared-host MySQL/PDO configurations.
        $stmt = $pdo->query('SELECT * FROM `' . $config['table'] . '` ORDER BY `id` DESC LIMIT 500');
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $row['_type'] = $type;
            $row['_item'] = firstValue($row, $config['item_keys'], $type . ' calibration');
            $row['_client'] = firstValue($row, ['client', 'client_name', 'customer', 'company_name']);
            $row['_certificate_no'] = firstValue($row, ['certificate_no', 'certificate_number', 'cert_no']);
            $row['_serial'] = firstValue($row, ['serial_no', 'serial_number', 'serial']);
            $row['_calibration_date'] = dateValue($row, $config['date_keys']);
            $row['_due_date'] = dateValue($row, $config['due_keys']);
            $row['_url'] = $config['link']($row);
            $records[] = $row;
        }
    } catch (Throwable $error) {
        error_log('Certificates list error (' . $config['table'] . '): ' . $error->getMessage());
        $dbErrors[] = $type;
    }
}

usort($records, static function (array $a, array $b): int {
    $aDate = strtotime((string)($a['created_at'] ?? $a['calibration_date'] ?? $a['test_date'] ?? '')) ?: 0;
    $bDate = strtotime((string)($b['created_at'] ?? $b['calibration_date'] ?? $b['test_date'] ?? '')) ?: 0;
    return $bDate <=> $aDate;
});

$search = trim((string)($_GET['q'] ?? ''));
$typeFilter = trim((string)($_GET['type'] ?? 'All'));
$filtered = array_values(array_filter($records, static function (array $r) use ($search, $typeFilter): bool {
    if ($typeFilter !== 'All' && $r['_type'] !== $typeFilter) return false;
    if ($search === '') return true;
    $haystack = implode(' ', [
        $r['_type'], $r['_certificate_no'], $r['_client'], $r['_item'], $r['_serial']
    ]);
    return stripos($haystack, $search) !== false;
}));

$userName = $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User';
$userRole = $_SESSION['role'] ?? 'technician';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CMS | Certificates</title>
<link rel="icon" type="image/png" href="/public/img/logo.png">
<link rel="apple-touch-icon" href="/public/img/logo.png">
<style>
*{box-sizing:border-box}body{margin:0;background:#f4f5f7;color:#171717;font-family:Arial,Helvetica,sans-serif}.sidebar{position:fixed;inset:0 auto 0 0;width:235px;background:#111;color:#fff;padding:24px 14px;display:flex;flex-direction:column}.brand{padding:4px 12px 24px;border-bottom:1px solid #303030;margin-bottom:18px}.brand strong{display:block;letter-spacing:2px;font-size:18px}.brand small{display:block;color:#aaa;font-size:10px;margin-top:7px;letter-spacing:1px}.nav-label{font-size:10px;color:#777;letter-spacing:2px;margin:10px 12px}.nav a{display:block;text-decoration:none;color:#c8c8c8;padding:13px 12px;margin:4px 0;font-size:13px;border-left:3px solid transparent}.nav a:hover,.nav a.active{background:#2a1717;color:#fff;border-left-color:#d00000}.sidebar-bottom{margin-top:auto;border-top:1px solid #303030;padding:16px 8px 0}.sidebar-bottom strong{display:block;font-size:12px;overflow-wrap:anywhere}.sidebar-bottom small{display:block;color:#d00000;margin:6px 0 14px;text-transform:uppercase;font-size:10px}.sidebar-bottom a{display:block;text-align:center;color:#fff;text-decoration:none;border:1px solid #d00000;padding:11px;font-size:11px}.main{margin-left:235px;min-height:100vh}.topbar{height:76px;background:#fff;border-bottom:3px solid #d00000;padding:0 30px;display:flex;align-items:center;justify-content:space-between;gap:15px}.eyebrow{font-size:10px;color:#d00000;letter-spacing:2px;font-weight:bold}.topbar h1{font-size:21px;margin:5px 0 0}.top-user{font-size:12px;text-align:right}.top-user small{display:block;color:#d00000;margin-top:5px;text-transform:uppercase}.content{padding:28px;max-width:1500px;margin:auto}.intro{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:22px}.intro h2{font-size:24px;margin:0 0 7px}.intro p{margin:0;color:#666;font-size:13px}.btn{display:inline-block;text-decoration:none;background:#d00000;color:white;padding:12px 16px;border:0;font-weight:bold;font-size:12px;cursor:pointer}.stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:22px}.stat,.panel{background:#fff;border:1px solid #e7e7e7;box-shadow:0 3px 12px rgba(0,0,0,.035)}.stat{padding:18px}.stat span{font-size:11px;color:#666;text-transform:uppercase;letter-spacing:1px}.stat strong{display:block;font-size:27px;margin-top:9px}.panel{padding:20px}.filters{display:flex;gap:10px;margin-bottom:18px}.filters input,.filters select{min-width:0;padding:12px;border:1px solid #ddd;background:#fff;font-size:13px}.filters input{flex:1}.filters button{padding:0 20px;background:#111;color:#fff;border:0;cursor:pointer}.table-wrap{overflow-x:auto}table{border-collapse:collapse;width:100%;min-width:850px}th{text-align:left;background:#171717;color:#fff;font-size:10px;letter-spacing:.8px;text-transform:uppercase;padding:14px 12px}td{padding:14px 12px;border-bottom:1px solid #eee;font-size:12px;vertical-align:middle}tbody tr:hover{background:#fffafa}.pill{display:inline-block;padding:6px 8px;background:#f4e5e5;color:#9f0000;font-size:10px;font-weight:bold;text-transform:uppercase}.view{display:inline-block;text-decoration:none;color:#b00000;border:1px solid #d00000;padding:8px 10px;font-size:11px;font-weight:bold;white-space:nowrap}.view:hover{background:#d00000;color:#fff}.empty{text-align:center;padding:40px 16px;color:#666}.notice{padding:12px 14px;margin-bottom:16px;background:#fff4df;border-left:4px solid #c88700;font-size:12px;color:#684900}.footer{padding:20px;text-align:center;color:#888;font-size:10px}@media(max-width:900px){.sidebar{width:200px}.main{margin-left:200px}.content{padding:18px}.stats{grid-template-columns:repeat(2,minmax(0,1fr))}.topbar{padding:0 18px}}@media(max-width:650px){.sidebar{position:static;width:auto;height:auto;padding:14px}.brand{padding:4px 8px 12px;margin-bottom:10px}.nav{display:flex;flex-wrap:wrap}.nav-label{width:100%}.nav a{padding:9px;font-size:11px}.sidebar-bottom{display:none}.main{margin-left:0}.topbar{height:auto;min-height:70px;padding:12px 16px}.topbar h1{font-size:17px}.top-user{display:none}.content{padding:14px}.intro{align-items:flex-start;flex-direction:column}.intro h2{font-size:21px}.stats{gap:8px}.stat{padding:12px}.stat strong{font-size:22px}.panel{padding:12px}.filters{flex-wrap:wrap}.filters input{flex-basis:100%}.filters select{flex:1}.filters button{min-height:40px}}
</style>
</head>
<body>
<aside class="sidebar">
  <div class="brand"><strong>CMS</strong><small>CALIBRATION MANAGEMENT</small></div>
  <nav class="nav">
    <div class="nav-label">MAIN MENU</div>
    <a href="dashboard.php">▦ &nbsp; Dashboard</a>
    <a href="gauge.php">◉ &nbsp; Gauge Calibration</a>
    <a href="recorder.php">▤ &nbsp; Recorder Calibration</a>
    <a href="valve.php">◇ &nbsp; Valve Testing</a>
    <a class="active" href="certificates.php">▣ &nbsp; Certificates</a>
    <a href="verify.php">✓ &nbsp; Verify Certificate</a>
    <a href="users.php">♙ &nbsp; Users</a>
    <a href="nuprc-settings.php">⚙ &nbsp; NUPRC Settings</a>
  </nav>
  <div class="sidebar-bottom"><strong><?= e($userName) ?></strong><small><?= e($userRole) ?></small><a href="../auth/logout.php">LOG OUT</a></div>
</aside>
<main class="main">
  <header class="topbar"><div><div class="eyebrow">CALIBRATION MANAGEMENT SYSTEM</div><h1>Certificates</h1></div><div class="top-user"><strong><?= e($userName) ?></strong><small><?= e($userRole) ?></small></div></header>
  <section class="content">
    <div class="intro"><div><h2>Calibration Records</h2><p>Search saved gauge, recorder, and valve certificates.</p></div><a class="btn" href="dashboard.php">← Dashboard</a></div>
    <?php if ($dbErrors): ?><div class="notice">Some records could not be loaded from the database. Please check the server error log for the affected record type: <?= e(implode(', ', $dbErrors)) ?>.</div><?php endif; ?>
    <div class="stats">
      <div class="stat"><span>All records loaded</span><strong><?= count($records) ?></strong></div>
      <div class="stat"><span>Gauge certificates</span><strong><?= count(array_filter($records, static fn($r) => $r['_type'] === 'Gauge')) ?></strong></div>
      <div class="stat"><span>Recorder certificates</span><strong><?= count(array_filter($records, static fn($r) => $r['_type'] === 'Recorder')) ?></strong></div>
      <div class="stat"><span>Valve certificates</span><strong><?= count(array_filter($records, static fn($r) => $r['_type'] === 'Valve')) ?></strong></div>
    </div>
    <div class="panel">
      <form class="filters" method="get" action="certificates.php">
        <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search certificate number, client, item or serial number…">
        <select name="type" aria-label="Filter by calibration type">
          <?php foreach (['All','Gauge','Recorder','Valve'] as $option): ?><option value="<?= e($option) ?>" <?= $typeFilter === $option ? 'selected' : '' ?>><?= e($option) ?> types</option><?php endforeach; ?>
        </select>
        <button type="submit">SEARCH</button>
        <?php if ($search !== '' || $typeFilter !== 'All'): ?><a class="view" href="certificates.php">CLEAR</a><?php endif; ?>
      </form>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Type</th><th>Certificate No.</th><th>Client</th><th>Test Item</th><th>Serial No.</th><th>Calibration Date</th><th>Due Date</th><th>Action</th></tr></thead>
          <tbody>
          <?php if (!$filtered): ?>
            <tr><td class="empty" colspan="8"><?php if (!$records): ?>No calibration records were found in the available database tables yet. Save a calibration record first, then refresh this page.<?php else: ?>No records match your search. Try another keyword or clear the filters.<?php endif; ?></td></tr>
          <?php else: foreach ($filtered as $record): ?>
            <tr>
              <td><span class="pill"><?= e($record['_type']) ?></span></td>
              <td><strong><?= e($record['_certificate_no']) ?></strong></td>
              <td><?= e($record['_client']) ?></td>
              <td><?= e($record['_item']) ?></td>
              <td><?= e($record['_serial']) ?></td>
              <td><?= e($record['_calibration_date']) ?></td>
              <td><?= e($record['_due_date']) ?></td>
              <td><a class="view" href="<?= e($record['_url']) ?>" target="_blank" rel="noopener">OPEN CERTIFICATE ↗</a></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="footer">CMS · Calibration Management System</div>
  </section>
</main>
</body>
</html>
