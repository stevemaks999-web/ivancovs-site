<?php
/**
 * Синхронизация броней с Google Календарём «Брони» (в одну сторону: сайт → календарь).
 * На каждую позицию заказа два события: «Выдача» и «Возврат». Отмена и удаление заказа убирают события,
 * отметки «выдано» и «возвращено» добавляют ✅ в название.
 *
 * Ключ служебного пользователя Google и номер календаря лежат на сервере вне папки сайта:
 *   <папка над public_html>/ivs-secrets/google-service-account.json
 *   <папка над public_html>/ivs-secrets/google-calendar-id.txt
 * Если файлов нет, синхронизация молча выключена.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ivs_gcal_dir() {
	return trailingslashit( dirname( untrailingslashit( ABSPATH ) ) ) . 'ivs-secrets/';
}

/** Ключ и номер календаря или null, если не настроено. */
function ivs_gcal_conf() {
	static $c = false;
	if ( false !== $c ) {
		return $c;
	}
	$c = null;
	$k = ivs_gcal_dir() . 'google-service-account.json';
	$i = ivs_gcal_dir() . 'google-calendar-id.txt';
	if ( is_readable( $k ) && is_readable( $i ) ) {
		$key = json_decode( (string) file_get_contents( $k ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		$cal = trim( (string) file_get_contents( $i ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( is_array( $key ) && ! empty( $key['private_key'] ) && $cal ) {
			$c = array( 'key' => $key, 'cal' => $cal );
		}
	}
	return $c;
}

function ivs_gcal_b64( $s ) {
	return rtrim( strtr( base64_encode( $s ), '+/', '-_' ), '=' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
}

/** Токен доступа (кешируется почти на час). */
function ivs_gcal_token( $conf ) {
	$t = get_transient( 'ivs_gcal_token' );
	if ( $t ) {
		return $t;
	}
	$now = time();
	$in  = ivs_gcal_b64( wp_json_encode( array( 'alg' => 'RS256', 'typ' => 'JWT' ) ) ) . '.' . ivs_gcal_b64( wp_json_encode( array(
		'iss'   => $conf['key']['client_email'],
		'scope' => 'https://www.googleapis.com/auth/calendar',
		'aud'   => 'https://oauth2.googleapis.com/token',
		'iat'   => $now,
		'exp'   => $now + 3600,
	) ) );
	$sig = '';
	if ( ! openssl_sign( $in, $sig, $conf['key']['private_key'], OPENSSL_ALGO_SHA256 ) ) {
		return '';
	}
	$res = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
		'timeout' => 10,
		'body'    => array(
			'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
			'assertion'  => $in . '.' . ivs_gcal_b64( $sig ),
		),
	) );
	$j = is_wp_error( $res ) ? array() : json_decode( wp_remote_retrieve_body( $res ), true );
	if ( empty( $j['access_token'] ) ) {
		return '';
	}
	set_transient( 'ivs_gcal_token', $j['access_token'], 3300 );
	return $j['access_token'];
}

/** Запрос к Calendar API. Возвращает [ код ответа, массив ]. */
function ivs_gcal_api( $conf, $method, $path, $body = null ) {
	$tok = ivs_gcal_token( $conf );
	if ( ! $tok ) {
		return array( 0, array() );
	}
	$args = array(
		'method'  => $method,
		'timeout' => 10,
		'headers' => array( 'Authorization' => 'Bearer ' . $tok, 'Content-Type' => 'application/json' ),
	);
	if ( null !== $body ) {
		$args['body'] = wp_json_encode( $body );
	}
	$res = wp_remote_request( 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode( $conf['cal'] ) . '/events' . $path, $args );
	if ( is_wp_error( $res ) ) {
		return array( 0, array() );
	}
	$j = json_decode( wp_remote_retrieve_body( $res ), true );
	return array( (int) wp_remote_retrieve_response_code( $res ), is_array( $j ) ? $j : array() );
}

/** Дата и время в формате события. */
function ivs_gcal_dt( $date, $time, $plus_min = 0 ) {
	$d = date_create( $date . ' ' . ( $time ? $time : '09:00' ), wp_timezone() );
	if ( ! $d ) {
		return null;
	}
	if ( $plus_min ) {
		$d->modify( '+' . (int) $plus_min . ' minutes' );
	}
	return array( 'dateTime' => $d->format( 'c' ), 'timeZone' => wp_timezone_string() );
}

/** Нужны ли заказу события в календаре. */
function ivs_gcal_wanted( $order ) {
	return ivs_order_has_booking( $order ) && in_array( $order->get_status(), array( 'on-hold', 'processing', 'completed' ), true );
}

/** Описание события. */
function ivs_gcal_desc( $order, $item ) {
	list( , $dep ) = ivs_order_lines( $order );
	$pay = $order->get_payment_method_title();
	$t   = 'Заказ №' . $order->get_order_number() . "\n";
	$t  .= 'Прибор: ' . $item->get_name() . ' × ' . (int) $item->get_quantity() . "\n";
	$t  .= 'Заберёт: ' . ivs_book_pickup_name( $order ) . "\n";
	$t  .= 'Телефон: ' . ( $order->get_billing_phone() ? $order->get_billing_phone() : 'не указан' ) . "\n";
	$t  .= 'Почта: ' . $order->get_billing_email() . "\n";
	$t  .= 'Итого: ' . wp_strip_all_tags( ivs_money( $order->get_total() ) ) . ( $dep > 0 ? ' (залог ' . wp_strip_all_tags( ivs_money( $dep ) ) . ')' : '' ) . "\n";
	$t  .= 'Оплата: ' . ( $pay ? $pay : '—' ) . "\n";
	$t  .= 'Заказ: ' . $order->get_edit_order_url();
	return $t;
}

/** Создать, обновить или удалить события заказа. */
function ivs_gcal_sync_order( $order_id ) {
	$conf  = ivs_gcal_conf();
	$order = wc_get_order( $order_id );
	if ( ! $conf || ! $order ) {
		return;
	}
	$map  = $order->get_meta( '_ivs_gcal' );
	$map  = is_array( $map ) ? $map : array();
	$want = ivs_gcal_wanted( $order );
	$keep = array();
	$err  = false;

	if ( $want ) {
		$issued   = $order->get_meta( '_ivs_issued_at' );
		$returned = $order->get_meta( '_ivs_returned_at' );
		foreach ( $order->get_items() as $iid => $item ) {
			$from = $item->get_meta( '_ivs_from' );
			if ( ! $from ) {
				continue;
			}
			$to    = $item->get_meta( '_ivs_to' );
			$name  = $item->get_name() . ( $item->get_quantity() > 1 ? ' × ' . (int) $item->get_quantity() : '' );
			$who   = ivs_book_pickup_name( $order );
			$parts = array(
				'out' => array( '📦 Выдача: ', $from, $item->get_meta( '_ivs_t_from' ), $issued ? '✅ ' : '' ),
				'in'  => array( '↩️ Возврат: ', $to, $item->get_meta( '_ivs_t_to' ), $returned ? '✅ ' : '' ),
			);
			foreach ( $parts as $kind => $p ) {
				$key   = $iid . '_' . $kind;
				$start = ivs_gcal_dt( $p[1], $p[2] );
				$end   = ivs_gcal_dt( $p[1], $p[2], 30 );
				if ( ! $start || ! $end ) {
					continue;
				}
				$ev = array(
					'summary'     => $p[3] . $p[0] . $name . ( $who ? ' — ' . $who : '' ),
					'description' => ivs_gcal_desc( $order, $item ),
					'start'       => $start,
					'end'         => $end,
					'reminders'   => array( 'useDefault' => false, 'overrides' => array( array( 'method' => 'popup', 'minutes' => 60 ) ) ),
				);
				if ( ! empty( $map[ $key ] ) ) {
					list( $code ) = ivs_gcal_api( $conf, 'PATCH', '/' . rawurlencode( $map[ $key ] ), $ev );
					if ( 404 === $code || 410 === $code ) {
						unset( $map[ $key ] );
					} elseif ( $code < 200 || $code >= 300 ) {
						$err = true;
					}
				}
				if ( empty( $map[ $key ] ) ) {
					list( $code, $j ) = ivs_gcal_api( $conf, 'POST', '', $ev );
					if ( 200 === $code && ! empty( $j['id'] ) ) {
						$map[ $key ] = $j['id'];
					} else {
						$err = true;
					}
				}
				$keep[ $key ] = true;
			}
		}
	}

	// События, которых больше быть не должно: отмена, удаление позиции, смена статуса.
	foreach ( $map as $key => $eid ) {
		if ( isset( $keep[ $key ] ) ) {
			continue;
		}
		list( $code ) = ivs_gcal_api( $conf, 'DELETE', '/' . rawurlencode( $eid ) );
		if ( $code && 404 !== $code && 410 !== $code && ( $code < 200 || $code >= 300 ) ) {
			$err = true;
			continue;
		}
		unset( $map[ $key ] );
	}

	$order->update_meta_data( '_ivs_gcal', $map );
	$order->save_meta_data();
	if ( $err ) {
		$order->add_order_note( 'Календарь «Брони»: не удалось обновить события, попробуем при следующем изменении заказа.' );
	}
}

/** Ставим заказ в очередь: синхронизация идёт после ответа клиенту. */
function ivs_gcal_queue( $order_id ) {
	if ( ! ivs_gcal_conf() ) {
		return;
	}
	$GLOBALS['ivs_gcal_queue'][ (int) $order_id ] = true;
}

add_action( 'woocommerce_after_order_object_save', function ( $order ) {
	if ( $order instanceof WC_Order && ! doing_action( 'shutdown' ) ) {
		ivs_gcal_queue( $order->get_id() );
	}
}, 50 );

foreach ( array( 'woocommerce_before_trash_order', 'woocommerce_before_delete_order', 'wp_trash_post', 'before_delete_post' ) as $ivs_hook ) {
	add_action( $ivs_hook, function ( $id ) {
		$o = wc_get_order( $id );
		if ( $o && ! ivs_gcal_conf() ) {
			return;
		}
		if ( $o ) {
			// Заказ уходит в корзину: убираем события сразу, пока заказ ещё читается.
			$o->set_status( 'cancelled' );
			ivs_gcal_sync_order_direct( $o );
		}
	}, 5 );
}

/** Синхронизация объекта заказа без повторного чтения (для удаления). */
function ivs_gcal_sync_order_direct( $order ) {
	$conf = ivs_gcal_conf();
	$map  = $order->get_meta( '_ivs_gcal' );
	if ( ! $conf || ! is_array( $map ) ) {
		return;
	}
	foreach ( $map as $eid ) {
		ivs_gcal_api( $conf, 'DELETE', '/' . rawurlencode( $eid ) );
	}
}

add_action( 'shutdown', function () {
	if ( empty( $GLOBALS['ivs_gcal_queue'] ) ) {
		return;
	}
	if ( function_exists( 'fastcgi_finish_request' ) ) {
		fastcgi_finish_request();
	}
	$ids = array_keys( $GLOBALS['ivs_gcal_queue'] );
	$GLOBALS['ivs_gcal_queue'] = array();
	foreach ( $ids as $id ) {
		ivs_gcal_sync_order( $id );
	}
}, 20 );
