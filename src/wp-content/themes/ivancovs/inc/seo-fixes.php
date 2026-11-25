<?php
/**
 * SEO: правки по аудиту 25.11.2026.
 * Файл подключается из seo.php, поэтому работает и на страницах старой темы (главная, блог, обучение),
 * и на страницах аренды (новая тема).
 *
 * 1. Тестовый сайт закрыт от индексации.
 * 2. Страница бренда (давала 500) ведёт редиректом в каталог.
 * 3. Демо-записи темы, теги, автор и служебные записи брони: вне карты сайта и с noindex.
 * 4. Две русские страницы получают язык RU (адрес /ru/...).
 * 5. Главная: один H1; 7. alt у картинок; 9. og:image там, где Yoast его не вывел.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- 1. Тестовый сайт не для Google ---------- */
if ( function_exists( 'ivs_is_dev_install' ) && ivs_is_dev_install() ) {
	// Заголовок работает и для файлов, и для страниц. robots.txt не закрываем целиком: иначе Google не увидит noindex.
	add_action( 'send_headers', function () {
		header( 'X-Robots-Tag: noindex, nofollow', true );
	}, 1 );
	add_filter( 'wpseo_robots', function () {
		return 'noindex, nofollow';
	}, 999 );
	add_filter( 'wp_robots', function ( $r ) {
		unset( $r['index'], $r['follow'], $r['max-image-preview'] );
		$r['noindex']  = true;
		$r['nofollow'] = true;
		return $r;
	}, 999 );
	add_filter( 'robots_txt', function ( $out ) {
		return trim( preg_replace( '/^Sitemap:.*$/mi', '', (string) $out ) ) . "\n";
	}, 999 );
}

/* ---------- 2. Страница бренда: редирект в каталог ---------- */
add_action( 'parse_request', function ( $wp ) {
	if ( is_admin() || empty( $wp->query_vars['product_brand'] ) ) {
		return;
	}
	wp_safe_redirect( home_url( '/noma/' ), 301 );
	exit;
}, 1 );
add_action( 'template_redirect', function () {
	if ( taxonomy_exists( 'product_brand' ) && is_tax( 'product_brand' ) ) {
		wp_safe_redirect( home_url( '/noma/' ), 301 );
		exit;
	}
}, 2 );

/* ---------- 3. Лишнее вне карты сайта и вне поиска ---------- */
function ivs_seo_junk_post_types() {
	return array( 'portfolio', 'testimonials', 'mwb_booking_cost', 'mwb_booking_service' );
}
function ivs_seo_junk_taxonomies() {
	return array( 'post_tag', 'product_tag', 'product_brand', 'portfolio_category', 'portfolio_type', 'testimonials_category' );
}
add_filter( 'wpseo_sitemap_exclude_post_type', function ( $excluded, $type ) {
	return $excluded || in_array( $type, ivs_seo_junk_post_types(), true );
}, 20, 2 );
add_filter( 'wpseo_sitemap_exclude_taxonomy', function ( $excluded, $tax ) {
	return $excluded || in_array( $tax, ivs_seo_junk_taxonomies(), true );
}, 20, 2 );
add_filter( 'wpseo_sitemap_exclude_author', '__return_empty_array', 20 );

// Подстраховка: убираем лишние карты из общего списка, даже если Yoast их не исключил.
add_filter( 'wpseo_sitemap_index', function ( $xml ) {
	$slugs = array_merge( ivs_seo_junk_post_types(), ivs_seo_junk_taxonomies(), array( 'author' ) );
	foreach ( $slugs as $s ) {
		$xml = preg_replace( '#<sitemap>\s*<loc>[^<]*/' . preg_quote( $s, '#' ) . '-sitemap\d*\.xml</loc>.*?</sitemap>#s', '', (string) $xml );
	}
	return $xml;
}, 30 );

// noindex для этих страниц (ссылки на них оставляем, вес по ним ходит).
add_filter( 'wpseo_robots', function ( $robots ) {
	if ( is_admin() ) {
		return $robots;
	}
	$junk = ( is_singular( ivs_seo_junk_post_types() ) || is_post_type_archive( ivs_seo_junk_post_types() )
		|| is_tax( ivs_seo_junk_taxonomies() ) || is_tag() || is_author() );
	return $junk ? 'noindex, follow' : $robots;
}, 40 );

