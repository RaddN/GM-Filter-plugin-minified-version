<?php

if (!defined('ABSPATH')) {
    exit;
}
function dapfforwc_product_filter_shortcode($atts)
{
    global $post, $wcapf_options, $dapfforwc_advance_settings, $wp;
    $dapfforwc_slug = isset($post) ? dapfforwc_get_full_slug(get_the_ID()) : "";
    $request = $wp->request;
    $shortcode = $dapfforwc_advance_settings["product_shortcode"] ?? 'products'; // Shortcode to search for
    $attributes_list = dapfforwc_get_shortcode_attributes_from_page($post->post_content ?? "", $shortcode);
    foreach ($attributes_list as $attributes) {
        // Ensure that the 'category', 'attribute', and 'terms' keys exist
        $arrayCata = isset($attributes['category']) ? array_map('trim', explode(",", $attributes['category'])) : [];
        $tagValue = isset($attributes['tags']) ? array_map('trim', explode(",", $attributes['tags'])) : [];
        $termsValue = isset($attributes['terms']) ? array_map('trim', explode(",", $attributes['terms'])) : [];
        $filters = !empty($arrayCata) ? $arrayCata : (!empty($tagValue) ? $tagValue : $termsValue);


        // Use the combined full slug as the key in default_filters
        $wcapf_options['default_filters'][$dapfforwc_slug] = $filters;
        $wcapf_options['product_show_settings'][$dapfforwc_slug] = [
            'per_page'        => $attributes['limit'] ?? $attributes['per_page'] ?? '',
            'orderby'         => $attributes['orderby'] ?? '',
            'order'           => $attributes['order'] ?? '',
            'operator_second' => $attributes['terms_operator'] ?? $attributes['tag_operator'] ?? $attributes['cat_operator'] ?? 'IN'
        ];
    }
    update_option('wcapf_options', $wcapf_options);
    $second_operator = strtoupper($wcapf_options["product_show_settings"][$dapfforwc_slug]["operator_second"] ?? "IN");
    $request_values = array_values(explode('/', $request));
    $default_filter = array_values(array_merge(
        isset($wcapf_options["default_filters"][$dapfforwc_slug]) && is_array($wcapf_options["default_filters"][$dapfforwc_slug]) ? $wcapf_options["default_filters"][$dapfforwc_slug] : [],
        isset($request_values) && is_array($request_values) ? $request_values : []
    ));
    $ratings = array_values(array_filter($default_filter, 'is_numeric'));

    $atts = shortcode_atts([
        'attribute' => '',
        'terms' => '',
        'category' => '',
        'tag' => '',
        'product_selector' => '',
        'pagination_selector' => ''
    ], $atts);
    $product_details = array_values(dapfforwc_get_woocommerce_product_details()["products"] ?? []);
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

    $updated_filters = dapfforwc_get_updated_filters($products_ids, $all_data) ?? [];
    ob_start();
?>
    <form id="product-filter" method="POST"
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
                width: 56px;
                height: 56px;
                border-radius: 50%;
                background: conic-gradient(#0000 10%, #474bff);
                -webkit-mask: radial-gradient(farthest-side, #0000 calc(100% - 9px), #000 0);
                animation: spinner-zp9dbg 1s infinite linear;
            }

            @keyframes spinner-zp9dbg {
                to {
                    transform: rotate(1turn);
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
    return '<label><input type="checkbox" class="filter-checkbox" name="' . $name . '[]" value="' . $value . '"' . $checked . '> ' . $title . '</label>';
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

    $cache_file = __DIR__ . '/woocommerce_attributes_cache.json'; // Path to cache file
    $cache_time = 43200; // 12 hours in seconds

    // Check if the cache file exists and is still valid
    if (file_exists($cache_file) && (filemtime($cache_file) > (time() - $cache_time))) {
        // Read the cached data
        return json_decode(file_get_contents($cache_file), true);
    }

    $data = ['attributes' => [], 'categories' => [], 'tags' => []];

    // Fetch attributes, categories, tags, and associated product IDs in a single query
    $query = "
        SELECT t.term_id, t.name, t.slug, tr.object_id, tt.taxonomy, a.attribute_name, a.attribute_label
        FROM {$wpdb->prefix}terms AS t
        INNER JOIN {$wpdb->prefix}term_taxonomy AS tt ON t.term_id = tt.term_id
        LEFT JOIN {$wpdb->prefix}term_relationships AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
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

    // Write the data to the cache file
    file_put_contents($cache_file, json_encode($data));

    return $data;
}

function dapfforwc_get_woocommerce_product_details()
{
    global $wpdb;
    $cache_file = __DIR__ . '/woocommerce_product_details.json';
    $cache_time = 43200; // 12 hours

    // Check and return cache if valid
    if (file_exists($cache_file) && (filemtime($cache_file) > (time() - $cache_time))) {
        $cached_data = file_get_contents($cache_file);
        if ($cached_data) {
            return json_decode($cached_data, true);
        }
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
        file_put_contents($cache_file, json_encode(['products' => []], JSON_UNESCAPED_UNICODE));
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

    // Save to cache with error handling
    if (!is_writable(dirname($cache_file))) {
        error_log('Cache directory is not writable: ' . dirname($cache_file));
    } else {
        file_put_contents($cache_file, json_encode($product_data, JSON_UNESCAPED_UNICODE));
    }

    return $product_data;
}


function dapfforwc_get_shortcode_attributes_from_page($content, $shortcode)
{
    // Use regex to match the shortcode and capture its attributes
    preg_match_all('/\[' . preg_quote($shortcode, '/') . '([^]]*)\]/', $content, $matches);

    $attributes_list = [];
    foreach ($matches[1] as $shortcode_instance) {
        // Clean up the attribute string and parse it
        $shortcode_instance = trim($shortcode_instance);
        $attributes_list[] = shortcode_parse_atts($shortcode_instance);
    }

    return $attributes_list;
}
