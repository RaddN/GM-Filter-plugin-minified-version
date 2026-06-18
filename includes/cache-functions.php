<?php

if (!defined('ABSPATH')) {
    exit;
}

function dapfforwc_get_cache_dir()
{
    $upload_dir = wp_upload_dir();
    $base_dir = empty($upload_dir['error']) && !empty($upload_dir['basedir']) ? $upload_dir['basedir'] : WP_CONTENT_DIR . '/uploads';
    $cache_dir = trailingslashit($base_dir) . 'ajax-product-filter-for-woocommerce';

    if (!is_dir($cache_dir)) {
        wp_mkdir_p($cache_dir);
    }

    return $cache_dir;
}

function dapfforwc_get_cache_file($filename)
{
    return trailingslashit(dapfforwc_get_cache_dir()) . sanitize_file_name($filename);
}

function dapfforwc_get_legacy_cache_files()
{
    return [
        __DIR__ . '/woocommerce_attributes_cache.json',
        __DIR__ . '/woocommerce_product_details.json',
        __DIR__ . '/permalinks_cache.json',
    ];
}

function dapfforwc_read_cache($filename, $cache_time)
{
    $cache_file = dapfforwc_get_cache_file($filename);

    if (!file_exists($cache_file) || filemtime($cache_file) <= (time() - absint($cache_time))) {
        return null;
    }

    $cached_data = file_get_contents($cache_file);
    if (false === $cached_data || '' === $cached_data) {
        return null;
    }

    $decoded = json_decode($cached_data, true);
    return is_array($decoded) ? $decoded : null;
}

function dapfforwc_write_cache($filename, $data)
{
    $cache_file = dapfforwc_get_cache_file($filename);

    if (!is_writable(dirname($cache_file))) {
        return false;
    }

    return false !== file_put_contents($cache_file, wp_json_encode($data, JSON_UNESCAPED_UNICODE));
}

function dapfforwc_clear_filter_cache()
{
    $deleted = 0;
    $cache_dir = dapfforwc_get_cache_dir();
    $cache_files = glob(trailingslashit($cache_dir) . '*.json');

    if (is_array($cache_files)) {
        foreach ($cache_files as $cache_file) {
            if (is_file($cache_file) && wp_delete_file($cache_file)) {
                $deleted++;
            }
        }
    }

    foreach (dapfforwc_get_legacy_cache_files() as $legacy_file) {
        if (is_file($legacy_file) && wp_delete_file($legacy_file)) {
            $deleted++;
        }
    }

    return $deleted;
}

function dapfforwc_is_filter_taxonomy($taxonomy)
{
    return in_array($taxonomy, ['product_cat', 'product_tag'], true) || 0 === strpos((string) $taxonomy, 'pa_');
}

function dapfforwc_clear_filter_cache_for_post($post_id)
{
    if ('product' === get_post_type($post_id)) {
        dapfforwc_clear_filter_cache();
    }
}

function dapfforwc_clear_filter_cache_for_status_change($new_status, $old_status, $post)
{
    if ($post instanceof WP_Post && 'product' === $post->post_type && $new_status !== $old_status) {
        dapfforwc_clear_filter_cache();
    }
}

function dapfforwc_clear_filter_cache_for_term($term_id, $tt_id = null, $taxonomy = '')
{
    if (dapfforwc_is_filter_taxonomy($taxonomy)) {
        dapfforwc_clear_filter_cache();
    }
}

function dapfforwc_register_cache_invalidation_hooks()
{
    add_action('save_post_product', 'dapfforwc_clear_filter_cache_for_post');
    add_action('before_delete_post', 'dapfforwc_clear_filter_cache_for_post');
    add_action('transition_post_status', 'dapfforwc_clear_filter_cache_for_status_change', 10, 3);
    add_action('created_term', 'dapfforwc_clear_filter_cache_for_term', 10, 3);
    add_action('edited_term', 'dapfforwc_clear_filter_cache_for_term', 10, 3);
    add_action('delete_term', 'dapfforwc_clear_filter_cache_for_term', 10, 3);
    add_action('set_object_terms', 'dapfforwc_clear_filter_cache_for_term_relationships', 10, 6);
}

function dapfforwc_clear_filter_cache_for_term_relationships($object_id, $terms, $tt_ids, $taxonomy)
{
    if ('product' === get_post_type($object_id) && dapfforwc_is_filter_taxonomy($taxonomy)) {
        dapfforwc_clear_filter_cache();
    }
}

function dapfforwc_handle_clear_cache()
{
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to clear the filter cache.', 'ajax-product-filter-for-woocommerce'));
    }

    check_admin_referer('dapfforwc_clear_cache');
    $deleted = dapfforwc_clear_filter_cache();
    $redirect = add_query_arg(
        [
            'page' => 'dapfforwc-admin',
            'tab' => 'advance_settings',
            'dapfforwc_cache_cleared' => absint($deleted),
        ],
        admin_url('admin.php')
    );

    wp_safe_redirect($redirect);
    exit;
}