/* ---------- 4. Русские страницы на латышском корне получают язык RU ---------- */
function ivs_seo_ru_slugs() {
	return array( 'izmereniya-po-iso-7730', 'izmerenie-metodom-k-faktor' );
}

// После загрузки Polylang: язык ставим, флаг пишем только когда язык действительно стал RU.
add_action( 'wp_loaded', function () {
	if ( get_option( 'ivs_ru_pages_lang_v2' ) || ! function_exists( 'pll_set_post_language' ) || ! function_exists( 'pll_get_post_language' ) ) {
		return;
	}
	$ok = true;
	foreach ( ivs_seo_ru_slugs() as $slug ) {
		$page = get_page_by_path( $slug );
		if ( ! $page ) {
			continue;
		}
		if ( 'ru' !== pll_get_post_language( $page->ID ) ) {
			pll_set_post_language( $page->ID, 'ru' );
		}
		if ( 'ru' !== pll_get_post_language( $page->ID ) ) {
			$ok = false;
		}
	}
	if ( $ok ) {
		update_option( 'ivs_ru_pages_lang_v2', 1, false );
		flush_rewrite_rules( false );
	}
} );

// Старый адрес без /ru/ ведёт на новый, но только если страница уже на RU (иначе получится петля).
add_action( 'template_redirect', function () {
	$path = strtolower( trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ) ); // phpcs:ignore
	if ( ! in_array( $path, ivs_seo_ru_slugs(), true ) || ! function_exists( 'pll_get_post_language' ) ) {
		return;
	}
	$page = get_page_by_path( $path );
	if ( $page && 'ru' === pll_get_post_language( $page->ID ) ) {
		wp_safe_redirect( home_url( '/ru/' . $path . '/' ), 301 );
		exit;
	}
}, 1 );

/* ---------- 5, 7, 9. Правка готового HTML: H1 главной, alt у картинок, og:image ---------- */
function ivs_seo_default_og() {
	$f = get_template_directory() . '/assets/og-default.jpg';
	return file_exists( $f ) ? array( content_url( '/themes/ivancovs/assets/og-default.jpg' ), 1200, 630 ) : array( content_url( '/themes/ivancovs/assets/dmitrijs.jpg' ), 600, 600 );
}

/** Картинка для соцсетей у страницы без своей: фото категории, первый прибор или общая. */
function ivs_seo_og_for_request() {
	$img = '';
	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term = get_queried_object();
		$tid  = $term ? (int) get_term_meta( $term->term_id, 'thumbnail_id', true ) : 0;
		if ( $tid ) {
			$img = wp_get_attachment_image_url( $tid, 'large' );
		}
		if ( ! $img && $term ) {
			$q = get_posts( array(
				'post_type'      => 'product',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_thumbnail_id', // phpcs:ignore
				'tax_query'      => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $term->term_id ) ), // phpcs:ignore
				'no_found_rows'  => true,
			) );
			if ( $q ) {
				$img = get_the_post_thumbnail_url( $q[0], 'large' );
			}
		}
	}
	if ( $img ) {
		return array( $img, 0, 0 );
	}
	return ivs_seo_default_og();
}

/** Читаемое имя из названия файла: "hermetic-test-2.jpg" => "hermetic test". */
function ivs_seo_alt_from_file( $src ) {
	$name = pathinfo( (string) wp_parse_url( $src, PHP_URL_PATH ), PATHINFO_FILENAME );
	$name = preg_replace( '/-(\d+x\d+|scaled|e\d{6,})$/i', '', $name );
	$name = trim( preg_replace( '/[\s_\-]+\d*$/', '', preg_replace( '/[_\-\s]+/', ' ', $name ) ) );
	if ( mb_strlen( $name ) < 8 || ! preg_match( '/\p{L}{3,}/u', $name ) || preg_match( '/^(img|dsc|screenshot|image|photo|pxl|mvimg|whatsapp)\b/i', $name ) ) {
		return '';
	}
	return $name;
}

