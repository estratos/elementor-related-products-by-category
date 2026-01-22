<?php
/**
 * Plugin Name: Elementor Pro Related Products - Same Category Only
 * Plugin URI: https://tusitio.com
 * Description: Modifica el widget de Productos Relacionados de Elementor Pro para mostrar solo productos de la primera categoría
 * Version: 1.0.0
 * Author: Tu Nombre
 * License: GPL v2 or later
 * Text Domain: elementor-related-category-only
 */

// Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Verificar que Elementor Pro y WooCommerce estén activos
add_action( 'admin_init', 'erc_check_dependencies' );
function erc_check_dependencies() {
    if ( ! is_plugin_active( 'elementor-pro/elementor-pro.php' ) || ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        add_action( 'admin_notices', 'erc_missing_dependencies_notice' );
        deactivate_plugins( plugin_basename( __FILE__ ) );
    }
}

function erc_missing_dependencies_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong>Elementor Pro Related Products - Same Category Only:</strong> 
            Este plugin requiere <strong>Elementor Pro</strong> y <strong>WooCommerce</strong> para funcionar.
            El plugin ha sido desactivado.
        </p>
    </div>
    <?php
}

// Sobrescribir la clase Product_Related de Elementor Pro
add_action( 'plugins_loaded', 'erc_override_product_related_class', 100 );
function erc_override_product_related_class() {
    // No hacer nada si Elementor Pro no está cargado
    if ( ! class_exists( '\ElementorPro\Plugin' ) ) {
        return;
    }
    
    // Verificar que la clase original existe
    if ( ! class_exists( '\ElementorPro\Modules\Woocommerce\Widgets\Product_Related' ) ) {
        return;
    }
    
    // Remover la acción original de registro de widgets
    remove_action( 'elementor/widgets/register', [ \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'woocommerce' )->get_widgets(), 'register_widgets' ], 20 );
    
    // Registrar nuestros widgets personalizados
    add_action( 'elementor/widgets/register', 'erc_register_widgets', 20 );
}

function erc_register_widgets( $widgets_manager ) {
    // Registrar todos los widgets de WooCommerce excepto Product_Related
    $woocommerce_widgets = [
        '\ElementorPro\Modules\Woocommerce\Widgets\Products',
        '\ElementorPro\Modules\Woocommerce\Widgets\Products_Deprecated',
        '\ElementorPro\Modules\Woocommerce\Widgets\Product_Additional_Info',
        '\ElementorPro\Modules\Woocommerce\Widgets\Product_Adsense',
        '\ElementorPro\Modules\Woocommerce\Widgets\Product_Content',
        '\ElementorPro\Modules\Woocommerce\Widgets\Product_Data_Tabs',
        '\ElementorPro\Modules\Woocommerce\Widgets\Product_Images',
        '\ElementorPro\Modules\Woocommerce\Widgets\Product_Meta',
        '\ElementorPro\Modules\Woocommerce\Widgets\Product_Price',
        '\ElementorPro\Modules\Woocommerce\Widgets\Product_Rating',
        '\ElementorPro\Modules\Woocommerce\Widgets\Product_Short_Description',
        '\ElementorPro\Modules\Woocommerce\Widgets\Product_Stock',
        '\ElementorPro\Modules\Woocommerce\Widgets\Product_Title',
        '\ElementorPro\Modules\Woocommerce\Widgets\Product_Upsell',
        '\ElementorPro\Modules\Woocommerce\Widgets\Add_To_Cart',
        '\ElementorPro\Modules\Woocommerce\Widgets\Categories',
        '\ElementorPro\Modules\Woocommerce\Widgets\Archive_Products',
        '\ElementorPro\Modules\Woocommerce\Widgets\Archive_Products_Deprecated',
        '\ElementorPro\Modules\Woocommerce\Widgets\Archive_Description',
    ];
    
    foreach ( $woocommerce_widgets as $widget_class ) {
        if ( class_exists( $widget_class ) ) {
            $widgets_manager->register( new $widget_class() );
        }
    }
    
    // Registrar nuestro widget personalizado de productos relacionados
    require_once __DIR__ . '/includes/class-product-related-custom.php';
    $widgets_manager->register( new ElementorPro\Modules\Woocommerce\Widgets\Product_Related_Custom() );
}

