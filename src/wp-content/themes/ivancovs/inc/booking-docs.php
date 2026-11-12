<?php
/**
 * Документы по брони: счёт с PVN 21%, акт выдачи, письма клиенту, возврат прибора.
 *
 * Цены на сайте включают PVN 21%. Залог в PVN не входит.
 * Реквизиты продавца лежат в настройках сайта (WooCommerce → Реквизиты для счёта), не в коде.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const IVS_VAT_RATE = 21;

/* ---------- Реквизиты продавца ---------- */

function ivs_seller_defaults() {
	return array(
		'name'    => 'SIA "Delumix"',
		'reg'     => '40203480127',
		'vat'     => 'LV40203480127',
		'address' => 'Lastādijas iela 32, Rīga, LV-1050',
		'bank'    => '',
		'iban'    => '',
		'email'   => IVS_EMAIL,
		'phone'   => IVS_PHONE,
	);
}

function ivs_seller() {
	$saved = get_option( 'ivs_seller', array() );
	return array_merge( ivs_seller_defaults(), is_array( $saved ) ? array_filter( $saved, 'strlen' ) : array() );
}

add_action( 'admin_menu', function () {
	add_submenu_page( 'woocommerce', 'Реквизиты для счёта', 'Реквизиты для счёта', 'manage_woocommerce', 'ivs-seller', 'ivs_seller_page' );
} );

function ivs_seller_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	if ( isset( $_POST['ivs_seller_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ivs_seller_nonce'] ) ), 'ivs_seller' ) ) {
		$new = array();
		foreach ( array_keys( ivs_seller_defaults() ) as $k ) {
			$new[ $k ] = isset( $_POST[ 'ivs_' . $k ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'ivs_' . $k ] ) ) : '';
		}
		update_option( 'ivs_seller', $new, false );
		echo '<div class="updated"><p>Сохранено.</p></div>';
	}
	$s      = ivs_seller();
	$labels = array(
		'name'    => 'Название',
		'reg'     => 'Регистрационный номер',
		'vat'     => 'PVN-номер',
		'address' => 'Юридический адрес',
		'bank'    => 'Банк',
		'iban'    => 'IBAN',
		'email'   => 'Эл. почта в счёте',
		'phone'   => 'Телефон в счёте',
	);
	echo '<div class="wrap"><h1>Реквизиты для счёта</h1><p>Эти данные печатаются в счёте и акте. Проверьте их и впишите банк и IBAN.</p><form method="post"><table class="form-table">';
	foreach ( $labels as $k => $l ) {
		printf( '<tr><th><label for="ivs_%1$s">%2$s</label></th><td><input class="regular-text" id="ivs_%1$s" name="ivs_%1$s" value="%3$s"></td></tr>', esc_attr( $k ), esc_html( $l ), esc_attr( $s[ $k ] ) );
	}
	wp_nonce_field( 'ivs_seller', 'ivs_seller_nonce' );
	echo '</table><p><button class="button button-primary">Сохранить</button></p></form></div>';
}

/* ---------- Номер счёта ---------- */

function ivs_invoice_number( $order ) {
	$no = $order->get_meta( '_ivs_invoice_no' );
	if ( $no ) {
		return $no;
	}
	global $wpdb;
	$wpdb->get_var( "SELECT GET_LOCK('ivs_invoice_no', 5)" ); // phpcs:ignore
	$order->read_meta_data( true );
	$no = $order->get_meta( '_ivs_invoice_no' );
	if ( ! $no ) {
		$n  = (int) get_option( 'ivs_invoice_counter', 0 ) + 1;
		$no = sprintf( 'IVS-%s-%05d', wp_date( 'Y' ), $n );
		update_option( 'ivs_invoice_counter', $n, false );
		$order->update_meta_data( '_ivs_invoice_no', $no );
		$order->save();
	}
	$wpdb->get_var( "SELECT RELEASE_LOCK('ivs_invoice_no')" ); // phpcs:ignore
	return $no;
}

