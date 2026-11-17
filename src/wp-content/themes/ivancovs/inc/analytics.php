<?php
/**
 * Аналитика: события в dataLayer для Tag Manager и GA4.
 * Цели: просмотр прибора, бронь, оплата, звонок, WhatsApp.
 * Сами теги срабатывают только после согласия в cookie-баннере (Consent Mode).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ivs_ga_push( $data ) {
	echo '<script>window.dataLayer=window.dataLayer||[];window.dataLayer.push(' . wp_json_encode( $data ) . ');</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}

function ivs_ga_item( $product, $qty = 1 ) {
	return array(
		'item_id'   => (string) $product->get_id(),
		'item_name' => $product->get_name(),
		'price'     => (float) $product->get_price(),
		'quantity'  => (int) $qty,
	);
}

// Просмотр прибора.
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}
	$product = wc_get_product( get_queried_object_id() );
	if ( ! $product ) {
		return;
	}
	ivs_ga_push( array(
		'event'     => 'view_item',
		'ecommerce' => array(
			'currency' => get_woocommerce_currency(),
			'value'    => (float) $product->get_price(),
			'items'    => array( ivs_ga_item( $product ) ),
		),
	) );
} );

// Начало оформления.
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() || ! WC()->cart ) {
		return;
	}
	$items = array();
	foreach ( WC()->cart->get_cart() as $row ) {
		$items[] = ivs_ga_item( $row['data'], $row['quantity'] );
	}
	ivs_ga_push( array(
		'event'     => 'begin_checkout',
		'ecommerce' => array(
			'currency' => get_woocommerce_currency(),
			'value'    => (float) WC()->cart->get_total( 'edit' ),
			'items'    => $items,
		),
	) );
} );

// Оплата (страница «Спасибо»): один раз на заказ, залог в выручку не входит.
add_action( 'woocommerce_thankyou', function ( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || $order->get_meta( '_ivs_ga_purchase' ) ) {
		return;
	}
	if ( ! $order->has_status( array( 'processing', 'completed', 'on-hold' ) ) ) {
		return;
	}
	$items   = array();
	$deposit = 0.0;
	foreach ( $order->get_items() as $it ) {
		$p = $it->get_product();
		if ( $p ) {
			$items[] = ivs_ga_item( $p, $it->get_quantity() );
		}
	}
	foreach ( $order->get_items( 'fee' ) as $fee ) {
		$deposit += (float) $fee->get_total();
	}
	ivs_ga_push( array(
		'event'     => 'purchase',
		'ecommerce' => array(
			'transaction_id' => (string) $order->get_order_number(),
			'currency'       => $order->get_currency(),
			'value'          => (float) $order->get_total() - $deposit,
			'tax'            => (float) $order->get_total_tax(),
			'items'          => $items,
		),
	) );
	$order->update_meta_data( '_ivs_ga_purchase', 1 );
	$order->save();
} );

// Бронь (кнопка в форме прибора), звонок и WhatsApp: клики по ссылкам с указанием места.
add_action( 'wp_footer', function () {
	?>
<script>
(function(){
  var dl=window.dataLayer=window.dataLayer||[];
  function where(a){var s=a.closest('footer,header,nav,form,section,main');return s?(s.id||s.className||s.tagName).toString().split(' ')[0].toLowerCase():'page';}
  document.addEventListener('click',function(e){
    var a=e.target.closest&&e.target.closest('a[href]');
    if(!a)return;
    var h=a.getAttribute('href')||'';
    var ev=h.indexOf('tel:')===0?'click_call':(h.indexOf('wa.me')>-1||h.indexOf('whatsapp.com')>-1)?'click_whatsapp':'';
    if(ev)dl.push({event:ev,link_location:where(a),page_path:location.pathname,page_lang:document.documentElement.lang||''});
  },true);
  var f=document.querySelector('form.ivs-book');
  if(f){f.addEventListener('submit',function(){
    var c={};try{c=JSON.parse(f.getAttribute('data-ivs'))||{};}catch(x){}
    var t=document.querySelector('h1');
    dl.push({event:'add_to_cart',ecommerce:{currency:'EUR',value:c.day||0,items:[{item_id:String(c.id||''),item_name:t?t.textContent.trim():'',price:c.day||0,quantity:1}]}});
  });}
})();
</script>
	<?php
}, 99 );
