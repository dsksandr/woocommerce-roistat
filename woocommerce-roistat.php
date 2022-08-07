<?php
/**
 * Plugin Name: Integration with Roistat
 * Plugin URI: http://roistat.com/woocommerce/
 * Description: Roistat is a transperent business analytics system.
 * Version: 1.0.0
 * Author: Roistat
 * Author URI: http://roistat.com
 */

/*  Copyright 2016 Roistat (email: info@roistat.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

final class WC_RS_Integration {

    const VERSION = '1.0.0';
    const PROMO_CODE_FIELD = 'roistat_visit';

    /**
     * @var string
     */
    private static $roistat_visit;

    public function __construct() {
            if ( ! $this->is_woocommerce_active() ) {
            add_action( 'admin_notices', array( $this, 'notice_activate_wc' ) );
        } else if ( $this->is_minimum_or_higher_version() ) {
            add_action( 'admin_notices', array( $this, 'notice_version_wc' ) );
        } else {
            $this->hooks_init();
            if ( is_admin() ) {
                $this->settings_page_init();
            }
        }
    }

    private function hooks_init() {
        add_action( 'wp_footer', array( $this, 'hook_add_tracking_code' ) );
        add_action( 'woocommerce_api_order_response', array( $this, 'hook_api_order_response' ) );
        add_action( 'woocommerce_api_create_order_data', array( $this, 'hook_api_prepare_order_data' ) );
        add_action( 'woocommerce_api_create_order', array( $this, 'hook_api_create_order' ) );
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'hook_save_roistat_visit' ) );
    }

    private function settings_page_init() {
        if ( class_exists( 'WC_Integration' ) ) {
            require_once __DIR__ . '/includes/class-wc-rs-settings-page.php';

            add_filter( 'woocommerce_integrations', array( $this, 'load_integration_settings_page' ) );
        }
    }

    /**
     * @param array $integrations
     * @return array
     */
    public static function load_integration_settings_page( array $integrations ) {
        $integrations[] = 'WC_RS_Integration_Settings_Page';

        return $integrations;
    }

    /**
     * @param string $order_id
     */
    public static function hook_save_roistat_visit( $order_id ) {
        if ( array_key_exists( self::PROMO_CODE_FIELD, $_COOKIE ) && strlen ( $_COOKIE[ self::PROMO_CODE_FIELD ] ) > 0 ) {
            add_post_meta( $order_id, self::PROMO_CODE_FIELD, $_COOKIE[ self::PROMO_CODE_FIELD ] );
        }
    }

    /**
     * @param string $order_id
     */
    public static function hook_api_create_order( $order_id ) {
        if ( self::$roistat_visit !== null ) {
            add_post_meta( $order_id, self::PROMO_CODE_FIELD, self::$roistat_visit );
        }
    }

    /**
     * @param array $orderData
     * @return array
     */
    public static function hook_api_prepare_order_data( array $orderData ) {
        if ( array_key_exists( 'roistat', $orderData ) && strlen ( $orderData['roistat'] ) > 0 ) {
            self::$roistat_visit  = $orderData['roistat'];
        }
        return $orderData;
    }

    /**
     * @param array $order
     * @return array
     */
    public static function hook_api_order_response(  array $order ) {
        $order['roistat'] = get_post_meta( $order['id'], self::PROMO_CODE_FIELD, true );

        return $order;
    }

    public static function hook_add_tracking_code() {
        $options = get_option( 'woocommerce_woocommerce-roistat_settings', array() );
        if ( ! array_key_exists( 'project_id', $options ) || intval( $options['project_id'] ) < 1 ) {
            return;
        }
        echo <<<JS
<script>
(function(w, d, s, h, id) {
    w.roistatProjectId = id; w.roistatHost = h;
    var p = d.location.protocol == "https:" ? "https://" : "http://";
    var u = /^.*roistat_visit=[^;]+(.*)?$/.test(d.cookie) ? "/dist/module.js" : "/api/site/1.0/"+id+"/init";
    var js = d.createElement(s); js.async = 1; js.src = p+h+u; var js2 = d.getElementsByTagName(s)[0]; js2.parentNode.insertBefore(js, js2);
})(window, document, 'script', 'cloud.roistat.com', '{$options['project_id']}');
</script>
JS;
    }

    public static function notice_version_wc() {
        ?>
            <div class="error">
                <p><?php _e( 'Please update WooCommerce to <strong>version 2.2.9 or higher</strong> in order for the Roistat integration extension to work!', 'woocommerce-roistat' ); ?></p>
            </div>
        <?php
    }

    public static function notice_activate_wc() {
        ?>
            <div class="error">
                <p><?php printf( __( 'Please install and activate %sWooCommerce%s for integration with Roistat!', 'woocommerce-roistat' ), '<a href="' . admin_url( 'plugin-install.php?tab=search&s=WooCommerce&plugin-search-input=Search+Plugins' ) . '">', '</a>' ); ?></p>
            </div>
        <?php
    }

    /**
     * @return bool
     */
    private function is_woocommerce_active() {
        $active_plugins = get_option( 'active_plugins', array() );

        if ( is_multisite() ) {
            $active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
        }
        return in_array( 'woocommerce/woocommerce.php', $active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', $active_plugins );
    }

    /**
     * @return bool
     */
    private function is_minimum_or_higher_version() {
        return version_compare( WC_VERSION, '2.2.0', '<' );
    }
}

function __woocommerce_roistat_main() {
    load_plugin_textdomain( 'woocommerce-roistat', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    new WC_RS_Integration();
}

// Create object - Plugin init
add_action( 'plugins_loaded', '__woocommerce_roistat_main' );