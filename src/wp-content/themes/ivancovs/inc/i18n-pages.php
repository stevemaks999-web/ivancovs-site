<?php
/**
 * Переводы страниц «Полезная информация», «Обучение» (и 8 тем обучения) и «Блог» на RU и EN.
 * Страницы создаются один раз и связываются с латышскими оригиналами через Polylang:
 * Polylang сам выводит hreflang и показывает переключатель языка на соответствующую страницу.
 * Тексты лежат в i18n/pages/<латышский-адрес>.<язык>.html.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Латышский адрес => [ родитель, [RU: заголовок, адрес], [EN: заголовок, адрес], порядок ]. */
function ivs_i18n_pages_map() {
	return array(
		'noderigi'                         => array( '', array( 'Полезная информация', 'poleznaya-informaciya' ), array( 'Useful information', 'useful-information' ), 0 ),
		'apmacibas'                        => array( '', array( 'Обучение', 'obuchenie' ), array( 'Training', 'training' ), 0 ),
		'apmacibas/juridiskie-aspekti'     => array( 'apmacibas', array( 'Юридические аспекты', 'yuridicheskie-aspekty' ), array( 'Legal aspects', 'legal-aspects' ), 1 ),
		'apmacibas/komforts'               => array( 'apmacibas', array( 'Тепловой комфорт', 'teplovoj-komfort' ), array( 'Thermal comfort', 'thermal-comfort' ), 2 ),
		'apmacibas/ventilacijas-sistemas'  => array( 'apmacibas', array( 'Вентиляционные системы', 'ventilyacionnye-sistemy' ), array( 'Ventilation systems', 'ventilation-systems' ), 3 ),
		'apmacibas/ventilacijas-iekartas'  => array( 'apmacibas', array( 'Вентиляционное оборудование', 'ventilyacionnoe-oborudovanie' ), array( 'Ventilation equipment', 'ventilation-equipment' ), 4 ),
		'apmacibas/gaisa_attirisana'       => array( 'apmacibas', array( 'Очистка воздуха', 'ochistka-vozduha' ), array( 'Air purification', 'air-purification' ), 7 ),
		'apmacibas/ventilacijas_parbaude'  => array( 'apmacibas', array( 'Проверка вентиляции', 'proverka-ventilyacii' ), array( 'Ventilation testing', 'ventilation-testing' ), 6 ),
		'apmacibas/apkure'                 => array( 'apmacibas', array( 'Отопление', 'otoplenie' ), array( 'Heating', 'heating' ), 6 ),
		'apmacibas/gaisa_kondicionesana'   => array( 'apmacibas', array( 'Кондиционирование воздуха', 'kondicionirovanie-vozduha' ), array( 'Air conditioning', 'air-conditioning' ), 6 ),
		'blog-2'                           => array( '', array( 'Блог', 'blog' ), array( 'Blog', 'the-blog' ), 0 ),
	);
}

/** Адрес страницы на языке посетителя (латышский адрес, если перевода нет). */
function ivs_page_url( $lv_path ) {
	$lv_path = trim( $lv_path, '/' );
	$lang    = ivs_lang();
	$page    = get_page_by_path( $lv_path );
	if ( $page && 'lv' !== $lang && function_exists( 'pll_get_post' ) ) {
		$tr = pll_get_post( $page->ID, $lang );
		if ( $tr ) {
			return get_permalink( $tr );
		}
	}
	return home_url( '/' . $lv_path . '/' );
}

/** Ссылки на латышские страницы внутри текста заменяем ссылками на переводы. */
function ivs_i18n_rewrite_links( $content, $lang ) {
	return preg_replace_callback(
		'#https?://ivancovs\.com/((?:apmacibas/[A-Za-z0-9_-]+)|apmacibas|noderigi|blog-2)/#',
		function ( $m ) use ( $lang ) {
			$page = get_page_by_path( $m[1] );
			if ( $page && function_exists( 'pll_get_post' ) ) {
				$tr = pll_get_post( $page->ID, $lang );
				if ( $tr ) {
					return get_permalink( $tr );
				}
			}
			return $m[0];
		},
		$content
	);
}

