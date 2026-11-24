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
		'proceed'  => ivs_t( 'Turpināt uz pasūtījumu', 'Перейти к оформлению', 'Proceed to checkout' ),
		'estimate' => ivs_t( 'Kopā', 'Итого', 'Total' ),
		'lookupUrl'  => esc_url_raw( rest_url( 'ivancovs/v1/company' ) ),
		'lookupBtn'  => ivs_t( 'Aizpildīt rekvizītus pēc reģ. Nr.', 'Заполнить реквизиты по рег. номеру', 'Fill in company details by reg. no.' ),
		'lookupBusy' => ivs_t( 'Meklējam…', 'Ищем…', 'Searching…' ),
		'lookupNeed' => ivs_t( 'Ievadiet reģistrācijas numuru (11 cipari) un nospiediet pogu.', 'Введите регистрационный номер (11 цифр) и нажмите кнопку.', 'Enter the registration number (11 digits) and press the button.' ),
		'lookupNone' => ivs_t( 'Uzņēmums netika atrasts. Aizpildiet datus manuāli.', 'Фирма не найдена. Заполните данные вручную.', 'Company not found. Please fill in the details manually.' ),
		'lookupErr'  => ivs_t( 'Neizdevās ielādēt datus. Aizpildiet manuāli.', 'Не удалось загрузить данные. Заполните вручную.', 'Could not load the data. Please fill in manually.' ),
		'lookupOk'   => ivs_t( 'Rekvizīti aizpildīti. Pārbaudiet datus.', 'Реквизиты заполнены. Проверьте данные.', 'Details filled in. Please check them.' ),
		'lookupOff'  => ivs_t( 'Uzņēmums reģistrā ir likvidēts vai izslēgts.', 'Фирма в реестре ликвидирована или исключена.', 'The company is liquidated or struck off in the register.' ),
	) ) . ';', 'before' );
} );

/* ---------- Оплата наличными при получении ---------- */

// Способ «наличными при получении»: аренда и залог вносятся при выдаче прибора, без доставки.
function ivs_cod_settings( $o ) {
	$o = is_array( $o ) ? $o : array();
	$o['enabled']            = 'yes';
	$o['enable_for_virtual'] = 'yes';
	$o['enable_for_methods'] = array();
	$o['title']              = ivs_t( 'Skaidrā naudā saņemot', 'Наличными при получении', 'Cash on pickup' );
	$o['description']        = ivs_t(
		'Nomu un drošības naudu maksājat skaidrā naudā, saņemot instrumentu. Drošības nauda tiek atdota skaidrā naudā pēc instrumenta atgriešanas un pārbaudes.',
		'Аренду и залог вы платите наличными при получении прибора. Залог мы возвращаем наличными после возврата и осмотра прибора.',
		'You pay the rental and the deposit in cash when you collect the instrument. The deposit is returned in cash after the instrument is returned and checked.'
	);
	$o['instructions'] = ivs_t(
		'Nomu un drošības naudu maksājiet skaidrā naudā, saņemot instrumentu.',
		'Аренду и залог оплачиваете наличными при получении прибора.',
		'Pay the rental and the deposit in cash when you collect the instrument.'
	);
	return $o;
}
// Если настройки способа ещё не сохранялись, опции в базе нет, и срабатывает только фильтр значения по умолчанию.
add_filter( 'option_woocommerce_cod_settings', 'ivs_cod_settings' );
add_filter( 'default_option_woocommerce_cod_settings', 'ivs_cod_settings' );

// Заказ «наличными» ждёт подтверждения Дмитрием (как заказ с ожиданием оплаты), даты при этом заняты.
add_filter( 'woocommerce_cod_process_payment_order_status', function () {
	return 'on-hold';
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


/* ---------- Подтяжка реквизитов по рег. номеру (Латвия) ---------- */

// Поиск фирмы: название и юр. адрес из открытых данных Uzņēmumu reģistrs (data.gov.lv), номер PVN проверяется через VIES.
add_action( 'rest_api_init', function () {
	register_rest_route( 'ivancovs/v1', '/company', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'args'                => array( 'reg' => array( 'required' => true ) ),
		'callback'            => 'ivs_rest_company',
	) );
} );

function ivs_rest_company( WP_REST_Request $req ) {
	$reg = preg_replace( '/\D+/', '', (string) preg_replace( '/^\s*LV/i', '', (string) $req->get_param( 'reg' ) ) );
	if ( 11 !== strlen( $reg ) ) {
		return new WP_REST_Response( array( 'found' => false, 'error' => 'format' ), 400 );
	}
	$key = 'ivs_co_' . $reg;
	$hit = get_transient( $key );
	if ( is_array( $hit ) ) {
		return new WP_REST_Response( $hit );
	}
	// Не больше 20 запросов за 10 минут с одного адреса.
	$rl = 'ivs_corl_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	$n  = (int) get_transient( $rl );
	if ( $n >= 20 ) {
		return new WP_REST_Response( array( 'found' => false, 'error' => 'limit' ), 429 );
	}
	set_transient( $rl, $n + 1, 10 * MINUTE_IN_SECONDS );

	$url = add_query_arg( array(
		'resource_id' => '25e80bf3-f107-4ab4-89ef-251b5b9374e9',
		'filters'     => wp_json_encode( array( 'regcode' => $reg ) ),
	), 'https://data.gov.lv/dati/api/3/action/datastore_search' );
	$res = wp_remote_get( $url, array( 'timeout' => 8 ) );
	if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
		return new WP_REST_Response( array( 'found' => false, 'error' => 'upstream' ), 502 );
	}
	$rec = json_decode( wp_remote_retrieve_body( $res ), true )['result']['records'][0] ?? null;
	if ( ! $rec ) {
		$out = array( 'found' => false );
		set_transient( $key, $out, HOUR_IN_SECONDS );
		return new WP_REST_Response( $out );
	}
	$addr = trim( (string) ( $rec['address'] ?? '' ) );
	if ( $addr && ! empty( $rec['index'] ) ) {
		$addr .= ', LV-' . $rec['index'];
	}
	// Номер плательщика PVN: подтверждаем в VIES, иначе поле оставляем пустым.
	$vat = '';
	$v   = wp_remote_get( 'https://ec.europa.eu/taxation_customs/vies/rest-api/ms/LV/vat/' . $reg, array( 'timeout' => 6 ) );
	if ( ! is_wp_error( $v ) && 200 === wp_remote_retrieve_response_code( $v ) ) {
		$vj = json_decode( wp_remote_retrieve_body( $v ), true );
		if ( ! empty( $vj['isValid'] ) ) {
			$vat = 'LV' . $reg;
		}
	}
	$out = array(
		'found'   => true,
		'name'    => (string) ( $rec['name'] ?? '' ),
		'address' => $addr,
		'vat'     => $vat,
		'active'  => empty( $rec['terminated'] ) && '' === trim( (string) ( $rec['closed'] ?? '' ) ),
	);
	set_transient( $key, $out, DAY_IN_SECONDS );
	return new WP_REST_Response( $out );
}
