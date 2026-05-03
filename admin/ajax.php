<?php
ob_start();
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
include 'admin_class.php';
$crud = new Action();

// ── Legacy actions (preserve numeric return codes) ──────────────────────────
if ($action === 'login') {
    $r = $crud->login(); echo $r; exit;
}
if ($action === 'logout') {
    $crud->logout(); exit;
}
if ($action === 'save_user') {
    echo $crud->save_user(); exit;
}
if ($action === 'save_settings') {
    echo $crud->save_settings(); exit;
}
if ($action === 'save_category') {
    echo $crud->save_category(); exit;
}
if ($action === 'delete_category') {
    echo $crud->delete_category(); exit;
}
if ($action === 'save_room') {
    echo $crud->save_room(); exit;
}
if ($action === 'delete_room') {
    echo $crud->delete_room(); exit;
}

// ── Enhanced check-in/out (return JSON) ─────────────────────────────────────
if ($action === 'save_check-in') {
    echo $crud->save_check_in(); exit;
}
if ($action === 'save_checkout') {
    echo $crud->save_checkout(); exit;
}
if ($action === 'save_book') {
    echo $crud->save_book(); exit;
}

// ── Guests ───────────────────────────────────────────────────────────────────
if ($action === 'save_guest') {
    echo $crud->save_guest(); exit;
}
if ($action === 'delete_guest') {
    echo $crud->delete_guest(); exit;
}
if ($action === 'search_guests') {
    echo $crud->search_guests(); exit;
}

// ── Guest requests ───────────────────────────────────────────────────────────
if ($action === 'save_guest_request') {
    echo $crud->save_guest_request(); exit;
}

// ── Housekeeping ─────────────────────────────────────────────────────────────
if ($action === 'save_housekeeping_task') {
    echo $crud->save_housekeeping_task(); exit;
}
if ($action === 'update_task_status') {
    echo $crud->update_task_status(); exit;
}
if ($action === 'update_room_hk_status') {
    echo $crud->update_room_hk_status(); exit;
}

// ── Billing ──────────────────────────────────────────────────────────────────
if ($action === 'add_invoice_item') {
    echo $crud->add_invoice_item(); exit;
}
if ($action === 'delete_invoice_item') {
    echo $crud->delete_invoice_item(); exit;
}
if ($action === 'update_invoice') {
    echo $crud->update_invoice(); exit;
}
if ($action === 'save_payment') {
    echo $crud->save_payment(); exit;
}

// ── Notifications ────────────────────────────────────────────────────────────
if ($action === 'get_notifications') {
    echo $crud->get_notifications(); exit;
}
if ($action === 'mark_notification_read') {
    echo $crud->mark_notification_read(); exit;
}

// ── Dashboard & Reports ──────────────────────────────────────────────────────
if ($action === 'get_dashboard_stats') {
    echo $crud->get_dashboard_stats(); exit;
}
if ($action === 'get_reports') {
    echo $crud->get_reports(); exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
