<?php
/**
 * Тема Ivancovs: функции.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const IVS_THEME_VERSION = '0.1.0';
const IVS_PHONE         = '+371 25553244';
const IVS_PHONE_WA      = '37125553244';
const IVS_EMAIL         = 'dmitrijs@ivancovs.com';

/** Текущий язык: lv, ru или en (Polylang, иначе по локали). */
function ivs_lang() {
	// Запросы Store API (корзина, оформление) идут без языка страницы: подписи делаем латышскими.
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST && ! isset( $_GET['ivl'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return 'lv';
	}
	// Товары не переводятся плагином, язык страницы прибора берём из параметра ?ivl=ru|en.
	// Адрес /ru/arenda/..., /en/product/...: язык уже определён при разборе адреса (mu-plugin), is_woocommerce() не нужен.
	if ( ! empty( $GLOBALS['ivs_pretty_lang'] ) && in_array( $GLOBALS['ivs_pretty_lang'], array( 'ru', 'en' ), true ) ) {
		return $GLOBALS['ivs_pretty_lang'];
	}
	// Защита от повторного входа: is_woocommerce() запрашивает категорию, а фильтр названий категорий снова спрашивает язык.
	static $busy = false;
	if ( ! $busy && isset( $_GET['ivl'] ) && in_array( $_GET['ivl'], array( 'ru', 'en' ), true ) && function_exists( 'is_woocommerce' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		$busy = true;
		$shop = is_woocommerce();
		$busy = false;
		if ( $shop ) {
			return $_GET['ivl']; // phpcs:ignore
		}
	}
	if ( function_exists( 'pll_current_language' ) ) {
		$l = pll_current_language();
		if ( $l ) {
			return in_array( $l, array( 'lv', 'ru', 'en' ), true ) ? $l : 'lv';
		}
	}
	$loc = substr( get_locale(), 0, 2 );
	return in_array( $loc, array( 'ru', 'en' ), true ) ? $loc : 'lv';
}

/** Текст на трёх языках. Если английского нет, берём русский. */
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

/** Ссылка на страницу: адрес зависит от языка, если страница есть. */
function ivs_url( $path ) {
	return esc_url( home_url( $path ) );
}

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'woocommerce' );
	register_nav_menus( array( 'primary' => 'Главное меню' ) );
} );

add_action( 'wp_enqueue_scripts', function () {
	$dir = get_template_directory_uri();
	// Версия по времени файла: после выкладки браузер и Cloudflare берут свежие стили.
	$css_ver = (string) filemtime( get_theme_file_path( 'style.css' ) );
	$js_ver  = (string) filemtime( get_theme_file_path( 'assets/theme.js' ) );
	wp_enqueue_style( 'ivancovs', get_stylesheet_uri(), array(), $css_ver );
	wp_enqueue_script( 'ivancovs', $dir . '/assets/theme.js', array(), $js_ver, array( 'in_footer' => true, 'strategy' => 'defer' ) );
} );

// Тема без мигания: выставляем до отрисовки.
add_action( 'wp_head', function () {
	?>
<meta name="viewport" content="width=device-width, initial-scale=1">
<script>try{var m=localStorage.getItem('ivt')||(matchMedia('(prefers-color-scheme:dark)').matches?'dark':'light');document.documentElement.setAttribute('data-theme',m)}catch(e){}</script>
	<?php
}, 1 );

add_filter( 'body_class', function ( $c ) {
	if ( function_exists( 'is_product' ) && is_product() ) {
		$c[] = 'pp';
	}
	return $c;
} );

// Главное меню: если не задано в админке, берём разделы, которые уже есть на сайте.
function ivs_primary_nav() {
	if ( has_nav_menu( 'primary' ) ) {
		wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'items_wrap' => '%3$s', 'walker' => new IVS_Flat_Walker() ) );
		return;
	}
	$items = array(
		array( '/noma/', ivs_t( 'Mērinstrumentu noma', 'Аренда приборов', 'Instrument rental' ) ),
		array( '/noderigi/', ivs_t( 'Noderīga informācija', 'Полезная информация', 'Useful information' ) ),
		array( '/apmacibas/', ivs_t( 'Apmācības', 'Обучение', 'Training' ) ),
		array( '/blog-2/', ivs_t( 'Blogs', 'Блог', 'Blog' ) ),
	);
	foreach ( $items as $i ) {
		$href = ivs_url( $i[0] );
		if ( '/noma/' === $i[0] ) {
			$href = esc_url( ivs_lang_url( $href ) );
		} elseif ( function_exists( 'ivs_page_url' ) ) {
			$href = esc_url( ivs_page_url( $i[0] ) );
		}
		printf( '<a href="%s">%s</a>', $href, esc_html( $i[1] ) );
	}
}

/** Меню без списков: только ссылки, как в макете. */
class IVS_Flat_Walker extends Walker_Nav_Menu {
	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		if ( 0 === $depth ) {
			$output .= sprintf( '<a href="%s">%s</a>', esc_url( $item->url ), esc_html( $item->title ) );
		}
	}
	public function end_el( &$output, $item, $depth = 0, $args = null ) {}
	public function start_lvl( &$output, $depth = 0, $args = null ) {}
	public function end_lvl( &$output, $depth = 0, $args = null ) {}
}

