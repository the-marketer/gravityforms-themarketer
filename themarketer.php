<?php
/**
 * Plugin Name:             Gravity Forms theMarketer Add-On
 * Plugin URI:                must complete
 * Description:             Integrates Gravity Forms with theMarketer, allowing form submissions to be automatically sent to your theMarketer account.
 * Version:                 1.0.0
 * Requires at least:       4.6
 * Requires PHP:            5.6
 * Author:                  themarketer.com
 * Author URI:              https://themarketer.com
 * Text Domain:             mktr
 * License:                 GPL2
 * License URI:             https://www.gnu.org/licenses/gpl-2.0.html
 *
 */

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! defined( 'API_URL' ) ) {
//	define( 'API_URL', 'http://host.docker.internal:8080/api/v1' );
	define( 'API_URL', 'https://api.themarketer.com/api/v1' );

}

if ( ! defined( "T_API_URL" ) ) {
	define( "T_API_URL", "https://t.themarketer.com/api/v1" );
}

if ( ! defined( 'SSL_MODE' ) ) {
	define( 'SSL_MODE', true );
}

if ( ! defined( 'GF_THEMARKETER_VERSION', '1.0' ) ) {
	define( 'GF_THEMARKETER_VERSION', '1.0' );
}


add_action( 'gform_loaded', array( 'GF_theMarketer_Bootstrap', 'load' ), 5 );

class GF_theMarketer_Bootstrap {
	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_feed_addon_framework' ) ) {
			return;
		}

		require_once( 'class-gf-themarketer.php' );

		GFAddOn::register( 'GFtheMarketer' );
	}
}

/**
 * Returns an instance of the GFTheMarketer class
 *
 * @return GFtheMarketer
 * @see    GFtheMarketer::get_instance()
 *
 */
function gf_themarketer() {
	return GFtheMarketer::get_instance();
}
