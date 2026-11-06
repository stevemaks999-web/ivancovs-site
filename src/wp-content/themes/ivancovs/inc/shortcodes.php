<?php
/**
 * Шорткоды старой темы, которые используют страницы «Apmācības»: [verticaltabs] и [verticaltab].
 * Без них на страницах видны сырые теги. Показываем разделы как раскрывающиеся блоки.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! shortcode_exists( 'verticaltabs' ) ) {
	add_shortcode( 'verticaltabs', function ( $atts, $content = '' ) {
		return '<div class="ivs-tabs">' . do_shortcode( $content ) . '</div>';
	} );
}

if ( ! shortcode_exists( 'verticaltab' ) ) {
	add_shortcode( 'verticaltab', function ( $atts, $content = '' ) {
		$atts = shortcode_atts( array( 'title' => '' ), $atts, 'verticaltab' );
		return '<details class="ivs-tab"><summary>' . esc_html( $atts['title'] ) . '</summary><div class="ivs-tab-body">' . do_shortcode( wpautop( $content ) ) . '</div></details>';
	} );
}
