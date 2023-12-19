<?php

/**
 * Plugin abstracting LinkedEvents integration.
 *
 * @link              https://www.evermade.fi
 * @since             1.0.0
 * @package           Logic
 *
 * @wordpress-plugin
 * Plugin Name:       LinkedEvents integration
 * Plugin URI:        https://www.evermade.fi
 * Description:       Plugin providing LinkedEvents integration to theme.
 * Version:           2.1.0
 * Author:            Juha Lehtonen, Jaakko Alajoki
 * Author URI:        https://www.evermade.fi
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       linkedevents
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

add_action( 'plugins_loaded', __NAMESPACE__ . '\\load_plugin_files' );
function load_plugin_files(): void {
	$includes = array(
		'class-linked-events', // Core class for performing API queries.
		'functions/rewrites', // Rewrites to create "virtual" store pages.
		'functions/api', // Rest API endpoints.
		'functions/cron', // Cron tasks.
		'functions/menu-page', // Settings page
		'functions/settings', // Settings
	);

	$path = trailingslashit( plugin_dir_path( __FILE__ ) );

	foreach ( $includes as $file ) {
		require_once "{$path}includes/{$file}.php";
	}

	define( 'LINKEDEVENTS_ACTIVE', true );
}
