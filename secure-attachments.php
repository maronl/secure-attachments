<?php
/**
 * The file responsible for starting the Secure Attachments plugin
 *
 * The Secure Attachments is a plugin that allow users to associate files to a given post. Files are stored as
 * attachment for the post and are not shared in the media library. They are stored in a dedicated folder not accessible
 * with a direct url so they can be protected.
 * Attachments are associate to a single post but they can be showed also on other pages knowing the right url.
 *
 * @package SAMARONL
 *
 * @wordpress-plugin
 * Plugin Name: Secure Attachments
 * Plugin URI: http://
 * Description: Secure Attachments is a plugin that allow users to associate files to a given post not using media library but a private and secure folder
 * Version: 1.0.0
 * Author: Luca Maroni
 * Author URI: http://maronl.it
 * Text Domain: secure-attachments
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path: /languages
 */

// If this file is called directly, then abort execution.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Include the core class responsible for loading all necessary components of the plugin.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-secure-attachments-manager.php';

/**
 * Instantiates the Single Post Meta Manager class and then
 * calls its run method officially starting up the plugin.
 */
function run_secure_attachments_manager() {

    $sam = new Secure_Attachments_Manager();
    $sam->run();

}

// Call the above function to begin execution of the plugin.
run_secure_attachments_manager();
