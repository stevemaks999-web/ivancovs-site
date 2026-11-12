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

/**
 * Тексты приборов на RU и EN лежат в полях товара (_ivs_short_ru, _ivs_desc_ru, _ivs_short_en, _ivs_desc_en).
 * Товар один, наличие и брони общие. Если поля нет, показываем латышский текст.
 */
function ivs_product_meta_text( $post_id, $key ) {
	$lang = ivs_lang();
	if ( 'lv' === $lang ) {
		return '';
	}
	$val = get_post_meta( $post_id, '_ivs_' . $key . '_' . $lang, true );
	return is_string( $val ) ? $val : '';
}

add_filter( 'get_the_excerpt', function ( $excerpt, $post ) {
	if ( $post && 'product' === $post->post_type ) {
		$t = ivs_product_meta_text( $post->ID, 'short' );
		if ( '' !== $t ) {
			return $t;
		}
	}
	return $excerpt;
}, 20, 2 );

add_filter( 'the_content', function ( $content ) {
	$post = get_post();
	if ( $post && 'product' === $post->post_type && in_the_loop() ) {
		$t = ivs_product_meta_text( $post->ID, 'desc' );
		if ( '' !== $t ) {
			return $t;
		}
	}
	return $content;
}, 5 );

/** Названия категорий приборов и комплектов на RU и EN (в базе они латышские). */
function ivs_cat_names() {
	return array(
		'Gaisa plūsma un ventilācija'          => array( 'Поток воздуха и вентиляция', 'Airflow and ventilation' ),
		'Temperatūra un mitrums'               => array( 'Температура и влажность', 'Temperature and humidity' ),
		'Spiediens'                            => array( 'Давление', 'Pressure' ),
		'CO₂ un gaisa kvalitāte'               => array( 'CO₂ и качество воздуха', 'CO₂ and air quality' ),
		'Skaņa, gaisma, elektrība'             => array( 'Звук, свет, электричество', 'Sound, light, electricity' ),
		'Biezums, vibrācija, ultraskaņa'       => array( 'Толщина, вибрация, ультразвук', 'Thickness, vibration, ultrasound' ),
		'Gāzes un dūmi'                        => array( 'Газы и дым', 'Gases and smoke' ),
		'Termokameras un endoskopi'            => array( 'Тепловизоры и эндоскопы', 'Thermal cameras and endoscopes' ),
		'Komplekti'                            => array( 'Комплекты', 'Kits' ),
		'Ventilācija un gaiss'                 => array( 'Вентиляция и воздух', 'Ventilation and air' ),
		'Apkure, aukstums un siltums'          => array( 'Отопление, холод и тепло', 'Heating, cooling and heat' ),
		'Auto, grīdas, santehnika, drošība'    => array( 'Авто, полы, сантехника, безопасность', 'Cars, floors, plumbing, safety' ),
		'Sertifikācija, elektrība un noliktavas' => array( 'Аттестация, электрика и склады', 'Certification, electrical and warehouses' ),
	);
}

add_filter( 'get_term', function ( $term ) {
	if ( $term instanceof WP_Term && 'product_cat' === $term->taxonomy && ! is_admin() ) {
		$lang = ivs_lang();
		$map  = ivs_cat_names();
		if ( 'lv' !== $lang && isset( $map[ $term->name ] ) ) {
			$term->name = $map[ $term->name ][ 'ru' === $lang ? 0 : 1 ];
		}
	}
	return $term;
} );