/** Бронь это заказ, где есть строки с датами. */
function ivs_order_has_booking( $order ) {
	foreach ( $order->get_items() as $item ) {
		if ( $item->get_meta( '_ivs_from' ) ) {
			return true;
		}
	}
	return false;
}

add_action( 'woocommerce_order_status_changed', function ( $order_id, $from, $to, $order ) {
	if ( in_array( $to, array( 'on-hold', 'processing' ), true ) && ivs_order_has_booking( $order ) ) {
		ivs_invoice_number( $order );
	}
}, 10, 4 );

/* ---------- Ссылки на документы ---------- */

function ivs_doc_url( $order, $type ) {
	return add_query_arg( array(
		'ivs_doc'  => $type,
		'order_id' => $order->get_id(),
		'key'      => $order->get_order_key(),
	), home_url( '/' ) );
}

function ivs_doc_can_view( $order ) {
	if ( current_user_can( 'manage_woocommerce' ) ) {
		return true;
	}
	$key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	return '' !== $key && hash_equals( $order->get_order_key(), $key );
}

function ivs_money( $v ) {
	return number_format( (float) $v, 2, ',', ' ' ) . ' €';
}

add_action( 'template_redirect', function () {
	if ( empty( $_GET['ivs_doc'] ) || empty( $_GET['order_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$type  = sanitize_key( wp_unslash( $_GET['ivs_doc'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$order = wc_get_order( absint( $_GET['order_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! $order || ! ivs_order_has_booking( $order ) || ! ivs_doc_can_view( $order ) ) {
		wp_die( 'Dokuments nav pieejams.', 'Dokuments', array( 'response' => 404 ) );
	}
	if ( 'act' === $type && ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Dokuments nav pieejams.', 'Dokuments', array( 'response' => 404 ) );
	}
	nocache_headers();
	header( 'X-Robots-Tag: noindex' );
	header( 'Content-Type: text/html; charset=utf-8' );
	if ( 'invoice' === $type ) {
		ivs_render_invoice( $order );
	} elseif ( 'act' === $type ) {
		ivs_render_act( $order );
	} else {
		wp_die( 'Dokuments nav pieejams.', 'Dokuments', array( 'response' => 404 ) );
	}
	exit;
} );

function ivs_doc_head( $title ) {
	echo '<!doctype html><html lang="lv"><head><meta charset="utf-8"><meta name="robots" content="noindex"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . esc_html( $title ) . '</title><style>
body{font:14px/1.45 Arial,Helvetica,sans-serif;color:#111;max-width:820px;margin:24px auto;padding:0 16px}
h1{font-size:22px;margin:0 0 4px}.mut{color:#555}.two{display:flex;gap:24px;margin:18px 0}.two>div{flex:1}
table{width:100%;border-collapse:collapse;margin:14px 0}th,td{border-bottom:1px solid #ccc;padding:7px 6px;text-align:left;vertical-align:top}th{background:#f2f2f2;font-size:12px}
td.r,th.r{text-align:right}.tot td{border:0;padding:3px 6px}.tot .b td{font-weight:bold;font-size:16px;border-top:2px solid #111}
.sign{display:flex;gap:40px;margin-top:48px}.sign div{flex:1;border-top:1px solid #111;padding-top:4px;font-size:12px}
.bar{margin-bottom:16px}.bar button{padding:8px 14px}@media print{.bar{display:none}body{margin:0}}
</style></head><body><div class="bar"><button onclick="window.print()">Drukāt / Saglabāt PDF</button></div>';
}

/** Строки аренды и залог заказа. */
function ivs_order_lines( $order ) {
	$rent    = array();
	$deposit = 0.0;
	foreach ( $order->get_items() as $item ) {
		$gross  = (float) $item->get_total() + (float) $item->get_total_tax();
		$net    = round( $gross / ( 1 + IVS_VAT_RATE / 100 ), 2 );
		$rent[] = array(
			'name'  => $item->get_name(),
			'qty'   => (int) $item->get_quantity(),
			'from'  => $item->get_meta( '_ivs_from' ),
			'to'    => $item->get_meta( '_ivs_to' ),
			't_from' => $item->get_meta( '_ivs_t_from' ),
			't_to'  => $item->get_meta( '_ivs_t_to' ),
			'days'  => $item->get_meta( '_ivs_from' ) ? ivs_book_days( $item->get_meta( '_ivs_from' ), $item->get_meta( '_ivs_to' ) ) : 0,
			'gross' => $gross,
			'net'   => $net,
			'vat'   => round( $gross - $net, 2 ),
		);
	}
	foreach ( $order->get_fees() as $fee ) {
		$deposit += (float) $fee->get_total();
	}
	return array( $rent, $deposit );
}

function ivs_render_invoice( $order ) {
	$s              = ivs_seller();
	$no             = ivs_invoice_number( $order );
	list( $rent, $deposit ) = ivs_order_lines( $order );
	$net            = array_sum( wp_list_pluck( $rent, 'net' ) );
	$vat            = array_sum( wp_list_pluck( $rent, 'vat' ) );
	$gross          = array_sum( wp_list_pluck( $rent, 'gross' ) );
	$buyer          = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
	if ( $order->get_billing_company() ) {
		$buyer = $order->get_billing_company() . ', ' . $buyer;
	}
	$addr = trim( implode( ', ', array_filter( array( $order->get_billing_address_1(), $order->get_billing_city(), $order->get_billing_postcode() ) ) ) );
	$date = $order->get_date_created() ? $order->get_date_created()->date_i18n( 'd.m.Y' ) : wp_date( 'd.m.Y' );

	ivs_doc_head( 'Rēķins ' . $no );
	echo '<h1>Rēķins Nr. ' . esc_html( $no ) . '</h1><div class="mut">Datums: ' . esc_html( $date ) . ' · Pasūtījums Nr. ' . (int) $order->get_id() . '</div>';
	echo '<div class="two"><div><b>Pakalpojuma sniedzējs</b><br>' . esc_html( $s['name'] ) . '<br>Reģ. Nr. ' . esc_html( $s['reg'] ) . '<br>PVN Nr. ' . esc_html( $s['vat'] ) . '<br>' . esc_html( $s['address'] );
	if ( $s['bank'] || $s['iban'] ) {
		echo '<br>' . esc_html( trim( $s['bank'] . ' ' . $s['iban'] ) );
	}
	echo '</div><div><b>Pakalpojuma saņēmējs</b><br>' . esc_html( $buyer ) . '<br>' . esc_html( $addr ) . '<br>' . esc_html( $order->get_billing_email() ) . '</div></div>';
	echo '<table><tr><th>Pakalpojums</th><th>Periods</th><th class="r">Skaits</th><th class="r">Bez PVN</th><th class="r">PVN ' . (int) IVS_VAT_RATE . '%</th><th class="r">Kopā</th></tr>';
	foreach ( $rent as $r ) {
		echo '<tr><td>Mērinstrumenta noma: ' . esc_html( $r['name'] ) . '</td><td>' . esc_html( $r['from'] . ' ' . $r['t_from'] . ' – ' . $r['to'] . ' ' . $r['t_to'] ) . ' (' . (int) $r['days'] . ' d.)</td><td class="r">' . (int) $r['qty'] . '</td><td class="r">' . esc_html( ivs_money( $r['net'] ) ) . '</td><td class="r">' . esc_html( ivs_money( $r['vat'] ) ) . '</td><td class="r">' . esc_html( ivs_money( $r['gross'] ) ) . '</td></tr>';
	}
	if ( $deposit > 0 ) {
		echo '<tr><td>Drošības nauda (atmaksājama pēc instrumenta atgriešanas, ārpus PVN)</td><td></td><td class="r">1</td><td class="r">' . esc_html( ivs_money( $deposit ) ) . '</td><td class="r">0,00 €</td><td class="r">' . esc_html( ivs_money( $deposit ) ) . '</td></tr>';
	}
	echo '</table><table class="tot"><tr><td class="r">Nomas summa bez PVN</td><td class="r" style="width:130px">' . esc_html( ivs_money( $net ) ) . '</td></tr>';
	echo '<tr><td class="r">PVN ' . (int) IVS_VAT_RATE . '%</td><td class="r">' . esc_html( ivs_money( $vat ) ) . '</td></tr>';
	if ( $deposit > 0 ) {
		echo '<tr><td class="r">Drošības nauda (ārpus PVN)</td><td class="r">' . esc_html( ivs_money( $deposit ) ) . '</td></tr>';
	}
	echo '<tr class="b"><td class="r">Apmaksai</td><td class="r">' . esc_html( ivs_money( $gross + $deposit ) ) . '</td></tr></table>';
	if ( $s['iban'] ) {
		echo '<p>Apmaksa pārskaitījumā: ' . esc_html( $s['bank'] ) . ', konts ' . esc_html( $s['iban'] ) . ', atsauce: ' . esc_html( $no ) . '.</p>';
	}
	echo '<p class="mut">Rēķins sagatavots elektroniski un ir derīgs bez paraksta. Instrumentu izsniedz un pieņem 9:00–18:00, uzrādot vienu dokumentu (pase, tiesības vai ID). ' . esc_html( $s['email'] . ', ' . $s['phone'] ) . '</p></body></html>';
}

/* ---------- Акт выдачи ---------- */

/** Что выдаём: строки [ ключ => название ] по приборам внутри заказа (комплект раскрывается на приборы). */
function ivs_act_rows( $order ) {
	$rows = array();
	foreach ( $order->get_items() as $item_id => $item ) {
		if ( ! $item->get_meta( '_ivs_from' ) ) {
			continue;
		}
		$qty = max( 1, (int) $item->get_quantity() );
		for ( $q = 1; $q <= $qty; $q++ ) {
			foreach ( ivs_book_components( (int) $item->get_product_id() ) as $cid => $n ) {
				for ( $i = 1; $i <= $n; $i++ ) {
					$rows[ $item_id . '_' . $q . '_' . $cid . '_' . $i ] = array(
						'item'  => $item->get_name(),
						'name'  => get_the_title( $cid ),
					);
				}
			}
		}
	}
	return $rows;
}

function ivs_render_act( $order ) {
	$s       = ivs_seller();
	$serials = (array) $order->get_meta( '_ivs_serials' );
	$doc     = $order->get_meta( '_ivs_doc_type' );
	$checked = (bool) $order->get_meta( '_ivs_doc_checked' );
	$issued  = $order->get_meta( '_ivs_issued_at' );
	$pick    = ivs_book_pickup_name( $order );
	list( , $deposit ) = ivs_order_lines( $order );
	$renter  = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

	ivs_doc_head( 'Izsniegšanas akts ' . $order->get_id() );
	echo '<h1>Instrumentu izsniegšanas akts</h1><div class="mut">Pasūtījums Nr. ' . (int) $order->get_id() . ( $issued ? ' · Izsniegts: ' . esc_html( $issued ) : '' ) . '</div>';
	echo '<div class="two"><div><b>Iznomātājs</b><br>' . esc_html( $s['name'] ) . '<br>Reģ. Nr. ' . esc_html( $s['reg'] ) . '<br>' . esc_html( $s['address'] ) . '</div><div><b>Nomnieks</b><br>' . esc_html( $renter ) . '<br>Instrumentu saņem: ' . esc_html( $pick ) . '<br>' . esc_html( $order->get_billing_email() ) . '</div></div>';
	echo '<p>Dokuments: <b>' . esc_html( $doc ? $doc : '—' ) . '</b> · ' . ( $checked ? 'dokuments pārbaudīts ✔' : 'dokuments nav pārbaudīts' ) . '</p>';
	echo '<table><tr><th>Instruments</th><th>Sērijas numurs</th></tr>';
	foreach ( ivs_act_rows( $order ) as $key => $r ) {
		echo '<tr><td>' . esc_html( $r['name'] ) . ( $r['name'] !== $r['item'] ? ' <span class="mut">(' . esc_html( $r['item'] ) . ')</span>' : '' ) . '</td><td>' . esc_html( $serials[ $key ] ?? '' ) . '</td></tr>';
	}
	echo '</table>';
	foreach ( $order->get_items() as $item ) {
		if ( $item->get_meta( '_ivs_from' ) ) {
			echo '<p>' . esc_html( $item->get_name() ) . ': ' . esc_html( $item->get_meta( '_ivs_from' ) . ' ' . $item->get_meta( '_ivs_t_from' ) . ' – ' . $item->get_meta( '_ivs_to' ) . ' ' . $item->get_meta( '_ivs_t_to' ) ) . '</p>';
		}
	}
	if ( $deposit > 0 ) {
		echo '<p>Drošības nauda: <b>' . esc_html( ivs_money( $deposit ) ) . '</b> (atmaksājama pēc instrumentu atgriešanas).</p>';
	}
	echo '<p>Nomnieks apstiprina, ka instrumenti izsniegti darba kārtībā, bez redzamiem bojājumiem, un apņemas tos atgriezt norādītajā laikā (līdz 18:00) tādā pašā stāvoklī.</p>';
	echo '<div class="sign"><div>Iznomātājs (paraksts)</div><div>Nomnieks (paraksts)</div></div></body></html>';
}

/* ---------- Блок в заказе (админка): серийные номера, документ, выдача, возврат ---------- */

add_action( 'add_meta_boxes', function () {
	$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
	add_meta_box( 'ivs_issue', 'Выдача и возврат приборов', 'ivs_issue_box', $screen, 'normal', 'high' );
} );

function ivs_issue_box( $post_or_order ) {
	$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
	if ( ! $order || ! ivs_order_has_booking( $order ) ) {
		echo '<p>В заказе нет аренды.</p>';
		return;
	}
	$serials = (array) $order->get_meta( '_ivs_serials' );
	$doc     = (string) $order->get_meta( '_ivs_doc_type' );
	$checked = (bool) $order->get_meta( '_ivs_doc_checked' );
	wp_nonce_field( 'ivs_issue_save', 'ivs_issue_nonce' );
	echo '<p>Кто заберёт: <b>' . esc_html( ivs_book_pickup_name( $order ) ) . '</b></p>';
	echo '<table class="widefat striped"><thead><tr><th>Прибор</th><th>Серийный номер</th></tr></thead><tbody>';
	foreach ( ivs_act_rows( $order ) as $key => $r ) {
		printf( '<tr><td>%s</td><td><input type="text" name="ivs_serial[%s]" value="%s" class="regular-text"></td></tr>', esc_html( $r['name'] ), esc_attr( $key ), esc_attr( $serials[ $key ] ?? '' ) );
	}
	echo '</tbody></table><p><label>Документ: <select name="ivs_doc_type">';
	foreach ( array( '', 'Pase', 'Autovadītāja apliecība', 'ID karte' ) as $d ) {
		printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $d ), selected( $doc, $d, false ), esc_html( '' === $d ? '— выбрать —' : $d ) );
	}
	echo '</select></label> &nbsp; <label><input type="checkbox" name="ivs_doc_checked" value="1"' . checked( $checked, true, false ) . '> документ проверен</label></p>';
	echo '<p class="description">Нажмите «Обновить» в правой колонке, чтобы сохранить.</p><p>';
	echo '<a class="button" target="_blank" href="' . esc_url( ivs_doc_url( $order, 'act' ) ) . '">Акт выдачи (печать)</a> ';
	echo '<a class="button" target="_blank" href="' . esc_url( ivs_doc_url( $order, 'invoice' ) ) . '">Счёт</a> ';
	if ( ! $order->get_meta( '_ivs_issued_at' ) ) {
		echo '<a class="button button-primary" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ivs_issue&order_id=' . $order->get_id() ), 'ivs_issue_' . $order->get_id() ) ) . '">Прибор выдан</a>';
	} elseif ( ! $order->get_meta( '_ivs_returned_at' ) ) {
		echo 'Выдан: ' . esc_html( $order->get_meta( '_ivs_issued_at' ) ) . ' &nbsp; <a class="button button-primary" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ivs_return&order_id=' . $order->get_id() ), 'ivs_return_' . $order->get_id() ) ) . '">Прибор возвращён</a>';
	} else {
		echo 'Выдан: ' . esc_html( $order->get_meta( '_ivs_issued_at' ) ) . ' · Возвращён: ' . esc_html( $order->get_meta( '_ivs_returned_at' ) );
	}
	echo '</p>';
}

