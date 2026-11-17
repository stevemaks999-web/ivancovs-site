<?php
/**
 * Скорость: не грузим скрипты и стили старых плагинов, которыми новая тема не пользуется.
 * Плагины остаются установленными. Чтобы вернуть всё как было, добавьте в wp-config.php: define( 'IVS_KEEP_LEGACY_ASSETS', true );
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Папки плагинов, чьи ресурсы убираем, и признак в тексте страницы, при котором их всё-таки оставляем. */
function ivs_legacy_asset_rules() {
	return array(
		'revslider'                 => '',
		'cubeportfolio'             => '',
		'meks-easy-instagram-widget' => '',
		'mwb-bookings-for-woocommerce' => '[mwb',
		'contact-form-7'            => '[contact-form-7',
		'js_composer'               => '[vc_',
		'Ultimate_VC_Addons'        => '[ult_|[ultimate_|[bsf-|[stat_counter|[icon_timeline',
	);
}

function ivs_strip_legacy_assets() {
	if ( is_admin() || ( defined( 'IVS_KEEP_LEGACY_ASSETS' ) && IVS_KEEP_LEGACY_ASSETS ) || is_customize_preview() ) {
		return;
	}
	$content = is_singular() ? (string) get_post_field( 'post_content', get_queried_object_id() ) : '';
	// Главная собирается шаблоном темы и текст записи не выводит.
	if ( is_front_page() ) {
		$content = '';
	}
	$rules = ivs_legacy_asset_rules();
	foreach ( array( 'scripts' => 'wp_scripts', 'styles' => 'wp_styles' ) as $kind => $getter ) {
		$reg = $getter();
		foreach ( (array) $reg->registered as $handle => $obj ) {
			$src = is_string( $obj->src ) ? $obj->src : '';
			foreach ( $rules as $folder => $keep_if ) {
				if ( false === strpos( $src, '/plugins/' . $folder . '/' ) ) {
					continue;
				}
				$keep = false;
				foreach ( array_filter( explode( '|', $keep_if ) ) as $needle ) {
					if ( false !== strpos( $content, $needle ) ) {
						$keep = true;
					}
				}
				if ( ! $keep ) {
					'scripts' === $kind ? wp_deregister_script( $handle ) : wp_deregister_style( $handle );
				}
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'ivs_strip_legacy_assets', PHP_INT_MAX );
add_action( 'wp_print_footer_scripts', 'ivs_strip_legacy_assets', 0 );
