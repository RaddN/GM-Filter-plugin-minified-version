<?php

if (!defined('ABSPATH')) {
    exit;
}

function dapfforwc_register_template()
{
    global $wp;
    $request = $wp->request;

    if (strpos($request, 'filters') === 0) {
        // Handle requests starting with "filters"
        $dapfforwc_slug = sanitize_text_field(substr($request, strlen("filters") + 1));
        wp_redirect(home_url("/?filters=$dapfforwc_slug"), 301);
        exit;
    } elseif (strpos($request, 'filters/') !== false) {
        // Handle requests containing "filters"
        $dapfforwc_root_slug = sanitize_text_field(substr($request, 0, strpos($request, 'filters') - 1));
        $dapfforwc_slug = sanitize_text_field(substr($request, strpos($request, 'filters') + strlen("filters") + 1));
        wp_redirect(home_url("/$dapfforwc_root_slug?filters=$dapfforwc_slug"), 301);
        exit;
    }
}


add_action('template_redirect', 'dapfforwc_register_template');