/** Языковые названия частей адреса аренды (как в mu-plugin ivancovs-theme-router): латышское => RU и EN. */
function ivs_lang_slugs() {
	return array(
		'noma'             => array( 'ru' => 'arenda', 'en' => 'rent' ),
		'product'          => array( 'ru' => 'pribor', 'en' => 'instrument' ),
		'product-category' => array( 'ru' => 'kategoriya', 'en' => 'category' ),
		'product-tag'      => array( 'ru' => 'metka', 'en' => 'tag' ),
	);
}

/**
 * Адрес страницы каталога на нужном языке: /ru/arenda/, /ru/pribor/..., /en/rent/, /en/instrument/... (для lv без префикса).
 * Старый параметр ?ivl= убирается. Любую языковую форму сначала приводим к латышской, потом строим нужную.
 */
function ivs_lang_url( $url, $lang = null ) {
	$lang = $lang ? $lang : ivs_lang();
	$url  = remove_query_arg( 'ivl', $url );
	if ( ! preg_match( '~^(https?://[^/]+)/(?:(ru|en)/)?([^/?#]+)~', $url, $m ) ) {
		return $url;
	}
	$base = '';
	foreach ( ivs_lang_slugs() as $lv => $tr ) {
		if ( '' === $m[2] && $m[3] === $lv ) {
			$base = $lv;
		} elseif ( '' !== $m[2] && ( $m[3] === $tr[ $m[2] ] || $m[3] === $lv ) ) {
			$base = $lv;
		}
	}
	if ( '' === $base ) {
		return $url;
	}
	$rest = substr( $url, strlen( $m[0] ) );
	$slug = ( 'lv' === $lang ) ? $base : $lang . '/' . ivs_lang_slugs()[ $base ][ $lang ];
	return $m[1] . '/' . $slug . $rest;
}

/** Переключатель языков: Polylang, иначе просто подпись. */
function ivs_lang_switch() {
	if ( function_exists( 'pll_the_languages' ) ) {
		$langs = pll_the_languages( array( 'raw' => 1 ) );
		if ( $langs ) {
			$out = array();
			$is_shop = function_exists( 'is_woocommerce' ) && is_woocommerce();
			$cur     = ivs_lang();
			foreach ( $langs as $l ) {
				$name = strtoupper( $l['slug'] );
				if ( $is_shop ) {
					$on  = $l['slug'] === $cur;
					$url = ivs_lang_url( home_url( add_query_arg( null, null ) ), $l['slug'] );
					$out[] = $on ? '<b>' . esc_html( $name ) . '</b>' : '<a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
					continue;
				}
				$out[] = $l['current_lang'] ? '<b>' . esc_html( $name ) . '</b>' : '<a href="' . esc_url( ivs_seo_fix_host( $l['url'] ) ) . '">' . esc_html( $name ) . '</a>';
			}
			echo '<span class="lang">' . implode( ' · ', $out ) . '</span>'; // phpcs:ignore
			return;
		}
	}
	echo '<span class="lang">LV · RU · EN</span>';
}

function ivs_wa_link( $text = '' ) {
	$url = 'https://wa.me/' . IVS_PHONE_WA;
	if ( $text ) {
		$url .= '?text=' . rawurlencode( $text );
	}
	return esc_url( $url );
}

/** Категории каталога: «Комплекты» (родитель с адресом komplekti) и обычные категории. */
function ivs_kits_term() {
	$t = get_term_by( 'slug', 'komplekti', 'product_cat' );
	return $t ? $t : null;
}

require_once get_template_directory() . '/inc/content.php';
require_once get_template_directory() . '/inc/shortcodes.php';
require_once get_template_directory() . '/inc/woocommerce.php';
require_once get_template_directory() . '/inc/booking.php';
require_once get_template_directory() . '/inc/booking-docs.php';
require_once get_template_directory() . '/inc/terms.php';
require_once get_template_directory() . '/inc/checkout.php';
require_once get_template_directory() . '/inc/telegram.php';
require_once get_template_directory() . '/inc/analytics.php';
require_once get_template_directory() . '/inc/kit-titles.php';
require_once get_template_directory() . '/inc/cross-sell.php';
require_once get_template_directory() . '/inc/i18n-pages.php';
require_once get_template_directory() . '/inc/cart-clean.php';
require_once get_template_directory() . '/inc/click-log.php';
require_once get_template_directory() . '/inc/seo.php';
require_once get_template_directory() . '/inc/perf.php';
require_once get_template_directory() . '/inc/product-seo.php';
require_once get_template_directory() . '/inc/kit-photos.php';

/**
 * Главная на RU/EN: у страницы «Sākums» нет перевода в Polylang, и /ru/ и /en/ показывали
 * пустую ленту записей («Ничего не найдено»). Пока перевода нет, отдаём шаблон главной.
 */
add_filter( 'template_include', function ( $template ) {
	if ( is_home() && ! is_paged() && ! is_search() && ! is_admin() ) {
		$front = get_theme_file_path( 'front-page.php' );
		if ( file_exists( $front ) ) {
			return $front;
		}
	}
	return $template;
}, 20 );

/** Атрибут lang страниц каталога и приборов: язык берётся из ?ivl=, а не из Polylang (там он латышский). */
add_filter( 'language_attributes', function ( $out ) {
	$lang = ivs_lang();
	if ( 'lv' === $lang || ! function_exists( 'is_woocommerce' ) || ! is_woocommerce() ) {
		return $out;
	}
	$code = 'ru' === $lang ? 'ru-RU' : 'en-GB';
	return preg_replace( '/lang="[^"]*"/', 'lang="' . $code . '"', $out );
} );
