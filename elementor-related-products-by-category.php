<?php
/**
 * Plugin Name: Elementor Related Products - Category Filter
 * Plugin URI: https://tusitio.com
 * Description: Filtra productos relacionados por categoría para Elementor Pro
 * Version: 1.0.0
 * Author: Tu Nombre
 * License: GPL v2 or later
 */

// Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Verificar que Elementor Pro y WooCommerce estén activos
add_action( 'admin_init', 'ercf_check_dependencies' );
function ercf_check_dependencies() {
    if ( ! is_plugin_active( 'elementor-pro/elementor-pro.php' ) || ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        add_action( 'admin_notices', 'ercf_missing_dependencies_notice' );
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
}

function ercf_missing_dependencies_notice() {
    ?>
    <div class="notice notice-error is-dismissible">
        <p>
            <strong>Elementor Related Products - Category Filter:</strong> 
            Este plugin requiere <strong>Elementor Pro</strong> y <strong>WooCommerce</strong> para funcionar.
            El plugin ha sido desactivado.
        </p>
    </div>
    <?php
}

// SOLUCIÓN PRINCIPAL: Modificar la consulta directamente
add_filter( 'woocommerce_product_related_posts_query', 'ercf_filter_related_products_by_category', 999, 2 );
function ercf_filter_related_products_by_category( $query, $product_id ) {
    global $wpdb;
    
    // Solo aplicar si estamos en un contexto de Elementor
    if ( ! ercf_is_elementor_context() ) {
        return $query;
    }
    
    // Obtener las categorías del producto actual
    $categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
    
    if ( empty( $categories ) ) {
        // Si no tiene categorías, no mostrar productos relacionados
        return "SELECT ID FROM {$wpdb->posts} WHERE 1=0";
    }
    
    // Construir la consulta SQL para obtener productos de las mismas categorías
    $category_ids = implode( ',', array_map( 'absint', $categories ) );
    
    $new_query = "
        SELECT DISTINCT p.ID
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)
        INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
        WHERE p.post_type = 'product'
        AND p.post_status = 'publish'
        AND p.ID != {$product_id}
        AND tt.taxonomy = 'product_cat'
        AND tt.term_id IN ({$category_ids})
        AND p.ID NOT IN (
            SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_stock_status' AND meta_value = 'outofstock'
        )
        ORDER BY RAND()
        LIMIT 10
    ";
    
    return $new_query;
}

// Función para detectar si estamos en contexto de Elementor
function ercf_is_elementor_context() {
    // Verificar si es una página de Elementor
    if ( isset( $_GET['elementor-preview'] ) ) {
        return true;
    }
    
    // Verificar si es el editor de Elementor
    if ( isset( $_GET['action'] ) && 'elementor' === $_GET['action'] ) {
        return true;
    }
    
    // Verificar por cabeceras HTTP
    if ( ! empty( $_SERVER['HTTP_REFERER'] ) && strpos( $_SERVER['HTTP_REFERER'], 'action=elementor' ) !== false ) {
        return true;
    }
    
    // Verificar si la página actual fue creada con Elementor
    if ( is_singular() ) {
        $post_id = get_the_ID();
        if ( get_post_meta( $post_id, '_elementor_edit_mode', true ) === 'builder' ) {
            return true;
        }
    }
    
    // Verificar widgets de Elementor en la página
    if ( class_exists( 'Elementor\Plugin' ) ) {
        $elementor = Elementor\Plugin::instance();
        
        // Si estamos en modo editor o preview
        if ( $elementor->editor->is_edit_mode() || $elementor->preview->is_preview_mode() ) {
            return true;
        }
        
        // Verificar si hay widgets de Elementor en la página
        $post_id = get_the_ID();
        if ( $post_id ) {
            $document = $elementor->documents->get( $post_id );
            if ( $document && $document->is_built_with_elementor() ) {
                return true;
            }
        }
    }
    
    // Por defecto, asumir que estamos en contexto de Elementor para páginas de productos
    if ( is_product() ) {
        return true;
    }
    
    return false;
}

