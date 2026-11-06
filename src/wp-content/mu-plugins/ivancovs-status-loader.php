<?php
/**
 * Plugin Name: Ivancovs Status Loader
 * Description: Loads the ivancovs-status plugin without manual activation (must-use).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ivs_main = WP_PLUGIN_DIR . '/ivancovs-status/ivancovs-status.php';
if ( is_readable( $ivs_main ) ) {
	require_once $ivs_main;
}
