<?php
/**
 * Бронирование: даты, занятость, цена, залог, имя того, кто заберёт.
 *
 * Правила: выдача и возврат с 9:00 до 18:00, в выходные только по договорённости.
 * Бронь занимает прибор на все календарные дни от выдачи до возврата включительно.
 * Комплект занимает все свои приборы, а занятый прибор закрывает комплекты с ним.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const IVS_BOOK_DB_VERSION = '2';
const IVS_BOOK_HORIZON    = 365; // на сколько дней вперёд можно бронировать
const IVS_BOOK_TIME_FROM  = 9;
const IVS_BOOK_TIME_TO    = 18;

/** Таблица броней: одна строка на прибор и заказ. */
function ivs_book_table() {
	global $wpdb;
	return $wpdb->prefix . 'ivs_bookings';
}

function ivs_book_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$t = ivs_book_table();
	dbDelta( "CREATE TABLE {$t} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		order_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		item_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
		product_id BIGINT UNSIGNED NOT NULL,
		component_id BIGINT UNSIGNED NOT NULL,
		qty INT UNSIGNED NOT NULL DEFAULT 1,
		date_from DATE NOT NULL,
		date_to DATE NOT NULL,
		t_from VARCHAR(4) NOT NULL DEFAULT '',
		t_to VARCHAR(4) NOT NULL DEFAULT '',
		active TINYINT(1) NOT NULL DEFAULT 1,
		created DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY component_dates (component_id, active, date_from, date_to),
		KEY order_id (order_id)
	) {$wpdb->get_charset_collate()};" );
	update_option( 'ivs_book_db_version', IVS_BOOK_DB_VERSION, false );
}

add_action( 'init', function () {
	if ( get_option( 'ivs_book_db_version' ) !== IVS_BOOK_DB_VERSION ) {
		ivs_book_install();
	}
} );

/* ---------- Приборы и комплекты ---------- */

/** Сколько штук прибора есть (число в поле остатка; наличием WooCommerce при этом не управляет). */
function ivs_book_units( $product_id ) {
	$q = get_post_meta( $product_id, '_stock', true );
	return '' === $q ? 1 : max( 1, (int) $q );
}

