<?php
/**
 * Plugin Name: Elementor Related Products by Category
 * Plugin URI: https://tusitio.com
 * Description: Modifica el widget de Productos Relacionados de Elementor Pro para mostrar solo productos de la misma categoría
 * Version: 1.0.0
 * Author: Tu Nombre
 * License: GPL v2 or later
 * Text Domain: elementor-related-products-by-category
 */

// Evitar acceso directo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Verificar que Elementor Pro está activo
add_action( 'admin_init', 'erpbc_check_required_plugins' );
function erpbc_check_required_plugins() {
    if ( ! is_plugin_active( 'elementor-pro/elementor-pro.php' ) ) {
        add_action( 'admin_notices', 'erpbc_missing_elementor_pro_notice' );
    }
}

function erpbc_missing_elementor_pro_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e( '<strong>Elementor Related Products by Category</strong> requiere Elementor Pro para funcionar. Por favor, instala y activa Elementor Pro.', 'elementor-related-products-by-category' ); ?></p>
    </div>
    <?php
}

// Cargar la clase modificada
add_action( 'elementor_pro/init', 'erpbc_override_product_related_class' );
function erpbc_override_product_related_class() {
    // Verificar que la clase original existe
    if ( ! class_exists( '\ElementorPro\Modules\Woocommerce\Widgets\Product_Related' ) ) {
        return;
    }
    
    // Incluir nuestra clase modificada
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-override-product-related.php';
    
    // Remover la acción original de registro del widget
    remove_action( 'elementor/widgets/register', [ \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'woocommerce' )->get_widgets(), 'register_widgets' ], 20 );
    
    // Registrar nuestros widgets personalizados
    add_action( 'elementor/widgets/register', 'erpbc_register_widgets', 20 );
}

function erpbc_register_widgets( $widgets_manager ) {
    // Primero, registrar todos los widgets de WooCommerce excepto Product_Related
    $woocommerce_module = \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'woocommerce' );
    $woocommerce_widgets = $woocommerce_module->get_widgets();
    
    foreach ( $woocommerce_widgets as $widget_class ) {
        if ( $widget_class !== '\ElementorPro\Modules\Woocommerce\Widgets\Product_Related' ) {
            $widgets_manager->register( new $widget_class() );
        }
    }
    
    // Registrar nuestro widget personalizado
    $widgets_manager->register( new \ElementorPro\Modules\Woocommerce\Widgets\Product_Related_Override() );
}

// Agregar enlace de configuración en la página de plugins
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'erpbc_add_settings_link' );
function erpbc_add_settings_link( $links ) {
    $settings_link = '<a href="' . admin_url( 'admin.php?page=elementor' ) . '">' . __( 'Configurar en Elementor', 'elementor-related-products-by-category' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}

// Desactivar el plugin si se desinstala
register_uninstall_hook( __FILE__, 'erpbc_uninstall' );
function erpbc_uninstall() {
    // Limpiar opciones si es necesario
    delete_option( 'erpbc_version' );
}
