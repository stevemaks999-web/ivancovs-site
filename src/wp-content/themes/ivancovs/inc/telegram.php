<?php
/**
 * Telegram: уведомления о заказах, управление бронью кнопками, ручные блокировки приборов.
 *
 * Токен бота хранится только в настройках сайта (WooCommerce → Telegram) или в константе IVS_TG_TOKEN
 * в wp-config.php. В репозиторий токен не попадает.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- Настройки ---------- */

function ivs_tg_opts() {
	$o = get_option( 'ivs_tg', array() );
	return array_merge( array(
		'token'  => '',
		'secret' => '',
		'code'   => '',
		'chats'  => array(),
	), is_array( $o ) ? $o : array() );
}

function ivs_tg_save( $o ) {
	update_option( 'ivs_tg', $o, false );
}

function ivs_tg_token() {
	if ( defined( 'IVS_TG_TOKEN' ) && IVS_TG_TOKEN ) {
		return IVS_TG_TOKEN;
	}
	$o = ivs_tg_opts();
	return $o['token'];
}

/** Вызов Telegram Bot API. Возвращает массив ответа или WP_Error. */
function ivs_tg_call( $method, $params = array(), $blocking = true ) {
	$token = ivs_tg_token();
	if ( ! $token ) {
		return new WP_Error( 'ivs_tg_no_token', 'Токен не задан' );
	}
	$res = wp_remote_post( 'https://api.telegram.org/bot' . $token . '/' . $method, array(
		'timeout'  => $blocking ? 8 : 0.5,
		'blocking' => $blocking,
		'headers'  => array( 'Content-Type' => 'application/json' ),
		'body'     => wp_json_encode( $params ),
	) );
	if ( is_wp_error( $res ) || ! $blocking ) {
		return $res;
	}
	$j = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( empty( $j['ok'] ) ) {
		return new WP_Error( 'ivs_tg_api', isset( $j['description'] ) ? $j['description'] : 'Ошибка Telegram' );
	}
	return $j;
}

function ivs_tg_send_all( $text, $keyboard = null, $blocking = false ) {
	$o = ivs_tg_opts();
	foreach ( $o['chats'] as $chat ) {
		$p = array(
			'chat_id'                  => $chat,
			'text'                     => $text,
			'parse_mode'               => 'HTML',
			'disable_web_page_preview' => true,
		);
		if ( $keyboard ) {
			$p['reply_markup'] = array( 'inline_keyboard' => $keyboard );
		}
		ivs_tg_call( 'sendMessage', $p, $blocking );
	}
}

function ivs_tg_h( $s ) {
	return htmlspecialchars( (string) $s, ENT_NOQUOTES, 'UTF-8' );
}

/* ---------- Страница настроек ---------- */

add_action( 'admin_menu', function () {
	add_submenu_page( 'woocommerce', 'Telegram', 'Telegram', 'manage_woocommerce', 'ivs-telegram', 'ivs_tg_page' );
} );

