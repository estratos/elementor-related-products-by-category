<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ERPBC_Plugin_Core {
    
    public function __construct() {
        // Esperar a que Elementor esté listo
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ], 20 );
        
        // Cargar traducciones
        add_action( 'init', [ $this, 'load_textdomain' ] );
    }
    
    public function load_textdomain() {
        load_plugin_textdomain(
            'elementor-related-products-by-category',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages/'
        );
    }
    
    public function register_widgets( $widgets_manager ) {
        // Cargar nuestra clase de widget
        require_once ERPBC_PLUGIN_DIR . 'includes/class-product-related-widget.php';
        
        // Registrar nuestro widget personalizado
        $widgets_manager->register( new \ElementorPro\Modules\Woocommerce\Widgets\Product_Related_Custom() );
    }
}
