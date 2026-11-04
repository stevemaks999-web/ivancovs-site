<?php
/**
 * Plugin Name: Ivancovs Project Status
 * Description: Client-facing project dashboard (plan checklist, GitHub issues/PRs, Gatekeeper checks). Use shortcode [ivancovs_status].
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ivs_load_data() {
	$file = __DIR__ . '/status.json';
	if ( ! is_readable( $file ) ) {
		return null;
	}
	$data = json_decode( (string) file_get_contents( $file ), true );
	return is_array( $data ) ? $data : null;
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

function ivs_check_icon( $state ) {
	$map = array( 'success' => '✅', 'failure' => '❌', 'pending' => '⏳', 'none' => '—' );
	return $map[ $state ] ?? '—';
}

add_shortcode( 'ivancovs_status', function () {
	if ( ! is_user_logged_in() ) {
		return '<p>' . esc_html__( 'Please log in to view the project status.', 'ivancovs-status' ) . '</p>'
			. '<p><a href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log in', 'ivancovs-status' ) . '</a></p>';
	}

	$d = ivs_load_data();
	if ( ! $d ) {
		return '<p>' . esc_html__( 'No data yet.', 'ivancovs-status' ) . '</p>';
	}

	$out  = '<style>.ivs{max-width:960px;margin:0 auto;font:16px/1.5 system-ui,sans-serif}.ivs h2{margin:2rem 0 .5rem}'
		. '.ivs .bar{background:#e5e7eb;border-radius:6px;height:10px;overflow:hidden}.ivs .bar i{display:block;height:100%;background:#16a34a}'
		. '.ivs .ph{margin:.75rem 0;padding:.75rem 1rem;border:1px solid #e5e7eb;border-radius:8px}.ivs .ph.cur{border-color:#2563eb;background:#eff6ff}'
		. '.ivs table{width:100%;border-collapse:collapse}.ivs td,.ivs th{padding:.4rem .5rem;border-bottom:1px solid #e5e7eb;text-align:left}'
		. '.ivs ul{list-style:none;padding-left:0}.ivs small{color:#6b7280}</style><div class="ivs">';

	$out .= '<p><small>' . esc_html( sprintf( 'Updated: %s', $d['generated_at'] ?? '' ) );
	if ( ! empty( $d['commit'] ) ) {
		$out .= ' · ' . esc_html( 'commit ' . substr( $d['commit'], 0, 7 ) ) . ' (' . esc_html( $d['branch'] ?? '' ) . ')';
	}
	$out .= '</small></p>';

	$out .= '<h2>Plan</h2>';
	foreach ( (array) ( $d['phases'] ?? array() ) as $ph ) {
		$pct  = $ph['total'] ? round( 100 * $ph['done'] / $ph['total'] ) : 0;
		$out .= '<div class="ph' . ( ! empty( $ph['current'] ) ? ' cur' : '' ) . '"><strong>' . esc_html( $ph['title'] ) . '</strong> '
			. '<small>' . (int) $ph['done'] . '/' . (int) $ph['total'] . ' · ' . (int) $pct . '%' . ( ! empty( $ph['current'] ) ? ' · in progress' : '' ) . '</small>'
			. '<div class="bar"><i style="width:' . (int) $pct . '%"></i></div><details><summary>Tasks</summary><ul>';
		foreach ( $ph['items'] as $it ) {
			$out .= '<li>' . ( $it['done'] ? '☑' : '☐' ) . ' ' . esc_html( $it['text'] ) . '</li>';
		}
		$out .= '</ul></details></div>';
	}

	$out .= '<h2>Pull requests</h2><table><tr><th>#</th><th>Title</th><th>Status</th><th>Gatekeeper</th></tr>';
	foreach ( (array) ( $d['pulls'] ?? array() ) as $pr ) {
		$out .= '<tr><td><a href="' . esc_url( $pr['url'] ) . '">' . (int) $pr['number'] . '</a></td><td>' . esc_html( $pr['title'] ) . '</td><td>'
			. esc_html( $pr['state'] ) . '</td><td>' . ivs_check_icon( $pr['checks'] ?? 'none' ) . '</td></tr>';
	}
	$out .= '</table>';

	$out .= '<h2>Issues</h2><table><tr><th>#</th><th>Title</th><th>State</th></tr>';
	foreach ( (array) ( $d['issues'] ?? array() ) as $is ) {
		$out .= '<tr><td><a href="' . esc_url( $is['url'] ) . '">' . (int) $is['number'] . '</a></td><td>' . esc_html( $is['title'] ) . '</td><td>' . esc_html( $is['state'] ) . '</td></tr>';
	}
	$out .= '</table></div>';

	return $out;
} );
