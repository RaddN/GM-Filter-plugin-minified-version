<?php

if (!defined('ABSPATH')) {
    exit;
}
function dapfforwc_product_filter_shortcode($atts)
{
    global $post, $wcapf_options, $dapfforwc_advance_settings, $wp;
    $dapfforwc_slug = isset($post) ? dapfforwc_get_full_slug(get_the_ID()) : "";
    $request = $wp->request;
    $shortcode = $dapfforwc_advance_settings["product_shortcode"] ?? 'latest_products_table_with_custom_sort';
    $attributes_list = dapfforwc_get_shortcode_attributes_from_page($post->post_content ?? "", $shortcode);
    $shortcode_filters = [];
    foreach ($attributes_list as $attributes) {
        // Ensure that the 'category', 'attribute', and 'terms' keys exist
        $arrayCata = isset($attributes['category']) ? array_map('trim', explode(",", $attributes['category'])) : [];
        $tagValue = isset($attributes['tags']) ? array_map('trim', explode(",", $attributes['tags'])) : [];
        $termsValue = isset($attributes['terms']) ? array_map('trim', explode(",", $attributes['terms'])) : [];
        $filters = !empty($arrayCata) ? $arrayCata : (!empty($tagValue) ? $tagValue : $termsValue);
        $shortcode_filters = array_merge($shortcode_filters, $filters);


        // Use the combined full slug as the key in default_filters
        $wcapf_options['default_filters'][$dapfforwc_slug] = $filters;
        $wcapf_options['product_show_settings'][$dapfforwc_slug] = [
            'per_page'        => $attributes['limit'] ?? $attributes['per_page'] ?? '30',
            'orderby'         => $attributes['orderby'] ?? '',
            'order'           => $attributes['order'] ?? '',
            'operator_second' => $attributes['terms_operator'] ?? $attributes['tag_operator'] ?? $attributes['cat_operator'] ?? 'IN'
        ];
    }
    update_option('wcapf_options', $wcapf_options);
    $second_operator = strtoupper($wcapf_options["product_show_settings"][$dapfforwc_slug]["operator_second"] ?? "IN");
    $shortcode_filters = dapfforwc_normalize_filter_values($shortcode_filters);
    $base_filter = isset($wcapf_options["default_filters"][$dapfforwc_slug]) && is_array($wcapf_options["default_filters"][$dapfforwc_slug])
        ? dapfforwc_normalize_filter_values($wcapf_options["default_filters"][$dapfforwc_slug])
        : [];
    $filter_word = $wcapf_options['filters_word_in_permalinks'] ?? 'filters';
    $request_values = dapfforwc_get_request_filter_values($request, $filter_word);
    $default_filter = array_values(array_merge($base_filter, $request_values));
    $default_filter = dapfforwc_normalize_filter_values($default_filter);
    $ratings = array_values(array_filter($default_filter, 'is_numeric'));

    $atts = shortcode_atts([
        'attribute' => '',
        'terms' => '',
        'category' => '',
        'tag' => '',
        'product_selector' => '',
        'pagination_selector' => ''
    ], $atts);
    $product_details_json = dapfforwc_get_woocommerce_product_details()["products"] ?? [];
    $product_details = array_values($product_details_json);
    $products_id_by_rating = [];
    if (!empty($ratings)) {
        // Get product ids by rating
        foreach ($ratings as $rating) {
            $products_id_by_rating[] = array_column(array_filter($product_details, function ($product) use ($rating) {
                return $product['rating'] == $rating;
            }), 'ID');
        }
        $products_id_by_rating = array_merge(...$products_id_by_rating);
    }
    // Get Categories, Tags, attributes using the existing function
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
        $products_id_by_cata = empty($matched_cata_with_ids) ? [] : array_values(array_intersect(...array_values($matched_cata_with_ids)));
    } else {
        $products_id_by_cata = empty($matched_cata_with_ids) ? [] : array_values(array_unique(array_merge(...array_values($matched_cata_with_ids))));
    }
    $matched_tag_with_ids = array_intersect_key($tag_lookup, array_flip(array_filter($default_filter)));
    if ($second_operator === 'AND') {
        $products_id_by_tag = empty($matched_tag_with_ids) ? [] : array_values(array_intersect(...array_values($matched_tag_with_ids)));
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
    $products_ids = dapfforwc_get_constrained_filter_product_ids($base_filter, $request_values, $all_data, $product_details, $second_operator);
    $products_ids = dapfforwc_filter_renderable_product_ids($products_ids, $product_details_json);

    $updated_filters = dapfforwc_get_updated_filters($products_ids, $all_data) ?? [];
    ob_start();
?>
    <form id="product-filter" method="POST"
        data-current_page_slug="<?php echo esc_attr($dapfforwc_slug); ?>"
        data-base_url="<?php echo esc_url(get_permalink(get_the_ID())); ?>"
        data-default_filters="<?php echo esc_attr(wp_json_encode($shortcode_filters)); ?>"
        <?php if (!empty($atts['product_selector'])) {
            echo 'data-product_selector="' . esc_attr($atts["product_selector"]) . '"';
        } ?>
        <?php if (!empty($atts['pagination_selector'])) {
            echo 'data-pagination_selector="' . esc_attr($atts["pagination_selector"]) . '"';
        }
        ?>>
        <?php
        wp_nonce_field('gm-product-filter-action', 'gm-product-filter-nonce');
        echo dapfforwc_filter_form($updated_filters, $default_filter);
        echo '</form>';
        ?>

        <div id="loader" style="display:none;"></div>
        <style>
            #loader {
                width: 48px;
                height: 48px;
                display: inline-block;
                position: relative;
            }

            #loader::after,
            #loader::before {
                content: '';
                box-sizing: border-box;
                width: 48px;
                height: 48px;
                border-radius: 50%;
                border: 2px solid var(--ast-global-color-0);
                position: absolute;
                left: 0;
                top: 0;
                animation: animloader 2s linear infinite;
            }

            #loader::after {
                animation-delay: 1s;
            }

            @keyframes animloader {
                0% {
                    transform: scale(0);
                    opacity: 1;
                }

                100% {
                    transform: scale(1);
                    opacity: 0;
                }
            }
        </style>
        <div id="roverlay" style="display: none;"></div>

        <div id="filtered-products">
            <!-- AJAX results will be displayed here -->
        </div>

    <?php
    return ob_get_clean();
}
add_shortcode('wcapf_product_filter', 'dapfforwc_product_filter_shortcode');

