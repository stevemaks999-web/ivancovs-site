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
