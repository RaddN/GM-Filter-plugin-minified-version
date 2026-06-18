<?php
if (!defined('ABSPATH')) {
    exit;
}

// Define constants for default values
define('DAPFFORWC_DEFAULT_PRODUCT_SELECTOR', '.products');
define('DAPFFORWC_DEFAULT_PAGINATION_SELECTOR', '.woocommerce-pagination ul.page-numbers');
define('DAPFFORWC_DEFAULT_PRODUCT_SHORTCODE', 'latest_products_table_with_custom_sort');

// Render the "Product Selector" field
function dapfforwc_product_selector_callback() {
    $wcapf_options = get_option('wcapf_advance_options')?:[];
    $product_selector = isset($wcapf_options['product_selector']) ? esc_attr($wcapf_options['product_selector']) : DAPFFORWC_DEFAULT_PRODUCT_SELECTOR;
    ?>
    <input type="text" name="wcapf_advance_options[product_selector]" value="<?php echo esc_attr($product_selector); ?>" placeholder="<?php echo esc_attr(DAPFFORWC_DEFAULT_PRODUCT_SELECTOR); ?>">
    <p class="description">
        <?php esc_html_e('Enter the CSS selector for the product container. Default is .products.', 'ajax-product-filter-for-woocommerce'); ?>
    </p>
    <?php
}

// Render the "Pagination Selector" field
function dapfforwc_pagination_selector_callback() {
    $wcapf_options = get_option('wcapf_advance_options')?:[];
    $pagination_selector = isset($wcapf_options['pagination_selector']) ? esc_attr($wcapf_options['pagination_selector']) : DAPFFORWC_DEFAULT_PAGINATION_SELECTOR;
    ?>
    <input type="text" name="wcapf_advance_options[pagination_selector]" value="<?php echo esc_attr($pagination_selector); ?>" placeholder="<?php echo esc_attr(DAPFFORWC_DEFAULT_PAGINATION_SELECTOR); ?>">
    <p class="description">
        <?php esc_html_e('Enter the CSS selector for the pagination container. Default is .woocommerce-pagination ul.page-numbers.', 'ajax-product-filter-for-woocommerce'); ?>
    </p>
    <?php
}

// Render the "Product Shortcode Selector" field
function dapfforwc_product_shortcode_callback() {
    $wcapf_options = get_option('wcapf_advance_options')?:[];
    $product_shortcode = isset($wcapf_options['product_shortcode']) ? esc_attr($wcapf_options['product_shortcode']) : DAPFFORWC_DEFAULT_PRODUCT_SHORTCODE;
    ?>
    <input type="text" name="wcapf_advance_options[product_shortcode]" value="<?php echo esc_attr($product_shortcode); ?>" placeholder="<?php echo esc_attr(DAPFFORWC_DEFAULT_PRODUCT_SHORTCODE); ?>">
    <p class="description">
        <?php esc_html_e('Enter one or more product shortcode names separated by commas. Default is latest_products_table_with_custom_sort.', 'ajax-product-filter-for-woocommerce'); ?>
    </p>
    <?php
}

function dapfforwc_cache_tools_callback() {
    $clear_url = wp_nonce_url(
        add_query_arg('action', 'dapfforwc_clear_cache', admin_url('admin-post.php')),
        'dapfforwc_clear_cache'
    );
    ?>
    <a class="button" href="<?php echo esc_url($clear_url); ?>">
        <?php esc_html_e('Clear filter cache', 'ajax-product-filter-for-woocommerce'); ?>
    </a>
    <p class="description">
        <?php esc_html_e('Clears cached product, term, and permalink data used by AJAX filtering. Product and product taxonomy changes also clear this cache automatically.', 'ajax-product-filter-for-woocommerce'); ?>
    </p>
    <?php
}

function dapfforwc_render_advance_checkbox($key) {
    $wcapf_options = get_option('wcapf_advance_options')?:[];
    ?>
    <label class="switch <?php echo esc_attr($key); ?>">
        <input type='checkbox' name='wcapf_advance_options[<?php echo esc_attr($key); ?>]' <?php checked(isset($wcapf_options[$key]) && $wcapf_options[$key] === "on"); ?>>
        <span class="slider round"></span>
    </label>
    <?php
}
