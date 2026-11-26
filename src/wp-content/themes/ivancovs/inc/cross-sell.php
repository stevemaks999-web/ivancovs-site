<?php
/**
 * Перекрёстные продажи: «Часто берут вместе».
 * Основа: готовые комплекты. Если приборы вместе входят в комплект, их берут под одну задачу.
 * На странице прибора: другие приборы из его комплектов и сами комплекты. В корзине: то же по составу заказа.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Комплекты: [id комплекта => [id прибора => штук]]. */
function ivs_cross_kits() {
	static $kits = null;
	if ( null !== $kits ) {
		return $kits;
	}
	$kits = array();
	if ( ! function_exists( 'ivs_book_components' ) ) {
		return $kits;
	}
	$ids = get_posts( array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array( array( 'key' => '_ivs_kit_items', 'value' => '', 'compare' => '!=' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
	) );
	foreach ( $ids as $kid ) {
		$comp = ivs_book_components( $kid );
		if ( isset( $comp[ $kid ] ) ) {
			continue; // состав не распознан
		}
		$kits[ (int) $kid ] = $comp;
	}
	return $kits;
}

/**
 * Что предложить к набору товаров (id приборов или комплектов).
 * Возвращает [ 'items' => [id...], 'kits' => [id...] ].
 */
function ivs_cross_suggest( $product_ids, $limit_items = 3, $limit_kits = 1 ) {
	$out  = array( 'items' => array(), 'kits' => array() );
	$kits = ivs_cross_kits();
	if ( ! $kits || ! $product_ids ) {
		return $out;
	}
	$have = array(); // приборы, которые уже в заказе (с учётом состава комплектов)
	$base = array(); // отдельные приборы, от которых строим предложения
	foreach ( $product_ids as $pid ) {
		$pid = (int) $pid;
		if ( isset( $kits[ $pid ] ) ) {
			foreach ( $kits[ $pid ] as $cid => $n ) {
				$have[ $cid ] = true;
			}
		} else {
			$have[ $pid ] = true;
			$base[]       = $pid;
		}
	}
	if ( ! $base ) {
		return $out;
	}
	$score = array();
	$hits  = array();
	foreach ( $kits as $kid => $comp ) {
		if ( in_array( $kid, $product_ids, true ) ) {
			continue;
		}
		$common = array_intersect( $base, array_keys( $comp ) );
		if ( ! $common ) {
			continue;
		}
		$hits[ $kid ] = count( $common );
		foreach ( $comp as $cid => $n ) {
			if ( empty( $have[ $cid ] ) ) {
				$score[ $cid ] = ( $score[ $cid ] ?? 0 ) + 1;
			}
		}
	}
	arsort( $score );
	arsort( $hits );
	foreach ( array_keys( $score ) as $cid ) {
		if ( 'publish' === get_post_status( $cid ) ) {
			$out['items'][] = (int) $cid;
		}
		if ( count( $out['items'] ) >= $limit_items ) {
			break;
		}
	}
	// Комплект предлагаем, если он добавляет к заказу хотя бы один новый прибор.
	foreach ( array_keys( $hits ) as $kid ) {
		$new = array_diff( array_keys( $kits[ $kid ] ), array_keys( $have ) );
		if ( $new ) {
			$out['kits'][] = (int) $kid;
		}
		if ( count( $out['kits'] ) >= $limit_kits ) {
			break;
		}
	}
	return $out;
}

function ivs_cross_card( $id ) {
	$p = wc_get_product( $id );
	if ( ! $p ) {
		return '';
	}
	$img = $p->get_image_id() ? wp_get_attachment_image( $p->get_image_id(), 'medium_large' ) : '';
	$why = has_excerpt( $id ) ? wp_trim_words( wp_strip_all_tags( get_the_excerpt( $id ) ), 18, '…' ) : '';
	return '<a class="card" href="' . esc_url( ivs_lang_url( get_permalink( $id ) ) ) . '"><div class="ph">' . $img . '</div><div class="b"><h3>' // phpcs:ignore
		. esc_html( get_the_title( $id ) ) . '</h3>' . ( $why ? '<p class="mut why">' . esc_html( $why ) . '</p>' : '' ) . ivs_price_html( $p ) . '</div></a>'; // phpcs:ignore
}

function ivs_cross_kit_row( $kid ) {
	$p = wc_get_product( $kid );
	if ( ! $p ) {
		return '';
	}
	$names = array();
	foreach ( ivs_cross_kits()[ $kid ] ?? array() as $cid => $n ) {
		$names[] = ( $n > 1 ? $n . '× ' : '' ) . get_the_title( $cid );
	}
	$img = $p->get_image_id() ? wp_get_attachment_image( $p->get_image_id(), 'medium_large' ) : '';
	return '<a class="card kitrow" href="' . esc_url( ivs_lang_url( get_permalink( $kid ) ) ) . '"><div class="ph">' . $img . '</div><div class="b"><span class="kitb">'
		. esc_html( ivs_t( 'Gatavs komplekts', 'Готовый комплект', 'Ready-made kit' ) ) . '</span><h3>' . esc_html( get_the_title( $kid ) ) . '</h3>'
		. '<div class="mut">' . esc_html( implode( ', ', $names ) ) . '</div>' . ivs_price_html( $p ) . '</div></a>'; // phpcs:ignore
}

/** HTML блока. $heading: заголовок, $sug: результат ivs_cross_suggest. */
function ivs_cross_html( $heading, $sug ) {
	if ( empty( $sug['items'] ) && empty( $sug['kits'] ) ) {
		return '';
	}
	$h = '<section class="cross"><h2>' . esc_html( $heading ) . '</h2>';
	if ( ! empty( $sug['items'] ) ) {
		$h .= '<div class="grid ' . ( count( $sug['items'] ) > 1 ? 'g3' : 'g1' ) . '">';
		foreach ( $sug['items'] as $id ) {
			$h .= ivs_cross_card( $id );
		}
		$h .= '</div>';
	}
	foreach ( $sug['kits'] as $kid ) {
		$h .= ivs_cross_kit_row( $kid );
	}
	return $h . '</section>';
}

// Страница прибора (после карточки брони).
add_action( 'ivs_after_booking', function ( $product ) {
	if ( ! $product ) {
		return;
	}
	echo ivs_cross_html( ivs_t( 'Bieži nomā kopā', 'Часто берут вместе', 'Often rented together' ), ivs_cross_suggest( array( $product->get_id() ) ) ); // phpcs:ignore
} );

// Корзина (блочная): под таблицей заказа.
add_filter( 'render_block_woocommerce/cart', function ( $content ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $content;
	}
	$ids = array();
	foreach ( WC()->cart->get_cart() as $row ) {
		$ids[] = (int) $row['product_id'];
	}
	$html = ivs_cross_html( ivs_t( 'Pievienot pie pasūtījuma', 'Добавьте к заказу', 'Add to your order' ), ivs_cross_suggest( $ids ) );
	if ( '' === $html ) {
		return $content;
	}
	// Когда последнюю позицию убрали, корзина пуста: предложения прячем.
	$js = '<script>(function(){function f(){var c=document.querySelector(".cross");if(!c)return;c.style.display=document.querySelector(".wc-block-cart-items")?"":"none";}f();new MutationObserver(f).observe(document.body,{childList:true,subtree:true});})();</script>';
	return $content . $html . $js; // phpcs:ignore
} );
