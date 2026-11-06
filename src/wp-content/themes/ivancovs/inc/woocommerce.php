<?php
/**
 * WooCommerce: каталог аренды без лишнего.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Свои шаблоны каталога и страницы прибора: убираем стандартные обёртки.
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10 );

add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

add_filter( 'loop_shop_per_page', function () {
	return 24;
} );

/** Цена за сутки в карточке. */
function ivs_price_html( $product ) {
	$price = $product->get_price();
	if ( '' === $price ) {
		return '';
	}
	return '<div class="price">' . wp_kses_post( wc_price( $price ) ) . ' <small>/ ' . esc_html( ivs_t( 'diena', 'сутки', 'day' ) ) . '</small></div>';
}

/** Ссылки-чипы для фильтра. */
function ivs_chip( $url, $label, $on = false ) {
	printf( '<a href="%s"%s>%s</a>', esc_url( $url ), $on ? ' class="on"' : '', esc_html( $label ) );
}