add_action( 'woocommerce_process_shop_order_meta', function ( $order_id ) {
	if ( empty( $_POST['ivs_issue_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ivs_issue_nonce'] ) ), 'ivs_issue_save' ) || ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return;
	}
	$serials = array();
	$post    = isset( $_POST['ivs_serial'] ) && is_array( $_POST['ivs_serial'] ) ? wp_unslash( $_POST['ivs_serial'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$allowed = ivs_act_rows( $order );
	foreach ( $post as $k => $v ) {
		if ( isset( $allowed[ $k ] ) ) {
			$serials[ $k ] = sanitize_text_field( $v );
		}
	}
	$order->update_meta_data( '_ivs_serials', $serials );
	$order->update_meta_data( '_ivs_doc_type', isset( $_POST['ivs_doc_type'] ) ? sanitize_text_field( wp_unslash( $_POST['ivs_doc_type'] ) ) : '' );
	$order->update_meta_data( '_ivs_doc_checked', empty( $_POST['ivs_doc_checked'] ) ? '' : '1' );
	$order->save();
} );

/** «Прибор выдан»: нужен проверенный документ и серийные номера. */
add_action( 'admin_post_ivs_issue', function () {
	$id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
	check_admin_referer( 'ivs_issue_' . $id );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Нет доступа' );
	}
	$order = wc_get_order( $id );
	if ( ! $order ) {
		wp_die( 'Заказ не найден' );
	}
	$serials = array_filter( (array) $order->get_meta( '_ivs_serials' ) );
	if ( ! $order->get_meta( '_ivs_doc_checked' ) || count( $serials ) < count( ivs_act_rows( $order ) ) ) {
		wp_die( 'Сначала впишите все серийные номера, выберите документ, отметьте «документ проверен» и сохраните заказ.', 'Выдача', array( 'back_link' => true ) );
	}
	$order->update_meta_data( '_ivs_issued_at', wp_date( 'd.m.Y H:i' ) );
	$order->add_order_note( 'Приборы выданы, документ проверен.' );
	$order->save();
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
	exit;
} );