function ivs_tg_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$o   = ivs_tg_opts();
	$msg = '';
	if ( isset( $_POST['ivs_tg_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ivs_tg_nonce'] ) ), 'ivs_tg' ) ) {
		$do = isset( $_POST['ivs_tg_do'] ) ? sanitize_key( wp_unslash( $_POST['ivs_tg_do'] ) ) : '';
		if ( 'save' === $do ) {
			$tok = isset( $_POST['ivs_tg_token'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['ivs_tg_token'] ) ) ) : '';
			if ( $tok ) {
				$o['token'] = $tok;
			}
			if ( ! $o['secret'] ) {
				$o['secret'] = wp_generate_password( 32, false );
			}
			ivs_tg_save( $o );
			$r = ivs_tg_call( 'setWebhook', array(
				'url'             => rest_url( 'ivs/v1/tg' ),
				'secret_token'    => $o['secret'],
				'allowed_updates' => array( 'message', 'callback_query' ),
			) );
			$msg = is_wp_error( $r ) ? 'Не удалось подключить: ' . $r->get_error_message() : 'Сохранено, бот подключён.';
		} elseif ( 'code' === $do ) {
			$o['code'] = strtoupper( wp_generate_password( 8, false ) );
			ivs_tg_save( $o );
		} elseif ( 'test' === $do ) {
			ivs_tg_send_all( '✅ Проверка связи с сайтом ivancovs.com', null, true );
			$msg = 'Тест отправлен всем подключённым чатам.';
		} elseif ( 'unlink' === $do ) {
			$o['chats'] = array();
			ivs_tg_save( $o );
			$msg = 'Все чаты отвязаны.';
		}
	}
	$o    = ivs_tg_opts();
	$me   = ivs_tg_token() ? ivs_tg_call( 'getMe' ) : null;
	$hook = ivs_tg_token() ? ivs_tg_call( 'getWebhookInfo' ) : null;
	echo '<div class="wrap"><h1>Telegram</h1>';
	if ( $msg ) {
		echo '<div class="updated"><p>' . esc_html( $msg ) . '</p></div>';
	}
	echo '<p>Бот присылает новые заказы и позволяет подтвердить, выдать, принять и отменить бронь кнопками.</p>';
	echo '<form method="post">';
	wp_nonce_field( 'ivs_tg', 'ivs_tg_nonce' );
	echo '<table class="form-table"><tr><th>Токен бота</th><td><input type="password" name="ivs_tg_token" class="regular-text" autocomplete="off" placeholder="' . ( ivs_tg_token() ? '•••••• сохранён, вставьте новый, чтобы заменить' : '123456:ABC...' ) . '"><p class="description">Токен выдаёт @BotFather. Он хранится только на сайте.</p></td></tr>';
	echo '<tr><th>Состояние</th><td>';
	if ( is_wp_error( $me ) || ! $me ) {
		echo 'Бот не подключён' . ( is_wp_error( $me ) ? ': ' . esc_html( $me->get_error_message() ) : '' );
	} else {
		echo 'Бот: <b>@' . esc_html( $me['result']['username'] ) . '</b>';
		if ( ! is_wp_error( $hook ) && ! empty( $hook['result']['url'] ) ) {
			echo ' · связь с сайтом включена';
			if ( ! empty( $hook['result']['last_error_message'] ) ) {
				echo '<br>Последняя ошибка Telegram: ' . esc_html( $hook['result']['last_error_message'] );
			}
		} else {
			echo ' · связь с сайтом не включена (нажмите «Сохранить и подключить»)';
		}
	}
	echo '</td></tr><tr><th>Подключённые чаты</th><td>' . ( $o['chats'] ? count( $o['chats'] ) . ' шт.' : 'нет' ) . '</td></tr></table>';
	echo '<p><button class="button button-primary" name="ivs_tg_do" value="save">Сохранить и подключить</button> ';
	echo '<button class="button" name="ivs_tg_do" value="test">Отправить проверку</button> ';
	echo '<button class="button" name="ivs_tg_do" value="unlink" onclick="return confirm(\'Отвязать все чаты?\')">Отвязать чаты</button></p>';
	echo '<h2>Подключить чат</h2><p>Дмитрий открывает бота в Telegram и отправляет ему сообщение <code>/start КОД</code>.</p>';
	if ( $o['code'] ) {
		echo '<p>Код: <b style="font-size:18px">' . esc_html( $o['code'] ) . '</b> (действует до первого использования)</p>';
	}
	echo '<p><button class="button" name="ivs_tg_do" value="code">Показать новый код</button></p></form></div>';
}

/* ---------- Webhook ---------- */

add_action( 'rest_api_init', function () {
	register_rest_route( 'ivs/v1', '/tg', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => function ( WP_REST_Request $req ) {
			$o   = ivs_tg_opts();
			$hdr = $req->get_header( 'x_telegram_bot_api_secret_token' );
			if ( ! $o['secret'] || ! $hdr || ! hash_equals( $o['secret'], $hdr ) ) {
				return new WP_REST_Response( array( 'ok' => false ), 403 );
			}
			$u = $req->get_json_params();
			if ( ! empty( $u['callback_query'] ) ) {
				ivs_tg_on_callback( $u['callback_query'] );
			} elseif ( ! empty( $u['message'] ) ) {
				ivs_tg_on_message( $u['message'] );
			}
			return new WP_REST_Response( array( 'ok' => true ), 200 );
		},
	) );
} );

function ivs_tg_is_paired( $chat_id ) {
	$o = ivs_tg_opts();
	return in_array( (string) $chat_id, array_map( 'strval', $o['chats'] ), true );
}

function ivs_tg_reply( $chat_id, $text, $keyboard = null ) {
	$p = array(
		'chat_id'                  => $chat_id,
		'text'                     => $text,
		'parse_mode'               => 'HTML',
		'disable_web_page_preview' => true,
	);
	if ( $keyboard ) {
		$p['reply_markup'] = array( 'inline_keyboard' => $keyboard );
	}
	ivs_tg_call( 'sendMessage', $p );
}