// Agregar enlace de configuración en la página de plugins
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'erc_add_settings_link' );
function erc_add_settings_link( $links ) {
    $settings_link = '<a href="https://wordpress.org/support/plugin/' . dirname( plugin_basename( __FILE__ ) ) . '/" target="_blank">' . esc_html__( 'Soporte', 'elementor-related-category-only' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}

// Crear el archivo de la clase personalizada si no existe
add_action( 'admin_init', 'erc_create_custom_class_file' );
function erc_create_custom_class_file() {
    $class_file = __DIR__ . '/includes/class-product-related-custom.php';
    
    if ( ! file_exists( $class_file ) ) {
        $plugin_dir = __DIR__ . '/includes';
        if ( ! file_exists( $plugin_dir ) ) {
            mkdir( $plugin_dir, 0755, true );
        }
        
        file_put_contents( $class_file, erc_get_custom_class_content() );
    }
}

function erc_get_custom_class_content() {
    return '<?php
namespace ElementorPro\Modules\Woocommerce\Widgets;

use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Typography;
use ElementorPro\Plugin;

if ( ! defined( \'ABSPATH\' ) ) {
	exit; // Exit if accessed directly
}

class Product_Related_Custom extends Products_Base {

	public function get_name() {
		return \'woocommerce-product-related\';
	}

	public function get_title() {
		return esc_html__( \'Product Related\', \'elementor-pro\' );
	}

	public function get_icon() {
		return \'eicon-product-related\';
	}

	public function get_keywords() {
		return [ \'woocommerce\', \'shop\', \'store\', \'related\', \'similar\', \'product\' ];
	}

	public function has_widget_inner_wrapper(): bool {
		return ! Plugin::elementor()->experiments->is_feature_active( \'e_optimized_markup\' );
	}

	/**
	 * Get style dependencies.
	 *
	 * Retrieve the list of style dependencies the widget requires.
	 *
	 * @since 3.24.0
	 * @access public
	 *
	 * @return array Widget style dependencies.
	 */
	public function get_style_depends(): array {
		return [ \'widget-woocommerce-products\' ];
	}

	protected function register_controls() {
		$this->start_controls_section(
			\'section_related_products_content\',
			[
				\'label\' => esc_html__( \'Related Products\', \'elementor-pro\' ),
			]
		);

		$this->add_control(
			\'posts_per_page\',
			[
				\'label\' => esc_html__( \'Products Per Page\', \'elementor-pro\' ),
				\'type\' => Controls_Manager::NUMBER,
				\'default\' => 4,
				\'range\' => [
					\'px\' => [
						\'max\' => 20,
					],
				],
			]
		);

		$this->add_columns_responsive_control();

		$this->add_control(
			\'orderby\',
			[
				\'label\' => esc_html__( \'Order By\', \'elementor-pro\' ),
				\'type\' => Controls_Manager::SELECT,
				\'default\' => \'date\',
				\'options\' => [
					\'date\' => esc_html__( \'Date\', \'elementor-pro\' ),
					\'title\' => esc_html__( \'Title\', \'elementor-pro\' ),
					\'price\' => esc_html__( \'Price\', \'elementor-pro\' ),
					\'popularity\' => esc_html__( \'Popularity\', \'elementor-pro\' ),
					\'rating\' => esc_html__( \'Rating\', \'elementor-pro\' ),
					\'rand\' => esc_html__( \'Random\', \'elementor-pro\' ),
					\'menu_order\' => esc_html__( \'Menu Order\', \'elementor-pro\' ),
				],
			]
		);

		$this->add_control(
			\'order\',
			[
				\'label\' => esc_html__( \'Order\', \'elementor-pro\' ),
				\'type\' => Controls_Manager::SELECT,
				\'default\' => \'desc\',
				\'options\' => [
					\'asc\' => esc_html__( \'ASC\', \'elementor-pro\' ),
					\'desc\' => esc_html__( \'DESC\', \'elementor-pro\' ),
				],
			]
		);

		$this->end_controls_section();

		parent::register_controls();

		$this->start_injection( [
			\'at\' => \'before\',
			\'of\' => \'section_design_box\',
		] );

		$this->start_controls_section(
			\'section_heading_style\',
			[
				\'label\' => esc_html__( \'Heading\', \'elementor-pro\' ),
				\'tab\' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			\'show_heading\',
			[
				\'label\' => esc_html__( \'Heading\', \'elementor-pro\' ),
				\'type\' => Controls_Manager::SWITCHER,
				\'label_off\' => esc_html__( \'Hide\', \'elementor-pro\' ),
				\'label_on\' => esc_html__( \'Show\', \'elementor-pro\' ),
				\'default\' => \'yes\',
				\'return_value\' => \'yes\',
				\'prefix_class\' => \'show-heading-\',
			]
		);

		$this->add_control(
			\'heading_color\',
			[
				\'label\' => esc_html__( \'Color\', \'elementor-pro\' ),
				\'type\' => Controls_Manager::COLOR,
				\'global\' => [
					\'default\' => Global_Colors::COLOR_PRIMARY,
				],
				\'selectors\' => [
					\'.woocommerce {{WRAPPER}}.elementor-wc-products .products > h2\' => \'color: {{VALUE}}\',
				],
				\'condition\' => [
					\'show_heading!\' => \'\',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				\'name\' => \'heading_typography\',
				\'global\' => [
					\'default\' => Global_Typography::TYPOGRAPHY_PRIMARY,
				],
				\'selector\' => \'.woocommerce {{WRAPPER}}.elementor-wc-products .products > h2\',
				\'condition\' => [
					\'show_heading!\' => \'\',
				],
			]
		);

		$this->add_responsive_control(
			\'heading_text_align\',
			[
				\'label\' => esc_html__( \'Text Align\', \'elementor-pro\' ),
				\'type\' => Controls_Manager::CHOOSE,
				\'options\' => [
					\'start\' => [
						\'title\' => esc_html__( \'Start\', \'elementor-pro\' ),
						\'icon\' => \'eicon-text-align-left\',
					],
					\'center\' => [
						\'title\' => esc_html__( \'Center\', \'elementor-pro\' ),
						\'icon\' => \'eicon-text-align-center\',
					],
					\'end\' => [
						\'title\' => esc_html__( \'End\', \'elementor-pro\' ),
						\'icon\' => \'eicon-text-align-right\',
					],
				],
				\'classes\' => \'elementor-control-start-end\',
				\'selectors_dictionary\' => [
					\'left\' => is_rtl() ? \'end\' : \'start\',
					\'right\' => is_rtl() ? \'start\' : \'end\',
				],
				\'selectors\' => [
					\'.woocommerce {{WRAPPER}}.elementor-wc-products .products > h2\' => \'text-align: {{VALUE}}\',
				],
				\'condition\' => [
					\'show_heading!\' => \'\',
				],
			]
		);

		$this->add_responsive_control(
			\'heading_spacing\',
			[
				\'label\' => esc_html__( \'Spacing\', \'elementor-pro\' ),
				\'type\' => Controls_Manager::SLIDER,
				\'size_units\' => [ \'px\', \'em\', \'rem\', \'custom\' ],
				\'selectors\' => [
					\'.woocommerce {{WRAPPER}}.elementor-wc-products .products > h2\' => \'margin-bottom: {{SIZE}}{{UNIT}}\',
				],
				\'condition\' => [
					\'show_heading!\' => \'\',
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
		if ( \'yes\' === $settings[\'automatically_align_buttons\'] ) {
			add_filter( \'woocommerce_loop_add_to_cart_link\', [ $this, \'add_to_cart_wrapper\' ], 10, 1 );
		}

		$args = [
			\'posts_per_page\' => 4,
			\'columns\' => 4,
			\'orderby\' => $settings[\'orderby\'],
			\'order\' => $settings[\'order\'],
		];

		if ( ! empty( $settings[\'posts_per_page\'] ) ) {
			$args[\'posts_per_page\'] = $settings[\'posts_per_page\'];
		}

		if ( ! empty( $settings[\'columns\'] ) ) {
			$args[\'columns\'] = $settings[\'columns\'];
		}

		$args = array_map( \'sanitize_text_field\', $args );

		// ====== MODIFICACIÓN: Filtrar productos relacionados por primera categoría ======
		
		// Obtener todas las categorías del producto
		$product_categories = wp_get_post_terms( $product->get_id(), \'product_cat\' );

		// Si tiene categorías, filtrar por la primera
		if ( ! empty( $product_categories ) && ! is_wp_error( $product_categories ) ) {
			// Tomar la primera categoría
			$main_category = reset( $product_categories );
			$category_id = $main_category->term_id;
			
			// Aplicar filtro temporal para modificar la consulta
			add_filter( \'woocommerce_product_related_posts_query\', 
				function( $query, $current_product_id ) use ( $category_id, $product ) {
					// Solo modificar para el producto actual
					if ( $current_product_id == $product->get_id() ) {
						global $wpdb;
						return $wpdb->prepare(
							"SELECT DISTINCT p.ID 
							FROM {$wpdb->posts} p 
							INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id 
							INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
							WHERE p.post_type = \'product\' 
							AND p.post_status = \'publish\' 
							AND p.ID != %d 
							AND tt.taxonomy = \'product_cat\' 
							AND tt.term_id = %d 
							ORDER BY RAND() 
							LIMIT 20",
							$current_product_id,
							$category_id
						);
					}
					return $query;
				}, 
				10, 
				2 
			);
		} else {
			// Si no tiene categorías, query vacía
			add_filter( \'woocommerce_product_related_posts_query\', 
				function( $query, $current_product_id ) use ( $product ) {
					if ( $current_product_id == $product->get_id() ) {
						global $wpdb;
						return "SELECT ID FROM {$wpdb->posts} WHERE 1=0";
					}
					return $query;
				}, 
				10, 
				2 
			);
		}
		
		// ====== CÓDIGO ORIGINAL (NO TOCAR) ======
		$args[\'related_products\'] = array_filter( array_map( \'wc_get_product\', wc_get_related_products( $product->get_id(), $args[\'posts_per_page\'], $product->get_upsell_ids() ) ), \'wc_products_array_filter_visible\' );
		// ====== FIN CÓDIGO ORIGINAL ======
		
		// Remover nuestro filtro después de usarlo
		remove_all_filters( \'woocommerce_product_related_posts_query\' );

		// Handle orderby.
		$args[\'related_products\'] = wc_products_array_orderby( $args[\'related_products\'], $args[\'orderby\'], $args[\'order\'] );

		ob_start();

		wc_get_template( \'single-product/related.php\', $args );

		$related_products_html = ob_get_clean();

		if ( $related_products_html ) {
			$related_products_html = str_replace( \'<ul class="products\', \'<ul class="products elementor-grid\', $related_products_html );

			// PHPCS - Doesn\'t need to be escaped since it\'s a WooCommerce template, and 3rd party plugins might hook into it.
			echo $related_products_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( \'yes\' === $settings[\'automatically_align_buttons\'] ) {
			remove_filter( \'woocommerce_loop_add_to_cart_link\', [ $this, \'add_to_cart_wrapper\' ] );
		}
	}

	public function render_plain_content() {}

	public function get_group_name() {
		return \'woocommerce\';
	}
}';
}

// Limpiar cuando se desinstale el plugin
register_uninstall_hook( __FILE__, 'erc_uninstall' );
function erc_uninstall() {
    // No hay opciones que limpiar
}