/** «Прибор возвращён»: освобождаем даты, закрываем заказ, пишем клиенту. */
add_action( 'admin_post_ivs_return', function () {
	$id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
	check_admin_referer( 'ivs_return_' . $id );
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( 'Нет доступа' );
	}
	$order = wc_get_order( $id );
	if ( ! $order ) {
		wp_die( 'Заказ не найден' );
	}
	global $wpdb;
	$order->update_meta_data( '_ivs_returned_at', wp_date( 'd.m.Y H:i' ) );
	$order->save();
	$order->update_status( 'completed', 'Приборы возвращены.' );
	$wpdb->update( ivs_book_table(), array( 'active' => 0 ), array( 'order_id' => $id ) ); // phpcs:ignore
	ivs_send_return_email( $order );
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
	exit;
} );

/* ---------- Письма клиенту ---------- */

function ivs_send_return_email( $order ) {
	$to = $order->get_billing_email();
	if ( ! $to ) {
		return;
	}
	list( , $deposit ) = ivs_order_lines( $order );
	$d  = $deposit > 0;
	$lv = 'Paldies! Instrumenti ir atgriezti.' . ( $d ? ' Drošības nauda ' . ivs_money( $deposit ) . ' tiks atmaksāta.' : '' );
	$ru = 'Спасибо! Приборы возвращены.' . ( $d ? ' Залог ' . ivs_money( $deposit ) . ' будет возвращён.' : '' );
	$en = 'Thank you! The instruments have been returned.' . ( $d ? ' The deposit of ' . ivs_money( $deposit ) . ' will be refunded.' : '' );
	$body = '<p>' . esc_html( $lv ) . '</p><p>' . esc_html( $ru ) . '</p><p>' . esc_html( $en ) . '</p>';
	$mailer = WC()->mailer();
	$mailer->send( $to, 'Instrumenti atgriezti / Приборы возвращены / Instruments returned', $mailer->wrap_message( 'Paldies! / Спасибо! / Thank you!', $body ) );
}

