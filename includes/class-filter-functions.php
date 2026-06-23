<?php
if (!defined('ABSPATH')) {
    exit;
}

class dapfforwc_Filter_Functions
{

    public function process_filter()
    {
        global $wcapf_options, $dapfforwc_advance_settings, $dapfforwc_front_page_slug;

        if (!$this->verify_nonce()) {
            wp_send_json_error(array('message' => 'Security check failed'), 403);
            wp_die();
        }

        $paged = $this->get_paged();
        $currentpage_slug = $this->get_current_page_slug();
        $currentpage_slug = $currentpage_slug == "/" ? $dapfforwc_front_page_slug : $currentpage_slug;
        $orderby = $this->get_orderby() !== "" ? $this->get_orderby() : ($wcapf_options['product_show_settings'][$currentpage_slug]['orderby'] ?? 'date');
        $selected_filter = $this->get_default_filter();
        $base_filter = isset($wcapf_options["default_filters"][$currentpage_slug]) && is_array($wcapf_options["default_filters"][$currentpage_slug])
            ? dapfforwc_normalize_filter_values($wcapf_options["default_filters"][$currentpage_slug])
            : [];
        $default_filter = dapfforwc_normalize_filter_values(array_merge($base_filter, $selected_filter));
        $ratings = array_values(array_filter($default_filter, 'is_numeric'));
        $product_details_json = dapfforwc_get_woocommerce_product_details()["products"] ?? [];
        $product_details = array_values($product_details_json);
        $second_operator = strtoupper($wcapf_options["product_show_settings"][$currentpage_slug]["operator_second"] ?? "IN");
        $order = strtoupper($wcapf_options["product_show_settings"][$currentpage_slug]["order"] ?? "ASC");

        if (!empty($ratings)) {
            // Get product ids by rating
            foreach ($ratings as $rating) {
                $products_id_by_rating[] = array_column(array_filter($product_details, function ($product) use ($rating) {
                    return $product['rating'] == $rating;
                }), 'ID');
            }
            $products_id_by_rating = array_merge(...$products_id_by_rating);
        }
        $all_data = dapfforwc_get_woocommerce_attributes_with_terms();
        $all_cata = $all_data['categories'] ?? [];
        $all_tags = $all_data['tags'] ?? [];
        $all_attributes = $all_data['attributes'] ?? [];
        // Create Lookup Arrays
        $cata_lookup = array_combine(
            array_column($all_cata, 'slug'),
            array_column($all_cata, 'products')
        );
        $tag_lookup = array_combine(
            array_column($all_tags, 'slug'),
            array_column($all_tags, 'products')
        );
        // Match Filters
        $matched_cata_with_ids = array_intersect_key($cata_lookup, array_flip(array_filter($default_filter)));
        if ($second_operator === 'AND') {
            $products_id_by_cata = empty($matched_cata_with_ids) ? [] : array_intersect(...array_values($matched_cata_with_ids));
        } else {
            $products_id_by_cata = empty($matched_cata_with_ids) ? [] : array_values(array_unique(array_merge(...array_values($matched_cata_with_ids))));
        }
        $matched_tag_with_ids = array_intersect_key($tag_lookup, array_flip(array_filter($default_filter)));
        if ($second_operator === 'AND') {
            $products_id_by_tag = empty($matched_tag_with_ids) ? [] : array_intersect(...array_values($matched_tag_with_ids));
        } else {
            $products_id_by_tag = empty($matched_tag_with_ids) ? [] : array_values(array_unique(array_merge(...array_values($matched_tag_with_ids))));
        }
        // Match Attributes
        $products_id_by_attributes = [];
        $match_attributes_with_ids = [];
        if ((is_array($all_attributes) || is_object($all_attributes))) {
            foreach ($all_data['attributes'] as $taxonomy => $lookup) {
                // Ensure 'terms' key exists and is an array
                if (isset($lookup['terms']) && is_array($lookup['terms'])) {
                    foreach ($lookup['terms'] as $term) {
                        if (in_array($term['slug'], $default_filter)) {
                            $match_attributes_with_ids[$taxonomy][] = $term['products'];
                            $products_id_by_attributes[] = $term['products'];
                        }
                    }
                }
            }
        }

        $common_values = empty($products_id_by_attributes) ? [] : array_intersect(...$products_id_by_attributes);

        if (empty($products_id_by_cata) && empty($products_id_by_tag) && empty($common_values)) {
            $products_ids = [];
        } elseif (empty($products_id_by_cata) && empty($products_id_by_tag) && !empty($common_values)) {
            $products_ids = $common_values;
        } elseif (empty($products_id_by_cata) && !empty($products_id_by_tag) && empty($common_values)) {
            $products_ids = $products_id_by_tag;
        } elseif (!empty($products_id_by_cata) && empty($products_id_by_tag) && empty($common_values)) {
            $products_ids = $products_id_by_cata;
        } elseif (!empty($products_id_by_cata) && !empty($products_id_by_tag) && empty($common_values)) {
            $products_ids = array_values(array_intersect($products_id_by_cata, $products_id_by_tag));
        } elseif (!empty($products_id_by_cata) && empty($products_id_by_tag) && !empty($common_values)) {
            $products_ids = array_values(array_intersect($products_id_by_cata, $common_values));
        } elseif (empty($products_id_by_cata) && !empty($products_id_by_tag) && !empty($common_values)) {
            $products_ids = array_values(array_intersect($products_id_by_tag, $common_values));
        } else {
            $products_ids = array_values(array_intersect($products_id_by_cata, $products_id_by_tag, $common_values));
        }
        if (!empty($products_id_by_rating)) {
            $products_ids = array_values(array_intersect($products_ids, $products_id_by_rating));
        }
        $products_ids = dapfforwc_get_constrained_filter_product_ids($base_filter, $selected_filter, $all_data, $product_details, $second_operator);
        $products_ids = dapfforwc_filter_renderable_product_ids($products_ids, $product_details_json);

        // Order products based on $orderby
        if (!empty($orderby)) {
            if ($orderby === 'menu_order date') {
                usort($products_ids, function ($a, $b) use ($product_details_json) {
                    $product_a = $product_details_json[$a] ?? [];
                    $product_b = $product_details_json[$b] ?? [];
                    $menu_order_a = (int) ($product_a['menu_order'] ?? 0);
                    $menu_order_b = (int) ($product_b['menu_order'] ?? 0);

                    if ($menu_order_a === $menu_order_b) {
                        $date_a = dapfforwc_get_product_sort_date_timestamp($product_a);
                        $date_b = dapfforwc_get_product_sort_date_timestamp($product_b);

                        if ($date_a === $date_b) {
                            return absint($a) <=> absint($b);
                        }

                        return $date_a <=> $date_b;
                    }

                    return $menu_order_a <=> $menu_order_b;
                });
            } else {
            $orderby = $orderby === 'date' ? 'post_modified' : $orderby;
            usort($products_ids, function ($a, $b) use ($product_details_json, $orderby) {
                if (!isset($product_details_json[$a][$orderby]) || !isset($product_details_json[$b][$orderby])) {
                return 0;
                }
                return $product_details_json[$a][$orderby] <=> $product_details_json[$b][$orderby];
            });
            }
        }
        if ('DESC' === $order) {
            $products_ids = array_reverse($products_ids);
        }
        $count_total_showing_product = count($products_ids);
        $updated_filters = dapfforwc_get_updated_filters($products_ids);
        $filterform = dapfforwc_filter_form($updated_filters, $default_filter);
        $cache_time = 12 * 60 * 60; // 12 hours in seconds

        $permalinks = dapfforwc_read_cache('permalinks_cache.json', $cache_time);
        if (!is_array($permalinks)) {
            $permalinks = get_option('woocommerce_permalinks');
            dapfforwc_write_cache('permalinks_cache.json', $permalinks);
        }
        $per_page = isset($wcapf_options["product_show_settings"][$currentpage_slug]["per_page"]) ? absint($wcapf_options["product_show_settings"][$currentpage_slug]["per_page"]) : 30;
        $per_page = $per_page > 0 ? $per_page : 30;
        $total_pages = ceil($count_total_showing_product / $per_page);
        $start_index = ($paged - 1) * $per_page;
        $end_index = min($start_index + $per_page, $count_total_showing_product);

        ob_start();
        $current_month_label = '';
        for ($i = $start_index; $i < $end_index; $i++) {
            if (isset($products_ids[$i])) {
                $product_id = $products_ids[$i];
                if (isset($product_details_json[$product_id])) {
                    $product = $product_details_json[$product_id];
                    if (function_exists('dapfforwc_get_conference_month_label_from_excerpt') && function_exists('dapfforwc_render_conference_month_row')) {
                        $month_label = dapfforwc_get_conference_month_label_from_excerpt($product['product_excerpt'] ?? '');

                        if ('' !== $month_label && $month_label !== $current_month_label) {
                            echo dapfforwc_render_conference_month_row($month_label);
                            $current_month_label = $month_label;
                        }
                    }
                    $this->display_product($product, $currentpage_slug, $permalinks);
                }
            }
        }
        $product_html = ob_get_clean();

        wp_send_json_success(array(
            'products' => $product_html,
            'total_product_fetch' => $count_total_showing_product,
            'pagination' => $this->pagination($paged, $total_pages),
            'filter_options' => $filterform
        ));

        wp_die();
    }