function ivs_seo_alt_for_src( $src, $fallback ) {
	static $n = 0;
	if ( false !== strpos( $src, '/uploads/' ) && $n < 60 ) {
		++$n;
		$clean = preg_replace( '/-(\d+x\d+|scaled)(\.[a-z0-9]+)$/i', '$2', strtok( $src, '?' ) );
		$id    = attachment_url_to_postid( $clean );
		if ( $id ) {
			$alt = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
			if ( $alt ) {
				return $alt;
			}
			$title = trim( wp_strip_all_tags( get_the_title( $id ) ) );
			if ( $title && ivs_seo_alt_from_file( $title . '.jpg' ) ) {
				return $title;
			}
		}
	}
	$alt = ivs_seo_alt_from_file( $src );
	return $alt ? $alt : $fallback;
}

function ivs_seo_home_h1() {
	return ivs_t( 'Ventilācijas regulēšana un mērinstrumentu noma Rīgā', 'Регулировка вентиляции и аренда приборов в Риге', 'Ventilation balancing and instrument rental in Riga' );
}

function ivs_seo_filter_html( $html ) {
	if ( ! is_string( $html ) || false === stripos( $html, '</head>' ) ) {
		return $html;
	}
	// Одна главная H1: заголовок баннера заменяем на запрос, остальные H1 понижаем до H2.
	if ( function_exists( 'ivs_is_any_home' ) && ivs_is_any_home() ) {
		$first = true;
		$html  = preg_replace_callback( '#<h1\b([^>]*)>(.*?)</h1>#is', function ( $m ) use ( &$first ) {
			if ( $first ) {
				$first = false;
				return '<h1' . $m[1] . '>' . esc_html( ivs_seo_home_h1() ) . '</h1>';
			}
			return '<h2' . $m[1] . '>' . $m[2] . '</h2>';
		}, $html );
	}
	// og:image: если Yoast вывел og:title, но не картинку.
	if ( false !== strpos( $html, 'property="og:title"' ) && false === strpos( $html, 'property="og:image"' ) ) {
		list( $u, $w, $h ) = ivs_seo_og_for_request();
		$meta = '<meta property="og:image" content="' . esc_url( ivs_seo_fix_host( $u ) ) . '" />' . "\n";
		if ( $w && $h ) {
			$meta .= '<meta property="og:image:width" content="' . (int) $w . '" />' . "\n" . '<meta property="og:image:height" content="' . (int) $h . '" />' . "\n";
		}
		$html = preg_replace( '#</head>#i', $meta . '</head>', $html, 1 );
	}
	// alt у картинок без описания.
	if ( false !== stripos( $html, '<img' ) ) {
		$fallback = ivs_seo_name( wp_strip_all_tags( is_singular() ? get_the_title( get_queried_object_id() ) : wp_get_document_title() ) );
		$fallback = trim( preg_replace( '/\s*[|–-]\s*IVANCOVS\s*$/u', '', $fallback ) );
		if ( function_exists( 'ivs_is_any_home' ) && ivs_is_any_home() ) {
			$fallback = ivs_seo_home_h1();
		}
		$html     = preg_replace_callback( '#<img\b[^>]*>#i', function ( $m ) use ( $fallback ) {
			$tag = $m[0];
			if ( ! preg_match( '#\s(?:data-src|src)=(["\'])(.*?)\1#is', $tag, $s ) ) {
				return $tag;
			}
			$has = preg_match( '#\salt=(["\'])(.*?)\1#is', $tag, $a );
			if ( $has && '' !== trim( $a[2] ) ) {
				return $tag;
			}
			if ( preg_match( '#\swidth=(["\']?)1\1[\s>/]#i', $tag ) || false !== stripos( $tag, 'role="presentation"' ) || false !== stripos( $tag, 'aria-hidden' ) ) {
				return $tag;
			}
			$ttl = preg_match( '#\stitle=(["\'])(.*?)\1#is', $tag, $t ) ? trim( html_entity_decode( $t[2] ) ) : '';
			$alt = $ttl ? esc_attr( $ttl ) : esc_attr( ivs_seo_alt_for_src( html_entity_decode( $s[2] ), $fallback ) );
			if ( '' === $alt ) {
				return $tag;
			}
			if ( $has ) {
				return str_replace( $a[0], ' alt="' . $alt . '"', $tag );
			}
			return preg_replace( '#<img\b#i', '<img alt="' . $alt . '"', $tag, 1 );
		}, $html );
	}
	return $html;
}

add_action( 'template_redirect', function () {
	if ( is_admin() || is_feed() || is_robots() || is_embed() || is_trackback() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	ob_start( 'ivs_seo_filter_html' );
}, 0 );
