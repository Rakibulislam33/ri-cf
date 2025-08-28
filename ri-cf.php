<?php
/**
 * Plugin Name: RI Contact Form
 * Plugin URI:  https://example.com/
 * Description: Simple OOP contact form plugin with DB table, AJAX submission, validation and admin entries list.
 * Version:     1.1.0
 * Author:      Rakibul Islam
 * Author URI:  https://rakibulislam33.github.io/
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 * Text Domain: ri-cf
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'RICF_PATH', plugin_dir_path( __FILE__ ) );
define( 'RICF_URL', plugin_dir_url( __FILE__ ) );

// Autoload classes
require_once RICF_PATH . 'includes/class-loader.php';


// Require Activator & Deactivator
require_once RICF_PATH . 'includes/class-activator.php';
require_once RICF_PATH . 'includes/class-deactivator.php';


register_activation_hook( __FILE__, array( 'RICF_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'RICF_Deactivator', 'deactivate' ) );

// Initialize plugin
function ri_cf_init() {
    $loader = new RICF_Loader();
    $loader->init();
}
add_action( 'plugins_loaded', 'ri_cf_init' );