    private function verify_nonce()
    {
        return isset($_POST['gm-product-filter-nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gm-product-filter-nonce'])), 'gm-product-filter-action');
    }

    private function get_paged()
    {
        return isset($_POST['paged']) ? intval($_POST['paged']) : 1;
    }

    private function get_orderby()
    {
        return isset($_POST['orderby']) && $_POST['orderby'] !== "undefined" ? sanitize_text_field(wp_unslash($_POST['orderby'])) : "";
    }

    private function get_current_page_slug()
    {
        return isset($_POST['current-page']) ? sanitize_text_field(wp_unslash($_POST['current-page'])) : "";
    }

    private function get_default_filter()
    {
        if (!empty($_POST['selectedvalues'])) {
            $selected_values = sanitize_text_field(wp_unslash($_POST['selectedvalues']));
            return array_map('sanitize_text_field', explode(',', $selected_values));
        }
        return [];
    }

    private function get_current_url()
    {
        if (!empty($_POST['current-url'])) {
            $posted_url = esc_url_raw(wp_unslash($_POST['current-url']));

            if (!empty($posted_url)) {
                return $posted_url;
            }
        }

        $referer = wp_get_referer();
        if (!empty($referer)) {
            return $referer;
        }

        return home_url('/');
    }

    private function get_pagination_base_url()
    {
        $current_url = $this->get_current_url();
        $current_url = preg_replace('#/page/\d+/?#', '/', $current_url);
        $current_url = remove_query_arg('paged', $current_url);
        $filter_word = $this->get_filter_permalink_word();
        $filter_marker = '/' . $filter_word . '/';
        $filter_position = strpos($current_url, $filter_marker);

        if (false !== $filter_position) {
            return substr_replace($current_url, '/%_%' . $filter_word . '/', $filter_position, strlen($filter_marker));
        }

        $query_filters = $this->get_query_filters_from_url($current_url);
        if (!empty($query_filters)) {
            $current_url = remove_query_arg('filters', $current_url);
            $query_position = strpos($current_url, '?');
            $url_before_query = false !== $query_position ? substr($current_url, 0, $query_position) : $current_url;
            $query_string = false !== $query_position ? substr($current_url, $query_position) : '';

            return trailingslashit($url_before_query) . '%_%' . $filter_word . '/' . implode('/', array_map('rawurlencode', $query_filters)) . '/' . $query_string;
        }

        $query_position = strpos($current_url, '?');
        if (false !== $query_position) {
            $url_before_query = substr($current_url, 0, $query_position);
            $query_string = substr($current_url, $query_position);

            return trailingslashit($url_before_query) . '%_%' . $query_string;
        }

        return trailingslashit($current_url) . '%_%';
    }

    private function get_filter_permalink_word()
    {
        global $wcapf_options;

        $filter_word = isset($wcapf_options['filters_word_in_permalinks']) ? trim((string) $wcapf_options['filters_word_in_permalinks'], '/') : 'filters';

        return '' !== $filter_word ? $filter_word : 'filters';
    }

    private function get_query_filters_from_url($url)
    {
        $query_string = wp_parse_url($url, PHP_URL_QUERY);
        if (empty($query_string)) {
            return [];
        }

        parse_str($query_string, $query_args);
        if (empty($query_args['filters'])) {
            return [];
        }

        $raw_filters = is_array($query_args['filters']) ? $query_args['filters'] : preg_split('#[,/]+#', (string) $query_args['filters']);
        $filters = array_map(
            function ($filter) {
                return trim(sanitize_text_field(rawurldecode((string) $filter)), '/');
            },
            $raw_filters
        );

        return array_values(array_filter($filters));
    }

    private function display_product($product, $currentpage_slug, $permalinks)
    {
        global $wcapf_options;
        // Get product details
        $product_id = absint($product['ID']);
        $product_link = esc_url(home_url($permalinks['product_base'] . '/' . $product['post_name']));
        $product_title = isset($product['post_title']) ? (string) $product['post_title'] : '';
        $product_title_attr = esc_attr($product_title);
        $product_title_html = esc_html($product_title);
        $product_price = $product['price'];
        $product_image = empty($product['product_image']) ? wc_placeholder_img_src('woocommerce_thumbnail') : $product['product_image'];
        $product_image = esc_url($product_image);
        $product_excerpt = $product['product_excerpt'];
        $conference_data = function_exists('dapfforwc_extract_conference_short_desc_data')
            ? dapfforwc_extract_conference_short_desc_data($product_excerpt)
            : [
                'date' => '',
                'place' => '',
                'type' => '',
            ];
        $product_date = $conference_data['date'] ?? '';
        $product_place = $conference_data['place'] ?? '';
        $product_type = $conference_data['type'] ?? '';
        $product_topics = function_exists('dapfforwc_render_product_topic_badges') ? dapfforwc_render_product_topic_badges($product_id) : '';
        $product_topics_text = function_exists('dapfforwc_get_product_topic_text') ? dapfforwc_get_product_topic_text($product_id) : '';
        $rating = isset($product['rating']) ? max(0, min(5, (float) $product['rating'])) : 0;
        $rating_width = esc_attr($rating * 20);
        $product_category = $product['product_category'];
        $cata_output = "";
        foreach (is_array($product_category) ? $product_category : []  as $index => $category) {
            $cata_output .= '<a href="' . esc_url(home_url($permalinks['category_base'] . '/' . $category['slug'])) . '">' . esc_html($category['name']) . '</a>';
            if ($index < count($product_category) - 1) {
                $cata_output .= ', ';
            }
        }
        $product_sku = $product['product_sku'];
        $product_stock = $product['product_stock'];
        $on_sale = $product['on_sale'];
        $add_to_cart_url = esc_url(add_query_arg('add-to-cart', $product_id, $product_link));
        if (isset($wcapf_options['use_custom_template']) && $wcapf_options['use_custom_template'] === "on") {

            // Retrieve the custom template from the database
            $custom_template = $wcapf_options['custom_template_code'];

            // Replace placeholders with actual values
            $custom_template = str_replace('{{product_link}}', esc_url($product_link), $custom_template);
            $custom_template = str_replace('{{product_title}}', esc_html($product_title), $custom_template);
            $custom_template = str_replace('{{product_image}}', esc_url($product_image), $custom_template);
            $custom_template = str_replace('{{product_excerpt}}', wp_kses_post(wpautop($product_excerpt)), $custom_template);
            $custom_template = str_replace('{{product_price}}', wp_kses_post($product_price), $custom_template);
            $custom_template = str_replace('{{product_category}}', $cata_output, $custom_template);
            $custom_template = str_replace('{{product_sku}}', esc_html($product_sku), $custom_template);
            $custom_template = str_replace('{{product_stock}}', esc_html($product_stock), $custom_template);
            $custom_template = str_replace('{{add_to_cart_url}}', $add_to_cart_url, $custom_template);
            $custom_template = str_replace('{{product_id}}', esc_html($product_id), $custom_template);
            $custom_template = str_replace('{{product_date}}', esc_html($product_date), $custom_template);
            $custom_template = str_replace('{{product_place}}', esc_html($product_place), $custom_template);
            $custom_template = str_replace('{{product_type}}', esc_html($product_type), $custom_template);
            $custom_template = str_replace('{{product_topics}}', $product_topics, $custom_template);
            $custom_template = str_replace('{{product_topics_text}}', esc_html($product_topics_text), $custom_template);
            $allowed_tags = array(
                'a' => array(
                    'href' => array(),
                    'title' => array(),
                    'class' => array(),
                    'target' => array(), // Allow target attribute for links
                ),
                'strong' => array(),
                'em' => array(),
                'li' => array(
                    'class' => array(),
                ),
                'div' => array(
                    'class' => array(),
                    'id' => array(), // Allow id for divs
                ),
                'img' => array(
                    'src' => array(),
                    'alt' => array(),
                    'class' => array(),
                    'width' => array(), // Allow width attribute
                    'height' => array(), // Allow height attribute
                ),
                'h1' => array('class' => array()), // Allow h1
                'h2' => array('class' => array()),
                'h3' => array('class' => array()), // Allow h3
                'h4' => array('class' => array()), // Allow h4
                'h5' => array('class' => array()), // Allow h5
                'h6' => array('class' => array()), // Allow h6
                'span' => array('class' => array()),
                'p' => array('class' => array()),
                'br' => array(), // Allow line breaks
                'blockquote' => array(
                    'cite' => array(), // Allow cite attribute for blockquotes
                    'class' => array(),
                ),
                'table' => array(
                    'class' => array(),
                    'style' => array(), // Allow inline styles
                ),
                'tr' => array(
                    'class' => array(),
                ),
                'td' => array(
                    'class' => array(),
                    'colspan' => array(), // Allow colspan attribute
                    'rowspan' => array(), // Allow rowspan attribute
                ),
                'th' => array(
                    'class' => array(),
                    'colspan' => array(),
                    'rowspan' => array(),
                ),
                'ul' => array('class' => array()), // Allow unordered lists
                'ol' => array('class' => array()), // Allow ordered lists
            );

            echo wp_kses(do_shortcode($custom_template), $allowed_tags);
        } else {
            $product['ID'] = $product_id;
            $product_title = $product_title_html;
            $product_price = wp_kses_post($product_price);
            $rating = esc_html($rating);

            echo '<li class="product type-product">
	<div class="astra-shop-thumbnail-wrap">
	<a href="' . $product_link . '" class="woocommerce-LoopProduct-link woocommerce-loop-product__link">
    <img fetchpriority="high" decoding="async" width="300" height="300" src="' . $product_image . '" class="woocommerce-placeholder wp-post-image" alt="Placeholder" srcset="' . $product_image . ' 300w">
        </a>
        ' . ($on_sale ? '<span class="ast-on-card-button ast-onsale-card" data-notification="default">Sale!</span>' : '') . '
        <a href="?add-to-cart=' . $product['ID'] . '" data-quantity="1" class="ast-on-card-button ast-select-options-trigger product_type_simple add_to_cart_button ajax_add_to_cart" data-product_id="' . $product['ID'] . '" data-product_sku="" aria-label="Add to cart: “' . $product_title . '”" rel="nofollow"> <span class="ast-card-action-tooltip"> Add to cart </span> <span class="ahfb-svg-iconset"> <span class="ast-icon icon-bag"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" id="ast-bag-icon-svg" x="0px" y="0px" width="100" height="100" viewBox="826 826 140 140" enable-background="new 826 826 140 140" xml:space="preserve">
				<path d="M960.758,934.509l2.632,23.541c0.15,1.403-0.25,2.657-1.203,3.761c-0.953,1.053-2.156,1.579-3.61,1.579H833.424  c-1.454,0-2.657-0.526-3.61-1.579c-0.952-1.104-1.354-2.357-1.203-3.761l2.632-23.541H960.758z M953.763,871.405l6.468,58.29H831.77  l6.468-58.29c0.15-1.203,0.677-2.218,1.58-3.045c0.903-0.827,1.981-1.241,3.234-1.241h19.254v9.627c0,2.658,0.94,4.927,2.82,6.807  s4.149,2.82,6.807,2.82c2.658,0,4.926-0.94,6.807-2.82s2.821-4.149,2.821-6.807v-9.627h28.882v9.627  c0,2.658,0.939,4.927,2.819,6.807c1.881,1.88,4.149,2.82,6.807,2.82s4.927-0.94,6.808-2.82c1.879-1.88,2.82-4.149,2.82-6.807v-9.627  h19.253c1.255,0,2.332,0.414,3.235,1.241C953.086,869.187,953.612,870.202,953.763,871.405z M924.881,857.492v19.254  c0,1.304-0.476,2.432-1.429,3.385s-2.08,1.429-3.385,1.429c-1.303,0-2.432-0.477-3.384-1.429c-0.953-0.953-1.43-2.081-1.43-3.385  v-19.254c0-5.315-1.881-9.853-5.641-13.613c-3.76-3.761-8.298-5.641-13.613-5.641s-9.853,1.88-13.613,5.641  c-3.761,3.76-5.641,8.298-5.641,13.613v19.254c0,1.304-0.476,2.432-1.429,3.385c-0.953,0.953-2.081,1.429-3.385,1.429  c-1.303,0-2.432-0.477-3.384-1.429c-0.953-0.953-1.429-2.081-1.429-3.385v-19.254c0-7.973,2.821-14.779,8.461-20.42  c5.641-5.641,12.448-8.461,20.42-8.461c7.973,0,14.779,2.82,20.42,8.461C922.062,842.712,924.881,849.519,924.881,857.492z"></path>
				</svg></span> </span> </a></div><div class="astra-shop-summary-wrap">			<span class="ast-woo-product-category">
				' . $cata_output . '			</span>
			<a href="' . $product_link . '" class="ast-loop-product__link"><h2 class="woocommerce-loop-product__title">' . $product_title . '</h2></a>
            <div class="review-rating"><div class="star-rating"><span style="width:' . ($rating * 20) . '%">Rated <strong class="rating">' . $rating . '</strong> out of 5</span></div></div>
    <span class="price"><span class="woocommerce-Price-amount amount"><bdi><span class="woocommerce-Price-currencySymbol">$</span>' . $product_price . '</bdi></span></span>
<a href="?add-to-cart=' . $product['ID'] . '" aria-describedby="woocommerce_loop_add_to_cart_link_describedby_' . $product['ID'] . '" data-quantity="1" class="button product_type_simple add_to_cart_button ajax_add_to_cart" data-product_id="' . $product['ID'] . '" data-product_sku="" aria-label="Add to cart: “' . $product_title . '”" rel="nofollow" data-success_message="“' . $product_title . '” has been added to your cart">Add to cart</a>	<span id="woocommerce_loop_add_to_cart_link_describedby_' . $product['ID'] . '" class="screen-reader-text">
			</span>
</div></li>';
        }
    }

    private function pagination($paged, $total_pages)
    {
        $paginationLinks = paginate_links(array(
            'base' => esc_url_raw($this->get_pagination_base_url()),
            'format' => 'page/%#%/',
            'current' => max(1, $paged),
            'total' => $total_pages,
            'prev_text' => __('« Prev', 'ajax-product-filter-for-woocommerce'),
            'next_text' => __('Next »', 'ajax-product-filter-for-woocommerce'),
            'type' => 'array',
        ));

        if ($paginationLinks) {
            $paginationHtml = '';
            if (is_array($paginationLinks) || is_object($paginationLinks)) {
                foreach ($paginationLinks as $link) {
                    $paginationHtml .= '<li>' . $link . '</li>';
                }
            }
            return $paginationHtml;
        }
        return '';
    }
}
