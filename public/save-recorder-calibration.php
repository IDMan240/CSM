<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['status'=>'error','message'=>'Your session has expired. Log in again and retry.']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['status'=>'error','message'=>'The save request arrived as GET, so no certificate was saved. The Recorder page or hosting redirect is still using an old/non-POST request. Open /public/recorder.php, refresh with Ctrl+F5, and use SAVE RECORDER CERTIFICATE.']); exit; }
if (($_POST['_cms_save_ajax'] ?? '') !== '1') { http_response_code(400); echo json_encode(['status'=>'error','message'=>'The Recorder save script did not submit the calibration readings. Refresh the Recorder page (Ctrl+F5) and press Save Recorder Certificate again. No record was saved.']); exit; }
require_once __DIR__ . '/../config/database.php';

function recorderPost(string $name): string { return isset($_POST[$name]) && !is_array($_POST[$name]) ? trim((string)$_POST[$name]) : ''; }
function recorderNumber($value): float { return is_numeric($value) ? (float)$value : 0.0; }
function recorderEnsureColumns(PDO $pdo, string $table, array $columns): void {
    foreach ($columns as $column => $definition) {
        $check = $pdo->query('SHOW COLUMNS FROM `' . $table . '` LIKE ' . $pdo->quote($column));
        if (!$check->fetch(PDO::FETCH_ASSOC)) $pdo->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
    }
}
function recorderEnsureTables(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS recorder_calibrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, certificate_no VARCHAR(100) NOT NULL UNIQUE,
        client VARCHAR(255) NOT NULL, nuprc VARCHAR(255) NULL, test_item VARCHAR(255) NULL,
        manufacturer VARCHAR(255) NULL, serial_no VARCHAR(255) NULL,
        pressure_range DECIMAL(15,4) NOT NULL DEFAULT 0, pressure_unit VARCHAR(20) NOT NULL DEFAULT 'BAR',
        temperature_range DECIMAL(15,4) NOT NULL DEFAULT 0, temperature_unit VARCHAR(20) NOT NULL DEFAULT 'F',
        calibration_date DATE NULL, due_date DATE NULL,
        pressure_equipment_name VARCHAR(255) NULL, pressure_equipment_range VARCHAR(255) NULL,
        pressure_equipment_calibration_date DATE NULL, pressure_equipment_due_date DATE NULL,
        pressure_equipment_certificate_no VARCHAR(255) NULL, pressure_equipment_serial VARCHAR(255) NULL,
        temperature_equipment_name VARCHAR(255) NULL, temperature_equipment_range VARCHAR(255) NULL,
        temperature_equipment_calibration_date DATE NULL, temperature_equipment_due_date DATE NULL,
        temperature_equipment_certificate_no VARCHAR(255) NULL, temperature_equipment_serial VARCHAR(255) NULL,
        tested_by VARCHAR(255) NULL, witnessed_by VARCHAR(255) NULL, tested_date DATE NULL, witnessed_date DATE NULL,
        tested_signature VARCHAR(255) NULL, witnessed_signature VARCHAR(255) NULL, stamp_image VARCHAR(255) NULL,
        created_by INT UNSIGNED NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    recorderEnsureColumns($pdo, 'recorder_calibrations', [
        'nuprc'=>'VARCHAR(255) NULL','test_item'=>'VARCHAR(255) NULL','manufacturer'=>'VARCHAR(255) NULL','serial_no'=>'VARCHAR(255) NULL',
        'pressure_range'=>'DECIMAL(15,4) NOT NULL DEFAULT 0','pressure_unit'=>"VARCHAR(20) NOT NULL DEFAULT 'BAR'",
        'temperature_range'=>'DECIMAL(15,4) NOT NULL DEFAULT 0','temperature_unit'=>"VARCHAR(20) NOT NULL DEFAULT 'F'",
        'calibration_date'=>'DATE NULL','due_date'=>'DATE NULL','pressure_equipment_name'=>'VARCHAR(255) NULL',
        'pressure_equipment_range'=>'VARCHAR(255) NULL','pressure_equipment_calibration_date'=>'DATE NULL','pressure_equipment_due_date'=>'DATE NULL',
        'pressure_equipment_certificate_no'=>'VARCHAR(255) NULL','pressure_equipment_serial'=>'VARCHAR(255) NULL',
        'temperature_equipment_name'=>'VARCHAR(255) NULL','temperature_equipment_range'=>'VARCHAR(255) NULL',
        'temperature_equipment_calibration_date'=>'DATE NULL','temperature_equipment_due_date'=>'DATE NULL',
        'temperature_equipment_certificate_no'=>'VARCHAR(255) NULL','temperature_equipment_serial'=>'VARCHAR(255) NULL',
        'tested_by'=>'VARCHAR(255) NULL','witnessed_by'=>'VARCHAR(255) NULL','tested_date'=>'DATE NULL','witnessed_date'=>'DATE NULL',
        'tested_signature'=>'VARCHAR(255) NULL','witnessed_signature'=>'VARCHAR(255) NULL','stamp_image'=>'VARCHAR(255) NULL',
        'created_by'=>'INT UNSIGNED NULL','created_at'=>'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP'
    ]);

    $pdo->exec("CREATE TABLE IF NOT EXISTS recorder_calibration_readings (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, calibration_id INT UNSIGNED NOT NULL,
        reading_type ENUM('rising','falling') NOT NULL, percentage_range DECIMAL(7,2) NOT NULL,
        actual_value DECIMAL(15,4) NOT NULL DEFAULT 0, dead_weight_reading DECIMAL(15,4) NOT NULL DEFAULT 0,
        recorder_reading DECIMAL(15,4) NOT NULL DEFAULT 0, temperature_cal DECIMAL(15,4) NOT NULL DEFAULT 0,
        temperature_recorder DECIMAL(15,4) NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX(calibration_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    recorderEnsureColumns($pdo, 'recorder_calibration_readings', [
        'calibration_id'=>'INT UNSIGNED NOT NULL DEFAULT 0',"reading_type"=>"ENUM('rising','falling') NOT NULL DEFAULT 'rising'",
        'percentage_range'=>'DECIMAL(7,2) NOT NULL DEFAULT 0','actual_value'=>'DECIMAL(15,4) NOT NULL DEFAULT 0',
        'dead_weight_reading'=>'DECIMAL(15,4) NOT NULL DEFAULT 0','recorder_reading'=>'DECIMAL(15,4) NOT NULL DEFAULT 0',
        'temperature_cal'=>'DECIMAL(15,4) NOT NULL DEFAULT 0','temperature_recorder'=>'DECIMAL(15,4) NOT NULL DEFAULT 0',
        'created_at'=>'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP'
    ]);
}
function recorderEnsureEquipmentSettings(PDO $pdo): array {
    $pdo->exec("CREATE TABLE IF NOT EXISTS recorder_equipment_settings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, equipment_type ENUM('pressure','temperature') NOT NULL UNIQUE,
        name VARCHAR(255) NOT NULL DEFAULT '', equipment_range VARCHAR(255) NOT NULL DEFAULT '',
        calibration_date DATE NULL, due_date DATE NULL, certificate_no VARCHAR(255) NOT NULL DEFAULT '',
        serial_no VARCHAR(255) NOT NULL DEFAULT '', updated_by INT UNSIGNED NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    foreach (['pressure'=>['DEADWEIGHT TESTER','20-10000 PSI'],'temperature'=>['TEMPERATURE CALIBRATOR','0-300C']] as $type=>$defaults) {
        $st=$pdo->prepare('INSERT IGNORE INTO recorder_equipment_settings (equipment_type,name,equipment_range) VALUES (?,?,?)');
        $st->execute([$type,$defaults[0],$defaults[1]]);
    }
    $out=['pressure'=>[],'temperature'=>[]];
    foreach ($pdo->query('SELECT * FROM recorder_equipment_settings') as $row) $out[$row['equipment_type']]=$row;
    return $out;
}
function recorderUpload(string $field, string $prefix): ?string {
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    $f=$_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK || $f['size'] <= 0 || $f['size'] > 2*1024*1024) throw new RuntimeException($prefix . ' upload failed. Use an image no larger than 2 MB.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $allow=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];
    if (!isset($allow[$mime]) || @getimagesize($f['tmp_name']) === false) throw new RuntimeException($prefix . ' must be a valid PNG, JPG or WebP image.');
    $folder=$prefix === 'stamp' ? 'stamps' : 'signatures'; $dir=__DIR__.'/uploads/'.$folder;
    if (!is_dir($dir) && !mkdir($dir,0755,true)) throw new RuntimeException('Unable to create upload directory: '.$folder.'. Check folder permissions.');
    $name=$prefix.'_'.bin2hex(random_bytes(12)).'.'.$allow[$mime];
    if (!move_uploaded_file($f['tmp_name'],$dir.'/'.$name)) throw new RuntimeException('Unable to store upload: '.$prefix.'. Check folder permissions.');
    return 'uploads/'.$folder.'/'.$name;
}
function recorderTableExists(PDO $pdo, string $table): bool { return (bool)$pdo->query('SHOW TABLES LIKE '.$pdo->quote($table))->fetchColumn(); }

$uploadedPaths=[];
$certificateNo=recorderPost('certificate_no');
try {
    if ($certificateNo === '') throw new RuntimeException('Certificate number is required.');
    if (recorderPost('client') === '') throw new RuntimeException('Client is required.');
    recorderEnsureTables($pdo);
    $equipment=recorderEnsureEquipmentSettings($pdo);
    $pdo->exec("CREATE TABLE IF NOT EXISTS nuprc_settings (id INT UNSIGNED PRIMARY KEY, nuprc VARCHAR(255) NOT NULL DEFAULT '', updated_by INT UNSIGNED NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("INSERT INTO nuprc_settings (id,nuprc) VALUES (1,'') ON DUPLICATE KEY UPDATE id=id");
    $adminNuprc=(string)($pdo->query('SELECT nuprc FROM nuprc_settings WHERE id=1')->fetchColumn() ?: '');

    foreach ([['gauge_calibrations','Gauge'],['recorder_calibrations','Recorder'],['valve_certificates','Valve']] as [$table,$label]) {
        if (!recorderTableExists($pdo,$table)) continue;
        $q=$pdo->prepare("SELECT id FROM `{$table}` WHERE certificate_no=:c LIMIT 1");
        $q->execute([':c'=>$certificateNo]);
        if ($q->fetchColumn() !== false) { echo json_encode(['status'=>'duplicate','certificate_no'=>$certificateNo,'message'=>'Certificate number already used by '.$label.'.']); exit; }
    }

    $pressureRange=recorderPost('pressure_range'); $temperatureRange=recorderPost('temperature_range');
    if (!is_numeric($pressureRange) || recorderNumber($pressureRange)<=0) throw new RuntimeException('Pressure range must be a number greater than zero.');
    if (!is_numeric($temperatureRange) || recorderNumber($temperatureRange)<=0) throw new RuntimeException('Temperature range must be a number greater than zero.');
    $testedSig=recorderUpload('tested_signature','signature'); if ($testedSig) $uploadedPaths[]=$testedSig;
    $witnessSig=recorderUpload('witnessed_signature','signature'); if ($witnessSig) $uploadedPaths[]=$witnessSig;
    $stamp=recorderUpload('stamp_image','stamp'); if ($stamp) $uploadedPaths[]=$stamp;

    $pdo->beginTransaction();
    $st=$pdo->prepare("INSERT INTO recorder_calibrations
        (certificate_no,client,nuprc,test_item,manufacturer,serial_no,pressure_range,pressure_unit,temperature_range,temperature_unit,
         calibration_date,due_date,pressure_equipment_name,pressure_equipment_range,pressure_equipment_calibration_date,
         pressure_equipment_due_date,pressure_equipment_certificate_no,pressure_equipment_serial,temperature_equipment_name,
         temperature_equipment_range,temperature_equipment_calibration_date,temperature_equipment_due_date,
         temperature_equipment_certificate_no,temperature_equipment_serial,tested_by,witnessed_by,tested_date,witnessed_date,
         tested_signature,witnessed_signature,stamp_image,created_by)
        VALUES (:certificate_no,:client,:nuprc,:test_item,:manufacturer,:serial_no,:pressure_range,:pressure_unit,:temperature_range,:temperature_unit,
         :calibration_date,:due_date,:pen,:per,:pcd,:pdd,:pcn,:ps,:ten,:ter,:tcd,:tdd,:tcn,:ts,:tested_by,:witnessed_by,
         :tested_date,:witnessed_date,:tested_signature,:witnessed_signature,:stamp_image,:created_by)");
    $st->execute([
        ':certificate_no'=>$certificateNo, ':client'=>recorderPost('client'), ':nuprc'=>$adminNuprc,
        ':test_item'=>recorderPost('test_item'), ':manufacturer'=>recorderPost('manufacturer'), ':serial_no'=>recorderPost('serial_no'),
        ':pressure_range'=>recorderNumber($pressureRange), ':pressure_unit'=>recorderPost('pressure_unit') ?: 'BAR',
        ':temperature_range'=>recorderNumber($temperatureRange), ':temperature_unit'=>recorderPost('temperature_unit') ?: 'F',
        ':calibration_date'=>recorderPost('calibration_date') ?: null, ':due_date'=>recorderPost('due_date') ?: null,
        ':pen'=>$equipment['pressure']['name'] ?? '', ':per'=>$equipment['pressure']['equipment_range'] ?? '',
        ':pcd'=>$equipment['pressure']['calibration_date'] ?? null, ':pdd'=>$equipment['pressure']['due_date'] ?? null,
        ':pcn'=>$equipment['pressure']['certificate_no'] ?? '', ':ps'=>$equipment['pressure']['serial_no'] ?? '',
        ':ten'=>$equipment['temperature']['name'] ?? '', ':ter'=>$equipment['temperature']['equipment_range'] ?? '',
        ':tcd'=>$equipment['temperature']['calibration_date'] ?? null, ':tdd'=>$equipment['temperature']['due_date'] ?? null,
        ':tcn'=>$equipment['temperature']['certificate_no'] ?? '', ':ts'=>$equipment['temperature']['serial_no'] ?? '',
        ':tested_by'=>recorderPost('tested_by'), ':witnessed_by'=>recorderPost('witnessed_by'),
        ':tested_date'=>recorderPost('tested_date') ?: null, ':witnessed_date'=>recorderPost('witnessed_date') ?: null,
        ':tested_signature'=>$testedSig, ':witnessed_signature'=>$witnessSig, ':stamp_image'=>$stamp, ':created_by'=>(int)$_SESSION['user_id']
    ]);
    $id=(int)$pdo->lastInsertId();
    if ($id<=0) throw new RuntimeException('The Recorder record was inserted but its ID could not be determined.');
    $readings=$pdo->prepare('INSERT INTO recorder_calibration_readings (calibration_id,reading_type,percentage_range,actual_value,dead_weight_reading,recorder_reading,temperature_cal,temperature_recorder) VALUES (:id,:type,:p,:a,:d,:r,:tc,:tr)');
    foreach (['rising'=>($_POST['rising']??[]),'falling'=>($_POST['falling']??[])] as $type=>$rows) {
        if (!is_array($rows)) continue;
        foreach ($rows as $row) {
            if (!is_array($row)) continue;
            $readings->execute([':id'=>$id,':type'=>$type,':p'=>recorderNumber($row['percentage']??0),':a'=>recorderNumber($row['actual']??0),
                ':d'=>recorderNumber($row['dead_weight']??0),':r'=>recorderNumber($row['recorder']??0),
                ':tc'=>recorderNumber($row['temperature_cal']??0),':tr'=>recorderNumber($row['temperature_recorder']??0)]);
        }
    }
    $pdo->commit();
    echo json_encode(['status'=>'success','certificate_id'=>$id,'certificate_no'=>$certificateNo,'url'=>'recorder-certificate.php?certificate_no='.rawurlencode($certificateNo)]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    foreach ($uploadedPaths as $path) { $abs=__DIR__.'/'.$path; if (is_file($abs)) @unlink($abs); }
    if ($e instanceof PDOException && $e->getCode()==='23000') {
        http_response_code(409); echo json_encode(['status'=>'duplicate','certificate_no'=>$certificateNo,'message'=>'This certificate number is already registered.']);
    } else {
        http_response_code(500); echo json_encode(['status'=>'error','message'=>$e->getMessage()]);
    }
}
