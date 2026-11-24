<?php
/**
 * Письмо клиенту «заказ hold»: тексты в inc/emails.php.
 *
 * @var WC_Order $order
 * @var string   $email_heading
 * @var WC_Email $email
 */

defined( 'ABSPATH' ) || exit;

$ivs_kind = 'hold';
$ivs_loc  = ivs_order_has_booking( $order ) ? ivs_em_locale( $order ) : '';
if ( $ivs_loc ) {
	switch_to_locale( $ivs_loc );
}

do_action( 'woocommerce_email_header', $email_heading, $email );

if ( $ivs_loc ) {
	ivs_em_order_intro( $order, $ivs_kind );
	ivs_em_enter( $order );
}

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
if ( ! $ivs_loc ) {
	do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );
}

if ( $ivs_loc ) {
	ivs_em_leave();
	ivs_em_order_notes( $order, $ivs_kind );
}

do_action( 'woocommerce_email_footer', $email );

if ( $ivs_loc ) {
	restore_previous_locale();
}