function dapfforwc_normalize_filter_values($values)
{
    $normalized = [];

    foreach ((array) $values as $value) {
        $value = sanitize_title((string) $value);
        if ('' !== $value) {
            $normalized[] = $value;
        }
    }

    return array_values(array_unique($normalized));
}

function dapfforwc_get_request_filter_values($request, $filter_word = 'filters')
{
    $filter_word = trim(sanitize_title((string) $filter_word), '/');
    $filter_word = '' === $filter_word ? 'filters' : $filter_word;
    $filter_values = [];

    if (isset($_GET['filters'])) {
        $raw_filters = sanitize_text_field(wp_unslash($_GET['filters']));
        $filter_values = array_merge($filter_values, preg_split('/[\/,]+/', $raw_filters));
    }

    $request_parts = array_values(array_filter(explode('/', trim((string) $request, '/'))));
    $filter_index = array_search($filter_word, $request_parts, true);
    if (false !== $filter_index) {
        $filter_values = array_merge($filter_values, array_slice($request_parts, $filter_index + 1));
    }

    $filter_values = array_values($filter_values);
    foreach ($filter_values as $index => $value) {
        if ('page' === $value) {
            unset($filter_values[$index], $filter_values[$index + 1]);
        }
    }

    return dapfforwc_normalize_filter_values($filter_values);
}

