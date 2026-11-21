<?php
/**
 * Корзина и оформление заказа без лишнего: без «New in store» и купонов (строки корзины переводит checkout-i18n.js).
 * Оформление заказа остаётся чистым: никаких предложений и подсказок.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Купоны не используем: поле скрывается в корзине и в оформлении.
add_filter( 'woocommerce_coupons_enabled', '__return_false' );

// Пустая корзина: без витрины «New in store» (её кнопки добавляют прибор без выбора дат).
add_filter( 'render_block_woocommerce/product-new', '__return_empty_string' );

add_filter( 'render_block_woocommerce/empty-cart-block', function ( $content ) {
	$title = esc_html( ivs_t( 'Jūsu grozs ir tukšs', 'Ваша корзина пуста', 'Your cart is empty' ) );
	$content = preg_replace( '#(<h2[^>]*wc-block-cart__empty-cart__title[^>]*>).*?(</h2>)#s', '$1' . $title . '$2', $content, 1 );
	// Всё после заголовка (разделитель, «New in store») убираем и даём ссылку в каталог.
	$link = '<p class="has-text-align-center"><a style="display:inline-block;background:var(--acc,#2563eb);color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;font-weight:600" href="' . esc_url( ivs_lang_url( ivs_shop_url() ) ) . '">'
		. esc_html( ivs_t( 'Skatīt instrumentus', 'Посмотреть приборы', 'Browse instruments' ) ) . '</a></p>';
	return preg_replace( '#(<h2[^>]*wc-block-cart__empty-cart__title[^>]*>.*?</h2>).*$#s', '$1' . $link . '</div>', $content, 1 );
} );
