<?php
/**
 * Plugin Name: Ivancovs: две темы на одном сайте
 * Description: Аренда приборов (каталог, приборы, корзина, оформление, договор) показывается в новой теме Ivancovs. Главная, обучение, полезная информация, блог и остальные страницы остаются в прежнем дизайне (тема Hazel). Чтобы отключить и вернуть везде новую тему, добавьте в wp-config.php: define( 'IVS_FORCE_NEW_THEME', true );
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const IVS_ROUTER_NEW_THEME = 'ivancovs';
const IVS_ROUTER_OLD_CHILD = 'hazel-child';
const IVS_ROUTER_OLD_PARENT = 'hazel';

/** Первые части адреса, которые относятся к аренде (на LV, а также после /ru/ и /en/). */
function ivs_router_rental_roots() {
	return array(
		'noma', 'product', 'product-category', 'product-tag',
		'grozs', 'pirkuma-veiksana', 'cart', 'checkout', 'my-account',
		'distances-ligums', 'dogovor-distantsionnoj-prodazhi', 'distance-contract',
	);
}

/**
 * Эту страницу показываем в прежнем дизайне?
 * Только обычный показ страницы посетителю: админка, запросы JS (REST, AJAX), планировщик, формы,
 * корзина с параметрами, документы и всё с лишними параметрами остаются в новой теме, там лежит вся логика аренды.
 */
function ivs_router_is_legacy() {
	static $res = null;
	if ( null !== $res ) {
		return $res;
	}
	$res = false;
	if ( defined( 'IVS_FORCE_NEW_THEME' ) && IVS_FORCE_NEW_THEME ) {
		return $res;
	}
	if ( defined( 'WP_ADMIN' ) || defined( 'DOING_AJAX' ) || defined( 'DOING_CRON' ) || defined( 'XMLRPC_REQUEST' ) || defined( 'WP_CLI' ) || defined( 'WP_INSTALLING' ) ) {
		return $res;
	}
	$script = isset( $_SERVER['SCRIPT_NAME'] ) ? basename( (string) $_SERVER['SCRIPT_NAME'] ) : ''; // phpcs:ignore
	if ( 'index.php' !== $script ) {
		return $res;
	}
	$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET'; // phpcs:ignore
	if ( ! in_array( $method, array( 'GET', 'HEAD' ), true ) ) {
		return $res;
	}
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '/'; // phpcs:ignore
	$qs  = (string) wp_parse_url( $uri, PHP_URL_QUERY );
	if ( '' !== $qs ) {
		parse_str( $qs, $args );
		foreach ( array_keys( $args ) as $k ) {
			if ( ! preg_match( '/^(utm_[a-z_]+|gclid|gbraid|wbraid|fbclid|msclkid|gad_[a-z_]+|_ga|_gl)$/i', (string) $k ) ) {
				return $res;
			}
		}
	}
	$path = trim( rawurldecode( (string) wp_parse_url( $uri, PHP_URL_PATH ) ), '/' );
	if ( 0 === strpos( $path, 'wp-json' ) || 0 === strpos( $path, 'wp-' ) ) {
		return $res;
	}
	$parts = '' === $path ? array() : explode( '/', $path );
	$lang  = '';
	if ( $parts && in_array( $parts[0], array( 'ru', 'en' ), true ) ) {
		$lang = array_shift( $parts );
	}
	// Главные /ru/ и /en/: у них не было старого дизайна, остаются в новой теме.
	if ( ! $parts && '' !== $lang ) {
		return $res;
	}
	if ( $parts && in_array( strtolower( $parts[0] ), ivs_router_rental_roots(), true ) ) {
		return $res;
	}
	$res = true;
	return $res;
}

if ( ivs_router_is_legacy() ) {
	$ivs_router_child  = static function () {
		return IVS_ROUTER_OLD_CHILD;
	};
	$ivs_router_parent = static function () {
		return IVS_ROUTER_OLD_PARENT;
	};
	add_filter( 'pre_option_stylesheet', $ivs_router_child );
	add_filter( 'pre_option_template', $ivs_router_parent );

	// Из новой темы нужны только общие части, не зависящие от оформления: SEO, переводы страниц, учёт кликов.
	if ( ! function_exists( 'ivs_lang' ) ) {
		function ivs_lang() {
			if ( function_exists( 'pll_current_language' ) ) {
				$l = pll_current_language();
				if ( in_array( $l, array( 'lv', 'ru', 'en' ), true ) ) {
					return $l;
				}
			}
			$loc = substr( get_locale(), 0, 2 );
			return in_array( $loc, array( 'ru', 'en' ), true ) ? $loc : 'lv';
		}
		function ivs_t( $lv, $ru, $en = null ) {
			$l = ivs_lang();
			if ( 'ru' === $l ) {
				return $ru;
			}
			if ( 'en' === $l ) {
				return null === $en ? $ru : $en;
			}
			return $lv;
		}
		function ivs_e( $lv, $ru, $en = null ) {
			echo esc_html( ivs_t( $lv, $ru, $en ) );
		}
	}
	$ivs_router_dir = WP_CONTENT_DIR . '/themes/' . IVS_ROUTER_NEW_THEME . '/inc/';
	foreach ( array( 'seo.php', 'i18n-pages.php', 'click-log.php' ) as $ivs_router_file ) {
		if ( file_exists( $ivs_router_dir . $ivs_router_file ) ) {
			require_once $ivs_router_dir . $ivs_router_file;
		}
	}
}
