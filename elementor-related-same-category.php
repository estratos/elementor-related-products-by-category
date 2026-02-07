<?php
/**
 * Plugin Name: Elementor Related Products - Same Category (STRICT)
 * Plugin URI: https://estratos.top
 * Description: Fuerza los productos relacionados de WooCommerce a usar únicamente la categoría principal del producto (compatible con Elementor Pro).
 * Version: 1.0.0
 * Author: Estratos
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Reemplaza los productos relacionados de WooCommerce
 * usando SOLO la categoría principal del producto actual.
 */
add_filter( 'woocommerce_related_products', function ( $related_ids, $product_id, $args ) {

    // Seguridad básica
    if ( ! function_exists( 'wc_get_product' ) ) {
        return $related_ids;
    }

    // Obtener categorías del producto
    $cats = wp_get_post_terms( $product_id, 'product_cat', [
        'fields'  => 'ids',
        'orderby' => 'term_order',
        'order'   => 'ASC',
    ]);

    if ( empty( $cats ) || is_wp_error( $cats ) ) {
        return $related_ids;
    }

    // Categoría principal
    $primary_cat = (int) reset( $cats );

    // Query controlada
    $query = new WP_Query([
        'post_type'      => 'product',
        'posts_per_page' => 4,
        'post__not_in'   => [ $product_id ],
        'tax_query'      => [
            [
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => [ $primary_cat ],
            ],
        ],
        'meta_query' => WC()->query->get_meta_query(),
        'orderby'    => 'rand',
        'fields'     => 'ids',
    ]);

    // Fallback seguro
    if ( empty( $query->posts ) ) {
        return $related_ids;
    }

    return $query->posts;

}, 20, 3 );
