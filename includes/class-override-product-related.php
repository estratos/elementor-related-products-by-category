<?php
namespace ElementorPro\Modules\Woocommerce\Widgets;

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Typography;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Verificar que la clase base existe
if ( ! class_exists( 'ElementorPro\Modules\Woocommerce\Widgets\Products_Base' ) ) {
    // Intentar cargar manualmente el archivo de la clase base
    $base_files = [
        WP_PLUGIN_DIR . '/elementor-pro/modules/woocommerce/widgets/products.php',
        WP_PLUGIN_DIR . '/elementor-pro/modules/woocommerce/widgets/products-base.php',
    ];
    
    $base_loaded = false;
    foreach ( $base_files as $base_file ) {
        if ( file_exists( $base_file ) ) {
            require_once $base_file;
            $base_loaded = true;
            break;
        }
    }
    
    if ( ! $base_loaded ) {
        // Si no se puede cargar la clase base, crear una versión alternativa
        return;
    }
}

/**
 * Widget personalizado de Productos Relacionados por Categoría
 */
class Product_Related_Custom extends Products_Base {
    
    public function get_name() {
        return 'woocommerce-product-related-custom';
    }
    
    public function get_title() {
        return esc_html__( 'Related Products by Category', 'elementor-pro' );
    }
    
    public function get_icon() {
        return 'eicon-product-related';
    }
    
    public function get_categories() {
        return [ 'woocommerce-elements' ];
    }
    
    public function get_keywords() {
        return [ 'woocommerce', 'shop', 'store', 'related', 'category', 'product' ];
    }
    
