<?php
/**
 * Почта сайта: оповещения и переписка с клиентами идут через рабочий адрес IVS_EMAIL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Адрес администратора WordPress (служебные оповещения).
add_filter( 'pre_option_admin_email', function () {
	return IVS_EMAIL;
} );

// Отправитель писем клиентам: ответ клиента попадает на рабочую почту.
add_filter( 'woocommerce_email_from_address', function () {
	return IVS_EMAIL;
} );

// Получатель оповещений магазина о заказах.
foreach ( array( 'new_order', 'cancelled_order', 'failed_order' ) as $ivs_mail_id ) {
	add_filter( 'woocommerce_email_recipient_' . $ivs_mail_id, function () {
		return IVS_EMAIL;
	} );
}
unset( $ivs_mail_id );

/* ---------- Одна цепочка писем на заказ ---------- */

function ivs_mail_root_id( $order_id ) {
	return '<ivs-order-' . (int) $order_id . '@ivancovs.com>';
}

/** Письма магазина: запоминаем, к какому заказу относится письмо. */
add_filter( 'woocommerce_email_headers', function ( $headers, $id, $object ) {
	if ( $object instanceof WC_Order ) {
		$GLOBALS['ivs_mail_order'] = (int) $object->get_id();
	}
	return $headers;
}, 10, 3 );

/** Одинаковая тема и ссылки на первое письмо: почта собирает письма заказа в одну цепочку. */
add_filter( 'wp_mail', function ( $args ) {
	if ( empty( $GLOBALS['ivs_mail_order'] ) ) {
		return $args;
	}
	$oid = (int) $GLOBALS['ivs_mail_order'];
	unset( $GLOBALS['ivs_mail_order'] );
	$GLOBALS['ivs_mail_cur'] = $oid;
	$h = $args['headers'];
	$h = is_array( $h ) ? $h : array_filter( explode( "\n", str_replace( "\r", '', (string) $h ) ) );
	$order = wc_get_order( $oid );
	if ( $order && $order->get_meta( '_ivs_mail_root' ) ) {
		$h[] = 'In-Reply-To: ' . ivs_mail_root_id( $oid );
		$h[] = 'References: ' . ivs_mail_root_id( $oid );
	}
	$args['headers'] = $h;
	$args['subject'] = 'Ivancovs · Pasūtījums / Заказ / Order №' . $oid;
	return $args;
} );

/** Самое первое письмо заказа получает общий идентификатор, остальные на него ссылаются. */
add_action( 'phpmailer_init', function ( $phpmailer ) {
	if ( empty( $GLOBALS['ivs_mail_cur'] ) ) {
		return;
	}
	$oid = (int) $GLOBALS['ivs_mail_cur'];
	unset( $GLOBALS['ivs_mail_cur'] );
	$order = wc_get_order( $oid );
	if ( $order && ! $order->get_meta( '_ivs_mail_root' ) ) {
		$phpmailer->MessageID = ivs_mail_root_id( $oid );
		$order->update_meta_data( '_ivs_mail_root', '1' );
		$order->save();
	}
} );

/* ---------- Дубли уведомлений: Telegram и почта ---------- */

function ivs_wc_email_on( $class ) {
	if ( ! function_exists( 'WC' ) ) {
		return false;
	}
	$all = WC()->mailer()->get_emails();
	return isset( $all[ $class ] ) && $all[ $class ]->is_enabled();
}

/** Письмо владельцу о событии заказа, в цепочке этого заказа. */
function ivs_notify_email( $order, $label ) {
	$GLOBALS['ivs_mail_order'] = (int) $order->get_id();
	$who  = ! empty( $GLOBALS['ivs_tg_actor'] ) ? "\nКто: " . ivs_tg_h( $GLOBALS['ivs_tg_actor'] ) . ' (Telegram)' : '';
	$body = nl2br( wp_kses( '<b>' . $label . '</b>' . $who . "\n\n" . ivs_tg_order_text( $order ), array( 'b' => array() ) ) );
	$body .= '<p><a href="' . esc_url( $order->get_edit_order_url() ) . '">Открыть заказ</a></p>';
	wp_mail( IVS_EMAIL, 'Заказ', $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
	unset( $GLOBALS['ivs_mail_order'] );
}

/**
 * Событие заказа: сообщение в Telegram и письмо на рабочую почту.
 * Канал, из которого действие сделано (кнопка в Telegram), не дублируем.
 * Письмо пропускаем, если магазин сам уже шлёт такое ($wc_email — класс письма WooCommerce),
 * кроме действий из Telegram: там в письме указываем, кто нажал.
 */
function ivs_notify_event( $order, $label, $wc_email = '' ) {
	if ( empty( $GLOBALS['ivs_tg_origin'] ) && ivs_tg_token() && ivs_tg_opts()['chats'] ) {
		ivs_tg_send_all( '🔔 <b>' . ivs_tg_h( $label ) . '</b>' . "\n" . ivs_tg_order_text( $order ), ivs_tg_order_keyboard( $order ) );
	}
	if ( ! empty( $GLOBALS['ivs_tg_actor'] ) || ! $wc_email || ! ivs_wc_email_on( $wc_email ) ) {
		ivs_notify_email( $order, $label );
	}
}

add_action( 'woocommerce_order_status_changed', function ( $order_id, $from, $to, $order ) {
	if ( ! ivs_order_has_booking( $order ) ) {
		return;
	}
	if ( 'cancelled' === $to ) {
		ivs_notify_event( $order, 'Заказ №' . $order_id . ' отменён', 'WC_Email_Cancelled_Order' );
	} elseif ( 'failed' === $to ) {
		ivs_notify_event( $order, 'Заказ №' . $order_id . ': оплата не прошла', 'WC_Email_Failed_Order' );
	} elseif ( 'processing' === $to && in_array( $from, array( 'on-hold', 'pending' ), true ) && $order->get_meta( '_ivs_tg_sent' ) ) {
		ivs_notify_event( $order, 'Заказ №' . $order_id . ' подтверждён' );
	}
}, 10, 4 );

add_action( 'ivs_order_issued', function ( $order ) {
	ivs_notify_event( $order, 'Заказ №' . $order->get_id() . ': приборы выданы' );
} );

add_action( 'ivs_order_returned', function ( $order ) {
	ivs_notify_event( $order, 'Заказ №' . $order->get_id() . ': приборы возвращены' );
} );

/** Кто нажал кнопку в Telegram: ник или имя. */
function ivs_tg_actor_name( $from ) {
	if ( ! empty( $from['username'] ) ) {
		return '@' . $from['username'];
	}
	$n = trim( ( $from['first_name'] ?? '' ) . ' ' . ( $from['last_name'] ?? '' ) );
	return $n ? $n : 'неизвестный пользователь';
}

/** Действие в Telegram не по заказу (снять прибор, выходной день): письмо с именем того, кто нажал. */
function ivs_notify_tg_action( $text ) {
	$who = ! empty( $GLOBALS['ivs_tg_actor'] ) ? $GLOBALS['ivs_tg_actor'] : 'Telegram';
	wp_mail(
		IVS_EMAIL,
		'Ivancovs · действие в Telegram',
		nl2br( esc_html( $who . " (Telegram) сделал изменение на сайте:\n\n" . $text ) ),
		array( 'Content-Type: text/html; charset=UTF-8' )
	);
}
