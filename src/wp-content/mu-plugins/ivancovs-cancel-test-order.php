<?php
/**
 * Plugin Name: Ivancovs: отмена тестового заказа 3167
 * Description: Одноразово отменяет тестовый заказ 3167 (почта test-order@example.com), чтобы освободить склад на 23–24.11.2026. Другие заказы не трогает. Файл удаляется следующим изменением.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
add_action( 'init', function () {
	if ( get_option( 'ivs_cancel_test_3167' ) || ! function_exists( 'wc_get_order' ) ) {
		return;
	}
	$order = wc_get_order( 3167 );
	if ( ! $order ) {
		return;
	}
	update_option( 'ivs_cancel_test_3167', gmdate( 'c' ), false );
	if ( 'test-order@example.com' !== $order->get_billing_email() || in_array( $order->get_status(), array( 'cancelled', 'completed', 'processing' ), true ) ) {
		return;
	}
	$order->update_status( 'cancelled', 'Тестовый заказ: отменён.' );
}, 30 );
