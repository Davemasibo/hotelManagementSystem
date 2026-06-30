<?php
/*
 * JSON API endpoint.
 *
 * This is a machine-consumed endpoint: the response body must be PURE JSON.
 * If a PHP warning/notice/deprecation is ever emitted while a handler runs,
 * display_errors would inject that text into the body ahead of the JSON,
 * breaking JSON.parse() on the client. The client's fallback then reports a
 * false "failed" even though the database write already committed (this is
 * exactly what produced the spurious "Check-in failed" message).
 *
 * So: never DISPLAY errors here (still LOG them), and discard any stray output
 * captured in the buffers before emitting the JSON.
 */
ini_set('display_errors', '0');
ini_set('log_errors', '1');

ob_start();
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
include 'admin_class.php';
$crud = new Action();

// logout performs a redirect and emits no body — handle it directly.
if ($action === 'logout') {
    $crud->logout();
    exit;
}

// action => Action method. Everything here returns a string to send verbatim.
$routes = [
    // Auth / legacy (numeric return codes preserved)
    'login'                 => 'login',
    'save_user'             => 'save_user',
    'approve_user'          => 'approve_user',
    'reject_user'           => 'reject_user',
    'save_settings'         => 'save_settings',
    'save_category'         => 'save_category',
    'delete_category'       => 'delete_category',
    'save_room'             => 'save_room',
    'delete_room'           => 'delete_room',
    // Check-in / out / booking
    'save_check-in'         => 'save_check_in',
    'save_checkout'         => 'save_checkout',
    'save_book'             => 'save_book',
    // Guests
    'save_guest'            => 'save_guest',
    'delete_guest'          => 'delete_guest',
    'search_guests'         => 'search_guests',
    // Guest requests
    'save_guest_request'    => 'save_guest_request',
    // Housekeeping
    'save_housekeeping_task' => 'save_housekeeping_task',
    'update_task_status'    => 'update_task_status',
    'update_room_hk_status' => 'update_room_hk_status',
    // Billing
    'add_invoice_item'      => 'add_invoice_item',
    'delete_invoice_item'   => 'delete_invoice_item',
    'update_invoice'        => 'update_invoice',
    'save_payment'          => 'save_payment',
    // Notifications
    'get_notifications'     => 'get_notifications',
    'mark_notification_read' => 'mark_notification_read',
    // Dashboard & Reports
    'get_dashboard_stats'   => 'get_dashboard_stats',
    'get_reports'           => 'get_reports',
];

if (isset($routes[$action])) {
    $out = $crud->{$routes[$action]}();
} else {
    $out = json_encode(['status' => 'error', 'message' => 'Unknown action']);
}

// Discard any stray output (warnings, whitespace, BOM) captured in the output
// buffers so the client receives the handler's JSON and nothing else.
while (ob_get_level() > 0) {
    ob_end_clean();
}
echo $out;
exit;
