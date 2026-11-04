<?php
/**
 * Plugin Name: Ivancovs Cookie Consent Loader
 * Description: Loads the ivancovs-cookie-consent plugin without manual activation (must-use).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ivc_main = WP_PLUGIN_DIR . '/ivancovs-cookie-consent/ivancovs-cookie-consent.php';
if ( is_readable( $ivc_main ) ) {
	require_once $ivc_main;
}
