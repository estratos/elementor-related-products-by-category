<?php
/**
 * Plugin Name: Elementor Related Products - Same Category (STRICT)
 * Plugin URI: https://tusitio.com
 * Description: Filtra productos relacionados SOLO por primera categoría, EXCLUYENDO tags y otras taxonomías
 * Version: 1.0.2
 * Author: Tu Nombre
 * License: GPL v2 or later
 */

// Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Elementor_Related_Same_Category_Strict {
    
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
            add_action( 'admin_notices', [ $this, 'woocommerce_missing_notice' ] );
            return;
        }
        
        // Aplicar nuestros filtros con alta prioridad
        add_filter( 'woocommerce_product_related_posts_query', [ $this, 'filter_related_query_strict' ], 9999, 2 );
        add_filter( 'woocommerce_related_products', [ $this, 'filter_related_results_strict' ], 9999, 3 );
        
        // Limpiar caché de productos relacionados
        add_action( 'save_post_product', [ $this, 'clear_related_transients' ], 10, 1 );
    }
    
    /**
     * Notifica si WooCommerce no está activo
     */
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p><strong>Elementor Related Products - Same Category:</strong> Este plugin requiere WooCommerce. Por favor, instala y activa WooCommerce.</p></div>';
    }
    
    /**
     * Limpia transitorios cuando se guarda un producto
     */
    public function clear_related_transients( $post_id ) {
        delete_transient( 'wc_related_' . $post_id );
    }
    
    /**
     * Filtra la consulta SQL de productos relacionados - VERSIÓN ESTRICTA
     * SOLO productos de la misma categoría principal
     */
    public function filter_related_query_strict( $query, $product_id ) {
        // Solo aplicar en el frontend
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return $query;
        }
        
        // Obtener la PRIMERA categoría del producto
        $product_categories = wp_get_post_terms( $product_id, 'product_cat', [ 
            'fields' => 'ids',
            'orderby' => 'term_order',
            'order' => 'ASC'
        ] );
        
        // Si no tiene categorías, devolver consulta vacía
        if ( empty( $product_categories ) || is_wp_error( $product_categories ) ) {
            global $wpdb;
            return "SELECT ID FROM {$wpdb->posts} WHERE 1=0";
        }
        
        // Tomar solo la PRIMERA categoría (la principal)
        $first_category_id = reset( $product_categories );
        
        global $wpdb;
        
        // CONSULTA ESTRICTA: Solo productos de ESTA categoría específica
        // EXCLUYE productos que puedan estar relacionados por otras razones
        $strict_query = $wpdb->prepare(
            "SELECT DISTINCT p.ID 
            FROM {$wpdb->posts} p 
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id 
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
            WHERE p.post_type = 'product' 
            AND p.post_status = 'publish' 
            AND p.ID != %d 
            AND tt.taxonomy = 'product_cat' 
            AND tt.term_id = %d 
            
            -- Asegurar que el producto esté en esta categoría
            AND p.ID IN (
                SELECT tr2.object_id 
                FROM {$wpdb->term_relationships} tr2 
                INNER JOIN {$wpdb->term_taxonomy} tt2 ON tr2.term_taxonomy_id = tt2.term_taxonomy_id 
                WHERE tt2.taxonomy = 'product_cat' 
                AND tt2.term_id = %d
            )
            
            ORDER BY RAND() 
            LIMIT 30",
            $product_id,
            $first_category_id,
            $first_category_id
        );
        
        return $strict_query;
    }
    
    /**
     * Filtro adicional ESTRICTO para asegurar que solo productos de la misma categoría
     * Este filtro es más agresivo y elimina cualquier producto que haya llegado por tags
     */
    public function filter_related_results_strict( $related_ids, $product_id, $args ) {
        // Solo aplicar en el frontend
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return $related_ids;
        }
        
        // Si no hay IDs, devolver vacío
        if ( empty( $related_ids ) ) {
            return [];
        }
        
        // Obtener la PRIMERA categoría del producto actual
        $product_categories = wp_get_post_terms( $product_id, 'product_cat', [ 
            'fields' => 'ids',
            'orderby' => 'term_order',
            'order' => 'ASC'
        ] );
        
        if ( empty( $product_categories ) || is_wp_error( $product_categories ) ) {
            return [];
        }
        
        $first_category_id = reset( $product_categories );
        
        // FILTRADO ESTRICTO
        $strictly_filtered_ids = [];
        $posts_per_page = isset( $args['posts_per_page'] ) ? $args['posts_per_page'] : 4;
        
        foreach ( $related_ids as $related_id ) {
            // Verificar que el producto relacionado esté en la MISMA categoría
            if ( has_term( $first_category_id, 'product_cat', $related_id ) ) {
                $strictly_filtered_ids[] = $related_id;
            }
            
            // Si ya tenemos suficientes, salir
            if ( count( $strictly_filtered_ids ) >= $posts_per_page ) {
                break;
            }
        }
        
        // 🔥 NUEVA MEJORA: Si no hay suficientes productos relacionados
        // Buscar más productos de la MISMA categoría para completar
        if ( count( $strictly_filtered_ids ) < $posts_per_page ) {
            $additional_products = $this->get_products_from_same_category(
                $product_id,
                $first_category_id,
                $posts_per_page - count( $strictly_filtered_ids ),
                $strictly_filtered_ids
            );
            
            $strictly_filtered_ids = array_merge( $strictly_filtered_ids, $additional_products );
            $strictly_filtered_ids = array_slice( $strictly_filtered_ids, 0, $posts_per_page );
        }
        
        return $strictly_filtered_ids;
    }
    
    /**
     * Obtiene productos adicionales de la misma categoría cuando no hay suficientes relacionados
     */
    private function get_products_from_same_category( $exclude_product_id, $category_id, $limit = 4, $already_included = [] ) {
        $exclude_ids = array_merge( [ $exclude_product_id ], $already_included );
        
        $args = [
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $limit * 2, // Tomar el doble por si hay exclusiones
            'post__not_in'   => $exclude_ids,
            'orderby'        => 'rand',
            'tax_query'      => [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $category_id,
                    'operator' => 'IN',
                ]
            ],
            'fields' => 'ids',
        ];
        
        $products = get_posts( $args );
        
        // Filtrar para asegurar que están en la categoría exacta
        $filtered_products = [];
        foreach ( $products as $product_id ) {
            if ( has_term( $category_id, 'product_cat', $product_id ) ) {
                $filtered_products[] = $product_id;
            }
            if ( count( $filtered_products ) >= $limit ) {
                break;
            }
        }
        
        return $filtered_products;
    }
    
    /**
     * Función para debug (solo administradores)
     */
    public static function debug_info() {
        if ( current_user_can( 'administrator' ) && is_product() ) {
            global $product;
            
            if ( ! $product ) {
                $product = wc_get_product( get_the_ID() );
            }
            
            if ( $product ) {
                $product_id = $product->get_id();
                
                // Obtener categorías
                $categories = wp_get_post_terms( $product_id, 'product_cat', [ 
                    'fields' => 'names',
                    'orderby' => 'term_order',
                    'order' => 'ASC'
                ] );
                
                // Obtener tags
                $tags = wp_get_post_terms( $product_id, 'product_tag', [ 'fields' => 'names' ] );
                
                // Obtener productos relacionados actuales
                $related_ids = wc_get_related_products( $product_id, 10 );
                
                // Verificar categoría de cada producto relacionado
                $related_details = [];
                foreach ( $related_ids as $related_id ) {
                    $related_product = wc_get_product( $related_id );
                    $related_cats = wp_get_post_terms( $related_id, 'product_cat', [ 'fields' => 'names' ] );
                    $related_details[] = [
                        'id'   => $related_id,
                        'name' => $related_product ? $related_product->get_name() : 'N/A',
                        'cats' => ! empty( $related_cats ) ? implode( ', ', $related_cats ) : 'Sin categoría',
                    ];
                }
                
                ob_start();
                ?>
                <div style="background:#f0f0f0;padding:15px;margin:15px 0;border:2px solid #cc0000;border-radius:5px;font-family:monospace;font-size:12px;">
                    <h4 style="margin-top:0;color:#cc0000;">🔍 DEBUG - Related Products Same Category (STRICT)</h4>
                    
                    <div style="margin-bottom:10px;">
                        <strong>Producto Actual:</strong><br>
                        ID: <?php echo $product_id; ?><br>
                        Nombre: <?php echo $product->get_name(); ?><br>
                        <strong>Categorías (orden):</strong> <?php echo ! empty( $categories ) ? implode( ' → ', $categories ) : 'Sin categorías'; ?><br>
                        <strong>Categoría Principal:</strong> <?php echo ! empty( $categories ) ? reset( $categories ) : 'N/A'; ?><br>
                        <strong>Tags:</strong> <?php echo ! empty( $tags ) ? implode( ', ', $tags ) : 'Sin tags'; ?>
                    </div>
                    
                    <div style="margin-bottom:10px;">
                        <strong>Productos Relacionados (<?php echo count( $related_ids ); ?>):</strong>
                        <?php if ( ! empty( $related_details ) ) : ?>
                            <table style="width:100%;border-collapse:collapse;margin-top:5px;">
                                <tr style="background:#ddd;">
                                    <th style="text-align:left;padding:5px;border:1px solid #999;">ID</th>
                                    <th style="text-align:left;padding:5px;border:1px solid #999;">Producto</th>
                                    <th style="text-align:left;padding:5px;border:1px solid #999;">Categorías</th>
                                </tr>
                                <?php foreach ( $related_details as $detail ) : ?>
                                <tr style="background:#fff;">
                                    <td style="padding:5px;border:1px solid #ccc;"><?php echo $detail['id']; ?></td>
                                    <td style="padding:5px;border:1px solid #ccc;"><?php echo esc_html( $detail['name'] ); ?></td>
                                    <td style="padding:5px;border:1px solid #ccc;"><?php echo $detail['cats']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        <?php else : ?>
                            <p>No hay productos relacionados.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div style="color:#666;font-size:11px;">
                        <strong>Nota:</strong> Este widget muestra SOLO productos que comparten la PRIMERA categoría.<br>
                        Productos relacionados por tags u otras taxonomías son EXCLUIDOS.
                    </div>
                </div>
                <?php
                echo ob_get_clean();
            }
        }
    }
}

// Inicializar el plugin
add_action( 'plugins_loaded', function() {
    Elementor_Related_Same_Category_Strict::get_instance();
} );

// Shortcode para debug
add_shortcode( 'related_debug_strict', function() {
    if ( current_user_can( 'administrator' ) && is_product() ) {
        ob_start();
        Elementor_Related_Same_Category_Strict::debug_info();
        return ob_get_clean();
    }
    return '';
} );

// Agregar enlace en plugins
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function( $links ) {
    $debug_link = '<a href="#" onclick="alert(\'Use shortcode [related_debug_strict] en una página de producto para ver información de debug.\'); return false;">' . esc_html__( 'Debug Info', 'elementor-related-same-category-strict' ) . '</a>';
    array_unshift( $links, $debug_link );
    return $links;
} );