function ivs_tg_on_message( $m ) {
	$chat = $m['chat']['id'] ?? 0;
	$text = trim( (string) ( $m['text'] ?? '' ) );
	if ( ! $chat || '' === $text ) {
		return;
	}
	if ( 0 === strpos( $text, '/start' ) ) {
		$code = trim( substr( $text, 6 ) );
		$o    = ivs_tg_opts();
		if ( ivs_tg_is_paired( $chat ) ) {
			ivs_tg_reply( $chat, 'Чат уже подключён. Команды: /orders, /block, /blocks, /help' );
		} elseif ( $code && $o['code'] && hash_equals( $o['code'], strtoupper( $code ) ) ) {
			$o['chats'][] = (string) $chat;
			$o['chats']   = array_values( array_unique( $o['chats'] ) );
			$o['code']    = '';
			ivs_tg_save( $o );
			ivs_tg_reply( $chat, '✅ Чат подключён. Новые заказы будут приходить сюда. Команды: /orders, /block, /blocks, /help' );
		} else {
			ivs_tg_reply( $chat, 'Нужен код подключения из админки сайта: /start КОД' );
		}
		return;
	}
	if ( ! ivs_tg_is_paired( $chat ) ) {
		return;
	}
	$cmd = strtolower( strtok( $text, " \n@" ) );
	$arg = trim( preg_replace( '/^@\S+\s*/', '', trim( (string) substr( $text, strlen( $cmd ) ) ) ) );
	if ( '/orders' === $cmd ) {
		ivs_tg_cmd_orders( $chat );
	} elseif ( '/block' === $cmd ) {
		ivs_tg_cmd_block( $chat, $arg );
	} elseif ( '/blocks' === $cmd ) {
		ivs_tg_cmd_blocks( $chat );
	} else {
		ivs_tg_reply( $chat, "Команды:\n/orders — активные брони\n/block Testo 420 21.11.2026 25.11.2026 — снять прибор с сайта на даты (одна дата = один день)\n/blocks — список снятых приборов с кнопками «вернуть на сайт»" );
	}
}

/* ---------- Заказ: текст и кнопки ---------- */

function ivs_tg_order_text( $order ) {
	$st    = array(
		'on-hold'    => 'ждёт оплаты',
		'pending'    => 'не оплачен',
		'processing' => 'подтверждён',
		'completed'  => 'закрыт',
		'cancelled'  => 'отменён',
	);
	$state = $st[ $order->get_status() ] ?? $order->get_status();
	if ( $order->get_meta( '_ivs_returned_at' ) ) {
		$state = 'возвращён ' . $order->get_meta( '_ivs_returned_at' );
	} elseif ( $order->get_meta( '_ivs_issued_at' ) ) {
		$state = 'выдан ' . $order->get_meta( '_ivs_issued_at' );
	}
	$t  = '🧾 <b>Заказ №' . (int) $order->get_id() . '</b> · ' . ivs_tg_h( $state ) . "\n";
	foreach ( $order->get_items() as $item ) {
		$t .= '• ' . ivs_tg_h( $item->get_name() ) . ' × ' . (int) $item->get_quantity();
		if ( $item->get_meta( '_ivs_from' ) ) {
			$t .= "\n   " . ivs_tg_h( $item->get_meta( '_ivs_from' ) . ' ' . $item->get_meta( '_ivs_t_from' ) . ' → ' . $item->get_meta( '_ivs_to' ) . ' ' . $item->get_meta( '_ivs_t_to' ) );
		}
		$t .= "\n";
	}
	list( , $dep ) = ivs_order_lines( $order );
	$t .= 'Итого: <b>' . ivs_tg_h( ivs_money( $order->get_total() ) ) . '</b>' . ( $dep > 0 ? ' (залог ' . ivs_tg_h( ivs_money( $dep ) ) . ')' : '' ) . "\n";
	$t .= '👤 Заберёт: ' . ivs_tg_h( ivs_book_pickup_name( $order ) ) . "\n";
	$t .= '✉️ ' . ivs_tg_h( $order->get_billing_email() );
	if ( $order->get_billing_phone() ) {
		$t .= ' · ' . ivs_tg_h( $order->get_billing_phone() );
	}
	return $t;
}

