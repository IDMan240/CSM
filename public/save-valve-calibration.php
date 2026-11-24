<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: valve.php'); exit; }
require_once __DIR__ . '/../config/database.php';

function valvePost(string $name, string $default = ''): string {
    return isset($_POST[$name]) && !is_array($_POST[$name]) ? trim((string)$_POST[$name]) : $default;
}
function valveEnsureColumns(PDO $pdo, string $table, array $columns): void {
    foreach ($columns as $column => $definition) {
        $check = $pdo->query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $pdo->quote($column));
        if (!$check->fetch(PDO::FETCH_ASSOC)) {
            $pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
        }
    }
}
function valveUpload(string $field, string $prefix): ?string {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] <= 0 || $f['size'] > 2 * 1024 * 1024) throw new RuntimeException('Invalid upload for ' . $prefix . '. Use an image no larger than 2 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $allowed = ['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];
    if (!isset($allowed[$mime]) || @getimagesize($f['tmp_name']) === false) throw new RuntimeException($prefix . ' must be a valid PNG, JPG or WebP image.');
    $folder = $prefix === 'stamp' ? 'stamps' : 'signatures';
    $dir = __DIR__ . '/uploads/' . $folder;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException('Unable to create the ' . $folder . ' upload directory. Check folder permissions.');
    $name = $prefix . '_' . bin2hex(random_bytes(12)) . '.' . $allowed[$mime];
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Unable to save the ' . $prefix . ' image. Check folder permissions.');
    return 'uploads/' . $folder . '/' . $name;
}

