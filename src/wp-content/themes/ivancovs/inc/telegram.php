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
		'menu'   => 0,
		'panel'  => array(),
		'pings'  => array(),
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

/** Кнопка «Меню» рядом с полем ввода и список команд. */
function ivs_tg_setup_menu() {
	ivs_tg_call( 'setMyCommands', array( 'commands' => array(
		array( 'command' => 'book', 'description' => 'Забронировать: снять прибор с сайта' ),
		array( 'command' => 'orders', 'description' => 'Активные брони' ),
		array( 'command' => 'blocks', 'description' => 'Снятые с сайта приборы' ),
		array( 'command' => 'today', 'description' => 'Сегодня и завтра: выдачи и возвраты' ),
		array( 'command' => 'income', 'description' => 'Доход с PVN и без' ),
		array( 'command' => 'menu', 'description' => 'Главное меню' ),
	) ) );
	ivs_tg_call( 'setChatMenuButton', array( 'menu_button' => array( 'type' => 'commands' ) ) );
	$o         = ivs_tg_opts();
	$o['menu'] = 2;
	ivs_tg_save( $o );
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
			ivs_tg_setup_menu();
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
			if ( (int) $o['menu'] < 2 ) {
				ivs_tg_setup_menu();
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
	$mid  = (int) ( $m['message_id'] ?? 0 );
	if ( ! $chat || '' === $text ) {
		return;
	}
	if ( 0 === strpos( $text, '/start' ) ) {
		$code = trim( substr( $text, 6 ) );
		$o    = ivs_tg_opts();
		if ( ivs_tg_is_paired( $chat ) ) {
			ivs_tg_del( $chat, $mid );
			ivs_tg_open( $chat );
		} elseif ( $code && $o['code'] && hash_equals( $o['code'], strtoupper( $code ) ) ) {
			$o['chats'][] = (string) $chat;
			$o['chats']   = array_values( array_unique( $o['chats'] ) );
			$o['code']    = '';
			ivs_tg_save( $o );
			ivs_tg_del( $chat, $mid );
			ivs_tg_open( $chat, '✅ Чат подключён. Новые заказы будут приходить сюда.' );
		} else {
			ivs_tg_reply( $chat, 'Нужен код подключения из админки сайта: /start КОД' );
		}
		return;
	}
	if ( ! ivs_tg_is_paired( $chat ) ) {
		return;
	}
	ivs_tg_del( $chat, $mid ); // в чате остаётся одно окно бота
	ivs_tg_clear_pings( $chat );
	$cmd = strtolower( strtok( $text, " \n@" ) );
	$arg = trim( preg_replace( '/^@\S+\s*/', '', trim( (string) substr( $text, strlen( $cmd ) ) ) ) );
	if ( '/orders' === $cmd || IVS_TG_BTN_ORDERS === $text ) {
		ivs_tg_go( $chat, 0, 'orders' );
	} elseif ( '/book' === $cmd || IVS_TG_BTN_BOOK === $text ) {
		ivs_tg_go( $chat, 0, 'pick', 0 );
	} elseif ( '/blocks' === $cmd || IVS_TG_BTN_BLOCKS === $text ) {
		ivs_tg_go( $chat, 0, 'blocks' );
	} elseif ( '/income' === $cmd ) {
		ivs_tg_go( $chat, 0, 'income', 'm' );
	} elseif ( '/today' === $cmd ) {
		ivs_tg_go( $chat, 0, 'today' );
	} elseif ( '/block' === $cmd ) {
		ivs_tg_cmd_block( $chat, $arg );
	} elseif ( preg_match( '/^№?\s*(\d{3,9})$/u', $text, $mm ) && wc_get_order( (int) $mm[1] ) ) {
		ivs_tg_go( $chat, 0, 'order', (int) $mm[1] );
	} else {
		ivs_tg_open( $chat );
	}
}

define( 'IVS_TG_BTN_BOOK', '📅 Забронировать' );
define( 'IVS_TG_BTN_ORDERS', '📋 Брони' );
define( 'IVS_TG_BTN_BLOCKS', '🚫 Снятые' );

/* ---------- Окно бота: одно сообщение, которое меняется ---------- */

function ivs_tg_panel_id( $chat ) {
	$o = ivs_tg_opts();
	return (int) ( $o['panel'][ $chat ] ?? 0 );
}

function ivs_tg_panel_set( $chat, $id ) {
	$o                    = ivs_tg_opts();
	$o['panel'][ $chat ] = (int) $id;
	ivs_tg_save( $o );
}

function ivs_tg_del( $chat, $mid ) {
	if ( $mid ) {
		ivs_tg_call( 'deleteMessage', array( 'chat_id' => $chat, 'message_id' => $mid ) );
	}
}

/**
 * Показать экран. $mid: сообщение, которое правим (по умолчанию окно бота).
 * $fresh: удалить старое окно и прислать новое внизу чата (со звуком).
 */
function ivs_tg_show( $chat, $text, $kb, $mid = 0, $fresh = false ) {
	$p = array(
		'chat_id'                  => $chat,
		'text'                     => $text,
		'parse_mode'               => 'HTML',
		'disable_web_page_preview' => true,
		'reply_markup'             => array( 'inline_keyboard' => $kb ),
	);
	$old = ivs_tg_panel_id( $chat );
	if ( ! $fresh ) {
		$m = $mid ? $mid : $old;
		if ( $m ) {
			$r = ivs_tg_call( 'editMessageText', $p + array( 'message_id' => $m ) );
			if ( ! is_wp_error( $r ) || false !== strpos( $r->get_error_message(), 'not modified' ) ) {
				ivs_tg_panel_set( $chat, $m );
				return;
			}
		}
	}
	$r = ivs_tg_call( 'sendMessage', $p );
	if ( ! is_wp_error( $r ) && ! empty( $r['result']['message_id'] ) ) {
		ivs_tg_panel_set( $chat, $r['result']['message_id'] );
		ivs_tg_call( 'pinChatMessage', array( 'chat_id' => $chat, 'message_id' => $r['result']['message_id'], 'disable_notification' => true ) );
		if ( $old && (int) $r['result']['message_id'] !== $old ) {
			ivs_tg_del( $chat, $old );
		}
	}
}

/** Короткие сигналы о новом заказе: живут до следующего действия, потом удаляются. */
function ivs_tg_clear_pings( $chat ) {
	$o = ivs_tg_opts();
	if ( empty( $o['pings'][ $chat ] ) ) {
		return;
	}
	foreach ( $o['pings'][ $chat ] as $id ) {
		ivs_tg_del( $chat, $id );
	}
	$o['pings'][ $chat ] = array();
	ivs_tg_save( $o );
}

function ivs_tg_ping( $chat, $text ) {
	$r = ivs_tg_call( 'sendMessage', array( 'chat_id' => $chat, 'text' => $text ) );
	if ( ! is_wp_error( $r ) && ! empty( $r['result']['message_id'] ) ) {
		$o                     = ivs_tg_opts();
		$o['pings'][ $chat ][] = (int) $r['result']['message_id'];
		ivs_tg_save( $o );
	}
}

/** Открыть главный экран в постоянном окне. */
function ivs_tg_open( $chat, $note = '' ) {
	// Убираем старые кнопки внизу экрана, если они остались.
	$r = ivs_tg_call( 'sendMessage', array( 'chat_id' => $chat, 'text' => '…', 'reply_markup' => array( 'remove_keyboard' => true ) ) );
	if ( ! is_wp_error( $r ) && ! empty( $r['result']['message_id'] ) ) {
		ivs_tg_del( $chat, $r['result']['message_id'] );
	}
	list( $t, $kb ) = ivs_tg_screen_home( $note );
	ivs_tg_show( $chat, $t, $kb );
}

/** Перейти на экран: правим сообщение $mid (или окно бота). */
function ivs_tg_go( $chat, $mid, $screen, $a = 0, $b = 0 ) {
	$fn = 'ivs_tg_screen_' . $screen;
	list( $t, $kb ) = $fn( $a, $b );
	ivs_tg_show( $chat, $t, $kb, $mid );
}

/* ---------- Данные ---------- */

function ivs_tg_c2d( $c ) {
	return preg_match( '/^\d{8}$/', $c ) ? substr( $c, 0, 4 ) . '-' . substr( $c, 4, 2 ) . '-' . substr( $c, 6, 2 ) : '';
}

function ivs_tg_d2c( $d ) {
	return str_replace( '-', '', $d );
}

function ivs_tg_fmt_date( $d ) {
	return substr( $d, 8, 2 ) . '.' . substr( $d, 5, 2 ) . '.' . substr( $d, 0, 4 );
}

function ivs_tg_fmt_time( $c ) {
	return preg_match( '/^\d{4}$/', $c ) ? (int) substr( $c, 0, 2 ) . ':' . substr( $c, 2, 2 ) : '';
}

function ivs_tg_active_orders( $limit = 15 ) {
	global $wpdb;
	$t = ivs_book_table();
	return $wpdb->get_results( $wpdb->prepare( "SELECT order_id, MIN(date_from) AS f, MAX(date_to) AS t FROM {$t} WHERE order_id>0 AND active=1 AND date_to>=%s GROUP BY order_id ORDER BY f LIMIT %d", ivs_book_today(), $limit ) ); // phpcs:ignore
}

function ivs_tg_order_short( $order ) {
	$items = $order->get_items();
	$first = $items ? reset( $items )->get_name() : '';
	return '№' . $order->get_id() . ' · ' . $first . ( count( $items ) > 1 ? ' +' . ( count( $items ) - 1 ) : '' );
}

function ivs_tg_blocks_rows() {
	global $wpdb;
	$t = ivs_book_table();
	return $wpdb->get_results( $wpdb->prepare( "SELECT id, product_id, date_from, date_to, t_from, t_to FROM {$t} WHERE order_id=0 AND active=1 AND date_to>=%s ORDER BY date_from LIMIT 20", ivs_book_today() ) ); // phpcs:ignore
}

function ivs_tg_block_label( $r ) {
	$s = get_the_title( $r->product_id ) . ' · ' . substr( $r->date_from, 8, 2 ) . '.' . substr( $r->date_from, 5, 2 );
	if ( $r->t_from ) {
		$s .= ' ' . ivs_tg_fmt_time( $r->t_from );
	}
	if ( $r->date_to !== $r->date_from || $r->t_to ) {
		$s .= ' → ' . substr( $r->date_to, 8, 2 ) . '.' . substr( $r->date_to, 5, 2 );
	}
	if ( $r->t_to ) {
		$s .= ' ' . ivs_tg_fmt_time( $r->t_to );
	}
	return $s;
}

/* ---------- Экраны ---------- */

function ivs_tg_home_row() {
	return array( array( 'text' => '🏠 Меню', 'callback_data' => 'h' ) );
}

function ivs_tg_screen_home( $note = '' ) {
	$act   = ivs_tg_active_orders( 30 );
	$wait  = 0;
	$going = 0;
	foreach ( $act as $r ) {
		$o = wc_get_order( $r->order_id );
		if ( ! $o ) {
			continue;
		}
		if ( in_array( $o->get_status(), array( 'on-hold', 'pending' ), true ) ) {
			$wait++;
		} elseif ( $o->get_meta( '_ivs_issued_at' ) && ! $o->get_meta( '_ivs_returned_at' ) ) {
			$going++;
		}
	}
	$bl = count( ivs_tg_blocks_rows() );
	$t  = ( $note ? $note . "\n\n" : '' ) . '🏠 <b>Ivancovs</b> · ' . ivs_tg_fmt_date( ivs_book_today() ) . "\n";
	$t .= '⏳ Ждут подтверждения: <b>' . $wait . "</b>\n📦 Сейчас в аренде: <b>" . $going . "</b>\n🚫 Снято с сайта: <b>" . $bl . '</b>';
	$kb = array(
		array( array( 'text' => '📅 Забронировать (снять с сайта)', 'callback_data' => 'bl|0' ) ),
		array( array( 'text' => '📋 Брони' . ( $act ? ' (' . count( $act ) . ')' : '' ), 'callback_data' => 'ol' ), array( 'text' => '📆 Сегодня и завтра', 'callback_data' => 'td' ) ),
		array( array( 'text' => '🚫 Снятые' . ( $bl ? ' (' . $bl . ')' : '' ), 'callback_data' => 'ul' ), array( 'text' => '🗓 Выходные дни', 'callback_data' => 'dp|' . substr( ivs_book_today(), 0, 7 ) ) ),
		array( array( 'text' => '💶 Доход', 'callback_data' => 'in|m' ), array( 'text' => '🔄 Обновить', 'callback_data' => 'h' ) ),
	);
	return array( $t . "\n\nНомер заказа, отправленный сообщением, откроет его.", $kb );
}

function ivs_tg_screen_orders() {
	$rows = ivs_tg_active_orders( 15 );
	if ( ! $rows ) {
		return array( '📋 Активных броней нет.', array( ivs_tg_home_row() ) );
	}
	$kb = array();
	foreach ( $rows as $r ) {
		$o = wc_get_order( $r->order_id );
		if ( $o ) {
			$kb[] = array( array( 'text' => ivs_tg_order_short( $o ) . ' · ' . substr( $r->f, 8, 2 ) . '.' . substr( $r->f, 5, 2 ), 'callback_data' => 'oc|' . $o->get_id() ) );
		}
	}
	$kb[] = ivs_tg_home_row();
	return array( '📋 <b>Активные брони</b> (по дате выдачи)', $kb );
}

function ivs_tg_screen_order( $id ) {
	$order = wc_get_order( $id );
	if ( ! $order ) {
		return array( 'Заказ не найден.', array( ivs_tg_home_row() ) );
	}
	return array( ivs_tg_order_text( $order ), ivs_tg_order_keyboard( $order ) );
}

function ivs_tg_screen_today() {
	global $wpdb;
	$t     = ivs_book_table();
	$today = ivs_book_today();
	$tom   = wp_date( 'Y-m-d', strtotime( $today . ' +1 day' ) );
	$txt   = '📆 <b>Сегодня и завтра</b>' . "\n";
	$kb    = array();
	$seen  = array();
	$sets  = array(
		array( 'Выдать сегодня', 'date_from', $today ),
		array( 'Принять сегодня', 'date_to', $today ),
		array( 'Выдать завтра', 'date_from', $tom ),
		array( 'Принять завтра', 'date_to', $tom ),
	);
	foreach ( $sets as $s ) {
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT order_id FROM {$t} WHERE order_id>0 AND active=1 AND {$s[1]}=%s", $s[2] ) ); // phpcs:ignore
		$txt .= "\n<b>" . $s[0] . ':</b> ' . ( $ids ? '' : '—' );
		foreach ( $ids as $oid ) {
			$o = wc_get_order( $oid );
			if ( ! $o || in_array( $o->get_status(), array( 'cancelled', 'failed', 'checkout-draft' ), true ) ) {
				continue;
			}
			$txt .= "\n• " . ivs_tg_h( ivs_tg_order_short( $o ) ) . ' — ' . ivs_tg_h( ivs_book_pickup_name( $o ) );
			if ( empty( $seen[ $oid ] ) ) {
				$kb[]          = array( array( 'text' => ivs_tg_order_short( $o ), 'callback_data' => 'oc|' . (int) $oid ) );
				$seen[ $oid ] = 1;
			}
		}
	}
	$kb[] = ivs_tg_home_row();
	return array( $txt, $kb );
}

function ivs_tg_screen_blocks() {
	$rows = ivs_tg_blocks_rows();
	if ( ! $rows ) {
		return array( '🚫 Снятых с сайта приборов нет.', array( array( array( 'text' => '📅 Забронировать', 'callback_data' => 'bl|0' ) ), ivs_tg_home_row() ) );
	}
	$kb = array();
	$tx = "🚫 <b>Сняты с сайта</b> (нажмите, чтобы вернуть на сайт):\n";
	foreach ( $rows as $r ) {
		$label = ivs_tg_block_label( $r );
		$tx   .= '• ' . ivs_tg_h( $label ) . "\n";
		$kb[]  = array( array( 'text' => '↩️ ' . $label, 'callback_data' => 'u|' . (int) $r->id ) );
	}
	$kb[] = array( array( 'text' => '📅 Забронировать ещё', 'callback_data' => 'bl|0' ) );
	$kb[] = ivs_tg_home_row();
	return array( $tx, $kb );
}

function ivs_tg_book_products() {
	$ids = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 200, 'orderby' => 'title', 'order' => 'ASC', 'fields' => 'ids' ) );
	$out = array();
	foreach ( $ids as $id ) {
		if ( ! ivs_book_is_kit( $id ) ) {
			$out[ $id ] = get_the_title( $id );
		}
	}
	return $out;
}

