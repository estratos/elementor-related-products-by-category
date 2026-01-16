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

// Definir constantes
define( 'ERPBC_VERSION', '1.0.0' );
define( 'ERPBC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ERPBC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Verificar requisitos
add_action( 'plugins_loaded', 'erpbc_init' );
function erpbc_init() {
    // Verificar que Elementor Pro y WooCommerce estén activos
    if ( ! did_action( 'elementor/loaded' ) || ! function_exists( 'wc' ) ) {
        add_action( 'admin_notices', 'erpbc_missing_plugins_notice' );
        return;
    }
    
    // Verificar versión mínima de Elementor Pro
    if ( ! version_compare( ELEMENTOR_VERSION, '3.0.0', '>=' ) ) {
        add_action( 'admin_notices', 'erpbc_elementor_version_notice' );
        return;
    }
    
    // Inicializar el plugin
    require_once ERPBC_PLUGIN_DIR . 'includes/class-plugin-core.php';
    new ERPBC_Plugin_Core();
}

function erpbc_missing_plugins_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php 
            printf(
                __( '<strong>Elementor Related Products by Category</strong> requiere %s y %s para funcionar.', 'elementor-related-products-by-category' ),
                '<a href="https://elementor.com/pro/" target="_blank">Elementor Pro</a>',
                '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>'
            );
        ?></p>
    </div>
    <?php
}

function erpbc_elementor_version_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php 
            printf(
                __( '<strong>Elementor Related Products by Category</strong> requiere Elementor Pro versión 3.0.0 o superior. Tu versión actual es %s.', 'elementor-related-products-by-category' ),
                ELEMENTOR_VERSION
            );
        ?></p>
    </div>
    <?php
}

// Agregar enlace de configuración en la página de plugins
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'erpbc_add_settings_link' );
function erpbc_add_settings_link( $links ) {
    $settings_link = '<a href="' . admin_url( 'admin.php?page=elementor' ) . '">' . __( 'Configurar en Elementor', 'elementor-related-products-by-category' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
