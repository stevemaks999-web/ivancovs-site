<?php
/**
 * Plugin Name: Ivancovs Project Status
 * Description: Public project dashboard (plan progress, GitHub commits, pull requests, issues, checks). Use shortcode [ivancovs_status].
 * Version: 0.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ivs_has_shortcode() {
	$post = get_post();
	return $post && has_shortcode( $post->post_content, 'ivancovs_status' );
}

add_filter( 'wp_robots', function ( $robots ) {
	if ( is_singular() && ivs_has_shortcode() ) {
		$robots['noindex']  = true;
		$robots['nofollow'] = true;
	}
	return $robots;
} );

add_action( 'template_redirect', function () {
	if ( is_singular() && ivs_has_shortcode() ) {
		nocache_headers();
	}
} );

add_shortcode( 'ivancovs_status', function () {
	$ver = '0.2.0';
	wp_enqueue_style( 'ivs-css', plugins_url( 'assets/ivs.css', __FILE__ ), array(), $ver );
	wp_enqueue_script( 'ivs-js', plugins_url( 'assets/ivs.js', __FILE__ ), array(), $ver, true );

	return '<div id="ivs-app" class="ivs-app" data-json="' . esc_url( plugins_url( 'status.json', __FILE__ ) ) . '">'
		. '<p class="ivs-loading">Loading…</p></div>';
} );
