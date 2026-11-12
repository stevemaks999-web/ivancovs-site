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
	// Товары не переводятся плагином, язык страницы прибора берём из параметра ?ivl=ru|en.
	if ( isset( $_GET['ivl'] ) && in_array( $_GET['ivl'], array( 'ru', 'en' ), true ) && function_exists( 'is_woocommerce' ) && is_woocommerce() ) { // phpcs:ignore WordPress.Security.NonceVerification
		return $_GET['ivl']; // phpcs:ignore
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
		printf( '<a href="%s">%s</a>', ivs_url( $i[0] ), esc_html( $i[1] ) );
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

/** Добавляет к адресу параметр языка (для lv убирает). Только для страниц каталога и приборов. */
function ivs_lang_url( $url, $lang = null ) {
	$lang = $lang ? $lang : ivs_lang();
	$url  = remove_query_arg( 'ivl', $url );
	return 'lv' === $lang ? $url : add_query_arg( 'ivl', $lang, $url );
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
				$out[] = $l['current_lang'] ? '<b>' . esc_html( $name ) . '</b>' : '<a href="' . esc_url( $l['url'] ) . '">' . esc_html( $name ) . '</a>';
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