function ivs_tg_order_keyboard( $order ) {
	$id  = $order->get_id();
	$st  = $order->get_status();
	$row = array();
	if ( in_array( $st, array( 'on-hold', 'pending' ), true ) ) {
		$row = array(
			array( 'text' => '✅ Подтвердить', 'callback_data' => 'o|' . $id . '|confirm' ),
			array( 'text' => '❌ Отменить', 'callback_data' => 'o|' . $id . '|cancel' ),
		);
	} elseif ( 'processing' === $st && ! $order->get_meta( '_ivs_issued_at' ) ) {
		$row = array(
			array( 'text' => '📦 Выдано', 'callback_data' => 'o|' . $id . '|issue' ),
			array( 'text' => '❌ Отменить', 'callback_data' => 'o|' . $id . '|cancel' ),
		);
	} elseif ( 'processing' === $st && $order->get_meta( '_ivs_issued_at' ) && ! $order->get_meta( '_ivs_returned_at' ) ) {
		$row = array( array( 'text' => '↩️ Возвращено', 'callback_data' => 'o|' . $id . '|return' ) );
	}
	$kb = array();
	if ( $row ) {
		$kb[] = $row;
	}
	$kb[] = array(
		array( 'text' => 'Открыть заказ', 'url' => $order->get_edit_order_url() ),
		array( 'text' => 'Счёт', 'url' => ivs_doc_url( $order, 'invoice' ) ),
	);
	return $kb;
}

/** Новый заказ: одно сообщение при первом переходе в «ждёт оплаты» или «в обработке». */
add_action( 'woocommerce_order_status_changed', function ( $order_id, $from, $to, $order ) {
	if ( ! in_array( $to, array( 'on-hold', 'processing' ), true ) || ! ivs_order_has_booking( $order ) || $order->get_meta( '_ivs_tg_sent' ) ) {
		return;
	}
	if ( ! ivs_tg_token() || ! ivs_tg_opts()['chats'] ) {
		return;
	}
	$order->update_meta_data( '_ivs_tg_sent', '1' );
	$order->save();
	ivs_tg_send_all( ivs_tg_order_text( $order ), ivs_tg_order_keyboard( $order ) );
}, 20, 4 );

/* ---------- Кнопки ---------- */

function ivs_tg_on_callback( $cb ) {
	$chat = $cb['message']['chat']['id'] ?? 0;
	$mid  = $cb['message']['message_id'] ?? 0;
	$data = (string) ( $cb['data'] ?? '' );
	$qid  = $cb['id'] ?? '';
	if ( ! $chat || ! ivs_tg_is_paired( $chat ) ) {
		return;
	}
	$p   = explode( '|', $data );
	$ans = '';
	if ( 'o' === $p[0] && isset( $p[2] ) ) {
		$order = wc_get_order( absint( $p[1] ) );
		if ( ! $order ) {
			$ans = 'Заказ не найден';
		} else {
			$act = $p[2];
			$err = '';
			if ( 'confirm' === $act && in_array( $order->get_status(), array( 'on-hold', 'pending' ), true ) ) {
				$order->update_status( 'processing', 'Подтверждено в Telegram.' );
				$ans = 'Подтверждено';
			} elseif ( 'cancel' === $act && ! in_array( $order->get_status(), array( 'cancelled', 'completed' ), true ) ) {
				$order->update_status( 'cancelled', 'Отменено в Telegram.' );
				$ans = 'Бронь отменена, даты свободны';
			} elseif ( 'issue' === $act ) {
				$err = ivs_do_issue( $order, 'в Telegram' );
				$ans = $err ? $err : 'Выдача отмечена';
			} elseif ( 'return' === $act ) {
				$err = ivs_do_return( $order, 'в Telegram' );
				$ans = $err ? $err : 'Возврат отмечен, клиенту отправлено письмо';
			} else {
				$ans = 'Действие недоступно';
			}
			$order = wc_get_order( $order->get_id() );
			ivs_tg_call( 'editMessageText', array(
				'chat_id'                  => $chat,
				'message_id'               => $mid,
				'text'                     => ivs_tg_order_text( $order ),
				'parse_mode'               => 'HTML',
				'disable_web_page_preview' => true,
				'reply_markup'             => array( 'inline_keyboard' => ivs_tg_order_keyboard( $order ) ),
			) );
		}
	} elseif ( 'u' === $p[0] && isset( $p[1] ) ) {
		global $wpdb;
		$wpdb->delete( ivs_book_table(), array( 'id' => absint( $p[1] ), 'order_id' => 0 ) ); // phpcs:ignore
		$ans = 'Прибор снова на сайте';
		ivs_tg_cmd_blocks( $chat );
	}
	ivs_tg_call( 'answerCallbackQuery', array( 'callback_query_id' => $qid, 'text' => $ans, 'show_alert' => strlen( $ans ) > 40 ) );
}

