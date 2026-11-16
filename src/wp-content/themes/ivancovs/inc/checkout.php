<?php
/**
 * Оформление заказа: без доставки и адреса, подписи на языке сайта.
 * Прибор забирают лично, доставки нет (см. Distances līgums).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Доставки нет: ни способов доставки, ни адреса доставки.
add_filter( 'woocommerce_cart_needs_shipping', '__return_false' );
add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );

/** Поля адреса скрываем и не требуем (остаются страна, имя, e-mail, телефон). */
function ivs_hide_address_fields( $fields ) {
	foreach ( array( 'address_1', 'address_2', 'city', 'state', 'postcode' ) as $k ) {
		if ( isset( $fields[ $k ] ) ) {
			$fields[ $k ]['required'] = false;
			$fields[ $k ]['hidden']   = true;
		}
	}
	return $fields;
}
add_filter( 'woocommerce_default_address_fields', 'ivs_hide_address_fields', 99 );

add_filter( 'woocommerce_get_country_locale', function ( $locale ) {
	foreach ( $locale as $cc => $fields ) {
		foreach ( array( 'address_1', 'address_2', 'city', 'state', 'postcode' ) as $k ) {
			$locale[ $cc ][ $k ]['required'] = false;
			$locale[ $cc ][ $k ]['hidden']   = true;
		}
	}
	return $locale;
}, 99 );

// Стандартную английскую строку «By proceeding… Terms and Conditions» убираем: есть своя галочка и ссылка.
add_filter( 'render_block', function ( $html, $block ) {
	return 'woocommerce/checkout-terms-block' === $block['blockName'] ? '' : $html;
}, 10, 2 );

// Подписи блочной корзины и оформления, которых нет в переводе WooCommerce.
add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'is_checkout' ) || ! ( is_checkout() || is_cart() ) ) {
		return;
	}
	$ver = (string) filemtime( get_theme_file_path( 'assets/checkout-i18n.js' ) );
	wp_enqueue_script( 'ivancovs-checkout-i18n', get_template_directory_uri() . '/assets/checkout-i18n.js', array(), $ver, array( 'in_footer' => true ) );
	wp_add_inline_script( 'ivancovs-checkout-i18n', 'window.IVS_I18N=' . wp_json_encode( array(
		'optional' => ivs_t( 'neobligāti', 'необязательно', 'optional' ),
		'add'      => ivs_t( 'Pievienot', 'Добавить', 'Add' ),
		'extra'    => ivs_t( 'Papildu informācija', 'Дополнительная информация', 'Additional information' ),
		'phone'    => ivs_t( 'Tālrunis', 'Телефон', 'Phone' ),
		'coupon'   => ivs_t( 'Pievienot kuponu', 'Добавить купон', 'Add coupon' ),
		'total'    => ivs_t( 'Kopā par', 'Всего за', 'Total for' ),
	) ) . ';', 'before' );
} );

/* ---------- Юридические лица (необязательные поля) ---------- */

// Название организации: необязательное поле в оформлении.
add_filter( 'pre_option_woocommerce_checkout_company_field', function () {
	return 'optional';
} );

/** Рег. номер, номер плательщика PVN и юридический адрес. Заполняют только юрлица. */
add_action( 'woocommerce_init', function () {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}
	$fields = array(
		'reg-nr'        => ivs_t( 'Reģistrācijas Nr. (juridiskai personai)', 'Регистрационный № (для юрлиц)', 'Registration No. (companies)' ),
		'vat-nr'        => ivs_t( 'PVN Nr. (ja ir)', 'Номер плательщика PVN (если есть)', 'VAT No. (if any)' ),
		'legal-address' => ivs_t( 'Juridiskā adrese (juridiskai personai)', 'Юридический адрес (для юрлиц)', 'Registered address (companies)' ),
	);
	foreach ( $fields as $key => $label ) {
		woocommerce_register_additional_checkout_field( array(
			'id'       => 'ivancovs/' . $key,
			'label'    => $label,
			'location' => 'address',
			'type'     => 'text',
			'required' => false,
		) );
	}
} );

/** Данные юрлица из заказа: [ reg, vat, address ]. */
function ivs_order_company_data( $order ) {
	$get = function ( $k ) use ( $order ) {
		$v = $order->get_meta( '_wc_billing/ivancovs/' . $k );
		return is_string( $v ) ? trim( $v ) : '';
	};
	return array( $get( 'reg-nr' ), $get( 'vat-nr' ), $get( 'legal-address' ) );
}

// Данные юрлица в админке заказа и в письмах.
add_action( 'woocommerce_admin_order_data_after_billing_address', function ( $order ) {
	list( $reg, $vat, $addr ) = ivs_order_company_data( $order );
	if ( $reg || $vat || $addr ) {
		echo '<p><strong>Juridiskā persona:</strong> ' . esc_html( trim( $order->get_billing_company() ) ) . '<br>Reģ. Nr.: ' . esc_html( $reg ) . '<br>PVN Nr.: ' . esc_html( $vat ) . '<br>Adrese: ' . esc_html( $addr ) . '</p>';
	}
} );
