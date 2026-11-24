<?php
/*
 * Same-origin Recorder POST relay.
 * Using a separate endpoint avoids any host rule tied specifically to
 * save-recorder-calibration.php. It delegates to the existing save logic.
 */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'error',
        'message' => 'Recorder save endpoint received GET. Open Recorder Calibration and submit with the Save Recorder Certificate button.'
    ]);
    exit;
}
require __DIR__ . '/save-recorder-calibration.php';