    protected function register_controls() {
        $this->start_controls_section(
            'section_related_products_content',
            [
                'label' => esc_html__( 'Related Products by Category', 'elementor-related-products-by-category' ),
            ]
        );
        
        $this->add_control(
            'posts_per_page',
            [
                'label' => esc_html__( 'Products Per Page', 'elementor-pro' ),
                'type' => Controls_Manager::NUMBER,
                'default' => 4,
                'min' => 1,
                'max' => 20,
            ]
        );
        
        $this->add_responsive_control(
            'columns',
            [
                'label' => esc_html__( 'Columns', 'elementor-pro' ),
                'type' => Controls_Manager::NUMBER,
                'prefix_class' => 'elementor-grid%s-',
                'min' => 1,
                'max' => 12,
                'default' => 4,
                'required' => true,
                'device_args' => [
                    Controls_Manager::TABLET => [
                        'default' => 2,
                        'max' => 8,
                    ],
                    Controls_Manager::MOBILE => [
                        'default' => 1,
                        'max' => 4,
                    ],
                ],
                'min_affected_device' => [
                    Controls_Manager::TABLET => 2,
                    Controls_Manager::MOBILE => 1,
                ],
            ]
        );
        
        $this->add_control(
            'orderby',
            [
                'label' => esc_html__( 'Order By', 'elementor-pro' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'date',
                'options' => [
                    'date' => esc_html__( 'Date', 'elementor-pro' ),
                    'title' => esc_html__( 'Title', 'elementor-pro' ),
                    'price' => esc_html__( 'Price', 'elementor-pro' ),
                    'popularity' => esc_html__( 'Popularity', 'elementor-pro' ),
                    'rating' => esc_html__( 'Rating', 'elementor-pro' ),
                    'rand' => esc_html__( 'Random', 'elementor-pro' ),
                    'menu_order' => esc_html__( 'Menu Order', 'elementor-pro' ),
                ],
            ]
        );
        
        $this->add_control(
            'order',
            [
                'label' => esc_html__( 'Order', 'elementor-pro' ),
                'type' => Controls_Manager::SELECT,
                'default' => 'desc',
                'options' => [
                    'asc' => esc_html__( 'ASC', 'elementor-pro' ),
                    'desc' => esc_html__( 'DESC', 'elementor-pro' ),
                ],
            ]
        );
        
        $this->add_control(
            'include_subcategories',
            [
                'label' => esc_html__( 'Include Subcategories', 'elementor-related-products-by-category' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Yes', 'elementor-related-products-by-category' ),
                'label_off' => esc_html__( 'No', 'elementor-related-products-by-category' ),
                'default' => 'yes',
                'return_value' => 'yes',
            ]
        );
        
        $this->end_controls_section();
        
        // Secciones de estilo heredadas de Products_Base
        $this->register_design_controls();
    }
    
    protected function render() {
        global $product;
        
        $product = wc_get_product();
        
        if ( ! $product ) {
            // Intentar obtener el producto del contexto actual
            $product_id = get_the_ID();
            if ( 'product' === get_post_type( $product_id ) ) {
                $product = wc_get_product( $product_id );
            }
            
            if ( ! $product ) {
                echo '<p>' . esc_html__( 'No product found', 'elementor-related-products-by-category' ) . '</p>';
                return;
            }
        }
        
        $settings = $this->get_settings_for_display();
        
        // Obtener productos relacionados por categoría
        $related_products = $this->get_related_products_by_category( $product, $settings );
        
        if ( empty( $related_products ) ) {
            return;
        }
        
        // Configurar argumentos para la plantilla
        $args = [
            'posts_per_page' => ! empty( $settings['posts_per_page'] ) ? absint( $settings['posts_per_page'] ) : 4,
            'columns' => ! empty( $settings['columns'] ) ? absint( $settings['columns'] ) : 4,
            'orderby' => ! empty( $settings['orderby'] ) ? $settings['orderby'] : 'date',
            'order' => ! empty( $settings['order'] ) ? $settings['order'] : 'desc',
            'related_products' => $related_products,
        ];
        
        // Forzar el número de columnas con clases CSS
        $columns_class = 'columns-' . $args['columns'];
        
        ?>
        <section class="related products">
            
            <?php
            $heading = apply_filters( 'woocommerce_product_related_products_heading', __( 'Related products', 'woocommerce' ) );
            
            if ( $heading ) :
                ?>
                <h2><?php echo esc_html( $heading ); ?></h2>
            <?php endif; ?>
            
            <ul class="products <?php echo esc_attr( $columns_class ); ?>">
                <?php
                foreach ( $related_products as $related_product ) :
                    $post_object = get_post( $related_product->get_id() );
                    setup_postdata( $GLOBALS['post'] =& $post_object ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.PHP.DisallowMultipleAssignments.Found
                    
                    wc_get_template_part( 'content', 'product' );
                endforeach;
                wp_reset_postdata();
                ?>
            </ul>
        </section>
        <?php
    }
    
    /**
     * Obtiene productos relacionados por categoría
     */
    private function get_related_products_by_category( $product, $settings ) {
        $product_id = $product->get_id();
        
        // Obtener categorías del producto
        $category_ids = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
        
        if ( empty( $category_ids ) ) {
            return [];
        }
        
        // Incluir subcategorías si está configurado
        if ( 'yes' === $settings['include_subcategories'] ) {
            $all_category_ids = $category_ids;
            foreach ( $category_ids as $category_id ) {
                $child_categories = get_term_children( $category_id, 'product_cat' );
                if ( ! is_wp_error( $child_categories ) && ! empty( $child_categories ) ) {
                    $all_category_ids = array_merge( $all_category_ids, $child_categories );
                }
            }
            $category_ids = array_unique( $all_category_ids );
        }
        
        // Argumentos de consulta
        $args = [
            'post_type'           => 'product',
            'post_status'         => 'publish',
            'posts_per_page'      => ! empty( $settings['posts_per_page'] ) ? absint( $settings['posts_per_page'] ) + 1 : 5,
            'post__not_in'        => [ $product_id ],
            'orderby'             => ! empty( $settings['orderby'] ) ? $settings['orderby'] : 'date',
            'order'               => ! empty( $settings['order'] ) ? $settings['order'] : 'desc',
            'tax_query'           => [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'term_id',
                    'terms'    => $category_ids,
                    'operator' => 'IN',
                ],
            ],
            'meta_query'          => [],
        ];
        
        // Manejar ordenamientos especiales
        switch ( $args['orderby'] ) {
            case 'price':
                $args['meta_key'] = '_price';
                $args['orderby'] = 'meta_value_num';
                break;
            case 'popularity':
                $args['meta_key'] = 'total_sales';
                $args['orderby'] = 'meta_value_num';
                break;
            case 'rating':
                $args['meta_key'] = '_wc_average_rating';
                $args['orderby'] = 'meta_value_num';
                break;
            case 'rand':
                $args['orderby'] = 'rand';
                break;
        }
        
        // Excluir productos ocultos
        $args['tax_query'][] = [
            'taxonomy' => 'product_visibility',
            'field'    => 'name',
            'terms'    => [ 'exclude-from-catalog', 'exclude-from-search' ],
            'operator' => 'NOT IN',
        ];
        
        // Solo productos en stock
        $args['meta_query'][] = [
            'key'     => '_stock_status',
            'value'   => 'outofstock',
            'compare' => '!=',
        ];
        
        // Ejecutar consulta
        $products_query = new \WP_Query( $args );
        $related_products = [];
        
        if ( $products_query->have_posts() ) {
            foreach ( $products_query->posts as $post ) {
                $related_product = wc_get_product( $post );
                if ( $related_product && $related_product->is_visible() ) {
                    $related_products[] = $related_product;
                    
                    // Limitar al número solicitado
                    if ( count( $related_products ) >= $settings['posts_per_page'] ) {
                        break;
                    }
                }
            }
            wp_reset_postdata();
        }
        
        return $related_products;
    }
    
    /**
     * Registrar controles de diseño heredados
     */
    private function register_design_controls() {
        // Sección de diseño de productos
        $this->start_controls_section(
            'section_design_box',
            [
                'label' => esc_html__( 'Box', 'elementor-pro' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'box_border_width',
            [
                'label' => esc_html__( 'Border Width', 'elementor-pro' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                'selectors' => [
                    '{{WRAPPER}} .product' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'box_border_radius',
            [
                'label' => esc_html__( 'Border Radius', 'elementor-pro' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                'selectors' => [
                    '{{WRAPPER}} .product' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'box_padding',
            [
                'label' => esc_html__( 'Padding', 'elementor-pro' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                'selectors' => [
                    '{{WRAPPER}} .product' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Sección de imagen
        $this->start_controls_section(
            'section_design_image',
            [
                'label' => esc_html__( 'Image', 'elementor-pro' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'image_border',
                'selector' => '{{WRAPPER}} .product img',
            ]
        );
        
        $this->add_control(
            'image_border_radius',
            [
                'label' => esc_html__( 'Border Radius', 'elementor-pro' ),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', '%', 'em', 'rem', 'custom' ],
                'selectors' => [
                    '{{WRAPPER}} .product img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->end_controls_section();
        
        // Sección de contenido
        $this->start_controls_section(
            'section_design_content',
            [
                'label' => esc_html__( 'Content', 'elementor-pro' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );
        
        $this->add_control(
            'heading_title_style',
            [
                'label' => esc_html__( 'Title', 'elementor-pro' ),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'title_color',
            [
                'label' => esc_html__( 'Color', 'elementor-pro' ),
                'type' => Controls_Manager::COLOR,
                'global' => [
                    'default' => Global_Colors::COLOR_PRIMARY,
                ],
                'selectors' => [
                    '{{WRAPPER}} .woocommerce-loop-product__title' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'global' => [
                    'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
                ],
                'selector' => '{{WRAPPER}} .woocommerce-loop-product__title',
            ]
        );
        
        $this->add_control(
            'heading_price_style',
            [
                'label' => esc_html__( 'Price', 'elementor-pro' ),
                'type' => Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );
        
        $this->add_control(
            'price_color',
            [
                'label' => esc_html__( 'Color', 'elementor-pro' ),
                'type' => Controls_Manager::COLOR,
                'global' => [
                    'default' => Global_Colors::COLOR_PRIMARY,
                ],
                'selectors' => [
                    '{{WRAPPER}} .price' => 'color: {{VALUE}}',
                ],
            ]
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'price_typography',
                'global' => [
                    'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
                ],
                'selector' => '{{WRAPPER}} .price',
            ]
        );
        
        $this->end_controls_section();
    }
}
