<?php
/**
 * Plugin Name: Elementor Related Products - Same Category
 * Plugin URI: https://tusitio.com
 * Description: Filtra productos relacionados por primera categoría para Elementor Pro
 * Version: 1.0.0
 * Author: Tu Nombre
 * License: GPL v2 or later
 */

// Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Elementor_Related_Same_Category {
    
    private static $instance = null;
    
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Inicializar solo si WooCommerce está activo
        if ( ! function_exists( 'wc' ) ) {
            return;
        }
        
        // Aplicar nuestro filtro con alta prioridad
        add_filter( 'woocommerce_product_related_posts_query', [ $this, 'filter_related_query' ], 9999, 2 );
        
        // También filtrar los resultados por si acaso
        add_filter( 'woocommerce_related_products', [ $this, 'filter_related_results' ], 9999, 3 );
    }
    
    /**
     * Filtra la consulta SQL de productos relacionados
     */
    public function filter_related_query( $query, $product_id ) {
        // Solo aplicar en el frontend
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return $query;
        }
        
        // Obtener categorías del producto
        $categories = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'ids' ] );
        
        // Si no tiene categorías, devolver consulta vacía
        if ( empty( $categories ) || is_wp_error( $categories ) ) {
            global $wpdb;
            return "SELECT ID FROM {$wpdb->posts} WHERE 1=0";
        }
        
        // Tomar solo la PRIMERA categoría
        $first_category_id = reset( $categories );
        
        // Modificar la consulta para incluir solo esa categoría
        global $wpdb;
        
        return $wpdb->prepare(
            "SELECT DISTINCT p.ID 
            FROM {$wpdb->posts} p 
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id 
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
            WHERE p.post_type = 'product' 
            AND p.post_status = 'publish' 
            AND p.ID != %d 
            AND tt.taxonomy = 'product_cat' 
            AND tt.term_id = %d 
            ORDER BY RAND() 
            LIMIT 20",
            $product_id,
            $first_category_id
        );
    }
    
    /**
     * Filtro adicional para asegurar que solo productos de la misma categoría se muestren
     */
    public function filter_related_results( $related_ids, $product_id, $args ) {
        // Solo aplicar en el frontend
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return $related_ids;
        }
        
        // Si ya no hay IDs, devolver vacío
        if ( empty( $related_ids ) ) {
            return [];
        }
        
        // Obtener la primera categoría del producto actual
        $product_categories = wp_get_post_terms( $product_id, 'product_cat', [ 'fields' => 'ids' ] );
        
        if ( empty( $product_categories ) || is_wp_error( $product_categories ) ) {
            return [];
        }
        
        $first_category_id = reset( $product_categories );
        
        // Filtrar productos que no estén en la misma categoría
        $filtered_ids = [];
        
        foreach ( $related_ids as $related_id ) {
            $related_categories = wp_get_post_terms( $related_id, 'product_cat', [ 'fields' => 'ids' ] );
            
            if ( ! empty( $related_categories ) && ! is_wp_error( $related_categories ) ) {
                if ( in_array( $first_category_id, $related_categories ) ) {
                    $filtered_ids[] = $related_id;
                }
            }
            
            // Limitar al número solicitado
            if ( count( $filtered_ids ) >= $args['posts_per_page'] ) {
                break;
            }
        }
        
        return $filtered_ids;
    }
    
    /**
     * Función para debug (opcional)
     */
    public static function debug_info() {
        if ( current_user_can( 'administrator' ) && is_product() ) {
            $product = wc_get_product();
            if ( $product ) {
                $categories = wp_get_post_terms( $product->get_id(), 'product_cat', [ 'fields' => 'names' ] );
                echo '<div style="background:#f0f0f0;padding:10px;margin:10px;border:1px solid #ccc;">';
                echo '<strong>Related Category Filter Debug:</strong><br>';
                echo 'Product ID: ' . $product->get_id() . '<br>';
                echo 'Categories: ' . implode( ', ', $categories ) . '<br>';
                echo '</div>';
            }
        }
    }
}

// Inicializar el plugin
add_action( 'plugins_loaded', function() {
    Elementor_Related_Same_Category::get_instance();
} );

// Opcional: Agregar shortcode para debug
add_shortcode( 'related_debug', function() {
    if ( current_user_can( 'administrator' ) && is_product() ) {
        ob_start();
        Elementor_Related_Same_Category::debug_info();
        return ob_get_clean();
    }
    return '';
} );

// Opcional: Agregar nota en admin
add_action( 'admin_notices', function() {
    if ( ! function_exists( 'wc' ) ) {
        echo '<div class="notice notice-warning"><p><strong>Elementor Related Products - Same Category:</strong> WooCommerce no está activo. Este plugin requiere WooCommerce.</p></div>';
    }
} );