function dapfforwc_get_constrained_filter_product_ids($base_filters, $selected_filters, $all_data, $product_details, $operator = 'IN')
{
    $base_filters = dapfforwc_normalize_filter_values($base_filters);
    $selected_filters = dapfforwc_normalize_filter_values($selected_filters);
    $selected_filters = array_values(array_diff($selected_filters, $base_filters));

    $base_product_ids = dapfforwc_resolve_filter_product_ids($base_filters, $all_data, $product_details, $operator);

    if (empty($selected_filters)) {
        return $base_product_ids;
    }

    $selected_product_ids = dapfforwc_resolve_filter_product_ids($selected_filters, $all_data, $product_details, $operator);

    if (empty($base_filters)) {
        return $selected_product_ids;
    }

    if (empty($base_product_ids) || empty($selected_product_ids)) {
        return [];
    }

    return array_values(array_intersect($base_product_ids, $selected_product_ids));
}

function dapfforwc_resolve_filter_product_ids($filters, $all_data, $product_details, $operator = 'IN')
{
    $filters = dapfforwc_normalize_filter_values($filters);

    if (empty($filters)) {
        return [];
    }

    $product_groups = [];
    $categories = dapfforwc_resolve_term_group_product_ids($all_data['categories'] ?? [], $filters, $operator);
    $tags = dapfforwc_resolve_term_group_product_ids($all_data['tags'] ?? [], $filters, $operator);
    $attributes = dapfforwc_resolve_attribute_group_product_ids($all_data['attributes'] ?? [], $filters, $operator);
    $ratings = dapfforwc_resolve_rating_product_ids($product_details, $filters);

    foreach ([$categories, $tags, $attributes, $ratings] as $product_ids) {
        if (null !== $product_ids) {
            $product_groups[] = $product_ids;
        }
    }

    if (empty($product_groups)) {
        return [];
    }

    return dapfforwc_combine_product_id_sets($product_groups, 'AND');
}

function dapfforwc_resolve_term_group_product_ids($terms, $filters, $operator = 'IN')
{
    if (empty($terms) || !is_array($terms)) {
        return null;
    }

    $product_sets = [];
    foreach ($terms as $term) {
        if (!is_array($term) || empty($term['slug']) || !in_array($term['slug'], $filters, true)) {
            continue;
        }

        $product_sets[] = $term['products'] ?? [];
    }

    if (empty($product_sets)) {
        return null;
    }

    return dapfforwc_combine_product_id_sets($product_sets, $operator);
}

function dapfforwc_resolve_attribute_group_product_ids($attributes, $filters, $operator = 'IN')
{
    if (empty($attributes) || !is_array($attributes)) {
        return null;
    }

    $attribute_sets = [];
    foreach ($attributes as $attribute) {
        if (empty($attribute['terms']) || !is_array($attribute['terms'])) {
            continue;
        }

        $term_sets = [];
        foreach ($attribute['terms'] as $term) {
            if (!is_array($term) || empty($term['slug']) || !in_array($term['slug'], $filters, true)) {
                continue;
            }

            $term_sets[] = $term['products'] ?? [];
        }

        if (!empty($term_sets)) {
            $attribute_sets[] = dapfforwc_combine_product_id_sets($term_sets, $operator);
        }
    }

    if (empty($attribute_sets)) {
        return null;
    }

    return dapfforwc_combine_product_id_sets($attribute_sets, 'AND');
}

function dapfforwc_resolve_rating_product_ids($product_details, $filters)
{
    $ratings = array_values(array_filter($filters, 'is_numeric'));

    if (empty($ratings) || empty($product_details) || !is_array($product_details)) {
        return null;
    }

    $product_ids = [];
    foreach ($product_details as $product) {
        if (!is_array($product) || !isset($product['ID'], $product['rating'])) {
            continue;
        }

        if (in_array((string) $product['rating'], array_map('strval', $ratings), true)) {
            $product_ids[] = $product['ID'];
        }
    }

    return array_values(array_unique(array_map('absint', $product_ids)));
}

function dapfforwc_combine_product_id_sets($product_sets, $operator = 'IN')
{
    $product_sets = array_values(array_filter(array_map(function ($product_ids) {
        return array_values(array_unique(array_map('absint', (array) $product_ids)));
    }, (array) $product_sets)));

    if (empty($product_sets)) {
        return [];
    }

    if ('AND' === strtoupper((string) $operator)) {
        return array_values(array_intersect(...$product_sets));
    }

    return array_values(array_unique(array_merge(...$product_sets)));
}