function ivs_tg_screen_pick( $page = 0 ) {
	$all   = ivs_tg_book_products();
	$per   = 8;
	$pages = max( 1, (int) ceil( count( $all ) / $per ) );
	$page  = max( 0, min( (int) $page, $pages - 1 ) );
	$kb    = array();
	foreach ( array_slice( $all, $page * $per, $per, true ) as $id => $title ) {
		$kb[] = array( array( 'text' => $title, 'callback_data' => 'bp|' . $id . '|' . substr( ivs_book_today(), 0, 7 ) ) );
	}
	$nav = array();
	if ( $page > 0 ) {
		$nav[] = array( 'text' => '‹ Назад', 'callback_data' => 'bl|' . ( $page - 1 ) );
	}
	if ( $page < $pages - 1 ) {
		$nav[] = array( 'text' => 'Дальше ›', 'callback_data' => 'bl|' . ( $page + 1 ) );
	}
	if ( $nav ) {
		$kb[] = $nav;
	}
	$kb[] = ivs_tg_home_row();
	return array( '📅 <b>Какой прибор снять с сайта?</b> (' . ( $page + 1 ) . '/' . $pages . ')', $kb );
}

/** Универсальный календарь. $nav и $cell: начала callback_data. $min: первый доступный день. */
function ivs_tg_cal( $ym, $nav, $cell, $min, $mark = array() ) {
	$names = array( 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь' );
	$y     = (int) substr( $ym, 0, 4 );
	$m     = (int) substr( $ym, 5, 2 );
	$prev  = gmdate( 'Y-m', gmmktime( 0, 0, 0, $m - 1, 1, $y ) );
	$next  = gmdate( 'Y-m', gmmktime( 0, 0, 0, $m + 1, 1, $y ) );
	$can   = $prev >= substr( $min, 0, 7 );
	$kb    = array( array(
		array( 'text' => $can ? '‹' : ' ', 'callback_data' => $can ? $nav . $prev : 'x' ),
		array( 'text' => $names[ $m - 1 ] . ' ' . $y, 'callback_data' => 'x' ),
		array( 'text' => '›', 'callback_data' => $nav . $next ),
	), array() );
	foreach ( array( 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс' ) as $w ) {
		$kb[1][] = array( 'text' => $w, 'callback_data' => 'x' );
	}
	$first = gmmktime( 0, 0, 0, $m, 1, $y );
	$lead  = ( (int) gmdate( 'N', $first ) ) - 1;
	$count = (int) gmdate( 't', $first );
	$row   = array();
	for ( $i = 0; $i < $lead; $i++ ) {
		$row[] = array( 'text' => ' ', 'callback_data' => 'x' );
	}
	for ( $d = 1; $d <= $count; $d++ ) {
		$date = sprintf( '%04d-%02d-%02d', $y, $m, $d );
		if ( $date < $min ) {
			$row[] = array( 'text' => '·', 'callback_data' => 'x' );
		} else {
			$row[] = array( 'text' => isset( $mark[ $date ] ) ? $mark[ $date ] : (string) $d, 'callback_data' => $cell . ivs_tg_d2c( $date ) );
		}
		if ( 7 === count( $row ) ) {
			$kb[] = $row;
			$row  = array();
		}
	}
	if ( $row ) {
		while ( count( $row ) < 7 ) {
			$row[] = array( 'text' => ' ', 'callback_data' => 'x' );
		}
		$kb[] = $row;
	}
	return $kb;
}

/** Сетка времени 9:00–18:00 с шагом 30 минут. $min: HHMM, раньше не показывать. */
function ivs_tg_times( $cell, $min = '' ) {
	$rows = array();
	$row  = array();
	for ( $mins = 9 * 60; $mins <= 18 * 60; $mins += 30 ) {
		$c = sprintf( '%02d%02d', intdiv( $mins, 60 ), $mins % 60 );
		if ( $min && $c <= $min ) {
			continue;
		}
		$row[] = array( 'text' => ivs_tg_fmt_time( $c ), 'callback_data' => $cell . $c );
		if ( 4 === count( $row ) ) {
			$rows[] = $row;
			$row    = array();
		}
	}
	if ( $row ) {
		$rows[] = $row;
	}
	return $rows;
}

function ivs_tg_back_pick() {
	return array( array( 'text' => '‹ К списку приборов', 'callback_data' => 'bl|0' ), array( 'text' => '🏠 Меню', 'callback_data' => 'h' ) );
}

/** Шаг 1: календарь, первый день. */
function ivs_tg_screen_bp( $pid, $ym ) {
	$kb   = ivs_tg_cal( $ym, 'bp|' . $pid . '|', 'bf|' . $pid . '|', ivs_book_today() );
	$kb[] = ivs_tg_back_pick();
	return array( '📅 <b>' . ivs_tg_h( get_the_title( $pid ) ) . "</b>\nС какого дня снять с сайта?", $kb );
}

/** Шаг 2: время забора. */
function ivs_tg_screen_bf( $pid, $d ) {
	$kb   = ivs_tg_times( 'bg|' . $pid . '|' . $d . '|' );
	$kb[] = array( array( 'text' => '‹ Другая дата', 'callback_data' => 'bp|' . $pid . '|' . substr( ivs_tg_c2d( $d ), 0, 7 ) ), array( 'text' => '🏠 Меню', 'callback_data' => 'h' ) );
	return array( '📅 <b>' . ivs_tg_h( get_the_title( $pid ) ) . '</b>' . "\nС " . ivs_tg_fmt_date( ivs_tg_c2d( $d ) ) . ".\nВо сколько прибор забирают?", $kb );
}

/** Шаг 3: календарь, день возврата на сайт. */
function ivs_tg_screen_bg( $pid, $d, $t, $ym = '' ) {
	$from = ivs_tg_c2d( $d );
	$kb   = ivs_tg_cal( $ym ? $ym : substr( $from, 0, 7 ), 'bq|' . $pid . '|' . $d . '|' . $t . '|', 'be|' . $pid . '|' . $d . '|' . $t . '|', $from );
	$kb[] = array( array( 'text' => '‹ Другое время', 'callback_data' => 'bf|' . $pid . '|' . $d ), array( 'text' => '🏠 Меню', 'callback_data' => 'h' ) );
	return array( '📅 <b>' . ivs_tg_h( get_the_title( $pid ) ) . '</b>' . "\nС " . ivs_tg_fmt_date( $from ) . ' ' . ivs_tg_fmt_time( $t ) . ".\nКогда вернуть на сайт? Выберите день (на один день нажмите ту же дату).", $kb );
}

/** Шаг 4: время возврата на сайт. */
function ivs_tg_screen_be( $pid, $d, $t, $d2 ) {
	$kb   = ivs_tg_times( 'bz|' . $pid . '|' . $d . '|' . $t . '|' . $d2 . '|', $d === $d2 ? $t : '' );
	$kb[] = array( array( 'text' => '‹ Другая дата', 'callback_data' => 'bg|' . $pid . '|' . $d . '|' . $t ), array( 'text' => '🏠 Меню', 'callback_data' => 'h' ) );
	return array( '📅 <b>' . ivs_tg_h( get_the_title( $pid ) ) . '</b>' . "\nС " . ivs_tg_fmt_date( ivs_tg_c2d( $d ) ) . ' ' . ivs_tg_fmt_time( $t ) . ' до ' . ivs_tg_fmt_date( ivs_tg_c2d( $d2 ) ) . ".\nВо сколько вернуть на сайт?", $kb );
}

/** Выходные дни: нажать на дату, чтобы открыть выходной или закрыть будний. */
function ivs_tg_screen_dp( $ym, $note = '' ) {
	$sp   = ivs_book_special_days();
	$mark = array();
	foreach ( $sp['open'] as $d ) {
		$mark[ $d ] = '🟢';
	}
	foreach ( $sp['closed'] as $d ) {
		$mark[ $d ] = '🔴';
	}
	$kb   = ivs_tg_cal( $ym, 'dp|', 'dd|', ivs_book_today(), $mark );
	$kb[] = ivs_tg_home_row();
	return array( ( $note ? $note . "\n\n" : '' ) . "🗓 <b>Выходные и особые дни</b>\nНажмите на дату:\n🟢 выходной открыт для выдачи и приёма\n🔴 будний день закрыт (праздник)\nПовторное нажатие убирает отметку.", $kb );
}

function ivs_tg_toggle_day( $date ) {
	$o  = get_option( 'ivs_special_days', array() );
	$o  = is_array( $o ) ? $o : array();
	$sp = ivs_book_special_days();
	$op = $sp['open'];
	$cl = $sp['closed'];
	if ( in_array( $date, $op, true ) ) {
		$op   = array_values( array_diff( $op, array( $date ) ) );
		$note = ivs_tg_fmt_date( $date ) . ': отметка снята';
	} elseif ( in_array( $date, $cl, true ) ) {
		$cl   = array_values( array_diff( $cl, array( $date ) ) );
		$note = ivs_tg_fmt_date( $date ) . ': отметка снята';
	} elseif ( ivs_book_is_weekend( $date ) ) {
		$op[] = $date;
		$note = '🟢 ' . ivs_tg_fmt_date( $date ) . ': выходной открыт';
	} else {
		$cl[] = $date;
		$note = '🔴 ' . ivs_tg_fmt_date( $date ) . ': день закрыт';
	}
	sort( $op );
	sort( $cl );
	$o['open']   = $op;
	$o['closed'] = $cl;
	update_option( 'ivs_special_days', $o );
	return $note;
}

/** Снять прибор с сайта на даты. Возвращает текст ошибки или ''. */
function ivs_tg_add_block( $pid, $from, $to, $tf = '', $tt = '' ) {
	global $wpdb;
	if ( ! $pid || ! get_post( $pid ) || ivs_book_is_kit( $pid ) || ! $from || ! $to || $to < $from ) {
		return 'Не удалось: проверьте прибор и даты';
	}
	$wpdb->insert( ivs_book_table(), array( // phpcs:ignore
		'order_id'     => 0,
		'item_id'      => 0,
		'product_id'   => $pid,
		'component_id' => $pid,
		'qty'          => ivs_book_units( $pid ),
		'date_from'    => $from,
		'date_to'      => $to,
		't_from'       => $tf,
		't_to'         => $tt,
		'active'       => 1,
		'created'      => current_time( 'mysql', true ),
	) );
	return '';
}


/* ---------- Доход ---------- */

/** Доход за период: аренда без залога. Считаем подтверждённые и закрытые заказы. */
function ivs_tg_income( $from, $to ) {
	$orders = wc_get_orders( array(
		'status'       => array( 'processing', 'completed' ),
		'limit'        => -1,
		'date_created' => $from . '...' . $to,
		'return'       => 'objects',
	) );
	$gross = 0.0;
	$n     = 0;
	foreach ( $orders as $o ) {
		if ( ! ivs_order_has_booking( $o ) ) {
			continue;
		}
		list( , $dep ) = ivs_order_lines( $o );
		$gross        += max( 0, (float) $o->get_total() - (float) $dep );
		$n++;
	}
	return array( round( $gross, 2 ), $n );
}

function ivs_tg_screen_income( $per = 'm' ) {
	$today = ivs_book_today();
	$ym    = substr( $today, 0, 7 );
	$y     = substr( $today, 0, 4 );
	$pm    = wp_date( 'Y-m', strtotime( $ym . '-01 -1 month' ) );
	$map   = array(
		'd'  => array( 'Сегодня', $today, $today ),
		'm'  => array( 'Этот месяц', $ym . '-01', $today ),
		'pm' => array( 'Прошлый месяц', $pm . '-01', wp_date( 'Y-m-t', strtotime( $pm . '-01' ) ) ),
		'y'  => array( 'Этот год', $y . '-01-01', $today ),
		'a'  => array( 'За всё время', '2020-01-01', $today ),
	);
	if ( ! isset( $map[ $per ] ) ) {
		$per = 'm';
	}
	list( $label, $from, $to ) = $map[ $per ];
	list( $gross, $n )         = ivs_tg_income( $from . ' 00:00:00', $to . ' 23:59:59' );
	$net = round( $gross / 1.21, 2 );
	$t   = '💶 <b>Доход: ' . $label . "</b>\n";
	$t  .= ivs_tg_fmt_date( $from ) . ' – ' . ivs_tg_fmt_date( $to ) . "\n\n";
	$t  .= 'С PVN 21%: <b>' . ivs_tg_h( ivs_money( $gross ) ) . "</b>\n";
	$t  .= 'Без PVN: <b>' . ivs_tg_h( ivs_money( $net ) ) . "</b>\n";
	$t  .= 'PVN: ' . ivs_tg_h( ivs_money( round( $gross - $net, 2 ) ) ) . "\n";
	$t  .= 'Заказов: ' . $n . ( $n ? ' · средний чек с PVN ' . ivs_tg_h( ivs_money( round( $gross / $n, 2 ) ) ) : '' ) . "\n\n";
	$t  .= '<i>Считаются подтверждённые и закрытые заказы, залог не входит.</i>';
	$row = array();
	foreach ( $map as $k => $v ) {
		$row[] = array( 'text' => ( $k === $per ? '• ' : '' ) . $v[0], 'callback_data' => 'in|' . $k );
	}
	return array( $t, array( array_slice( $row, 0, 3 ), array_slice( $row, 3 ), ivs_tg_home_row() ) );
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
	$kb[] = array( array( 'text' => '‹ Брони', 'callback_data' => 'ol' ), array( 'text' => '🏠 Меню', 'callback_data' => 'h' ) );
	return $kb;
}

/** Новый заказ: окно бота обновляется и приходит внизу чата (одно сообщение). */
add_action( 'woocommerce_order_status_changed', function ( $order_id, $from, $to, $order ) {
	if ( ! in_array( $to, array( 'on-hold', 'processing' ), true ) || ! ivs_order_has_booking( $order ) || $order->get_meta( '_ivs_tg_sent' ) ) {
		return;
	}
	if ( ! ivs_tg_token() || ! ivs_tg_opts()['chats'] ) {
		return;
	}
	$order->update_meta_data( '_ivs_tg_sent', '1' );
	$order->save();
	$GLOBALS['ivs_tg_queue'][] = (int) $order->get_id();
}, 20, 4 );

// Отправляем после ответа клиенту, чтобы оформление не ждало Telegram.
add_action( 'shutdown', function () {
	if ( empty( $GLOBALS['ivs_tg_queue'] ) ) {
		return;
	}
	if ( function_exists( 'fastcgi_finish_request' ) ) {
		fastcgi_finish_request();
	}
	foreach ( array_unique( $GLOBALS['ivs_tg_queue'] ) as $oid ) {
		$order = wc_get_order( $oid );
		if ( ! $order ) {
			continue;
		}
		foreach ( ivs_tg_opts()['chats'] as $chat ) {
			ivs_tg_show( $chat, '🆕 ' . ivs_tg_order_text( $order ), ivs_tg_order_keyboard( $order ) );
			ivs_tg_ping( $chat, '🆕 Новый заказ №' . $order->get_id() . ' — смотрите закреплённое окно' );
		}
	}
	$GLOBALS['ivs_tg_queue'] = array();
} );

/* ---------- Кнопки ---------- */

function ivs_tg_on_callback( $cb ) {
	$chat = $cb['message']['chat']['id'] ?? 0;
	$mid  = (int) ( $cb['message']['message_id'] ?? 0 );
	$data = (string) ( $cb['data'] ?? '' );
	$qid  = $cb['id'] ?? '';
	if ( ! $chat || ! ivs_tg_is_paired( $chat ) ) {
		return;
	}
	ivs_tg_clear_pings( $chat );
	$p   = explode( '|', $data );
	$ans = '';
	$k   = $p[0];
	if ( 'h' === $k ) {
		list( $t, $kb ) = ivs_tg_screen_home();
		ivs_tg_show( $chat, $t, $kb, $mid );
	} elseif ( 'ol' === $k ) {
		ivs_tg_go( $chat, $mid, 'orders' );
	} elseif ( 'oc' === $k ) {
		ivs_tg_go( $chat, $mid, 'order', absint( $p[1] ?? 0 ) );
	} elseif ( 'td' === $k ) {
		ivs_tg_go( $chat, $mid, 'today' );
	} elseif ( 'in' === $k ) {
		ivs_tg_go( $chat, $mid, 'income', $p[1] ?? 'm' );
	} elseif ( 'ul' === $k ) {
		ivs_tg_go( $chat, $mid, 'blocks' );
	} elseif ( 'o' === $k && isset( $p[2] ) ) {
		$order = wc_get_order( absint( $p[1] ) );
		if ( ! $order ) {
			$ans = 'Заказ не найден';
		} else {
			$act = $p[2];
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
			ivs_tg_go( $chat, $mid, 'order', $order->get_id() );
		}
	} elseif ( 'u' === $k && isset( $p[1] ) ) {
		global $wpdb;
		$wpdb->delete( ivs_book_table(), array( 'id' => absint( $p[1] ), 'order_id' => 0 ) ); // phpcs:ignore
		$ans = 'Прибор снова на сайте';
		ivs_tg_go( $chat, $mid, 'blocks' );
	} elseif ( 'bl' === $k ) {
		ivs_tg_go( $chat, $mid, 'pick', (int) ( $p[1] ?? 0 ) );
	} elseif ( 'bp' === $k && isset( $p[2] ) ) {
		ivs_tg_go( $chat, $mid, 'bp', absint( $p[1] ), $p[2] );
	} elseif ( 'bf' === $k && isset( $p[2] ) ) {
		ivs_tg_go( $chat, $mid, 'bf', absint( $p[1] ), $p[2] );
	} elseif ( 'bg' === $k && isset( $p[3] ) ) {
		list( $t, $kb ) = ivs_tg_screen_bg( absint( $p[1] ), $p[2], $p[3] );
		ivs_tg_show( $chat, $t, $kb, $mid );
	} elseif ( 'bq' === $k && isset( $p[4] ) ) {
		list( $t, $kb ) = ivs_tg_screen_bg( absint( $p[1] ), $p[2], $p[3], $p[4] );
		ivs_tg_show( $chat, $t, $kb, $mid );
	} elseif ( 'be' === $k && isset( $p[4] ) ) {
		list( $t, $kb ) = ivs_tg_screen_be( absint( $p[1] ), $p[2], $p[3], $p[4] );
		ivs_tg_show( $chat, $t, $kb, $mid );
	} elseif ( 'bz' === $k && isset( $p[5] ) ) {
		$pid  = absint( $p[1] );
		$from = ivs_tg_c2d( $p[2] );
		$to   = ivs_tg_c2d( $p[4] );
		$err  = ivs_tg_add_block( $pid, $from, $to, preg_replace( '/\D/', '', $p[3] ), preg_replace( '/\D/', '', $p[5] ) );
		if ( $err ) {
			$ans = $err;
		} else {
			$ans = 'Готово';
			ivs_tg_show( $chat, '🚫 <b>' . ivs_tg_h( get_the_title( $pid ) ) . "</b> снят с сайта\nс " . ivs_tg_fmt_date( $from ) . ' ' . ivs_tg_fmt_time( $p[3] ) . "\nдо " . ivs_tg_fmt_date( $to ) . ' ' . ivs_tg_fmt_time( $p[5] ) . "\n\nВернуть на сайт раньше можно в разделе «Снятые».", array( array( array( 'text' => '📅 Забронировать ещё', 'callback_data' => 'bl|0' ), array( 'text' => '🚫 Снятые', 'callback_data' => 'ul' ) ), ivs_tg_home_row() ), $mid );
		}
	} elseif ( 'dp' === $k && isset( $p[1] ) ) {
		list( $t, $kb ) = ivs_tg_screen_dp( $p[1] );
		ivs_tg_show( $chat, $t, $kb, $mid );
	} elseif ( 'dd' === $k && isset( $p[1] ) ) {
		$date = ivs_tg_c2d( $p[1] );
		if ( $date ) {
			$note = ivs_tg_toggle_day( $date );
			list( $t, $kb ) = ivs_tg_screen_dp( substr( $date, 0, 7 ), $note );
			ivs_tg_show( $chat, $t, $kb, $mid );
			$ans = $note;
		}
	}
	ivs_tg_call( 'answerCallbackQuery', array( 'callback_query_id' => $qid, 'text' => $ans, 'show_alert' => strlen( $ans ) > 40 ) );
}

/* ---------- Команда /block (по-старому, текстом) ---------- */

/** «/block Testo 420 21.11.2026 25.11.2026». */
function ivs_tg_cmd_block( $chat, $arg ) {
	$parts = preg_split( '/\s+/', trim( $arg ), -1, PREG_SPLIT_NO_EMPTY );
	$dates = array();
	while ( $parts && count( $dates ) < 2 && ivs_book_parse_dates( end( $parts ) ) ) {
		array_unshift( $dates, ivs_book_parse_dates( array_pop( $parts ) )[0] );
	}
	$name = implode( ' ', $parts );
	$note = '';
	if ( '' === $name || ! $dates ) {
		$note = 'Проще через кнопку «Забронировать». Текстом: /block Testo 420 21.11.2026 25.11.2026';
	} else {
		$from = $dates[0];
		$to   = $dates[1] ?? $dates[0];
		$pid  = ivs_book_find_by_title( $name );
		if ( ! $pid ) {
			global $wpdb;
			$like = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_title LIKE %s LIMIT 6", '%' . $wpdb->esc_like( $name ) . '%' ) ); // phpcs:ignore
			$pid  = 1 === count( $like ) ? (int) $like[0]->ID : 0;
			if ( ! $pid ) {
				$note = $like ? 'Уточните название: ' . ivs_tg_h( implode( '; ', wp_list_pluck( $like, 'post_title' ) ) ) : 'Прибор «' . ivs_tg_h( $name ) . '» не найден.';
			}
		}
		if ( $pid ) {
			$err  = ivs_tg_add_block( $pid, $from, $to );
			$note = $err ? $err : '🚫 ' . ivs_tg_h( get_the_title( $pid ) ) . ' снят с сайта: ' . ivs_tg_fmt_date( $from ) . ( $to !== $from ? ' → ' . ivs_tg_fmt_date( $to ) : '' );
		}
	}
	ivs_tg_open( $chat, $note );
}