/* ---------- Команды ---------- */

function ivs_tg_cmd_orders( $chat ) {
	global $wpdb;
	$t   = ivs_book_table();
	$ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT order_id FROM {$t} WHERE order_id>0 AND active=1 AND date_to>=%s ORDER BY date_from LIMIT 10", ivs_book_today() ) ); // phpcs:ignore
	if ( ! $ids ) {
		ivs_tg_reply( $chat, 'Активных броней нет.' );
		return;
	}
	foreach ( $ids as $oid ) {
		$order = wc_get_order( $oid );
		if ( $order ) {
			ivs_tg_reply( $chat, ivs_tg_order_text( $order ), ivs_tg_order_keyboard( $order ) );
		}
	}
}

/** «/block Testo 420 21.11.2026 25.11.2026»: убрать прибор с сайта на даты. */
function ivs_tg_cmd_block( $chat, $arg ) {
	$parts = preg_split( '/\s+/', trim( $arg ), -1, PREG_SPLIT_NO_EMPTY );
	$dates = array();
	while ( $parts && count( $dates ) < 2 && ivs_book_parse_dates( end( $parts ) ) ) {
		array_unshift( $dates, ivs_book_parse_dates( array_pop( $parts ) )[0] );
	}
	$name = implode( ' ', $parts );
	if ( '' === $name || ! $dates ) {
		ivs_tg_reply( $chat, 'Формат: /block Testo 420 21.11.2026 25.11.2026' );
		return;
	}
	$from = $dates[0];
	$to   = $dates[1] ?? $dates[0];
	if ( $to < $from ) {
		ivs_tg_reply( $chat, 'Вторая дата раньше первой.' );
		return;
	}
	$pid = ivs_book_find_by_title( $name );
	if ( ! $pid ) {
		global $wpdb;
		$like = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_title LIKE %s LIMIT 6", '%' . $wpdb->esc_like( $name ) . '%' ) ); // phpcs:ignore
		if ( 1 === count( $like ) ) {
			$pid = (int) $like[0]->ID;
		} elseif ( $like ) {
			ivs_tg_reply( $chat, 'Уточните название: ' . ivs_tg_h( implode( '; ', wp_list_pluck( $like, 'post_title' ) ) ) );
			return;
		} else {
			ivs_tg_reply( $chat, 'Прибор «' . ivs_tg_h( $name ) . '» не найден.' );
			return;
		}
	}
	if ( ivs_book_is_kit( $pid ) ) {
		ivs_tg_reply( $chat, 'Это комплект. Снимите с сайта нужный прибор, комплект закроется сам.' );
		return;
	}
	global $wpdb;
	$wpdb->insert( ivs_book_table(), array( // phpcs:ignore
		'order_id'     => 0,
		'item_id'      => 0,
		'product_id'   => $pid,
		'component_id' => $pid,
		'qty'          => ivs_book_units( $pid ),
		'date_from'    => $from,
		'date_to'      => $to,
		'active'       => 1,
		'created'      => current_time( 'mysql', true ),
	) );
	ivs_tg_reply( $chat, '🚫 ' . ivs_tg_h( get_the_title( $pid ) ) . ' снят с сайта: ' . ivs_tg_h( $from . ( $to !== $from ? ' → ' . $to : '' ) ) . '. Список: /blocks' );
}

function ivs_tg_cmd_blocks( $chat ) {
	global $wpdb;
	$t    = ivs_book_table();
	$rows = $wpdb->get_results( $wpdb->prepare( "SELECT id, product_id, date_from, date_to FROM {$t} WHERE order_id=0 AND date_to>=%s ORDER BY date_from LIMIT 20", ivs_book_today() ) ); // phpcs:ignore
	if ( ! $rows ) {
		ivs_tg_reply( $chat, 'Снятых с сайта приборов нет.' );
		return;
	}
	$kb = array();
	$tx = "Сняты с сайта:\n";
	foreach ( $rows as $r ) {
		$label = get_the_title( $r->product_id ) . ' ' . $r->date_from . ( $r->date_to !== $r->date_from ? ' → ' . $r->date_to : '' );
		$tx   .= '• ' . ivs_tg_h( $label ) . "\n";
		$kb[]  = array( array( 'text' => '🗑 Вернуть: ' . $label, 'callback_data' => 'u|' . (int) $r->id ) );
	}
	ivs_tg_reply( $chat, $tx, $kb );
}
