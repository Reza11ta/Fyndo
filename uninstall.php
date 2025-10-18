<?php

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;
$like = $wpdb->esc_like('fyndo_search_') . '%';
$transients = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", "_transient_{$like}"));
if (! empty($transients)) {
    foreach ($transients as $tr) {
        $key = str_replace('_transient_', '', $tr);
        delete_transient($key);
    }
}
