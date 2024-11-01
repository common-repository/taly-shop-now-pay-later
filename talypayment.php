<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              www.google.com
 * @since             1.0.0
 * @package           Talypayment
 *
 * @wordpress-plugin
 * Plugin Name:       Taly- Shop Now Pay Later
 * Plugin URI:        https://dev-taly.io/
 * Description:       Shopping with Taly is more enjoyable. Divide your purchase into four interest-free installments or pay in full within 30 days.
 * Version:           1.0.2
 * Author:            Taly Payment
 * Author URI:        https://www.dev-taly.io/home
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       talypayment
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'TALYPAYMENT_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-talypayment-activator.php
 */
function activate_talypayment() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-talypayment-activator.php';
	Talypayment_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-talypayment-deactivator.php
 */
function deactivate_talypayment() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-talypayment-deactivator.php';
	Talypayment_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_talypayment' );
register_deactivation_hook( __FILE__, 'deactivate_talypayment' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-talypayment.php';


function talypayment_settings_link($links) { 
          $settings_link = '<a href="/wp-admin/admin.php?page=wc-settings&tab=checkout&section=talypayment">Settings</a>'; 
          array_unshift($links, $settings_link); 
          return $links; 
}

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway.
 */
add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ),'talypayment_settings_link' );

/*
 * This action hook registers our PHP class as a WooCommerce payment gateway.
 */
add_filter( 'woocommerce_payment_gateways', 'talypayment_add_gateway_class' );


/**
 * Adding our Gateway to Woocommerce.
 *
 * @since    1.0.0
 * @param    array 	$gateways   Array of existing gateways.
 */
function talypayment_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Talypayment_Gateway'; // Your class name is here.
	return $gateways;
}

add_action( 'plugins_loaded', 'talypayment_init_gateway_classes' );

/**
 * Adding our Gateway Class to Woocommerce.
 *
 * @since    1.0.0
 */
function talypayment_init_gateway_classes(){

/**
 * The Gateway class for talypayment.
 *
 * @package    talypayment
 * @author     Taly Payment 
 * 
 */
require_once plugin_dir_path( __FILE__) . 'includes/woo-payment.php';
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_talypayment() {

	$plugin = new Talypayment();
	$plugin->run();

}

run_talypayment();
