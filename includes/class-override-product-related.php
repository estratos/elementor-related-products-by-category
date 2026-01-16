<?php
namespace ElementorPro\Modules\Woocommerce\Widgets;

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Typography;
use ElementorPro\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sobrescribe la clase Product_Related para mostrar productos de la misma categoría
 */
class Product_Related_Override extends Products_Base {

    public function get_name() {
        return 'woocommerce-product-related';
    }

    public function get_title() {
        return esc_html__( 'Product Related', 'elementor-pro' );
    }

    public function get_icon() {
        return 'eicon-product-related';
    }

    public function get_keywords() {
        return [ 'woocommerce', 'shop', 'store', 'related', 'similar', 'product' ];
    }

    public function has_widget_inner_wrapper(): bool {
        return ! Plugin::elementor()->experiments->is_feature_active( 'e_optimized_markup' );
    }

    public function get_style_depends(): array {
        return [ 'widget-woocommerce-products' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_related_products_content',
            [
                'label' => esc_html__( 'Related Products', 'elementor-pro' ),
            ]
        );

        $this->add_control(
            'posts_per_page',
            [
                'label' => esc_html__( 'Products Per Page', 'elementor-pro' ),
                'type' => Controls_Manager::NUMBER,
                'default' => 4,
                'range' => [
                    'px' => [
                        'max' => 20,
                    ],
                ],
            ]
        );

        $this->add_columns_responsive_control();

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

        // Nuevo control para incluir/excluir subcategorías
        $this->add_control(
            'include_subcategories',
            [
                'label' => esc_html__( 'Incluir Subcategorías', 'elementor-related-products-by-category' ),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => esc_html__( 'Sí', 'elementor-related-products-by-category' ),
                'label_off' => esc_html__( 'No', 'elementor-related-products-by-category' ),
                'default' => 'yes',
                'return_value' => 'yes',
                'description' => esc_html__( 'Incluir productos de subcategorías de la categoría actual', 'elementor-related-products-by-category' ),
            ]
        );

        $this->end_controls_section();

        parent::register_controls();

        $this->start_injection( [
            'at' => 'before',
            'of' => 'section_design_box',
        ] );

        $this->start_controls_section(
            'section_heading_style',
            [
                'label' => esc_html__( 'Heading', 'elementor-pro' ),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'show_heading',
            [
                'label' => esc_html__( 'Heading', 'elementor-pro' ),
                'type' => Controls_Manager::SWITCHER,
                'label_off' => esc_html__( 'Hide', 'elementor-pro' ),
                'label_on' => esc_html__( 'Show', 'elementor-pro' ),
                'default' => 'yes',
                'return_value' => 'yes',
                'prefix_class' => 'show-heading-',
            ]
        );

        $this->add_control(
            'heading_color',
            [
                'label' => esc_html__( 'Color', 'elementor-pro' ),
                'type' => Controls_Manager::COLOR,
                'global' => [
                    'default' => Global_Colors::COLOR_PRIMARY,
                ],
                'selectors' => [
                    '.woocommerce {{WRAPPER}}.elementor-wc-products .products > h2' => 'color: {{VALUE}}',
                ],
                'condition' => [
                    'show_heading!' => '',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name' => 'heading_typography',
                'global' => [
                    'default' => Global_Typography::TYPOGRAPHY_PRIMARY,
                ],
                'selector' => '.woocommerce {{WRAPPER}}.elementor-wc-products .products > h2',
                'condition' => [
                    'show_heading!' => '',
                ],
            ]
        );

        $this->add_responsive_control(
            'heading_text_align',
            [
                'label' => esc_html__( 'Text Align', 'elementor-pro' ),
                'type' => Controls_Manager::CHOOSE,
                'options' => [
                    'start' => [
                        'title' => esc_html__( 'Start', 'elementor-pro' ),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__( 'Center', 'elementor-pro' ),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'end' => [
                        'title' => esc_html__( 'End', 'elementor-pro' ),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'classes' => 'elementor-control-start-end',
                'selectors_dictionary' => [
                    'left' => is_rtl() ? 'end' : 'start',
                    'right' => is_rtl() ? 'start' : 'end',
                ],
                'selectors' => [
                    '.woocommerce {{WRAPPER}}.elementor-wc-products .products > h2' => 'text-align: {{VALUE}}',
                ],
                'condition' => [
                    'show_heading!' => '',
                ],
            ]
        );

        $this->add_responsive_control(
            'heading_spacing',
            [
                'label' => esc_html__( 'Spacing', 'elementor-pro' ),
                'type' => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em', 'rem', 'custom' ],
                'selectors' => [
                    '.woocommerce {{WRAPPER}}.elementor-wc-products .products > h2' => 'margin-bottom: {{SIZE}}{{UNIT}}',
                ],
                'condition' => [
                    'show_heading!' => '',
                ],
            ]
        );

        $this->end_controls_section();

        $this->end_injection();
    }

    protected function render() {
        global $product;

        $product = $this->get_product();

        if ( ! $product ) {
            return;
        }

        $settings = $this->get_settings_for_display();

        // Add a wrapper class to the Add to Cart & View Items elements if the automically_align_buttons switch has been selected.
        if ( 'yes' === $settings['automatically_align_buttons'] ) {
            add_filter( 'woocommerce_loop_add_to_cart_link', [ $this, 'add_to_cart_wrapper' ], 10, 1 );
        }

        $args = [
            'posts_per_page' => 4,
            'columns' => 4,
            'orderby' => $settings['orderby'],
            'order' => $settings['order'],
        ];

        if ( ! empty( $settings['posts_per_page'] ) ) {
            $args['posts_per_page'] = $settings['posts_per_page'];
        }

        if ( ! empty( $settings['columns'] ) ) {
            $args['columns'] = $settings['columns'];
        }

        $args = array_map( 'sanitize_text_field', $args );

        // Obtener productos de la misma categoría usando nuestro método personalizado
        $include_subcategories = isset( $settings['include_subcategories'] ) && 'yes' === $settings['include_subcategories'];
        $args['related_products'] = $this->get_products_by_same_category( $product, $args['posts_per_page'], $include_subcategories );

        // Handle orderby.
        $args['related_products'] = wc_products_array_orderby( $args['related_products'], $args['orderby'], $args['order'] );

        ob_start();

        wc_get_template( 'single-product/related.php', $args );

        $related_products_html = ob_get_clean();

        if ( $related_products_html ) {
            $related_products_html = str_replace( '<ul class="products', '<ul class="products elementor-grid', $related_products_html );

            echo $related_products_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        if ( 'yes' === $settings['automatically_align_buttons'] ) {
            remove_filter( 'woocommerce_loop_add_to_cart_link', [ $this, 'add_to_cart_wrapper' ] );
        }
    }

    /**
     * Obtiene productos de la misma categoría que el producto actual
     *
     * @param \WC_Product $product Producto actual
     * @param int $limit Límite de productos a mostrar
     * @param bool $include_subcategories Incluir subcategorías
     * @return array Array de productos
     */
    private function get_products_by_same_category( $product, $limit, $include_subcategories = true ) {
        $product_id = $product->get_id();
        
        // Obtener las categorías del producto actual
        $categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
        
        if ( empty( $categories ) ) {
            return array();
        }
        
        // Si se deben incluir subcategorías, obtener todos los IDs de categorías hijas
        if ( $include_subcategories ) {
            $all_category_ids = $categories;
            foreach ( $categories as $category_id ) {
                $child_categories = get_term_children( $category_id, 'product_cat' );
                if ( ! is_wp_error( $child_categories ) && ! empty( $child_categories ) ) {
                    $all_category_ids = array_merge( $all_category_ids, $child_categories );
                }
            }
            $categories = array_unique( $all_category_ids );
        }
        
        // Argumentos para la consulta de productos
        $args = array(
            'post_type'             => 'product',
            'post_status'           => 'publish',
            'ignore_sticky_posts'   => 1,
            'posts_per_page'        => $limit + 5, // Traer más para filtrar productos no visibles
            'post__not_in'          => array( $product_id ),
            'tax_query'             => array(
                'relation' => 'AND',
                array(
                    'taxonomy'      => 'product_cat',
                    'field'         => 'term_id',
                    'terms'         => $categories,
                    'operator'      => 'IN'
                ),
                array(
                    'taxonomy' => 'product_visibility',
                    'field'    => 'name',
                    'terms'    => 'exclude-from-catalog',
                    'operator' => 'NOT IN',
                ),
            ),
            'meta_query'            => array(
                'relation' => 'AND',
                array(
                    'key'     => '_stock_status',
                    'value'   => 'outofstock',
                    'compare' => '!='
                ),
            ),
        );
        
        // Ordenar por aleatorio si se seleccionó 'rand'
        if ( isset( $_GET['orderby'] ) && 'rand' === $_GET['orderby'] ) {
            $args['orderby'] = 'rand';
        }
        
        $products_query = new \WP_Query( $args );
        $related_products = array();
        
        if ( $products_query->have_posts() ) {
            while ( $products_query->have_posts() && count( $related_products ) < $limit ) {
                $products_query->the_post();
                $related_product = wc_get_product( get_the_ID() );
                
                if ( $related_product && $related_product->is_visible() && $related_product->is_in_stock() ) {
                    $related_products[] = $related_product;
                }
            }
            wp_reset_postdata();
        }
        
        return $related_products;
    }

    public function render_plain_content() {}

    public function get_group_name() {
        return 'woocommerce';
    }
}

// Asegurar que la clase base Products_Base esté disponible
if ( ! class_exists( 'ElementorPro\Modules\Woocommerce\Widgets\Products_Base' ) ) {
    // Intentar cargar la clase base si no está disponible
    $base_file = WP_PLUGIN_DIR . '/elementor-pro/modules/woocommerce/widgets/products-base.php';
    if ( file_exists( $base_file ) ) {
        require_once $base_file;
    }
}
