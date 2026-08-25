<?php
/**
 * Shared bits for the booking / check-in forms.
 *
 * Hotel check-in and check-out times come from system_settings when that row
 * (and the migration that added the columns) exists; otherwise the industry
 * defaults 14:00 / 11:00 are used so the forms never break on an older database.
 */
function reservation_time_defaults($conn) {
    $defaults = ['in' => '14:00', 'out' => '11:00'];
    try {
        $res = $conn->query("SELECT checkin_time, checkout_time FROM system_settings LIMIT 1");
        if ($res && ($row = $res->fetch_assoc())) {
            if (!empty($row['checkin_time']))  $defaults['in']  = substr($row['checkin_time'], 0, 5);
            if (!empty($row['checkout_time'])) $defaults['out'] = substr($row['checkout_time'], 0, 5);
        }
    } catch (Throwable $e) {
        // system_settings not migrated yet — fall through to the defaults.
    }
    return $defaults;
}
