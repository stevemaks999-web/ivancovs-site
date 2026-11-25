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
		'apmacibas/gaisa-attirisana'       => array( 'apmacibas', array( 'Очистка воздуха', 'ochistka-vozduha' ), array( 'Air purification', 'air-purification' ), 7 ),
		'apmacibas/ventilacijas-parbaude'  => array( 'apmacibas', array( 'Проверка вентиляции', 'proverka-ventilyacii' ), array( 'Ventilation testing', 'ventilation-testing' ), 6 ),
		'apmacibas/apkure'                 => array( 'apmacibas', array( 'Отопление', 'otoplenie' ), array( 'Heating', 'heating' ), 6 ),
		'apmacibas/gaisa-kondicionesana'   => array( 'apmacibas', array( 'Кондиционирование воздуха', 'kondicionirovanie-vozduha' ), array( 'Air conditioning', 'air-conditioning' ), 6 ),
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
	// Старые адреса с подчёркиванием заменены на адреса с дефисом.
	$content = str_replace(
		array( 'apmacibas/gaisa_attirisana/', 'apmacibas/gaisa_kondicionesana/', 'apmacibas/ventilacijas_parbaude/' ),
		array( 'apmacibas/gaisa-attirisana/', 'apmacibas/gaisa-kondicionesana/', 'apmacibas/ventilacijas-parbaude/' ),
		$content
	);
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

/**
 * Главные /ru/ и /en/ в прежнем дизайне: переводы латышской главной (страница, которую WordPress показывает как главную).
 * Создаём один раз, как у остальных переводов. Тексты в i18n/pages/home.<язык>.html (их собирает scripts/build_home_i18n.py).
 * Пока флаг ivs_home_i18n_v1 не стоит, главные RU и EN остаются в новой теме (см. ivancovs-theme-router.php).
 */
add_action( 'init', function () {
	if ( get_option( 'ivs_home_i18n_v1' ) || ! function_exists( 'pll_set_post_language' ) || ! function_exists( 'pll_save_post_translations' ) || wp_doing_ajax() ) {
		return;
	}
	$front = (int) get_option( 'page_on_front' );
	if ( ! $front || 'page' !== get_option( 'show_on_front' ) ) {
		return;
	}
	$dir = get_theme_file_path( 'i18n/pages/' );
	$tr  = function_exists( 'pll_get_post_translations' ) ? pll_get_post_translations( $front ) : array();
	$tr['lv'] = $front;
	$def = array(
		'ru' => array( 'Главная', 'glavnaya' ),
		'en' => array( 'Home', 'home-en' ),
	);
	foreach ( $def as $lang => $d ) {
		if ( ! empty( $tr[ $lang ] ) ) {
			continue;
		}
		$file = $dir . 'home.' . $lang . '.html';
		if ( ! file_exists( $file ) ) {
			return; // без текстов ничего не создаём и флаг не ставим
		}
		$id = wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $d[0],
			'post_name'    => $d[1],
			'post_content' => wp_slash( file_get_contents( $file ) ),
		) );
		if ( ! $id || is_wp_error( $id ) ) {
			return;
		}
		pll_set_post_language( $id, $lang );
		// Оформление страницы (шаблон, стили конструктора) берём с латышской главной; SEO-поля и служебные метки не копируем.
		foreach ( get_post_meta( $front ) as $key => $vals ) {
			if ( preg_match( '/^(_edit_|_pll|_yoast_wpseo_|_wp_old_slug|_wp_trash)/', $key ) ) {
				continue;
			}
			foreach ( $vals as $v ) {
				add_post_meta( $id, $key, maybe_unserialize( $v ) );
			}
		}
		$tr[ $lang ] = $id;
	}
	pll_save_post_translations( $tr );
	update_option( 'ivs_home_i18n_v1', 1 );
}, 70 );

/** Polylang помнит главную страницу каждого языка в кеше языков: после создания переводов главной сбрасываем его один раз. */
add_action( 'init', function () {
	if ( get_option( 'ivs_home_i18n_v2' ) || ! get_option( 'ivs_home_i18n_v1' ) || ! function_exists( 'PLL' ) || wp_doing_ajax() ) {
		return;
	}
	$pll = PLL();
	if ( $pll && isset( $pll->model ) && method_exists( $pll->model, 'clean_languages_cache' ) ) {
		$pll->model->clean_languages_cache();
	}
	delete_transient( 'pll_languages_list' );
	update_option( 'ivs_home_i18n_v2', 1 );
}, 80 );
