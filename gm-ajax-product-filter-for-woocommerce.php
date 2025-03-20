<?php

/**
 * Plugin Name: GM AJAX Product Filter for WooCommerce
 * Plugin URI:  https://plugincy.com/
 * Description: A WooCommerce plugin to filter products by attributes, categories, and tags using AJAX for seamless user experience.
 * Version:     2.0.9
 * Author:      Plugincy
 * Author URI:  https://plugincy.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gm-ajax-product-filter-for-woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

// Global Variables
global $wcapf_options, $dapfforwc_advance_settings, $dapfforwc_slug;

$wcapf_options = get_option('wcapf_options') ?: [];
$dapfforwc_advance_settings = get_option('wcapf_advance_options') ?: [];
$dapfforwc_slug = "";
$dapfforwc_front_page_id = get_option('page_on_front') ?: null;
// Get the front page object
$dapfforwc_front_page = isset($dapfforwc_front_page_id) ? get_post($dapfforwc_front_page_id) : null;
// Get the slug of the front page
$dapfforwc_front_page_slug = isset($dapfforwc_front_page) ? $dapfforwc_front_page->post_name : "";

// Check if WooCommerce is active
add_action('plugins_loaded', 'dapfforwc_check_woocommerce');

function dapfforwc_check_woocommerce()
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'dapfforwc_missing_woocommerce_notice');
    } else {
        if (is_admin()) {
            require_once plugin_dir_path(__FILE__) . 'admin/admin-page.php';
        }
        require_once plugin_dir_path(__FILE__) . 'includes/filter-template.php';
        add_action('wp_enqueue_scripts', 'dapfforwc_enqueue_scripts');
        add_action('admin_enqueue_scripts', 'dapfforwc_admin_scripts');
        require_once plugin_dir_path(__FILE__) . 'includes/class-filter-functions.php';
        add_action('wp_ajax_dapfforwc_filter_products', 'dapfforwc_filter_products');
        add_action('wp_ajax_nopriv_dapfforwc_filter_products', 'dapfforwc_filter_products');

        register_setting('wcapf_options_group', 'dapfforwc_filters', 'sanitize_text_field');

        add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'dapfforwc_add_settings_link');
    }
}

function dapfforwc_missing_woocommerce_notice()
{
    echo '<div class="notice notice-error"><p><strong>' . esc_html__('Filter Plugin', 'gm-ajax-product-filter-for-woocommerce') . '</strong> ' . esc_html__('requires WooCommerce to be installed and activated.', 'gm-ajax-product-filter-for-woocommerce') . '</p></div>';
}

// Enqueue scripts and styles
function dapfforwc_enqueue_scripts()
{
    global $wcapf_options, $dapfforwc_slug, $dapfforwc_advance_settings, $dapfforwc_front_page_slug;

    $script_handle = 'permalinksfilter-ajax';
    $script_path = 'assets/js/permalinksfilter.js';
    $dapfforwc_slug =  '';

    wp_enqueue_script($script_handle, plugin_dir_url(__FILE__) . $script_path, ['jquery'], '2.0.9', true);
    wp_localize_script($script_handle, 'dapfforwc_data', compact('wcapf_options', 'dapfforwc_slug', 'dapfforwc_advance_settings', 'dapfforwc_front_page_slug'));
    wp_localize_script($script_handle, 'dapfforwc_ajax', ['ajax_url' => admin_url('admin-ajax.php')]);

    wp_enqueue_style('filter-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', [], '2.0.9');
}

function dapfforwc_admin_scripts($hook)
{
    if ($hook !== 'toplevel_page_dapfforwc-admin') {
        return; // Load only on the plugin's admin page
    }
    wp_enqueue_style('dapfforwc-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css', [], '2.0.9');
    wp_enqueue_style('dapfforwc-admin-codemirror-style', plugin_dir_url(__FILE__) . 'assets/css/codemirror.min.css', [], '5.65.2');
    wp_enqueue_script('dapfforwc-admin-codemirror-script', plugin_dir_url(__FILE__) . 'assets/js/codemirror.min.js', [], '5.65.2', true);
    wp_enqueue_script('dapfforwc-admin-xml-script', plugin_dir_url(__FILE__) . 'assets/js/xml.min.js', [], '5.65.2', true);
    wp_enqueue_script('dapfforwc-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin-script.js', [], '2.0.9', true);
}

function dapfforwc_filter_products()
{
    if (class_exists('dapfforwc_Filter_Functions')) {
        $filter = new dapfforwc_Filter_Functions();
        $filter->process_filter();
    } else {
        wp_send_json_error('Filter class not found.');
    }
}


function dapfforwc_add_settings_link($links)
{
    $settings_link = '<a href="admin.php?page=dapfforwc-admin">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}


require_once(plugin_dir_path(__FILE__) . 'includes/permalinks-setup.php');

function dapfforwc_get_full_slug($post_id)
{
    if (empty($post_id)) {
        return ''; // Return an empty string if $post_id is not defined
    }
    $dapfforwc_slug_parts = [];
    $current_post_id = $post_id;

    while ($current_post_id) {
        $current_post = get_post($current_post_id);

        if (!$current_post) {
            break; // Exit if no post is found
        }

        // Prepend the current slug
        array_unshift($dapfforwc_slug_parts, $current_post->post_name);

        // Get the parent post ID
        $current_post_id = wp_get_post_parent_id($current_post_id);
    }

    return implode('/', $dapfforwc_slug_parts); // Combine slugs with '/'
}


require_once(plugin_dir_path(__FILE__) . 'includes/widget_design_template.php');