function dapfforwc_customSort($a, $b)
{
    $dateA = strtotime($a);
    $dateB = strtotime($b);

    if ($dateA && $dateB) {
        return $dateA <=> $dateB;
    }

    if (is_numeric($a) && is_numeric($b)) {
        return $a <=> $b;
    }

    return strcmp($a, $b);
}

function dapfforwc_render_filter_option($title, $value, $checked, $name)
{
    return '<label><input type="checkbox" class="filter-checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($value) . '"' . $checked . '> ' . esc_html($title) . '</label>';
}

function dapfforwc_product_filter_shortcode_single($atts)
{
    $atts = shortcode_atts(['name' => ''], $atts, 'get_terms_by_attribute');

    if (empty($atts['name'])) {
        return '<p style="background:red;text-align: center;color: #fff;">Please provide an attribute slug.</p>';
    }

    return '<form class="rfilterbuttons" id="' . esc_attr($atts['name']) . '"><ul></ul></form>';
}
add_shortcode('wcapf_product_filter_single', 'dapfforwc_product_filter_shortcode_single');


function dapfforwc_get_updated_filters($product_ids)
{
    $product_ids = array_values(array_unique(array_map('absint', (array) $product_ids)));
    $categories = [];
    $attributes = [];
    $tags = [];

    if (!empty($product_ids)) {
        // Get attributes with terms
        $all_data = dapfforwc_get_woocommerce_attributes_with_terms();

        // Extract categories and tags from all_data
        // Categories
        if (is_array($all_data['categories']) || is_object($all_data['categories'])) {
            foreach ($all_data['categories'] as $term_id => $category) {
                if (!empty(array_intersect($product_ids, $category['products']))) {
                    $categories[$term_id] = (object) [
                        'term_id' => $term_id,
                        'name'    => $category['name'],
                        'slug'    => $category['slug'],
                        'taxonomy' => 'product_cat',
                    ];
                }
            }
        }

        // Tags
        if (is_array($all_data['tags']) || is_object($all_data['tags'])) {
            foreach ($all_data['tags'] as $term_id => $tag) {
                if (!empty(array_intersect($product_ids, $tag['products']))) {
                    $tags[$term_id] = (object) [
                        'term_id' => $term_id,
                        'name'    => $tag['name'],
                        'slug'    => $tag['slug'],
                        'taxonomy' => 'product_tag',
                    ];
                }
            }
        }

        // Extract attributes
        if (is_array($all_data['attributes']) || is_object($all_data['attributes'])) {
            foreach ($all_data['attributes'] as $attribute) {
                $attribute_name = $attribute['attribute_name'];
                $terms = $attribute['terms'];

                if (is_array($terms) || is_object($terms)) {
                    foreach ($terms as $term) {
                        // Check if the term's products match the provided product IDs
                        if (!empty(array_intersect($product_ids, $term['products']))) {
                            $attributes[$attribute_name][] = [
                                'term_id' => $term['term_id'],
                                'name'    => $term['name'],
                                'slug'    => $term['slug'],
                            ];
                        }
                    }
                }
            }
        }
    }

    return [
        'categories' => array_values($categories), // Return as array
        'attributes' => $attributes,
        'tags' => array_values($tags), // Return as array
    ];
}