// Añadir opción en personalizador para activar/desactivar
add_action( 'customize_register', 'ercf_customize_register' );
function ercf_customize_register( $wp_customize ) {
    $wp_customize->add_section( 'ercf_section', array(
        'title'    => __( 'Elementor Related Products', 'ercf' ),
        'priority' => 200,
    ) );
    
    $wp_customize->add_setting( 'ercf_enable_filter', array(
        'default'           => 'yes',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    
    $wp_customize->add_control( 'ercf_enable_filter', array(
        'label'    => __( 'Filtrar productos relacionados por categoría', 'ercf' ),
        'section'  => 'ercf_section',
        'type'     => 'select',
        'choices'  => array(
            'yes' => __( 'Sí (recomendado)', 'ercf' ),
            'no'  => __( 'No', 'ercf' ),
        ),
    ) );
    
    $wp_customize->add_setting( 'ercf_include_subcategories', array(
        'default'           => 'yes',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    
    $wp_customize->add_control( 'ercf_include_subcategories', array(
        'label'    => __( 'Incluir productos de subcategorías', 'ercf' ),
        'section'  => 'ercf_section',
        'type'     => 'select',
        'choices'  => array(
            'yes' => __( 'Sí', 'ercf' ),
            'no'  => __( 'No', 'ercf' ),
        ),
    ) );
}

// Modificar la función principal para respetar las opciones
add_filter( 'woocommerce_product_related_posts_query', 'ercf_enhanced_filter_related_products', 999, 2 );
function ercf_enhanced_filter_related_products( $query, $product_id ) {
    global $wpdb;
    
    // Verificar si el filtro está activado
    $enable_filter = get_theme_mod( 'ercf_enable_filter', 'yes' );
    if ( 'no' === $enable_filter ) {
        return $query;
    }
    
    // Verificar si estamos en contexto de Elementor
    if ( ! ercf_is_elementor_context() ) {
        return $query;
    }
    
    // Obtener categorías del producto
    $categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
    
    if ( empty( $categories ) ) {
        return "SELECT ID FROM {$wpdb->posts} WHERE 1=0";
    }
    
    // Incluir subcategorías si está configurado
    $include_subcategories = get_theme_mod( 'ercf_include_subcategories', 'yes' );
    if ( 'yes' === $include_subcategories ) {
        $all_categories = $categories;
        foreach ( $categories as $category_id ) {
            $child_categories = get_term_children( $category_id, 'product_cat' );
            if ( ! is_wp_error( $child_categories ) && ! empty( $child_categories ) ) {
                $all_categories = array_merge( $all_categories, $child_categories );
            }
        }
        $categories = array_unique( $all_categories );
    }
    
    // Preparar IDs de categorías
    $category_ids = implode( ',', array_map( 'absint', $categories ) );
    
    // Consulta optimizada
    $new_query = $wpdb->prepare( "
        SELECT DISTINCT p.ID
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
        INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_stock_status'
        LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_visibility'
        WHERE p.post_type = 'product'
        AND p.post_status = 'publish'
        AND p.ID != %d
        AND tt.taxonomy = 'product_cat'
        AND tt.term_id IN ({$category_ids})
        AND (pm1.meta_value IS NULL OR pm1.meta_value != 'outofstock')
        AND (pm2.meta_value IS NULL OR pm2.meta_value IN ('visible', 'catalog'))
        ORDER BY RAND()
        LIMIT 20
    ", $product_id );
    
    return $new_query;
}

// Función de debug para desarrolladores
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    add_action( 'wp_footer', 'ercf_debug_info' );
    function ercf_debug_info() {
        if ( current_user_can( 'administrator' ) && is_product() ) {
            $product_id = get_the_ID();
            $categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
            echo '<div style="background:#f0f0f0;padding:10px;margin:10px;border:1px solid #ccc;">';
            echo '<strong>ERC Debug:</strong><br>';
            echo 'Product ID: ' . $product_id . '<br>';
            echo 'Categories: ' . implode( ', ', $categories ) . '<br>';
            echo 'Elementor Context: ' . ( ercf_is_elementor_context() ? 'YES' : 'NO' ) . '<br>';
            echo 'Filter Enabled: ' . get_theme_mod( 'ercf_enable_filter', 'yes' ) . '<br>';
            echo '</div>';
        }
    }
}

// Shortcode para mostrar productos relacionados manualmente
add_shortcode( 'ercf_related_products', 'ercf_related_products_shortcode' );
function ercf_related_products_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'limit' => 4,
        'columns' => 4,
        'product_id' => get_the_ID(),
    ), $atts, 'ercf_related_products' );
    
    if ( ! $atts['product_id'] ) {
        return '';
    }
    
    // Obtener categorías del producto
    $categories = wp_get_post_terms( $atts['product_id'], 'product_cat', array( 'fields' => 'ids' ) );
    
    if ( empty( $categories ) ) {
        return '<p>No hay productos relacionados.</p>';
    }
    
    // Argumentos para la consulta
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => $atts['limit'],
        'post__not_in' => array( $atts['product_id'] ),
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $categories,
                'operator' => 'IN',
            ),
        ),
        'meta_query' => array(
            array(
                'key' => '_stock_status',
                'value' => 'outofstock',
                'compare' => '!=',
            ),
        ),
        'orderby' => 'rand',
    );
    
    $products = new WP_Query( $args );
    
    if ( ! $products->have_posts() ) {
        return '<p>No hay productos relacionados en la misma categoría.</p>';
    }
    
    ob_start();
    
    echo '<div class="ercf-related-products columns-' . esc_attr( $atts['columns'] ) . '">';
    
    woocommerce_product_loop_start();
    
    while ( $products->have_posts() ) {
        $products->the_post();
        wc_get_template_part( 'content', 'product' );
    }
    
    woocommerce_product_loop_end();
    
    echo '</div>';
    
    wp_reset_postdata();
    
    return ob_get_clean();
}