/** Находит товар по названию без учёта регистра. Названия приборов уникальны. */
function ivs_book_find_by_title( $title ) {
	static $map = null;
	if ( null === $map ) {
		global $wpdb;
		$map  = array();
		$rows = $wpdb->get_results( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type='product' AND post_status IN ('publish','private')" ); // phpcs:ignore
		foreach ( $rows as $r ) {
			$map[ mb_strtolower( trim( html_entity_decode( $r->post_title ) ) ) ] = (int) $r->ID;
		}
	}
	return $map[ mb_strtolower( trim( $title ) ) ] ?? 0;
}

/**
 * Из чего состоит товар: [id прибора => сколько штук]. Для прибора это он сам.
 * Комплект собирается из поля _ivs_kit_items («Testo 420, 2× testo 549i»).
 * Если названия не нашлись, комплект считается одним целым прибором.
 */
function ivs_book_components( $product_id, $sel = null ) {
	$out = ivs_book_components_default( $product_id );
	if ( ! is_array( $sel ) || ! $sel || isset( $out[ (int) $product_id ] ) ) {
		return $out;
	}
	// Выбранное число приборов внутри комплекта: можно менять только позиции, где в составе больше одной штуки.
	$c = array();
	foreach ( $out as $cid => $def ) {
		$n         = isset( $sel[ $cid ] ) ? (int) $sel[ $cid ] : $def;
		$c[ $cid ] = $def > 1 ? max( 1, min( $n, ivs_book_units( $cid ) ) ) : $def;
	}
	return $c;
}

function ivs_book_components_default( $product_id ) {
	$kit = get_post_meta( $product_id, '_ivs_kit_items', true );
	if ( ! is_string( $kit ) || '' === trim( $kit ) ) {
		return array( (int) $product_id => 1 );
	}
	$out = array();
	foreach ( explode( ',', $kit ) as $part ) {
		$part = trim( $part );
		$n    = 1;
		if ( preg_match( '/^(\d+)\s*[×x]\s*(.+)$/u', $part, $m ) ) {
			$n    = (int) $m[1];
			$part = trim( $m[2] );
		}
		$id = ivs_book_find_by_title( $part );
		if ( ! $id ) {
			return array( (int) $product_id => 1 );
		}
		$out[ $id ] = ( $out[ $id ] ?? 0 ) + $n;
	}
	return $out ? $out : array( (int) $product_id => 1 );
}

/** Цена прибора за сутки (для расчёта доли при неполном составе комплекта). */
function ivs_book_day_price( $pid ) {
	$d = (float) get_post_meta( $pid, '_regular_price', true );
	return $d > 0 ? $d : (float) get_post_meta( $pid, '_price', true );
}

/** Состав комплекта для формы: [ ['id','name','def','max','price','fixed'] ]. Пусто, если менять нечего. */
function ivs_book_kit_comps( $pid ) {
	// Комплект берут целиком: выбор числа приборов внутри комплекта выключен (решение владельца). Включить: define( 'IVS_KIT_PARTIAL', true ).
	if ( ! ( defined( 'IVS_KIT_PARTIAL' ) && IVS_KIT_PARTIAL ) ) {
		return array();
	}
	$def = ivs_book_components_default( $pid );
	if ( ! ivs_book_is_kit( $pid ) || isset( $def[ (int) $pid ] ) ) {
		return array();
	}
	$out = array();
	$any = false;
	foreach ( $def as $cid => $n ) {
		$any   = $any || $n > 1;
		$out[] = array( 'id' => (int) $cid, 'name' => get_the_title( $cid ), 'def' => (int) $n, 'max' => $n > 1 ? ivs_book_units( $cid ) : (int) $n, 'total' => ivs_book_units( $cid ), 'start' => max( 1, min( (int) $n, ivs_book_units( $cid ) ) ), 'price' => ivs_book_day_price( $cid ), 'fixed' => $n <= 1 );
	}
	return $any ? $out : array();
}

/** Доля цены комплекта при выбранном составе: по суточной цене приборов. 1, если состав полный. */
function ivs_book_kit_ratio( $pid, $sel ) {
	if ( ! is_array( $sel ) || ! $sel ) {
		return 1.0;
	}
	$def = ivs_book_components_default( $pid );
	$cur = ivs_book_components( $pid, $sel );
	$a   = 0.0;
	$b   = 0.0;
	foreach ( $def as $cid => $n ) {
		$p  = ivs_book_day_price( $cid );
		$p  = $p > 0 ? $p : 1;
		$a += $p * ( $cur[ $cid ] ?? $n );
		$b += $p * $n;
	}
	return $b > 0 ? min( 1.0, round( $a / $b, 6 ) ) : 1.0;
}

/** Выбранный состав из запроса: только если он отличается от полного, иначе null. */
function ivs_book_posted_kit( $pid ) {
	$raw = isset( $_POST['ivs_kit'] ) && is_array( $_POST['ivs_kit'] ) ? wp_unslash( $_POST['ivs_kit'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
	if ( ! $raw || ! ivs_book_kit_comps( $pid ) ) {
		return null;
	}
	$sel = array();
	foreach ( $raw as $cid => $n ) {
		$sel[ (int) $cid ] = (int) $n;
	}
	$cur = ivs_book_components( $pid, $sel );
	return $cur === ivs_book_components_default( $pid ) ? null : $cur;
}

/** Состав позиции заказа (если клиент менял число приборов в комплекте). */
function ivs_book_item_sel( $item ) {
	$j = $item->get_meta( '_ivs_kit' );
	$a = is_string( $j ) && '' !== $j ? json_decode( $j, true ) : null;
	return is_array( $a ) && $a ? $a : null;
}

/** Все приборы, которые закрывает этот товар, и те комплекты, которые закрываются вместе с ним. */
function ivs_book_is_kit( $product_id ) {
	return '' !== trim( (string) get_post_meta( $product_id, '_ivs_kit_items', true ) );
}

/* ---------- Занятость ---------- */

function ivs_book_ymd( $ts ) {
	return gmdate( 'Y-m-d', $ts );
}

/**
 * Свободно штук на каждый день: [ 'Y-m-d' => число ] для товара (с учётом состава комплекта).
 * Возвращает только дни, где свободно меньше, чем всего (остальные свободны полностью).
 */
function ivs_book_busy_days( $product_id, $from, $to, $exclude_order = 0, $sel = null ) {
	global $wpdb;
	$t    = ivs_book_table();
	$comp = ivs_book_components( $product_id, $sel );
	$free = array(); // date => min free
	foreach ( $comp as $cid => $need ) {
		$total = ivs_book_units( $cid );
		$sql   = $wpdb->prepare(
			"SELECT date_from, date_to, qty FROM {$t} WHERE component_id=%d AND active=1 AND date_to>=%s AND date_from<=%s AND (order_id=0 OR order_id<>%d)", // phpcs:ignore
			$cid,
			$from,
			$to,
			$exclude_order
		);
		$used = array();
		foreach ( $wpdb->get_results( $sql ) as $r ) { // phpcs:ignore
			for ( $d = strtotime( $r->date_from . ' UTC' ); $d <= strtotime( $r->date_to . ' UTC' ); $d += DAY_IN_SECONDS ) {
				$k          = ivs_book_ymd( $d );
				$used[ $k ] = ( $used[ $k ] ?? 0 ) + (int) $r->qty;
			}
		}
		foreach ( $used as $day => $u ) {
			$units = (int) floor( ( $total - $u ) / $need );
			$units = max( 0, $units );
			if ( ! isset( $free[ $day ] ) || $units < $free[ $day ] ) {
				$free[ $day ] = $units;
			}
		}
	}
	return $free;
}

/** Сколько штук товара (комплекта) всего можно выдать одновременно, без учёта броней. */
function ivs_book_total_units( $product_id ) {
	$max = PHP_INT_MAX;
	foreach ( ivs_book_components( $product_id ) as $cid => $need ) {
		$max = min( $max, (int) floor( ivs_book_units( $cid ) / $need ) );
	}
	return PHP_INT_MAX === $max ? 1 : $max;
}

/** Сколько штук прибора свободно по дням: [ 'Y-m-d' => число ] (только дни, где свободно меньше, чем всего). */
function ivs_book_comp_free( $cid, $from, $to ) {
	global $wpdb;
	$total = ivs_book_units( $cid );
	$used  = array();
	$rows  = $wpdb->get_results( $wpdb->prepare( 'SELECT date_from, date_to, qty FROM ' . ivs_book_table() . ' WHERE component_id=%d AND active=1 AND date_to>=%s AND date_from<=%s', $cid, $from, $to ) ); // phpcs:ignore
	foreach ( $rows as $r ) {
		for ( $d = strtotime( $r->date_from . ' UTC' ); $d <= strtotime( $r->date_to . ' UTC' ); $d += DAY_IN_SECONDS ) {
			$k          = ivs_book_ymd( $d );
			$used[ $k ] = ( $used[ $k ] ?? 0 ) + (int) $r->qty;
		}
	}
	return array_map( function ( $u ) use ( $total ) {
		return max( 0, $total - $u );
	}, $used );
}

/** Сколько штук товара свободно на весь период (минимум по дням). */
function ivs_book_free_for_range( $product_id, $from, $to, $exclude_order = 0, $sel = null ) {
	$comp = ivs_book_components( $product_id, $sel );
	$max  = PHP_INT_MAX;
	foreach ( $comp as $cid => $need ) {
		$max = min( $max, (int) floor( ivs_book_units( $cid ) / $need ) );
	}
	$busy = ivs_book_busy_days( $product_id, $from, $to, $exclude_order, $sel );
	foreach ( $busy as $day => $f ) {
		if ( $day >= $from && $day <= $to ) {
			$max = min( $max, $f );
		}
	}
	return $max;
}

/* ---------- Даты и цена ---------- */

function ivs_book_is_ymd( $s ) {
	return is_string( $s ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $s ) && checkdate( (int) substr( $s, 5, 2 ), (int) substr( $s, 8, 2 ), (int) substr( $s, 0, 4 ) );
}

function ivs_book_is_weekend( $ymd ) {
	return (int) gmdate( 'N', strtotime( $ymd . ' UTC' ) ) >= 6;
}

/** Особые дни: выходные, открытые Дмитрием для выдачи, и будни, закрытые (праздники). */
function ivs_book_special_days() {
	$d = get_option( 'ivs_special_days', array() );
	return array(
		'open'   => isset( $d['open'] ) && is_array( $d['open'] ) ? array_values( $d['open'] ) : array(),
		'closed' => isset( $d['closed'] ) && is_array( $d['closed'] ) ? array_values( $d['closed'] ) : array(),
	);
}

/** Можно ли в этот день выдать и принять прибор. */
function ivs_book_day_open( $ymd ) {
	$d = ivs_book_special_days();
	if ( in_array( $ymd, $d['closed'], true ) ) {
		return false;
	}
	return ! ivs_book_is_weekend( $ymd ) || in_array( $ymd, $d['open'], true );
}

function ivs_book_parse_dates( $text ) {
	$out = array();
	foreach ( preg_split( '/[\s,;]+/', (string) $text, -1, PREG_SPLIT_NO_EMPTY ) as $t ) {
		if ( preg_match( '/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $t, $m ) ) {
			$t = sprintf( '%04d-%02d-%02d', $m[3], $m[2], $m[1] );
		}
		if ( ivs_book_is_ymd( $t ) ) {
			$out[ $t ] = $t;
		}
	}
	ksort( $out );
	return array_values( $out );
}

add_action( 'admin_menu', function () {
	add_submenu_page( 'woocommerce', 'Выходные и особые дни', 'Выходные и особые дни', 'manage_woocommerce', 'ivs-days', 'ivs_days_page' );
} );

function ivs_days_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	if ( isset( $_POST['ivs_days_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ivs_days_nonce'] ) ), 'ivs_days' ) ) {
		update_option( 'ivs_special_days', array(
			'open'   => ivs_book_parse_dates( isset( $_POST['ivs_open'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ivs_open'] ) ) : '' ),
			'closed' => ivs_book_parse_dates( isset( $_POST['ivs_closed'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ivs_closed'] ) ) : '' ),
		), false );
		echo '<div class="updated"><p>Сохранено.</p></div>';
	}
	$d     = ivs_book_special_days();
	$today = ivs_book_today();
	$keep  = function ( $list ) use ( $today ) {
		return implode( "\n", array_filter( $list, function ( $x ) use ( $today ) {
			return $x >= $today;
		} ) );
	};
	echo '<div class="wrap"><h1>Выходные и особые дни</h1>';
	echo '<p>Выдача и возврат с 9:00 до 18:00 по будням. По субботам и воскресеньям онлайн-бронь закрыта: откройте здесь нужные выходные, и клиенты смогут выбрать их для выдачи и возврата. Праздничные будни можно закрыть.</p>';
	echo '<form method="post"><table class="form-table">';
	echo '<tr><th><label for="ivs_open">Открытые выходные</label></th><td><textarea id="ivs_open" name="ivs_open" rows="6" cols="30">' . esc_textarea( $keep( $d['open'] ) ) . '</textarea><p class="description">Одна дата на строку: ГГГГ-ММ-ДД или ДД.ММ.ГГГГ. Эти субботы и воскресенья доступны для выдачи и возврата.</p></td></tr>';
	echo '<tr><th><label for="ivs_closed">Закрытые будни</label></th><td><textarea id="ivs_closed" name="ivs_closed" rows="6" cols="30">' . esc_textarea( $keep( $d['closed'] ) ) . '</textarea><p class="description">Праздники и дни, когда вы не работаете. В эти дни выдача и возврат недоступны.</p></td></tr>';
	wp_nonce_field( 'ivs_days', 'ivs_days_nonce' );
	echo '</table><p><button class="button button-primary">Сохранить</button></p></form></div>';
}

function ivs_book_today() {
	return wp_date( 'Y-m-d' );
}

function ivs_book_days( $from, $to ) {
	return (int) ( ( strtotime( $to . ' UTC' ) - strtotime( $from . ' UTC' ) ) / DAY_IN_SECONDS ) + 1;
}

/** Время выдачи и возврата: целый час или полчаса от 9:00 до 18:00. */
function ivs_book_valid_time( $t ) {
	if ( ! is_string( $t ) || ! preg_match( '/^(\d{1,2}):(00|30)$/', $t, $m ) ) {
		return false;
	}
	$mins = (int) $m[1] * 60 + (int) $m[2];
	return $mins >= IVS_BOOK_TIME_FROM * 60 && $mins <= IVS_BOOK_TIME_TO * 60;
}

/** Цена одной штуки за n дней: самое дешёвое сочетание суток, недель и месяцев. */
function ivs_book_price( $product_id, $days ) {
	$day = (float) get_post_meta( $product_id, '_regular_price', true );
	if ( $day <= 0 ) {
		$day = (float) get_post_meta( $product_id, '_price', true );
	}
	$units = array( array( 1, $day ) );
	$w     = (float) get_post_meta( $product_id, '_ivs_price_week', true );
	$m     = (float) get_post_meta( $product_id, '_ivs_price_month', true );
	if ( $w > 0 ) {
		$units[] = array( 7, $w );
	}
	if ( $m > 0 ) {
		$units[] = array( 30, $m );
	}
	$dp    = array( 0.0 );
	for ( $i = 1; $i <= $days; $i++ ) {
		$best = INF;
		foreach ( $units as $u ) {
			$best = min( $best, $dp[ max( 0, $i - $u[0] ) ] + $u[1] );
		}
		$dp[ $i ] = $best;
	}
	return round( $dp[ $days ], 2 );
}

function ivs_book_deposit( $product_id ) {
	return max( 0.0, (float) get_post_meta( $product_id, '_ivs_deposit', true ) );
}

/**
 * Проверяет выбранные даты. Возвращает текст ошибки или '' если всё в порядке.
 */
function ivs_book_validate( $product_id, $from, $to, $t_from, $t_to, $qty, $exclude_order = 0, $sel = null ) {
	if ( ! $exclude_order && function_exists( 'WC' ) && WC()->session ) {
		$exclude_order = (int) WC()->session->get( 'store_api_draft_order', 0 );
	}
	if ( ! ivs_book_is_ymd( $from ) || ! ivs_book_is_ymd( $to ) ) {
		return ivs_t( 'Izvēlieties datumus.', 'Выберите даты.', 'Please choose the dates.' );
	}
	if ( $to < $from ) {
		return ivs_t( 'Atgriešanas datums nevar būt agrāks par saņemšanas datumu.', 'Возврат не может быть раньше выдачи.', 'Return cannot be earlier than pickup.' );
	}
	if ( $from <= ivs_book_today() ) {
		return ivs_t( 'Rezervēt var no rītdienas.', 'Бронировать можно с завтрашнего дня.', 'Booking is possible from tomorrow.' );
	}
	if ( ivs_book_days( $from, $to ) > IVS_BOOK_HORIZON || $to > gmdate( 'Y-m-d', strtotime( ivs_book_today() . ' UTC' ) + IVS_BOOK_HORIZON * DAY_IN_SECONDS ) ) {
		return ivs_t( 'Datumi ir pārāk tālu.', 'Даты слишком далеко.', 'The dates are too far ahead.' );
	}
	if ( ! ivs_book_day_open( $from ) || ! ivs_book_day_open( $to ) ) {
		return ivs_t( 'Brīvdienās izsniegšana un atgriešana tikai pēc vienošanās ar Dmitriju.', 'В выходные и праздники выдача и возврат только по договорённости с Дмитрием.', 'Pickup and return are not available on this day (weekends by arrangement with Dmitrijs).' );
	}
	if ( ! ivs_book_valid_time( $t_from ) || ! ivs_book_valid_time( $t_to ) ) {
		return ivs_t( 'Izsniegšana un atgriešana no 9:00 līdz 18:00.', 'Выдача и возврат с 9:00 до 18:00.', 'Pickup and return between 9:00 and 18:00.' );
	}
	if ( $qty < 1 ) {
		return ivs_t( 'Norādiet skaitu.', 'Укажите количество.', 'Please set the quantity.' );
	}
	if ( ivs_book_free_for_range( $product_id, $from, $to, $exclude_order, $sel ) < $qty ) {
		return ivs_t( 'Šajos datumos instruments nav pieejams.', 'На эти даты прибор занят.', 'This instrument is not available on these dates.' );
	}
	return '';
}

/* ---------- REST: занятые дни для календаря ---------- */

add_action( 'rest_api_init', function () {
	register_rest_route( 'ivs/v1', '/availability/(?P<id>\d+)', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => function ( WP_REST_Request $req ) {
			$id = (int) $req['id'];
			if ( 'product' !== get_post_type( $id ) || 'publish' !== get_post_status( $id ) ) {
				return new WP_Error( 'ivs_no_product', 'Not found', array( 'status' => 404 ) );
			}
			$from = ivs_book_today();
			$to   = gmdate( 'Y-m-d', strtotime( $from . ' UTC' ) + IVS_BOOK_HORIZON * DAY_IN_SECONDS );
			$comp = ivs_book_components( $id );
			$max  = PHP_INT_MAX;
			$cfree = array();
			if ( ivs_book_kit_comps( $id ) ) {
				foreach ( $comp as $cid => $need ) {
					$cfree[ $cid ] = (object) ivs_book_comp_free( $cid, $from, $to );
				}
			}
			foreach ( $comp as $cid => $need ) {
				$max = min( $max, (int) floor( ivs_book_units( $cid ) / $need ) );
			}
			$res = new WP_REST_Response( array(
				'max'  => $max,
				'free' => (object) ivs_book_busy_days( $id, $from, $to ),
				'days' => ivs_book_special_days(),
				'cfree' => (object) $cfree,
			) );
			$res->header( 'Cache-Control', 'no-store' );
			return $res;
		},
	) );
} );

/* ---------- Форма на странице прибора ---------- */

add_action( 'ivs_booking_calendar', function ( $product ) {
	$id   = $product->get_id();
	$data = array(
		'id'      => $id,
		'max'     => max( 1, ivs_book_total_units( $id ) ),
		'comps'   => ivs_book_kit_comps( $id ),
		'rest'    => esc_url_raw( rest_url( 'ivs/v1/availability/' . $id ) ),
		'day'     => (float) $product->get_price(),
		'week'    => (float) get_post_meta( $id, '_ivs_price_week', true ),
		'month'   => (float) get_post_meta( $id, '_ivs_price_month', true ),
		'deposit' => ivs_book_deposit( $id ),
		'today'   => ivs_book_today(),
		'horizon' => IVS_BOOK_HORIZON,
		'cur'     => get_woocommerce_currency_symbol(),
		'lang'    => ivs_lang(),
		'months'  => ivs_t(
			array( 'janvāris', 'februāris', 'marts', 'aprīlis', 'maijs', 'jūnijs', 'jūlijs', 'augusts', 'septembris', 'oktobris', 'novembris', 'decembris' ),
			array( 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь' ),
			array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' )
		),
		't'       => array(
			'pick'     => ivs_t( 'Izvēlieties saņemšanas datumu', 'Выберите дату выдачи', 'Choose the pickup date' ),
			'ret'      => ivs_t( 'Tagad izvēlieties atgriešanas datumu', 'Теперь выберите дату возврата', 'Now choose the return date' ),
			'days'     => ivs_t( 'dienas', 'суток', 'days' ),
			'rent'     => ivs_t( 'Noma', 'Аренда', 'Rental' ),
			'dep'      => ivs_t( 'Drošības nauda (skaidrā naudā, saņemot)', 'Залог (наличными при получении)', 'Deposit (cash on pickup)' ),
			'qty'      => ivs_t( 'Skaits', 'Количество', 'Quantity' ),
			'time_in'  => ivs_t( 'Saņemšanas laiks', 'Время выдачи', 'Pickup time' ),
			'time_out' => ivs_t( 'Atgriešanas laiks', 'Время возврата', 'Return time' ),
			'weekend'  => ivs_t( 'Brīvdienas tikai pēc vienošanās (WhatsApp)', 'Выходные только по договорённости (WhatsApp)', 'Weekends by arrangement only (WhatsApp)' ),
			'busy'     => ivs_t( 'Šajā periodā ir aizņemtas dienas', 'В этом периоде есть занятые дни', 'Some days in this period are taken' ),
			'wd'       => ivs_lang() === 'lv' ? array( 'P', 'O', 'T', 'C', 'Pk', 'S', 'Sv' ) : ( ivs_lang() === 'ru' ? array( 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс' ) : array( 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su' ) ),
		),
	);
	?>
<form class="ivs-book" method="post" action="<?php echo esc_url( wc_get_cart_url() ); ?>" data-ivs="<?php echo esc_attr( wp_json_encode( $data ) ); ?>">
	<?php $ivs_total = ivs_book_total_units( $id ); ?>
	<?php $ivs_kc = ivs_book_kit_comps( $id ); ?>
	<?php if ( $ivs_kc ) : ?>
	<div class="ivs-kit">
		<b><?php ivs_e( 'Cik instrumentu nepieciešams', 'Сколько приборов нужно', 'How many instruments you need' ); ?></b>
		<?php foreach ( $ivs_kc as $c ) : ?>
			<?php if ( $c['fixed'] ) { continue; } ?>
			<label><span><?php echo esc_html( $c['name'] ); ?></span>
				<input type="number" name="ivs_kit[<?php echo (int) $c['id']; ?>]" value="<?php echo (int) $c['start']; ?>" min="1" max="<?php echo (int) $c['max']; ?>" step="1" inputmode="numeric">
				<small class="mut"><?php echo esc_html( ivs_t( 'no 1 līdz ' . $c['max'] . ', komplektā ' . $c['def'], 'от 1 до ' . $c['max'] . ', в комплекте ' . $c['def'], '1 to ' . $c['max'] . ', kit has ' . $c['def'] ) ); ?></small></label>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
	<label class="ivs-qty"<?php echo ( $ivs_total > 1 && ! $ivs_kc ) ? '' : ' hidden'; ?>><?php ivs_e( 'Cik vienību nomāt', 'Сколько штук арендовать', 'How many units to rent' ); ?>
		<select name="quantity"><option value="1">1</option></select>
		<small class="mut"><?php echo esc_html( ivs_t( 'Pieejami līdz ' . $ivs_total . ' gab.', 'Всего в наличии: ' . $ivs_total . ' шт.', 'Up to ' . $ivs_total . ' units available' ) ); ?></small></label>
	<div class="ivs-cal" aria-live="polite"></div>
	<p class="ivs-hint mut"></p>
	<div class="ivs-times" hidden>
		<label><?php ivs_e( 'Saņemšanas laiks', 'Время выдачи', 'Pickup time' ); ?>
			<select name="ivs_t_from"><?php ivs_book_time_options( '09:00' ); ?></select></label>
		<label><?php ivs_e( 'Atgriešanas laiks', 'Время возврата', 'Return time' ); ?>
			<select name="ivs_t_to"><?php ivs_book_time_options( '18:00' ); ?></select></label>
	</div>
	<div class="ivs-sum" hidden></div>
	<input type="hidden" name="ivs_from" value="">
	<input type="hidden" name="ivs_to" value="">
	<input type="hidden" name="add-to-cart" value="<?php echo (int) $id; ?>">
	<?php wp_nonce_field( 'ivs_book', 'ivs_nonce' ); ?>
	<button type="submit" class="btn block" disabled><?php ivs_e( 'Rezervēt', 'Забронировать', 'Book' ); ?></button>
</form>
	<?php
} );

function ivs_book_time_options( $selected ) {
	for ( $m = IVS_BOOK_TIME_FROM * 60; $m <= IVS_BOOK_TIME_TO * 60; $m += 30 ) {
		$v = sprintf( '%02d:%02d', intdiv( $m, 60 ), $m % 60 );
		printf( '<option value="%1$s"%2$s>%1$s</option>', esc_attr( $v ), selected( $v, $selected, false ) ); // phpcs:ignore
	}
}

add_action( 'wp_enqueue_scripts', function () {
	if ( function_exists( 'is_product' ) && is_product() ) {
		$ver = (string) filemtime( get_theme_file_path( 'assets/booking.js' ) );
		wp_enqueue_script( 'ivs-booking', get_template_directory_uri() . '/assets/booking.js', array(), $ver, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	}
} );

/* ---------- Корзина ---------- */

/** Остатки WooCommerce не уменьшаем: число штук в карточке это парк приборов, а занятость считаем по датам. */
add_filter( 'woocommerce_can_reduce_order_stock', '__return_false' );
// Смена статуса заказа в админке тоже не должна менять число штук прибора.
add_filter( 'woocommerce_prevent_adjust_line_item_product_stock', '__return_true' );
add_filter( 'woocommerce_product_get_manage_stock', function ( $v ) {
	return is_admin() && ! wp_doing_ajax() ? $v : false;
} );

function ivs_book_posted() {
	// Одноразовые поля формы бронирования проверяются по nonce в ivs_book_cart_validate.
	return array(
		'from'   => isset( $_POST['ivs_from'] ) ? sanitize_text_field( wp_unslash( $_POST['ivs_from'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification
		'to'     => isset( $_POST['ivs_to'] ) ? sanitize_text_field( wp_unslash( $_POST['ivs_to'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification
		't_from' => isset( $_POST['ivs_t_from'] ) ? sanitize_text_field( wp_unslash( $_POST['ivs_t_from'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification
		't_to'   => isset( $_POST['ivs_t_to'] ) ? sanitize_text_field( wp_unslash( $_POST['ivs_t_to'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification
	);
}

add_filter( 'woocommerce_add_to_cart_validation', function ( $ok, $product_id, $qty ) {
	if ( ! $ok || ! function_exists( 'WC' ) || WC()->cart === null ) {
		return $ok;
	}
	if ( empty( $_POST['ivs_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ivs_nonce'] ) ), 'ivs_book' ) ) {
		wc_add_notice( ivs_t( 'Rezervējiet instrumentu no tā lapas.', 'Бронируйте прибор на его странице.', 'Please book from the instrument page.' ), 'error' );
		return false;
	}
	$p   = ivs_book_posted();
	$err = ivs_book_validate( (int) $product_id, $p['from'], $p['to'], $p['t_from'], $p['t_to'], (int) $qty, 0, ivs_book_posted_kit( (int) $product_id ) );
	if ( '' !== $err ) {
		wc_add_notice( $err, 'error' );
		return false;
	}
	return true;
}, 10, 3 );

add_filter( 'woocommerce_add_cart_item_data', function ( $data, $product_id ) {
	$p = ivs_book_posted();
	if ( ivs_book_is_ymd( $p['from'] ) && ivs_book_is_ymd( $p['to'] ) ) {
		$data['ivs_from']   = $p['from'];
		$data['ivs_to']     = $p['to'];
		$data['ivs_t_from'] = $p['t_from'];
		$data['ivs_t_to']   = $p['t_to'];
		$kit                = ivs_book_posted_kit( (int) $product_id );
		if ( $kit ) {
			$data['ivs_kit'] = $kit;
		}
	}
	return $data;
}, 10, 2 );

/** Цена каждой штуки = аренда за выбранные дни. Считаем на сервере. */
add_action( 'woocommerce_before_calculate_totals', function ( $cart ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	foreach ( $cart->get_cart() as $item ) {
		if ( empty( $item['ivs_from'] ) ) {
			continue;
		}
		$days = ivs_book_days( $item['ivs_from'], $item['ivs_to'] );
		$item['data']->set_price( round( ivs_book_price( $item['product_id'], $days ) * ivs_book_kit_ratio( $item['product_id'], $item['ivs_kit'] ?? null ), 2 ) );
	}
}, 20 );

add_filter( 'woocommerce_cart_item_quantity', function ( $html, $key, $item ) {
	return empty( $item['ivs_from'] ) ? $html : '<span>' . (int) $item['quantity'] . '</span>';
}, 10, 3 );

add_filter( 'woocommerce_get_item_data', function ( $data, $item ) {
	if ( empty( $item['ivs_from'] ) ) {
		return $data;
	}
	$days   = ivs_book_days( $item['ivs_from'], $item['ivs_to'] );
	$data[] = array(
		'key'   => ivs_t( 'Saņemšana', 'Выдача', 'Pickup' ),
		'value' => esc_html( $item['ivs_from'] . ' ' . $item['ivs_t_from'] ),
	);
	$data[] = array(
		'key'   => ivs_t( 'Atgriešana', 'Возврат', 'Return' ),
		'value' => esc_html( $item['ivs_to'] . ' ' . $item['ivs_t_to'] ),
	);
	$data[] = array(
		'key'   => ivs_t( 'Dienas', 'Суток', 'Days' ),
		'value' => (int) $days,
	);
	if ( ! empty( $item['ivs_kit'] ) ) {
		$data[] = array( 'key' => ivs_t( 'Sastāvs', 'Состав', 'Contents' ), 'value' => esc_html( ivs_book_kit_text( $item['ivs_kit'] ) ) );
	}
	return $data;
}, 10, 2 );

function ivs_book_kit_text( $comp ) {
	$out = array();
	foreach ( $comp as $cid => $n ) {
		$out[] = get_the_title( $cid ) . ' ×' . (int) $n;
	}
	return implode( ', ', $out );
}

/** Залог: отдельной строкой, без PVN, возвращается после возврата прибора. */
add_action( 'woocommerce_cart_calculate_fees', function ( $cart ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	// Банк (Citadele/Klix) запретил брать залог с карты: залог только наличными при получении.
	$method = ( function_exists( 'WC' ) && WC()->session ) ? WC()->session->get( 'chosen_payment_method' ) : '';
	if ( 'cod' !== $method ) {
		return;
	}
	$sum = 0.0;
	foreach ( $cart->get_cart() as $item ) {
		if ( empty( $item['ivs_from'] ) ) {
			continue;
		}
		$sum += ivs_book_deposit( $item['product_id'] ) * ivs_book_kit_ratio( $item['product_id'], $item['ivs_kit'] ?? null ) * (int) $item['quantity'];
	}
	if ( $sum > 0 ) {
		$cart->add_fee( ivs_t( 'Drošības nauda (atgriežama)', 'Залог (возвращается)', 'Deposit (refundable)' ), $sum, false );
	}
} );

/** После «Забронировать» сразу в корзину. */
add_filter( 'woocommerce_add_to_cart_redirect', function ( $url ) {
	return wc_get_cart_url();
} );
add_filter( 'wc_add_to_cart_message_html', '__return_empty_string' );

/** Перед оплатой ещё раз проверяем, что даты не заняли. */
add_action( 'woocommerce_check_cart_items', function () {
	// В запросах блочной корзины сообщения не показываются и копятся в сессии: убираем старые, чтобы старая ошибка не висела.
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST && function_exists( 'wc_clear_notices' ) ) {
		wc_clear_notices();
	}
	// Заказ уже создан и проверен без учёта себя (ivs_book store_api хук): повторная проверка корзины увидит его же даты занятыми.
	if ( ! empty( $GLOBALS['ivs_order_checked'] ) ) {
		return;
	}
	foreach ( WC()->cart->get_cart() as $item ) {
		if ( empty( $item['ivs_from'] ) ) {
			continue;
		}
		$err = ivs_book_validate( $item['product_id'], $item['ivs_from'], $item['ivs_to'], $item['ivs_t_from'], $item['ivs_t_to'], (int) $item['quantity'], 0, $item['ivs_kit'] ?? null );
		if ( '' !== $err ) {
			wc_add_notice( get_the_title( $item['product_id'] ) . ': ' . $err, 'error' );
		}
	}
} );

/* ---------- Оформление заказа ---------- */

add_filter( 'woocommerce_checkout_fields', function ( $fields ) {
	$fields['billing']['ivs_pickup_name'] = array(
		'type'        => 'text',
		'label'       => ivs_t( 'Vārds un uzvārds, kas saņems instrumentu', 'Имя и фамилия того, кто заберёт прибор', 'Full name of the person collecting the instrument' ),
		'description' => ivs_t( 'Uzrādiet vienu dokumentu: pasi, tiesības vai ID.', 'При выдаче покажите один документ: паспорт, права или ID.', 'Please show one ID document at pickup: passport, licence or ID card.' ),
		'required'    => true,
		'class'       => array( 'form-row-wide' ),
		'priority'    => 5,
	);
	return $fields;
} );

add_action( 'woocommerce_checkout_create_order', function ( $order ) {
	// Поле проверяется самим WooCommerce (nonce оформления заказа).
	$name = isset( $_POST['ivs_pickup_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ivs_pickup_name'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	$order->update_meta_data( '_ivs_pickup_name', $name );
}, 10 );

add_action( 'woocommerce_checkout_create_order_line_item', function ( $item, $key, $values ) {
	if ( empty( $values['ivs_from'] ) ) {
		return;
	}
	$item->add_meta_data( '_ivs_from', $values['ivs_from'], true );
	$item->add_meta_data( '_ivs_to', $values['ivs_to'], true );
	$item->add_meta_data( '_ivs_t_from', $values['ivs_t_from'], true );
	$item->add_meta_data( '_ivs_t_to', $values['ivs_t_to'], true );
	// Залог по строке: берётся только наличными при получении (для писем, даже если заказ оплачен картой).
	$item->add_meta_data( '_ivs_deposit', ivs_book_deposit( $values['product_id'] ) * ivs_book_kit_ratio( $values['product_id'], $values['ivs_kit'] ?? null ) * (int) $values['quantity'], true );
	$item->add_meta_data( ivs_t( 'Saņemšana', 'Выдача', 'Pickup' ), $values['ivs_from'] . ' ' . $values['ivs_t_from'], true );
	$item->add_meta_data( ivs_t( 'Atgriešana', 'Возврат', 'Return' ), $values['ivs_to'] . ' ' . $values['ivs_t_to'], true );
	$item->add_meta_data( ivs_t( 'Dienas', 'Суток', 'Days' ), ivs_book_days( $values['ivs_from'], $values['ivs_to'] ), true );
	if ( ! empty( $values['ivs_kit'] ) ) {
		$item->add_meta_data( '_ivs_kit', wp_json_encode( $values['ivs_kit'] ), true );
		$item->add_meta_data( ivs_t( 'Sastāvs', 'Состав', 'Contents' ), ivs_book_kit_text( $values['ivs_kit'] ), true );
	}
}, 10, 3 );

/** Заказ создаётся только если приборы всё ещё свободны. */
add_action( 'woocommerce_after_checkout_validation', function ( $data, $errors ) {
	foreach ( WC()->cart->get_cart() as $item ) {
		if ( empty( $item['ivs_from'] ) ) {
			continue;
		}
		$err = ivs_book_validate( $item['product_id'], $item['ivs_from'], $item['ivs_to'], $item['ivs_t_from'], $item['ivs_t_to'], (int) $item['quantity'], 0, $item['ivs_kit'] ?? null );
		if ( '' !== $err ) {
			$errors->add( 'ivs_busy', get_the_title( $item['product_id'] ) . ': ' . $err );
		}
	}
}, 10, 2 );

/** Занимаем приборы на даты заказа. */
add_action( 'woocommerce_checkout_order_created', function ( $order ) {
	// Блочное оформление сначала создаёт черновик заказа: он даты не занимает.
	if ( 'checkout-draft' === $order->get_status() ) {
		return;
	}
	ivs_book_save_order( $order );
} );

function ivs_book_save_order( $order ) {
	global $wpdb;
	$t = ivs_book_table();
	$wpdb->delete( $t, array( 'order_id' => $order->get_id() ) ); // phpcs:ignore
	foreach ( $order->get_items() as $item_id => $item ) {
		$from = $item->get_meta( '_ivs_from' );
		$to   = $item->get_meta( '_ivs_to' );
		if ( ! $from || ! $to ) {
			continue;
		}
		$pid = (int) $item->get_product_id();
		foreach ( ivs_book_components( $pid, ivs_book_item_sel( $item ) ) as $cid => $n ) {
			$wpdb->insert( $t, array( // phpcs:ignore
				'order_id'     => $order->get_id(),
				'item_id'      => $item_id,
				'product_id'   => $pid,
				'component_id' => $cid,
				'qty'          => $n * (int) $item->get_quantity(),
				'date_from'    => $from,
				'date_to'      => $to,
				'active'       => ivs_book_status_active( $order->get_status() ) ? 1 : 0,
				'created'      => current_time( 'mysql', true ),
			) );
		}
	}
}

/** Бронь держит прибор, пока заказ не отменён, не провален и не возвращён деньгами. */
function ivs_book_status_active( $status ) {
	return in_array( $status, array( 'pending', 'on-hold', 'processing', 'completed' ), true );
}

add_action( 'woocommerce_order_status_changed', function ( $order_id, $from, $to ) {
	global $wpdb;
	$wpdb->update( ivs_book_table(), array( 'active' => ivs_book_status_active( $to ) ? 1 : 0 ), array( 'order_id' => $order_id ) ); // phpcs:ignore
}, 10, 3 );

add_action( 'before_delete_post', function ( $post_id ) {
	global $wpdb;
	$wpdb->delete( ivs_book_table(), array( 'order_id' => $post_id ) ); // phpcs:ignore
} );

/** Неоплаченные заказы держат приборы 30 минут, потом отменяются. */
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'ivs_book_release_unpaid' ) ) {
		wp_schedule_event( time() + 300, 'hourly', 'ivs_book_release_unpaid' );
	}
} );
add_action( 'ivs_book_release_unpaid', function () {
	global $wpdb;
	$t   = ivs_book_table();
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT order_id FROM {$t} WHERE active=1 AND created < %s", gmdate( 'Y-m-d H:i:s', time() - 1800 ) ) ); // phpcs:ignore
	foreach ( $ids as $oid ) {
		$order = wc_get_order( $oid );
		if ( $order && 'pending' === $order->get_status() ) {
			$order->update_status( 'cancelled', 'Не оплачен 30 минут, даты освобождены.' );
		}
	}
} );


/* ---------- Блочное оформление заказа (Store API) ---------- */

/** Поле «кто заберёт» в блочном оформлении. */
add_action( 'woocommerce_init', function () {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}
	woocommerce_register_additional_checkout_field( array(
		'id'       => 'ivancovs/pickup-name',
		'label'    => 'Vārds un uzvārds, kas saņems instrumentu / Имя и фамилия того, кто заберёт прибор',
		'location' => 'order',
		'type'     => 'text',
		'required' => true,
	) );
} );

/** Имя того, кто заберёт: из нашего поля или из поля блочного оформления. */
function ivs_book_pickup_name( $order ) {
	$n = $order->get_meta( '_ivs_pickup_name' );
	if ( ! $n ) {
		$n = $order->get_meta( '_wc_other/ivancovs/pickup-name' );
	}
	return is_string( $n ) ? $n : '';
}

/**
 * Блочное оформление: даты берём из корзины, проверяем занятость и занимаем приборы до оплаты.
 */
add_action( 'woocommerce_store_api_checkout_order_processed', function ( $order ) {
	$used = array();
	$GLOBALS['ivs_order_checked'] = true;
	foreach ( WC()->cart->get_cart() as $ci ) {
		if ( empty( $ci['ivs_from'] ) ) {
			continue;
		}
		$err = ivs_book_validate( $ci['product_id'], $ci['ivs_from'], $ci['ivs_to'], $ci['ivs_t_from'], $ci['ivs_t_to'], (int) $ci['quantity'], $order->get_id(), $ci['ivs_kit'] ?? null );
		if ( '' !== $err ) {
			throw new \Automattic\WooCommerce\StoreApi\Exceptions\RouteException( 'ivs_busy', esc_html( get_the_title( $ci['product_id'] ) . ': ' . $err ), 409 );
		}
		foreach ( $order->get_items() as $item_id => $item ) {
			if ( isset( $used[ $item_id ] ) || (int) $item->get_product_id() !== (int) $ci['product_id'] || (int) $item->get_quantity() !== (int) $ci['quantity'] ) {
				continue;
			}
			$used[ $item_id ] = true;
			$item->update_meta_data( '_ivs_from', $ci['ivs_from'] );
			$item->update_meta_data( '_ivs_to', $ci['ivs_to'] );
			$item->update_meta_data( '_ivs_t_from', $ci['ivs_t_from'] );
			$item->update_meta_data( '_ivs_t_to', $ci['ivs_t_to'] );
			$item->update_meta_data( ivs_t( 'Saņemšana', 'Выдача', 'Pickup' ), $ci['ivs_from'] . ' ' . $ci['ivs_t_from'] );
			$item->update_meta_data( ivs_t( 'Atgriešana', 'Возврат', 'Return' ), $ci['ivs_to'] . ' ' . $ci['ivs_t_to'] );
			$item->update_meta_data( ivs_t( 'Dienas', 'Суток', 'Days' ), ivs_book_days( $ci['ivs_from'], $ci['ivs_to'] ) );
			if ( ! empty( $ci['ivs_kit'] ) ) {
				$item->update_meta_data( '_ivs_kit', wp_json_encode( $ci['ivs_kit'] ) );
				$item->update_meta_data( ivs_t( 'Sastāvs', 'Состав', 'Contents' ), ivs_book_kit_text( $ci['ivs_kit'] ) );
			}
			$item->save();
			break;
		}
	}
	$name = $order->get_meta( '_wc_other/ivancovs/pickup-name' );
	if ( $name ) {
		$order->update_meta_data( '_ivs_pickup_name', sanitize_text_field( $name ) );
		$order->save();
	}
	ivs_book_save_order( $order );
}, 10, 1 );

/* ---------- Имя того, кто заберёт: админка и письма ---------- */

add_action( 'woocommerce_admin_order_data_after_billing_address', function ( $order ) {
	$n = ivs_book_pickup_name( $order );
	if ( $n ) {
		echo '<p><strong>' . esc_html__( 'Заберёт', 'ivancovs' ) . ':</strong> ' . esc_html( $n ) . '</p>';
	}
} );

add_filter( 'woocommerce_email_order_meta_fields', function ( $fields, $sent_to_admin, $order ) {
	$n = ivs_book_pickup_name( $order );
	if ( $n ) {
		$fields['ivs_pickup_name'] = array(
			'label' => ivs_t( 'Instrumentu saņems', 'Прибор заберёт', 'Collected by' ),
			'value' => $n,
		);
	}
	return $fields;
}, 10, 3 );
