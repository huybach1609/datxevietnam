<?php
/**
 * Plugin Name: DXVN PWA
 * Description: Reusable Progressive Web App layer with install prompt, manifest, and service worker support for WordPress sites.
 * Version: 0.1.0
 * Author: Minh Thắng Hạ Long Travel
 * Text Domain: dxvn-pwa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DXVN_PWA_VERSION', '0.1.0' );
define( 'DXVN_PWA_PATH', plugin_dir_path( __FILE__ ) );
define( 'DXVN_PWA_URL', plugin_dir_url( __FILE__ ) );

require_once DXVN_PWA_PATH . 'includes/class-dxvn-pwa.php';

register_activation_hook( __FILE__, array( 'DXVN_PWA', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'DXVN_PWA', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function() {
		DXVN_PWA::init();
	}
);