// Agregar CSS básico
add_action( 'wp_enqueue_scripts', 'ercf_enqueue_styles' );
function ercf_enqueue_styles() {
    wp_add_inline_style( 'woocommerce-general', '
        .ercf-related-products ul.products {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -10px;
        }
        .ercf-related-products ul.products li.product {
            padding: 0 10px;
            margin-bottom: 20px;
        }
        .ercf-related-products.columns-1 ul.products li.product { width: 100%; }
        .ercf-related-products.columns-2 ul.products li.product { width: 50%; }
        .ercf-related-products.columns-3 ul.products li.product { width: 33.333%; }
        .ercf-related-products.columns-4 ul.products li.product { width: 25%; }
        .ercf-related-products.columns-5 ul.products li.product { width: 20%; }
        .ercf-related-products.columns-6 ul.products li.product { width: 16.666%; }
    ' );
}

// Agregar información en la página de plugins
add_filter( 'plugin_row_meta', 'ercf_plugin_row_meta', 10, 2 );
function ercf_plugin_row_meta( $links, $file ) {
    if ( plugin_basename( __FILE__ ) === $file ) {
        $row_meta = array(
            'docs' => '<a href="' . esc_url( admin_url( 'customize.php' ) ) . '" aria-label="' . esc_attr__( 'Configuración', 'ercf' ) . '">' . esc_html__( 'Configuración', 'ercf' ) . '</a>',
        );
        return array_merge( $links, $row_meta );
    }
    return $links;
}

// Limpiar cache de transients cuando se actualiza un producto
add_action( 'save_post_product', 'ercf_clear_related_cache', 10, 3 );
function ercf_clear_related_cache( $post_id, $post, $update ) {
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }
    
    // Eliminar transient de productos relacionados
    delete_transient( 'wc_related_' . $post_id );
}
