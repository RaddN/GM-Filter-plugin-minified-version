<?php
if (!defined('ABSPATH')) {
    exit;
}
function dapfforwc_settings_init() {
    $wcapf_options = get_option('wcapf_options') ?: [
        'show_categories' =>0,
        'show_attributes' => "on",
        'show_tags' => 0,
        'show_price_range' => "",
        'show_search' => "",
        'use_url_filter' => 'permalinks',
        'update_filter_options' => "on",
        'show_loader' => "on",
        'pages_filter_auto' => "on",
        'pages' => [],
        'loader_html'=>"<div id='loader' style='display:none;'></div>",
        'loader_css'=>'#loader { width: 56px; height: 56px; border-radius: 50%; background: conic-gradient(#0000 10%,#474bff); -webkit-mask: radial-gradient(farthest-side,#0000 calc(100% - 9px),#000 0); animation: spinner-zp9dbg 1s infinite linear; } @keyframes spinner-zp9dbg { to { transform: rotate(1turn); } }',
        'default_filters' => [],
        'use_custom_template' => "on",
        'custom_template_code' => '<tr>
  <td>[product_date id="{{product_id}}"]</td>
  <td><a href="{{product_link}}">{{product_title}}</a></td>
  <td>[product_place id="{{product_id}}"]</td>
</tr>',
        'product_selector' => '.products',
        'pagination_selector' => '.woocommerce-pagination ul.page-numbers',
        'filters_word_in_permalinks' => 'filters',
    ];
    update_option('wcapf_options', $wcapf_options);

    register_setting('wcapf_options_group', 'wcapf_options');
    
    add_settings_section('dapfforwc_section', __('Filter Settings', 'gm-ajax-product-filter-for-woocommerce'), null, 'dapfforwc-admin');

    $fields = [
        'show_categories' => __('Show Categories', 'gm-ajax-product-filter-for-woocommerce'),
        'show_attributes' => __('Show Attributes', 'gm-ajax-product-filter-for-woocommerce'),
        'show_tags' => __('Show Tags', 'gm-ajax-product-filter-for-woocommerce'),
        'update_filter_options' => __('Update filter options', 'gm-ajax-product-filter-for-woocommerce'),
        'show_loader' => __('Show Loader', 'gm-ajax-product-filter-for-woocommerce'),
        'use_custom_template' => __('Use Custom Product Template', 'gm-ajax-product-filter-for-woocommerce'),
    ];

    if (is_array($fields) || is_object($fields)) {
    foreach ($fields as $key => $label) {
        add_settings_field($key, $label, "dapfforwc_{$key}_render", 'dapfforwc-admin', 'dapfforwc_section');
    }
}
    
    // custom code template
    add_settings_field('custom_template_code', __('product custom template code', 'gm-ajax-product-filter-for-woocommerce'), 'dapfforwc_custom_template_code_render', 'dapfforwc-admin', 'dapfforwc_section');

    $default_style = get_option('wcapf_style_options') ?: [];
    update_option('wcapf_style_options', $default_style);
    // form style register
    register_setting('wcapf_style_options_group', 'wcapf_style_options');

        // Add Form Style section
    add_settings_section(
        'dapfforwc_style_section',
        __('Form Style Options', 'gm-ajax-product-filter-for-woocommerce'),
        function () {
            echo '<p>' . esc_html__('Select the filter box style for each attribute below. Additional options will appear based on your selection.', 'gm-ajax-product-filter-for-woocommerce') . '</p>';
        },
        'dapfforwc-style'
    );

//   advance settings register
$Advance_options = get_option('wcapf_advance_options') ?: [
    'product_selector' => 'table.featured-conferences-table',
    'pagination_selector' => '.woocommerce-pagination ul.page-numbers',
    'product_shortcode' => 'latest_products_table',
];
    update_option('wcapf_advance_options', $Advance_options);
    register_setting('dapfforwc_advance_settings', 'wcapf_advance_options');
    // Add the "Advance Settings" section
    add_settings_section(
        'dapfforwc_advance_settings_section',
        __('Advance Settings', 'gm-ajax-product-filter-for-woocommerce'),
        null,
        'dapfforwc-advance-settings'
    );

    // Add the "Product Selector" field
    add_settings_field(
        'product_selector',
        __('Product Selector', 'gm-ajax-product-filter-for-woocommerce'),
        'dapfforwc_product_selector_callback',
        'dapfforwc-advance-settings',
        'dapfforwc_advance_settings_section'
    );
    // Add the "Pagination Selector" field
    add_settings_field(
        'pagination_selector',
        __('Pagination Selector', 'gm-ajax-product-filter-for-woocommerce'),
        'dapfforwc_pagination_selector_callback',
        'dapfforwc-advance-settings',
        'dapfforwc_advance_settings_section'
    );
    // Add the "Product shotcode Selector" field
    add_settings_field(
        'product_shortcode',
        __('Product Shortcode Selector', 'gm-ajax-product-filter-for-woocommerce'),
        'dapfforwc_product_shortcode_callback',
        'dapfforwc-advance-settings',
        'dapfforwc_advance_settings_section'
    );

}
add_action('admin_init', 'dapfforwc_settings_init');


