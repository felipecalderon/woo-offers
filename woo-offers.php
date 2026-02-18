<?php
/**
 * Plugin Name: Woo Offers
 * Plugin URI:  https://example.com/woo-offers
 * Description: Plugin base profesional para extender WooCommerce con ofertas y reglas comerciales.
 * Version:     0.1.3
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:      Felipe Calderon
 * Author URI:  https://github.com/felipecalderon
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: woo-offers
 * Domain Path: /languages
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WOO_OFFERS_VERSION', '0.1.3');
define('WOO_OFFERS_FILE', __FILE__);
define('WOO_OFFERS_PATH', plugin_dir_path(__FILE__));
define('WOO_OFFERS_URL', plugin_dir_url(__FILE__));
define('WOO_OFFERS_MIN_PHP', '8.1');
define('WOO_OFFERS_MIN_WP', '6.5');
define('WOO_OFFERS_MIN_WC', '8.0');
define('WOO_OFFERS_OPTION_RULES', 'woo_offers_rules');

require_once WOO_OFFERS_PATH . 'src/Autoloader.php';
WooOffers\Autoloader::register();

register_activation_hook(WOO_OFFERS_FILE, static function (): void {
    try {
        WooOffers\Plugin::activate();
    } catch (\Throwable $exception) {
        error_log('[Woo Offers] Activation error: ' . $exception->getMessage());
        wp_die(
            esc_html__('Woo Offers fallo durante la activacion. Revisa el log de PHP/WordPress para el detalle.', 'woo-offers'),
            esc_html__('Activacion fallida', 'woo-offers'),
            ['back_link' => true]
        );
    }
});
register_deactivation_hook(WOO_OFFERS_FILE, ['WooOffers\\Plugin', 'deactivate']);

add_action('plugins_loaded', static function (): void {
    try {
        WooOffers\Plugin::boot();
    } catch (\Throwable $exception) {
        error_log('[Woo Offers] Boot error: ' . $exception->getMessage());

        add_action('admin_notices', static function (): void {
            if (!current_user_can('activate_plugins')) {
                return;
            }

            echo '<div class="notice notice-error"><p>'
                . esc_html__('Woo Offers detecto un error al iniciar. Revisa el log de PHP/WordPress para mas detalle.', 'woo-offers')
                . '</p></div>';
        });
    }
});