$uploadedPaths = [];
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS valve_certificates (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, certificate_no VARCHAR(100) NOT NULL UNIQUE,
        client VARCHAR(255) NOT NULL, nuprc VARCHAR(255) NULL, test_location VARCHAR(255) NULL,
        tested_item VARCHAR(255) NULL, valve_class VARCHAR(255) NULL, serial_no VARCHAR(255) NULL,
        matp VARCHAR(255) NULL, test_date DATE NULL, body_test_date DATE NULL, seat_test_date DATE NULL,
        body_total_time VARCHAR(100) NULL, seat_total_time VARCHAR(100) NULL, body_medium VARCHAR(100) NULL,
        seat_medium VARCHAR(100) NULL, body_final_pressure VARCHAR(100) NULL, seat_final_pressure VARCHAR(100) NULL,
        tested_by VARCHAR(255) NULL, tested_name VARCHAR(255) NULL, witnessed_by VARCHAR(255) NULL,
        tested_signature VARCHAR(255) NULL, witnessed_signature VARCHAR(255) NULL, stamp_image VARCHAR(255) NULL,
        created_by INT UNSIGNED NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    valveEnsureColumns($pdo, 'valve_certificates', [
        'nuprc'=>'VARCHAR(255) NULL','test_location'=>'VARCHAR(255) NULL','tested_item'=>'VARCHAR(255) NULL',
        'valve_class'=>'VARCHAR(255) NULL','serial_no'=>'VARCHAR(255) NULL','matp'=>'VARCHAR(255) NULL',
        'test_date'=>'DATE NULL','body_test_date'=>'DATE NULL','seat_test_date'=>'DATE NULL',
        'body_total_time'=>'VARCHAR(100) NULL','seat_total_time'=>'VARCHAR(100) NULL','body_medium'=>'VARCHAR(100) NULL',
        'seat_medium'=>'VARCHAR(100) NULL','body_final_pressure'=>'VARCHAR(100) NULL','seat_final_pressure'=>'VARCHAR(100) NULL',
        'tested_by'=>'VARCHAR(255) NULL','tested_name'=>'VARCHAR(255) NULL','witnessed_by'=>'VARCHAR(255) NULL',
        'tested_signature'=>'VARCHAR(255) NULL','witnessed_signature'=>'VARCHAR(255) NULL','stamp_image'=>'VARCHAR(255) NULL',
        'created_by'=>'INT UNSIGNED NULL','created_at'=>'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP'
    ]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS valve_pressure_log (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, valve_id INT UNSIGNED NOT NULL,
        description VARCHAR(255) NOT NULL, cavity_body_test VARCHAR(255) NULL, seat_test VARCHAR(255) NULL,
        remarks VARCHAR(255) NULL, sort_order INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(valve_id), CONSTRAINT fk_valve_pressure_log_valve FOREIGN KEY(valve_id) REFERENCES valve_certificates(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    valveEnsureColumns($pdo, 'valve_pressure_log', [
        'valve_id'=>'INT UNSIGNED NOT NULL DEFAULT 0','description'=>'VARCHAR(255) NOT NULL DEFAULT ""',
        'cavity_body_test'=>'VARCHAR(255) NULL','seat_test'=>'VARCHAR(255) NULL','remarks'=>'VARCHAR(255) NULL',
        'sort_order'=>'INT NOT NULL DEFAULT 0','created_at'=>'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP'
    ]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS valve_physical_checks (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, valve_id INT UNSIGNED NOT NULL,
        check_left VARCHAR(255) NULL, result_left VARCHAR(100) NULL, check_right VARCHAR(255) NULL,
        result_right VARCHAR(100) NULL, sort_order INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(valve_id), CONSTRAINT fk_valve_physical_valve FOREIGN KEY(valve_id) REFERENCES valve_certificates(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    valveEnsureColumns($pdo, 'valve_physical_checks', [
        'valve_id'=>'INT UNSIGNED NOT NULL DEFAULT 0','check_left'=>'VARCHAR(255) NULL','result_left'=>'VARCHAR(100) NULL',
        'check_right'=>'VARCHAR(255) NULL','result_right'=>'VARCHAR(100) NULL','sort_order'=>'INT NOT NULL DEFAULT 0',
        'created_at'=>'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP'
    ]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS nuprc_settings (
        id INT UNSIGNED PRIMARY KEY, nuprc VARCHAR(255) NOT NULL DEFAULT '', updated_by INT UNSIGNED NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("INSERT INTO nuprc_settings (id,nuprc) VALUES (1,'') ON DUPLICATE KEY UPDATE id=id");
    $nuprc = (string)($pdo->query('SELECT nuprc FROM nuprc_settings WHERE id=1')->fetchColumn() ?: '');

    $no = valvePost('certificate_no');
    $client = valvePost('client');
    if ($no === '') throw new RuntimeException('Certificate number is required.');
    if ($client === '') throw new RuntimeException('Client is required.');

    // This project keeps certificate numbers unique across Gauge, Recorder and Valve.
    foreach ([['valve_certificates','Valve'], ['gauge_calibrations','Gauge'], ['recorder_calibrations','Recorder']] as [$table,$label]) {
        try {
            $exists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchColumn();
            if ($exists) {
                $q = $pdo->prepare("SELECT id FROM `{$table}` WHERE certificate_no=:n LIMIT 1");
                $q->execute([':n'=>$no]);
                if ($q->fetchColumn() !== false) throw new RuntimeException('CERTIFICATE NUMBER ALREADY USED by ' . $label . ': ' . $no);
            }
        } catch (RuntimeException $e) { throw $e; }
    }

    $testedSig = valveUpload('tested_signature','tested'); if ($testedSig) $uploadedPaths[] = $testedSig;
    $witnessedSig = valveUpload('witnessed_signature','witnessed'); if ($witnessedSig) $uploadedPaths[] = $witnessedSig;
    $stamp = valveUpload('stamp_image','stamp'); if ($stamp) $uploadedPaths[] = $stamp;

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO valve_certificates
        (certificate_no,client,nuprc,test_location,tested_item,valve_class,serial_no,matp,test_date,body_test_date,seat_test_date,
         body_total_time,seat_total_time,body_medium,seat_medium,body_final_pressure,seat_final_pressure,tested_by,tested_name,
         witnessed_by,tested_signature,witnessed_signature,stamp_image,created_by)
        VALUES (:no,:client,:nuprc,:loc,:item,:class,:serial,:matp,:td,:bd,:sd,:btime,:stime,:bm,:sm,:bfp,:sfp,:tb,:tn,:wb,:ts,:ws,:stamp,:uid)");
    $stmt->execute([
        ':no'=>$no, ':client'=>$client, ':nuprc'=>$nuprc, ':loc'=>valvePost('test_location'), ':item'=>valvePost('tested_item'),
        ':class'=>valvePost('valve_class'), ':serial'=>valvePost('serial_no'), ':matp'=>valvePost('matp'),
        ':td'=>valvePost('test_date') ?: null, ':bd'=>valvePost('body_test_date') ?: null, ':sd'=>valvePost('seat_test_date') ?: null,
        ':btime'=>valvePost('body_total_time'), ':stime'=>valvePost('seat_total_time'), ':bm'=>valvePost('body_medium'),
        ':sm'=>valvePost('seat_medium'), ':bfp'=>valvePost('body_final_pressure'), ':sfp'=>valvePost('seat_final_pressure'),
        ':tb'=>valvePost('tested_by'), ':tn'=>valvePost('tested_name'), ':wb'=>valvePost('witnessed_by'),
        ':ts'=>$testedSig, ':ws'=>$witnessedSig, ':stamp'=>$stamp, ':uid'=>(int)$_SESSION['user_id']
    ]);
    $id = (int)$pdo->lastInsertId();
    if ($id <= 0) throw new RuntimeException('The valve record was inserted but its ID could not be determined.');

    $descs = isset($_POST['description']) && is_array($_POST['description']) ? $_POST['description'] : [];
    $bodies = isset($_POST['body_log']) && is_array($_POST['body_log']) ? $_POST['body_log'] : [];
    $seats = isset($_POST['seat_log']) && is_array($_POST['seat_log']) ? $_POST['seat_log'] : [];
    $remarks = isset($_POST['remarks']) && is_array($_POST['remarks']) ? $_POST['remarks'] : [];
    $ins = $pdo->prepare('INSERT INTO valve_pressure_log (valve_id,description,cavity_body_test,seat_test,remarks,sort_order) VALUES (:vid,:d,:b,:s,:r,:o)');
    foreach ($descs as $i=>$d) {
        if (is_array($d)) continue; $d = trim((string)$d); if ($d === '') continue;
        $ins->execute([':vid'=>$id, ':d'=>$d, ':b'=>trim((string)($bodies[$i] ?? '')), ':s'=>trim((string)($seats[$i] ?? '')), ':r'=>trim((string)($remarks[$i] ?? '')), ':o'=>(int)$i]);
    }
    $cl = isset($_POST['check_left']) && is_array($_POST['check_left']) ? $_POST['check_left'] : [];
    $rl = isset($_POST['result_left']) && is_array($_POST['result_left']) ? $_POST['result_left'] : [];
    $cr = isset($_POST['check_right']) && is_array($_POST['check_right']) ? $_POST['check_right'] : [];
    $rr = isset($_POST['result_right']) && is_array($_POST['result_right']) ? $_POST['result_right'] : [];
    $pin = $pdo->prepare('INSERT INTO valve_physical_checks (valve_id,check_left,result_left,check_right,result_right,sort_order) VALUES (:v,:cl,:rl,:cr,:rr,:o)');
    foreach ($cl as $i=>$v) {
        if (is_array($v)) continue;
        $pin->execute([':v'=>$id, ':cl'=>trim((string)$v), ':rl'=>trim((string)($rl[$i] ?? '')), ':cr'=>trim((string)($cr[$i] ?? '')), ':rr'=>trim((string)($rr[$i] ?? '')), ':o'=>(int)$i]);
    }
    $pdo->commit();
    header('Location: valve-certificate.php?id=' . rawurlencode((string)$id));
    exit;
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    foreach ($uploadedPaths as $path) { $abs = __DIR__ . '/' . $path; if (is_file($abs)) @unlink($abs); }
    http_response_code(500);
    $msg = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    echo "<!doctype html><html><head><meta charset='utf-8'><meta name='viewport' content='width=device-width,initial-scale=1'><title>CMS | Valve Save Error</title><style>body{font-family:Arial;background:#f4f5f7;margin:0;min-height:100vh;display:grid;place-items:center;padding:18px}.box{background:#fff;padding:30px;max-width:650px;width:100%;border-top:5px solid #c90000;box-shadow:0 12px 40px #0001}h1{color:#b40000;font-size:23px}p{line-height:1.6;overflow-wrap:anywhere}a{display:inline-block;margin-top:18px;background:#111;color:#fff;padding:12px 18px;text-decoration:none}</style></head><body><div class='box'><h1>VALVE CERTIFICATE NOT SAVED</h1><p>{$msg}</p><a href='valve.php'>BACK TO VALVE CERTIFICATE</a></div></body></html>";
}
