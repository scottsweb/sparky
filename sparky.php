<?php
/**
 * Sparky
 *
 * Spark Core meet WordPress
 *
 * @package   sparky
 * @author    Scott Evans <git@scott.ee>
 * @license   GPL-2.0+
 * @link      http://scott.ee
 * @copyright 2014 Scott Evans
 *
 * @wordpress-plugin
 * Plugin Name:       Sparky
 * Plugin URI:        http://scott.ee
 * Description:       Spark Core meet WordPress
 * Version:           1.0
 * Author:            Scott Evans
 * Author URI:        http://scott.ee
 * Text Domain:       sparky
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/scottsweb/sparky
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Libraries
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'lib/extended-cpts.php' );
require_once( plugin_dir_path( __FILE__ ) . 'lib/extended-taxos.php' );
require_once( plugin_dir_path( __FILE__ ) . 'lib/spark-api.php' );

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

require_once( plugin_dir_path( __FILE__ ) . 'public/class-sparky-shortcode.php' );
require_once( plugin_dir_path( __FILE__ ) . 'public/class-sparky.php' );

register_activation_hook( __FILE__, array( 'sparky', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'sparky', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'sparky', 'get_instance' ) );

/*----------------------------------------------------------------------------*
 * Dashboard and Administrative Functionality
 *----------------------------------------------------------------------------*/

if ( is_admin() ) {

	require_once( plugin_dir_path( __FILE__ ) . 'lib/extended-meta-box.php' );
	require_once( plugin_dir_path( __FILE__ ) . 'admin/class-sparky-admin.php' );

	add_action( 'plugins_loaded', array( 'sparky_admin', 'get_instance' ) );

}