add_action( 'init', function () {
	if ( get_option( 'ivs_i18n_pages_v1' ) || ! function_exists( 'pll_set_post_language' ) || ! function_exists( 'pll_save_post_translations' ) || wp_doing_ajax() ) {
		return;
	}
	$dir     = get_theme_file_path( 'i18n/pages/' );
	$created = array();
	$map     = ivs_i18n_pages_map();
	foreach ( $map as $lv_path => $def ) {
		$lv = get_page_by_path( $lv_path );
		if ( ! $lv ) {
			continue;
		}
		$tr = function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $lv->ID ) : array();
		$tr['lv'] = $lv->ID;
		foreach ( array( 'ru' => 1, 'en' => 2 ) as $lang => $i ) {
			if ( ! empty( $tr[ $lang ] ) ) {
				continue;
			}
			$file = $dir . basename( $lv_path ) . '.' . $lang . '.html';
			$body = file_exists( $file ) ? file_get_contents( $file ) : '';
			if ( '' === $body && 'blog-2' !== $lv_path ) {
				continue;
			}
			$parent = 0;
			if ( $def[0] ) {
				$pp = get_page_by_path( $def[0] );
				$pt = $pp && function_exists( 'pll_get_post' ) ? pll_get_post( $pp->ID, $lang ) : 0;
				$parent = $pt ? (int) $pt : 0;
			}
			$id = wp_insert_post( array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => $def[ $i ][0],
				'post_name'    => $def[ $i ][1],
				'post_content' => wp_slash( $body ),
				'post_parent'  => $parent,
				'menu_order'   => $def[3],
			) );
			if ( $id && ! is_wp_error( $id ) ) {
				pll_set_post_language( $id, $lang );
				$tpl = get_post_meta( $lv->ID, '_wp_page_template', true );
				if ( $tpl ) {
					update_post_meta( $id, '_wp_page_template', $tpl );
				}
				$tr[ $lang ] = $id;
				$created[ $id ] = $lang;
			}
		}
		pll_save_post_translations( $tr );
	}
	update_option( 'ivs_i18n_pages_v1', 1 );
}, 40 );

/** Ссылки на латышские страницы в тексте показываем ссылками на переводы (язык берём на момент показа, чтобы был префикс /ru/ или /en/). */
add_filter( 'the_content', function ( $content ) {
	$lang = function_exists( 'ivs_lang' ) ? ivs_lang() : 'lv';
	if ( 'lv' === $lang || ! is_page() || false === strpos( $content, 'ivancovs.com/' ) ) {
		return $content;
	}
	return ivs_i18n_rewrite_links( $content, $lang );
}, 9 );

/** Исправление: возвращаем тексты переводов из файлов (без подмены ссылок на момент создания) и задаём адреса. */
add_action( 'init', function () {
	if ( get_option( 'ivs_i18n_pages_v2' ) || ! get_option( 'ivs_i18n_pages_v1' ) || ! function_exists( 'pll_get_post' ) || wp_doing_ajax() ) {
		return;
	}
	$dir = get_theme_file_path( 'i18n/pages/' );
	foreach ( ivs_i18n_pages_map() as $lv_path => $def ) {
		$lv = get_page_by_path( $lv_path );
		if ( ! $lv ) {
			continue;
		}
		foreach ( array( 'ru' => 1, 'en' => 2 ) as $lang => $i ) {
			$id = pll_get_post( $lv->ID, $lang );
			if ( ! $id ) {
				continue;
			}
			$args = array( 'ID' => $id, 'post_name' => $def[ $i ][1] );
			$file = $dir . basename( $lv_path ) . '.' . $lang . '.html';
			if ( file_exists( $file ) ) {
				$args['post_content'] = wp_slash( file_get_contents( $file ) );
			}
			wp_update_post( $args );
		}
	}
	update_option( 'ivs_i18n_pages_v2', 1 );
}, 50 );

/** Короткий адрес блога: слаг «blog» занимала старая картинка в медиатеке, освобождаем его и ставим переводам. */
add_action( 'init', function () {
	global $wpdb;
	if ( get_option( 'ivs_i18n_pages_v4' ) || ! get_option( 'ivs_i18n_pages_v2' ) || ! function_exists( 'pll_get_post' ) || wp_doing_ajax() ) {
		return;
	}
	$att = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_type = 'attachment'", 'blog' ) );
	foreach ( $att as $aid ) {
		wp_update_post( array( 'ID' => (int) $aid, 'post_name' => 'blog-image' ) );
	}
	$lv = get_page_by_path( 'blog-2' );
	if ( $lv ) {
		foreach ( array( 'ru', 'en' ) as $lang ) {
			$id = pll_get_post( $lv->ID, $lang );
			if ( $id ) {
				wp_update_post( array( 'ID' => $id, 'post_name' => 'ru' === $lang ? 'blog' : 'the-blog' ) );
			}
		}
	}
	update_option( 'ivs_i18n_pages_v4', 1 );
}, 60 );