function dapfforwc_get_woocommerce_attributes_with_terms()
{
    global $wpdb;

    $cache_time = 43200; // 12 hours in seconds
    $cached_data = dapfforwc_read_cache('woocommerce_attributes_cache.json', $cache_time);
    if (is_array($cached_data)) {
        return $cached_data;
    }

    $data = ['attributes' => [], 'categories' => [], 'tags' => []];

    // Fetch attributes, categories, tags, and associated product IDs in a single query
    $query = "
        SELECT t.term_id, t.name, t.slug, tr.object_id, tt.taxonomy, a.attribute_name, a.attribute_label
        FROM {$wpdb->prefix}terms AS t
        INNER JOIN {$wpdb->prefix}term_taxonomy AS tt ON t.term_id = tt.term_id
        INNER JOIN {$wpdb->prefix}term_relationships AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
        INNER JOIN {$wpdb->prefix}posts AS p ON p.ID = tr.object_id AND p.post_type = 'product' AND p.post_status = 'publish'
        LEFT JOIN {$wpdb->prefix}woocommerce_attribute_taxonomies AS a ON tt.taxonomy = CONCAT('pa_', a.attribute_name)
        WHERE tt.taxonomy IN ('product_cat', 'product_tag') OR a.attribute_name IS NOT NULL
        ORDER BY tt.taxonomy, t.name;
    ";

    $results = $wpdb->get_results($query, ARRAY_A);

    if (is_array($results) || is_object($results)) {
        foreach ($results as $row) {
            $term_id = $row['term_id'];
            $taxonomy = $row['taxonomy'];

            if ($taxonomy === 'product_cat') {
                if (!isset($data['categories'][$term_id])) {
                    $data['categories'][$term_id] = [
                        'name' => $row['name'],
                        'slug' => $row['slug'],
                        'products' => []
                    ];
                }
                if ($row['object_id']) {
                    $data['categories'][$term_id]['products'][] = $row['object_id'];
                }
            } elseif ($taxonomy === 'product_tag') {
                if (!isset($data['tags'][$term_id])) {
                    $data['tags'][$term_id] = [
                        'name' => $row['name'],
                        'slug' => $row['slug'],
                        'products' => []
                    ];
                }
                if ($row['object_id']) {
                    $data['tags'][$term_id]['products'][] = $row['object_id'];
                }
            } elseif (!empty($row['attribute_name'])) {
                $attr_name = $row['attribute_name'];

                if (!isset($data['attributes'][$attr_name])) {
                    $data['attributes'][$attr_name] = [
                        'attribute_label' => $row['attribute_label'],
                        'attribute_name' => $attr_name,
                        'terms' => []
                    ];
                }

                // Check if the term already exists
                $term_key = array_search($term_id, array_column($data['attributes'][$attr_name]['terms'], 'term_id'));

                if ($term_key === false) {
                    $data['attributes'][$attr_name]['terms'][] = [
                        'term_id'    => $term_id,
                        'name'       => $row['name'],
                        'slug'       => $row['slug'],
                        'products'   => $row['object_id'] ? [$row['object_id']] : [],
                    ];
                } else {
                    if ($row['object_id']) {
                        $data['attributes'][$attr_name]['terms'][$term_key]['products'][] = $row['object_id'];
                    }
                }
            }
        }
    }

    dapfforwc_normalize_filter_lookup_products($data);
    dapfforwc_write_cache('woocommerce_attributes_cache.json', $data);

    return $data;
}

