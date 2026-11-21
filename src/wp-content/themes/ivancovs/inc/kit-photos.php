<?php
/**
 * Фото комплектов: на картинке все приборы комплекта (assets/kit-photos/KIT-XX.jpg).
 * Один раз загружает их в медиатеку и ставит главным фото товара с артикулом KIT-XX.
 * Повторить загрузку: удалить опцию ivs_kit_photos_v1.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	if ( wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! function_exists( 'wc_get_product_id_by_sku' ) ) {
		return;
	}
	if ( get_option( 'ivs_kit_photos_v1' ) || get_transient( 'ivs_kit_photos_lock' ) ) {
		return;
	}
	set_transient( 'ivs_kit_photos_lock', 1, 300 );
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$done = 0;
	$all  = true;
	for ( $i = 1; $i <= 20; $i++ ) {
		$sku  = sprintf( 'KIT-%02d', $i );
		$file = get_theme_file_path( 'assets/kit-photos/' . $sku . '.jpg' );
		$pid  = wc_get_product_id_by_sku( $sku );
		if ( ! $pid || ! file_exists( $file ) ) {
			$all = false;
			continue;
		}
		$tmp = wp_tempnam( $sku . '.jpg' );
		copy( $file, $tmp );
		$att = media_handle_sideload( array( 'name' => 'komplekts-' . strtolower( $sku ) . '.jpg', 'tmp_name' => $tmp ), $pid, get_the_title( $pid ) );
		if ( is_wp_error( $att ) ) {
			@unlink( $tmp ); // phpcs:ignore
			$all = false;
			continue;
		}
		set_post_thumbnail( $pid, $att );
		++$done;
	}
	if ( $all ) {
		update_option( 'ivs_kit_photos_v1', gmdate( 'c' ), false );
	}
	delete_transient( 'ivs_kit_photos_lock' );
} );
