<?php
/* Admin-only Calibration Management System settings page.
   Upload to htdocs/public/settings.php. Uses existing PDO $pdo. */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); exit('403 Forbidden: Administrator access only.'); }
require_once __DIR__ . '/../config/database.php';
if (!isset($pdo) || !($pdo instanceof PDO)) { http_response_code(500); exit('Expected PDO connection $pdo in config/database.php.'); }
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$pdo->exec("CREATE TABLE IF NOT EXISTS system_settings (
 setting_key VARCHAR(100) NOT NULL PRIMARY KEY, setting_value TEXT NULL,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if (empty($_SESSION['settings_csrf'])) $_SESSION['settings_csrf'] = bin2hex(random_bytes(32));
$csrf = $_SESSION['settings_csrf']; $message = ''; $error = '';
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function setting(PDO $pdo, string $key, string $default=''): string {
 $s=$pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key=?'); $s->execute([$key]); $v=$s->fetchColumn();
 return ($v===false || $v===null) ? $default : (string)$v;
}
function saveSetting(PDO $pdo, string $key, string $value): void {
 $s=$pdo->prepare('INSERT INTO system_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)'); $s->execute([$key,$value]);
}
if ($_SERVER['REQUEST_METHOD']==='POST') {
 try {
  if (!hash_equals($csrf,(string)($_POST['csrf_token']??''))) throw new RuntimeException('Security check failed. Refresh and retry.');
  $action=(string)($_POST['action']??'');
  if ($action==='save_settings') {
   $values=[
    'company_name'=>trim((string)($_POST['company_name']??'')),
    'company_address'=>trim((string)($_POST['company_address']??'')),
    'company_phone'=>trim((string)($_POST['company_phone']??'')),
    'company_email'=>trim((string)($_POST['company_email']??'')),
    'certificate_prefix'=>strtoupper(trim((string)($_POST['certificate_prefix']??'CAL'))),
    'next_certificate_number'=>trim((string)($_POST['next_certificate_number']??'1')),
    'verification_url'=>trim((string)($_POST['verification_url']??'')),
    'verification_enabled'=>isset($_POST['verification_enabled'])?'1':'0'
   ];
   if ($values['certificate_prefix']==='' || !preg_match('/^[A-Z0-9_-]{1,20}$/',$values['certificate_prefix'])) throw new RuntimeException('Certificate prefix: use 1–20 letters, numbers, hyphens or underscores.');
   if (!ctype_digit($values['next_certificate_number']) || (int)$values['next_certificate_number']<1) throw new RuntimeException('Next certificate number must be a whole number above zero.');
   if ($values['company_email']!=='' && !filter_var($values['company_email'],FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Enter a valid company email.');
   if ($values['verification_url']!=='' && !filter_var($values['verification_url'],FILTER_VALIDATE_URL)) throw new RuntimeException('Enter a complete verification URL.');
   foreach ($values as $k=>$v) saveSetting($pdo,$k,$v);
   if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error']!==UPLOAD_ERR_NO_FILE) {
    $f=$_FILES['company_logo'];
    if ($f['error']!==UPLOAD_ERR_OK || $f['size']>2097152) throw new RuntimeException('Logo upload failed or exceeds 2 MB.');
    $info=@getimagesize($f['tmp_name']); $mime=$info['mime']??'';
    $ext=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'][$mime]??null;
    if (!$ext) throw new RuntimeException('Logo must be a valid PNG, JPG or WEBP image.');
    $dir=__DIR__.'/uploads';
    if (!is_dir($dir) && !mkdir($dir,0755,true) && !is_dir($dir)) throw new RuntimeException('Cannot create public/uploads. Create htdocs/public/uploads in File Manager.');
    $name='company-logo-'.bin2hex(random_bytes(8)).'.'.$ext;
    if (!move_uploaded_file($f['tmp_name'],$dir.'/'.$name)) throw new RuntimeException('Could not save logo; check uploads folder permissions.');
    saveSetting($pdo,'company_logo','uploads/'.$name);
   }
   $message='Settings saved.';
  } elseif ($action==='create_user') {
   $username=trim((string)($_POST['username']??'')); $full=trim((string)($_POST['full_name']??'')); $pass=(string)($_POST['new_password']??'');
   $role=(string)($_POST['role']??'technician'); $status=(string)($_POST['status']??'active');
   if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/',$username)) throw new RuntimeException('Username must be 3–50 letters, numbers, dots, underscores or hyphens.');
   if ($full==='') throw new RuntimeException('Enter full name.');
   if (strlen($pass)<10) throw new RuntimeException('Password must be at least 10 characters.');
   if (!in_array($role,['admin','technician','staff'],true) || !in_array($status,['active','inactive'],true)) throw new RuntimeException('Invalid role or status.');
   $s=$pdo->prepare('SELECT id FROM users WHERE username=? LIMIT 1'); $s->execute([$username]); if ($s->fetch()) throw new RuntimeException('Username already exists.');
   $s=$pdo->prepare('INSERT INTO users(username,password,full_name,role,status) VALUES(?,?,?,?,?)');
   $s->execute([$username,password_hash($pass,PASSWORD_DEFAULT),$full,$role,$status]); $message='User account created.';
  } elseif ($action==='update_user') {
   $id=filter_var($_POST['user_id']??null,FILTER_VALIDATE_INT); $role=(string)($_POST['role']??''); $status=(string)($_POST['status']??'');
   if (!$id || !in_array($role,['admin','technician','staff'],true) || !in_array($status,['active','inactive'],true)) throw new RuntimeException('Invalid user details.');
   if ((int)$id===(int)$_SESSION['user_id'] && ($role!=='admin'||$status!=='active')) throw new RuntimeException('You cannot remove admin access from or deactivate your own account.');
   $s=$pdo->prepare('UPDATE users SET role=?,status=? WHERE id=?'); $s->execute([$role,$status,$id]); $message='User access updated.';
  } elseif ($action==='reset_password') {
   $id=filter_var($_POST['user_id']??null,FILTER_VALIDATE_INT); $pass=(string)($_POST['reset_password']??'');
   if (!$id || strlen($pass)<10) throw new RuntimeException('Select a user and enter a password of at least 10 characters.');
   $s=$pdo->prepare('UPDATE users SET password=? WHERE id=?'); $s->execute([password_hash($pass,PASSWORD_DEFAULT),$id]); $message='Password updated.';
  } elseif ($action==='backup') {
   $tables=$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
   $dump="-- Calibration Management System database backup\n-- Generated ".date('Y-m-d H:i:s')."\nSET FOREIGN_KEY_CHECKS=0;\n\n";
   foreach ($tables as $table) {
    $t='`'.str_replace('`','``',$table).'`'; $create=$pdo->query("SHOW CREATE TABLE $t")->fetch(PDO::FETCH_NUM);
    if (!$create || !isset($create[1])) continue;
    $dump.="DROP TABLE IF EXISTS $t;\n".$create[1].";\n\n";
    foreach ($pdo->query("SELECT * FROM $t")->fetchAll(PDO::FETCH_ASSOC) as $row) {
     $cols=array_map(static fn($c)=>'`'.str_replace('`','``',$c).'`',array_keys($row)); $vals=[];
     foreach ($row as $v) $vals[]=$v===null?'NULL':$pdo->quote((string)$v);
     $dump.="INSERT INTO $t (".implode(', ',$cols).") VALUES (".implode(', ',$vals).");\n";
    }
    $dump.="\n";
   }
   $dump.="SET FOREIGN_KEY_CHECKS=1;\n"; header('Content-Type: application/sql; charset=utf-8');
   header('Content-Disposition: attachment; filename="calibration-backup-'.date('Y-m-d-His').'.sql"'); header('X-Content-Type-Options: nosniff'); echo $dump; exit;
  } else throw new RuntimeException('Unknown action.');
 } catch (Throwable $ex) { $error=$ex->getMessage(); }
}
$keys=['company_name'=>'','company_address'=>'','company_phone'=>'','company_email'=>'','company_logo'=>'','certificate_prefix'=>'CAL','next_certificate_number'=>'1','verification_url'=>'','verification_enabled'=>'1'];
foreach ($keys as $k=>$default) $keys[$k]=setting($pdo,$k,$default);
$users=$pdo->query('SELECT id,username,full_name,role,status,created_at FROM users ORDER BY id DESC')->fetchAll();
$adminName=$_SESSION['full_name']??$_SESSION['username']??'Administrator';
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Administrator Settings | Calibration Management System</title>
<style>
:root{--red:#d90000;--black:#111;--dark:#171717;--line:#ddd;--text:#111;--muted:#666;--page:#f3f3f3}
*{box-sizing:border-box}body{margin:0;background:var(--page);color:var(--text);font:14px/1.5 Arial,Helvetica,sans-serif}
a{color:var(--red);text-decoration:none}.app{min-height:100vh}.sidebar{position:fixed;left:0;top:0;bottom:0;width:245px;background:#101010;color:#fff;display:flex;flex-direction:column;z-index:10}
.brand{height:88px;display:flex;align-items:center;gap:12px;padding:16px 18px;border-bottom:1px solid #292929}.brand-mark{width:43px;height:43px;border:3px solid var(--red);border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:10px}.brand-name{font-size:16px;font-weight:bold;letter-spacing:3px}.brand small{display:block;font-size:7px;line-height:1.3;letter-spacing:1px;color:#aaa}
.nav-label{padding:25px 22px 12px;font-size:8px;letter-spacing:2px;color:#888}.nav{padding:0 14px}.nav a{display:flex;align-items:center;gap:12px;color:#eee;padding:14px 14px;margin:2px 0;font-size:11px;border-left:4px solid transparent}.nav a:hover,.nav a.active{background:var(--red);color:#fff;border-left-color:#fff}.nav-icon{display:inline-flex;align-items:center;justify-content:center;width:25px;height:25px;border:1px solid #555;font-size:13px}.side-bottom{margin-top:auto;padding:15px;background:#151515;font-size:10px}.side-bottom strong{display:block}.side-bottom small{color:#ff3535}
.main-area{margin-left:245px;min-height:100vh}.topbar{height:76px;background:#fff;border-bottom:3px solid var(--red);display:flex;align-items:center;justify-content:space-between;padding:12px 28px;gap:15px}.eyebrow{font-size:8px;letter-spacing:3px;color:var(--red);font-weight:bold}.topbar h1{font-size:20px;margin:0;line-height:1.3}.top-actions{display:flex;align-items:center;gap:18px;text-align:right;font-size:11px}.top-actions small{display:block;color:var(--red);font-size:9px}.logout{background:var(--red);color:#fff;padding:11px 18px;font-size:10px;font-weight:bold}
.wrap{max-width:1500px;margin:0 auto;padding:28px 30px}.page-intro{background:#fff;border:1px solid #ddd;border-left:6px solid var(--red);padding:25px 30px;margin-bottom:24px;box-shadow:0 3px 12px #00000008}.page-intro h2{font-size:25px;margin:3px 0 0}.page-intro p{margin:5px 0;color:#555}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.card{background:#fff;border:1px solid #ddd;padding:21px;min-width:0;box-shadow:0 3px 12px #00000008}.card h2{font-size:17px;margin:0 0 14px}.wide{grid-column:1/-1}.muted{color:var(--muted)}label{display:block;margin:10px 0 5px;color:#333;font-size:12px}input,select,textarea{width:100%;padding:10px 11px;border-radius:0;border:1px solid #ccc;background:#fff;color:#111;font:inherit}input:focus,select:focus,textarea:focus{outline:1px solid var(--red);border-color:var(--red)}input[type=checkbox]{width:auto;accent-color:var(--red)}input[type=file]{padding:7px}button,.button{display:inline-block;border:0;border-radius:0;background:var(--red);color:#fff;padding:10px 14px;cursor:pointer;font:inherit;font-size:11px;font-weight:bold}.secondary{background:#222}.notice{padding:12px 14px;margin:15px 0;background:#fff;border-left:5px solid #333;box-shadow:0 2px 8px #00000008}.error{border-left-color:var(--red);color:#8b0000}.formgrid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 14px}.actions{margin-top:14px}.tablewrap{overflow:auto}table{border-collapse:collapse;width:100%;min-width:720px}td,th{text-align:left;padding:10px 8px;border-bottom:1px solid #e5e5e5;vertical-align:top}th{font-size:10px;color:#555;text-transform:uppercase;background:#f6f6f6}td form{display:flex;gap:6px;align-items:center;flex-wrap:wrap}td select{width:auto;min-width:100px;padding:7px}td button{padding:7px 9px}.logo{display:block;max-width:170px;max-height:90px;object-fit:contain;background:#f5f5f5;padding:7px;margin:10px 0}
@media(max-width:850px){.sidebar{width:74px}.brand{padding:12px;justify-content:center}.brand-mark{flex-shrink:0}.brand-name,.brand small,.nav-label,.nav a span:not(.nav-icon),.side-bottom{display:none}.nav{padding:0 8px}.nav a{justify-content:center;padding:12px 5px}.main-area{margin-left:74px}.topbar{padding:12px 15px}.wrap{padding:18px 12px}.grid,.formgrid{grid-template-columns:1fr}.wide{grid-column:auto}.page-intro{padding:18px}.page-intro h2{font-size:21px}}
@media(max-width:520px){.topbar{height:auto;align-items:flex-start}.top-actions{gap:7px}.top-actions .admin-name{display:none}.logout{padding:9px}.wrap{padding:12px 8px}}
</style></head><body>
<div class="app">
<aside class="sidebar">
  <div class="brand"><div class="brand-mark">CAL</div><div><div class="brand-name">CMS</div><small>CALIBRATION MANAGEMENT<br>SYSTEM</small></div></div>
  <div class="nav-label">MAIN MENU</div>
  <nav class="nav">
    <a href="dashboard.php"><span class="nav-icon">⌂</span><span>Dashboard</span></a>
    <a href="gauge.php"><span class="nav-icon">⊙</span><span>Gauge</span></a>
    <a href="recorder.php"><span class="nav-icon">◇</span><span>Recorder</span></a>
    <a href="valve.php"><span class="nav-icon">◇</span><span>Valve Test Certificate</span></a>
    <a href="certificates.php"><span class="nav-icon">▤</span><span>Certificates</span></a>
    <a href="verify.php"><span class="nav-icon">⌕</span><span>Verify Certificate</span></a>
    <a class="active" href="settings.php"><span class="nav-icon">⚙</span><span>Settings</span></a>
  </nav>
  <div class="side-bottom"><strong><?= e($adminName) ?></strong><small>ADMIN</small></div>
</aside>
<div class="main-area">
<header class="topbar"><div><div class="eyebrow">CALIBRATION MANAGEMENT SYSTEM</div><h1>Administrator Settings</h1></div><div class="top-actions"><div class="admin-name"><strong><?= e($adminName) ?></strong><small>ADMIN</small></div><a class="logout" href="../auth/logout.php">LOGOUT</a></div></header>
<main class="wrap"><section class="page-intro"><div class="eyebrow">CALIBRATION MANAGEMENT SYSTEM</div><h2>Settings &amp; Administration</h2><p>Manage user accounts, roles and database backups.</p><p class="muted">Existing calibration records are not intentionally modified.</p></section>
<?php if($message):?><div class="notice"><?=e($message)?></div><?php endif;?><?php if($error):?><div class="notice error"><?=e($error)?></div><?php endif;?>
<div class="grid">
<section class="card"><h2>Create user account</h2><form method="post"><input type="hidden" name="csrf_token" value="<?=e($csrf)?>"><input type="hidden" name="action" value="create_user">
<label>Username</label><input name="username" minlength="3" maxlength="50" pattern="[A-Za-z0-9_.-]+" required>
<label>Full name</label><input name="full_name" required maxlength="190">
<label>Password (minimum 10 characters)</label><input type="password" name="new_password" minlength="10" required autocomplete="new-password">
<div class="formgrid"><div><label>Role</label><select name="role"><option value="technician">Technician / ordinary user</option><option value="staff">Staff</option><option value="admin">Administrator</option></select></div><div><label>Status</label><select name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div></div><div class="actions"><button>Create User</button></div></form></section>

<section class="card wide"><h2>Existing users and roles</h2><p class="muted">Do not remove admin access from your own account. Passwords are stored as hashes and never displayed.</p><div class="tablewrap"><table><thead><tr><th>ID</th><th>Username</th><th>Name</th><th>Role / status</th><th>Save access</th><th>Reset password</th></tr></thead><tbody>
<?php foreach($users as $u):?><tr><td><?= (int)$u['id']?></td><td><?=e($u['username'])?><?=(int)$u['id']===(int)$_SESSION['user_id']?' (you)':''?></td><td><?=e($u['full_name']??'')?></td><td><form method="post"><input type="hidden" name="csrf_token" value="<?=e($csrf)?>"><input type="hidden" name="action" value="update_user"><input type="hidden" name="user_id" value="<?=(int)$u['id']?>"><select name="role"><option value="admin" <?=($u['role']??'')==='admin'?'selected':''?>>Admin</option><option value="technician" <?=($u['role']??'')==='technician'?'selected':''?>>Technician</option><option value="staff" <?=($u['role']??'')==='staff'?'selected':''?>>Staff</option></select><select name="status"><option value="active" <?=($u['status']??'')==='active'?'selected':''?>>Active</option><option value="inactive" <?=($u['status']??'')==='inactive'?'selected':''?>>Inactive</option></select></td><td><button class="secondary">Save Access</button></form></td><td><form method="post" onsubmit="return confirm('Reset this user password?')"><input type="hidden" name="csrf_token" value="<?=e($csrf)?>"><input type="hidden" name="action" value="reset_password"><input type="hidden" name="user_id" value="<?=(int)$u['id']?>"><input type="password" name="reset_password" minlength="10" placeholder="New password" required autocomplete="new-password" style="width:145px"><button class="secondary">Reset</button></form></td></tr><?php endforeach;?>
<?php if(!$users):?><tr><td colspan="6">No users found.</td></tr><?php endif;?></tbody></table></div></section>

<section class="card"><h2>Database backup</h2><p>Download an SQL export of all tables and rows in the connected database. Keep the backup private because it may include user password hashes and calibration/customer records.</p><form method="post" onsubmit="return confirm('Download a backup of all database tables and records?')"><input type="hidden" name="csrf_token" value="<?=e($csrf)?>"><input type="hidden" name="action" value="backup"><button>Download SQL Backup</button></form></section>
<section class="card"><h2>Administrator-only access</h2><p>This page checks the server-side session role. Users whose role is not exactly <code>admin</code> receive a 403 response.</p><p class="muted">Hiding a dashboard link alone is not sufficient; protect other admin-only pages at the top of those PHP files too.</p><a class="button secondary" href="dashboard.php">Return to Dashboard</a></section>
</div></main></div></div></body></html>