function dapfforwc_get_woocommerce_product_details()
{
    global $wpdb;
    $cache_time = 43200; // 12 hours

    $cached_data = dapfforwc_read_cache('woocommerce_product_details.json', $cache_time);
    if (is_array($cached_data)) {
        return $cached_data;
    }

    // Use batch processing for large datasets
    $batch_size = 1000;
    $offset = 0;
    $products = [];

    // First, get all product IDs efficiently - now ordered by date (post_date)
    $id_query = "
    SELECT ID 
    FROM {$wpdb->prefix}posts 
    WHERE post_type = 'product' AND post_status = 'publish'
    ORDER BY post_date DESC
    ";

    $product_ids = $wpdb->get_col($id_query);

    if (empty($product_ids)) {
        // Save empty result to cache
        dapfforwc_write_cache('woocommerce_product_details.json', ['products' => []]);
        return ['products' => []];
    }

    // Process in batches
    $total_products = count($product_ids);

    while ($offset < $total_products) {
        $batch_ids = array_slice($product_ids, $offset, $batch_size);
        $placeholders = implode(',', array_fill(0, count($batch_ids), '%d'));

        // Main query using prepare for security and performance
        // Maintain the same order as in the ID query
        $query = $wpdb->prepare("
        SELECT p.ID, p.post_title, p.post_name, p.menu_order, p.post_excerpt, p.post_date
        FROM {$wpdb->prefix}posts p
        WHERE p.ID IN ($placeholders)
        ORDER BY p.post_date DESC
        ", $batch_ids);

        $results = $wpdb->get_results($query, ARRAY_A);

        // Get product meta in bulk (more efficient than separate joins)
        $meta_query = $wpdb->prepare("
        SELECT post_id, meta_key, meta_value
        FROM {$wpdb->prefix}postmeta
        WHERE post_id IN ($placeholders)
        AND meta_key IN ('_price', '_sale_price', '_regular_price', '_wc_average_rating', 
                         '_product_type', '_sku', '_stock_status', '_thumbnail_id')
        ", $batch_ids);

        $meta_results = $wpdb->get_results($meta_query, ARRAY_A);

        // Organize meta data by product ID
        $product_meta = [];
        foreach ($meta_results as $meta) {
            $product_meta[$meta['post_id']][$meta['meta_key']] = $meta['meta_value'];
        }

        // Get product categories in bulk
        $term_query = $wpdb->prepare("
        SELECT tr.object_id, t.name, t.slug
        FROM {$wpdb->prefix}term_relationships tr
        JOIN {$wpdb->prefix}term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        JOIN {$wpdb->prefix}terms t ON tt.term_id = t.term_id
        WHERE tr.object_id IN ($placeholders)
        AND tt.taxonomy = 'product_cat'
        ORDER BY t.name ASC
        ", $batch_ids);

        $term_results = $wpdb->get_results($term_query, ARRAY_A);

        // Organize category data by product ID
        $product_categories = [];
        foreach ($term_results as $term) {
            $product_categories[$term['object_id']][] = [
                'name' => $term['name'],
                'slug' => $term['slug']
            ];
        }

        // Get thumbnail URLs in bulk
        $thumbnail_ids = array_filter(array_map(function ($id) use ($product_meta) {
            return isset($product_meta[$id]['_thumbnail_id']) ? $product_meta[$id]['_thumbnail_id'] : null;
        }, $batch_ids));

        $thumbnail_urls = [];
        if (!empty($thumbnail_ids)) {
            $thumb_placeholders = implode(',', array_fill(0, count($thumbnail_ids), '%d'));
            $thumb_query = $wpdb->prepare("
            SELECT ID, guid
            FROM {$wpdb->prefix}posts
            WHERE ID IN ($thumb_placeholders)
            ", $thumbnail_ids);

            $thumb_results = $wpdb->get_results($thumb_query, ARRAY_A);

            foreach ($thumb_results as $thumb) {
                $thumbnail_urls[$thumb['ID']] = $thumb['guid'];
            }
        }

        // Combine all data
        foreach ($results as $row) {
            $product_id = $row['ID'];
            $meta = $product_meta[$product_id] ?? [];

            // Determine product type and pricing
            $product_type = $meta['_product_type'] ?? 'simple';
            $price = $meta['_price'] ?? '';
            $sale_price = $meta['_sale_price'] ?? '';
            $sale_active = !empty($sale_price) && $sale_price == $price;

            // Get rating
            $rating = isset($meta['_wc_average_rating']) ? floatval($meta['_wc_average_rating']) : 0;

            // Get product thumbnail
            $thumbnail_id = $meta['_thumbnail_id'] ?? '';
            $thumbnail_url = isset($thumbnail_urls[$thumbnail_id]) ? $thumbnail_urls[$thumbnail_id] : '';

            // Add product to array
            $products[$product_id] = [
                'ID' => $product_id,
                'post_title' => $row['post_title'],
                'post_name' => $row['post_name'],
                'price' => $price,
                'rating' => $rating,
                'post_modified' => $row['post_date'],
                'post_date' => $row['post_date'],
                'menu_order' => intval($row['menu_order']),
                'on_sale' => $sale_active,
                'product_image' => $thumbnail_url,
                'product_excerpt' => $row['post_excerpt'],
                'product_sku' => $meta['_sku'] ?? '',
                'product_stock' => $meta['_stock_status'] ?? 'instock',
                'product_category' => $product_categories[$product_id] ?? [],
            ];
        }

        // Move to next batch
        $offset += $batch_size;
    }

    // Convert to indexed array for better JSON compatibility
    $product_data = ['products' => $products];

    dapfforwc_write_cache('woocommerce_product_details.json', $product_data);

    return $product_data;
}

function dapfforwc_normalize_filter_lookup_products(&$data)
{
    foreach (['categories', 'tags'] as $group) {
        if (empty($data[$group]) || !is_array($data[$group])) {
            continue;
        }

        foreach ($data[$group] as $term_id => $term) {
            $data[$group][$term_id]['products'] = array_values(array_unique(array_map('absint', $term['products'] ?? [])));
        }
    }

    if (empty($data['attributes']) || !is_array($data['attributes'])) {
        return;
    }

    foreach ($data['attributes'] as $attribute_name => $attribute) {
        if (empty($attribute['terms']) || !is_array($attribute['terms'])) {
            continue;
        }

        foreach ($attribute['terms'] as $term_index => $term) {
            $data['attributes'][$attribute_name]['terms'][$term_index]['products'] = array_values(array_unique(array_map('absint', $term['products'] ?? [])));
        }
    }
}

function dapfforwc_filter_renderable_product_ids($product_ids, &$product_details_json = null)
{
    $product_ids = array_values(array_unique(array_filter(array_map('absint', (array) $product_ids))));

    if (null === $product_details_json) {
        $product_details_json = dapfforwc_get_woocommerce_product_details()["products"] ?? [];
    }

    $product_details_json = is_array($product_details_json) ? $product_details_json : [];
    $known_ids = array_map('absint', array_keys($product_details_json));
    $missing_product_ids = array_diff($product_ids, $known_ids);

    if (!empty($missing_product_ids) && function_exists('wc_get_products')) {
        $missing_products = wc_get_products([
            'include' => array_values($missing_product_ids),
            'status' => 'publish',
            'limit' => -1,
        ]);

        foreach ($missing_products as $product) {
            if (!is_a($product, 'WC_Product')) {
                continue;
            }

            $modified = $product->get_date_modified();
            $image_id = $product->get_image_id();
            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail') : '';
            $categories = [];
            $product_terms = wp_get_post_terms($product->get_id(), 'product_cat');
            if (!is_wp_error($product_terms)) {
                foreach ($product_terms as $product_term) {
                    $categories[] = [
                        'name' => $product_term->name,
                        'slug' => $product_term->slug,
                    ];
                }
            }

            $product_details_json[$product->get_id()] = [
                'ID' => $product->get_id(),
                'post_title' => $product->get_title(),
                'post_name' => $product->get_slug(),
                'price' => $product->get_price(),
                'product_image' => $image_url,
                'product_excerpt' => $product->get_short_description(),
                'rating' => $product->get_average_rating(),
                'product_category' => $categories,
                'product_sku' => $product->get_sku(),
                'product_stock' => $product->get_stock_quantity(),
                'on_sale' => $product->is_on_sale(),
                'menu_order' => $product->get_menu_order(),
                'post_modified' => $modified ? $modified->date('Y-m-d H:i:s') : '',
            ];
        }
    }

    return array_values(array_filter($product_ids, function ($product_id) use ($product_details_json) {
        return isset($product_details_json[$product_id]);
    }));
}


function dapfforwc_get_shortcode_attributes_from_page($content, $shortcode)
{
    $shortcodes = array_filter(array_map('trim', explode(',', (string) $shortcode)));
    foreach (['latest_products_table_with_custom_sort', 'latest_products_table', 'products'] as $fallback_shortcode) {
        if (!in_array($fallback_shortcode, $shortcodes, true)) {
            $shortcodes[] = $fallback_shortcode;
        }
    }

    $attributes_list = [];
    foreach ($shortcodes as $single_shortcode) {
        preg_match_all('/\[' . preg_quote($single_shortcode, '/') . '([^]]*)\]/', $content, $matches);

        foreach ($matches[1] as $shortcode_instance) {
            $shortcode_instance = trim($shortcode_instance);
            $parsed_atts = shortcode_parse_atts($shortcode_instance);
            if (is_array($parsed_atts)) {
                $attributes_list[] = $parsed_atts;
            }
        }
    }

    return $attributes_list;
}
