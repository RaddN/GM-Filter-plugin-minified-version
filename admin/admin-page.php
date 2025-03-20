<?php
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
function dapfforwc_admin_menu() {
    add_menu_page(
        'WooCommerce Product Filters',
        'Product Filters',
        'manage_options',
        'dapfforwc-admin',
        'dapfforwc_admin_page_content',
        'dashicons-filter',
        58
    );
}
add_action('admin_menu', 'dapfforwc_admin_menu');

// Admin page content
function dapfforwc_admin_page_content() {
    global $wcapf_options;
    ?>
    <div class="wrap wcapf_admin">
        <h1>Manage WooCommerce Product Filters</h1>
        <?php settings_errors(); // Displays success or error notices
        $nonce = wp_create_nonce('dapfforwc_tab_nonce');
        ?>
        <h2 class="nav-tab-wrapper">
            <a href="?page=dapfforwc-admin&tab=form_manage&_wpnonce=<?php echo esc_attr($nonce); ?>" class="nav-tab <?php echo isset($_GET['tab']) && sanitize_text_field(wp_unslash($_GET['tab'])) == 'form_manage' ? 'nav-tab-active' : ''; ?>">Form Manage</a>
            <a href="?page=dapfforwc-admin&tab=advance_settings&_wpnonce=<?php echo esc_attr($nonce); ?>" class="nav-tab <?php echo isset($_GET['tab']) && sanitize_text_field(wp_unslash($_GET['tab'])) == 'advance_settings' ? 'nav-tab-active' : ''; ?>">Advance Settings</a>
        </h2>

        <div class="tab-content">
            <?php
            $active_tab = 'form_manage'; // Default tab
            if (isset($_GET['_wpnonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'dapfforwc_tab_nonce')) {
                $active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'form_manage';
            }

            if ($active_tab == 'form_manage') {
                ?>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('wcapf_options_group');
                    do_settings_sections('dapfforwc-admin');
                    submit_button();
                    ?>
                    <p>Use shortcode to show filter: <b>[wcapf_product_filter]</b></p>
                    <p>For button style filter use this shortcode: <b>[wcapf_product_filter_single name="conference-by-month"]</b></p>
                </form>
                <?php
            } elseif ($active_tab == 'advance_settings') {
                ?>
                <form method="post" action="options.php">
                    <?php
                    settings_fields('dapfforwc_advance_settings');
                    do_settings_sections('dapfforwc-advance-settings');
                    submit_button();
                    ?>
                </form>
                <?php
            }
            ?>
        </div>
    </div>
    <?php
}

// Include necessary files
require_once(plugin_dir_path(__FILE__) . 'settings-init.php');
require_once(plugin_dir_path(__FILE__) . 'form-manage.php');
require_once(plugin_dir_path(__FILE__) . 'advance_settings.php');


?>