/** В письма о заказе добавляем ссылку на счёт и правила выдачи. */
add_action( 'woocommerce_email_after_order_table', function ( $order, $sent_to_admin, $plain_text ) {
	if ( $sent_to_admin || ! ivs_order_has_booking( $order ) ) {
		return;
	}
	$link = ivs_doc_url( $order, 'invoice' );
	$rows = array(
		array( 'Rēķins (PVN 21%)', 'Счёт (PVN 21%)', 'Invoice (21% VAT)' ),
	);
	$rules = array(
		'Izsniegšana un atgriešana 9:00–18:00. Brīvdienās tikai pēc vienošanās ar Dmitriju. Uzrādiet vienu dokumentu: pase, tiesības vai ID. Instrumentu var saņemt tikai pasūtījumā norādītā persona.',
		'Выдача и возврат с 9:00 до 18:00. В выходные только по договорённости с Дмитрием. Покажите один документ: паспорт, права или ID. Прибор заберёт только человек, указанный в заказе.',
		'Pickup and return 9:00–18:00. Weekends only by arrangement with Dmitrijs. Show one ID: passport, licence or ID card. Only the person named in the order can collect the instrument.',
	);
	if ( $plain_text ) {
		echo "\n" . $link . "\n" . implode( "\n", $rules ) . "\n"; // phpcs:ignore
		return;
	}
	echo '<p><a href="' . esc_url( $link ) . '">' . esc_html( $rows[0][0] . ' / ' . $rows[0][1] . ' / ' . $rows[0][2] ) . '</a></p>';
	foreach ( $rules as $r ) {
		echo '<p style="font-size:13px;color:#555">' . esc_html( $r ) . '</p>';
	}
}, 20, 3 );

/** Ссылка на счёт на странице «Заказ получен». */
add_action( 'woocommerce_thankyou', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( $order && ivs_order_has_booking( $order ) ) {
		echo '<p><a class="btn" href="' . esc_url( ivs_doc_url( $order, 'invoice' ) ) . '">' . esc_html( ivs_t( 'Rēķins (PVN 21%)', 'Счёт (PVN 21%)', 'Invoice (21% VAT)' ) ) . '</a></p>';
	}
}, 5 );

/** Служебные поля дат не показываем в заказе. */
add_filter( 'woocommerce_hidden_order_itemmeta', function ( $keys ) {
	return array_merge( $keys, array( '_ivs_from', '_ivs_to', '_ivs_t_from', '_ivs_t_to' ) );
} );
