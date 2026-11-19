<?php
/**
 * SEO: адреса, hreflang, заголовки, описания, разметка (по аудиту 17.11.2026).
 * Метатеги формирует Yoast, здесь только правки через его фильтры, без ручных дублей в <head>.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Тестовая установка (папка dev.ivancovs.com) или боевая. */
function ivs_is_dev_install() {
	return false !== strpos( wp_normalize_path( ABSPATH ), '/dev.ivancovs.com/' );
}

/** На боевом сайте адреса из общей базы иногда приходят с dev.ivancovs.com: заменяем на основной. */
function ivs_seo_fix_host( $v ) {
	if ( ivs_is_dev_install() ) {
		return $v;
	}
	if ( is_string( $v ) ) {
		return str_replace( array( 'https://dev.ivancovs.com', '//dev.ivancovs.com', 'https%3A%2F%2Fdev.ivancovs.com' ), array( 'https://ivancovs.com', '//ivancovs.com', 'https%3A%2F%2Fivancovs.com' ), $v );
	}
	if ( is_array( $v ) ) {
		foreach ( $v as $k => $item ) {
			$v[ $k ] = ivs_seo_fix_host( $item );
		}
	}
	return $v;
}
foreach ( array( 'wpseo_canonical', 'wpseo_opengraph_url', 'wpseo_schema_graph', 'pll_rel_hreflang_attributes', 'redirect_canonical', 'wp_redirect', 'wpseo_sitemap_url', 'oembed_discovery_links' ) as $ivs_hook ) {
	add_filter( $ivs_hook, 'ivs_seo_fix_host', 99 );
}

/** Главная страница на RU или EN (/ru/ и /en/): это языковая главная Polylang, а не обычная страница. */
function ivs_is_lang_home() {
	if ( is_admin() || is_singular() || is_404() ) {
		return false;
	}
	$q = (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY ); // phpcs:ignore
	$p = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ); // phpcs:ignore
	return in_array( $p, array( 'ru', 'en' ), true ) && ( '' === $q || 0 === strpos( $q, 'ivl=' ) || 0 === strpos( $q, 'lang=' ) );
}

function ivs_is_any_home() {
	return is_front_page() || ivs_is_lang_home();
}

function ivs_home_title() {
	return ivs_t( 'Ventilācijas regulēšana, mērinstrumentu noma Rīgā', 'Регулировка вентиляции и аренда приборов в Риге', 'Ventilation balancing and instrument rental in Riga' ) . ' | IVANCOVS';
}

function ivs_home_desc() {
	return ivs_t(
		'Ventilācijas sistēmu regulēšana, auditi un ekspertīzes Rīgā. Profesionālu mērinstrumentu noma: anemometri, termokameras, CO₂ mērītāji. Dmitrijs Ivancovs.',
		'Регулировка, аудит и экспертиза вентиляции в Риге. Аренда профессиональных приборов: анемометры, тепловизоры, CO₂-измерители. Дмитрий Иванцов.',
		'Ventilation balancing, audits and expert assessments in Riga. Rental of professional instruments: anemometers, thermal cameras and CO₂ meters.'
	);
}

/** Страница каталога, товара или категории приборов. */
function ivs_is_catalog_page() {
	return function_exists( 'is_woocommerce' ) && is_woocommerce() && ! is_cart() && ! is_checkout() && ! is_account_page();
}

/** Адрес каталога: если WooCommerce отдаёт ?page_id=, берём красивый /noma/. */
function ivs_shop_url() {
	$u = wc_get_page_permalink( 'shop' );
	return ( ! $u || false !== strpos( $u, '?' ) ) ? home_url( '/noma/' ) : $u;
}

/** Язык версии страницы: на товарах RU/EN лежат на том же адресе с ?ivl=. */
function ivs_catalog_lang_url( $lang ) {
	$base = ( is_singular( 'product' ) ) ? get_permalink() : ( ( is_tax() || is_post_type_archive( 'product' ) ) ? ( is_tax() ? get_term_link( get_queried_object() ) : ivs_shop_url() ) : '' );
	if ( ! $base || is_wp_error( $base ) ) {
		return '';
	}
	$base = remove_query_arg( array( 'ivl', 'paged' ), $base );
	return 'lv' === $lang ? $base : add_query_arg( 'ivl', $lang, $base );
}

/** Canonical: сам на себя. Страница каталога на RU/EN канонична со своим ?ivl=. */
add_filter( 'wpseo_canonical', function ( $canonical ) {
	if ( is_string( $canonical ) && false !== strpos( $canonical, '?page_id=' ) && is_page() ) {
		return get_permalink( get_queried_object_id() );
	}
	if ( is_post_type_archive( 'product' ) && ( false !== strpos( (string) $canonical, '?' ) || ! $canonical ) ) {
		$canonical = ivs_shop_url();
	}
	if ( ivs_is_catalog_page() && 'lv' !== ivs_lang() ) {
		$u = ivs_catalog_lang_url( ivs_lang() );
		if ( $u ) {
			return $u;
		}
	}
	return $canonical;
}, 98 );

/** hreflang: добавляем x-default (латышский) к парам Polylang. */
add_filter( 'pll_rel_hreflang_attributes', function ( $urls ) {
	if ( is_array( $urls ) && ! isset( $urls['x-default'] ) && isset( $urls['lv'] ) ) {
		$urls['x-default'] = $urls['lv'];
	}
	return $urls;
}, 98 );

/** hreflang для каталога: Polylang его не знает, версии лежат на ?ivl=ru|en. */
add_action( 'wp_head', function () {
	if ( ! ivs_is_catalog_page() || ( ! is_singular( 'product' ) && ! is_tax() && ! is_post_type_archive( 'product' ) ) || is_paged() ) {
		return;
	}
	$out = array();
	foreach ( array( 'lv', 'ru', 'en' ) as $l ) {
		$u = ivs_catalog_lang_url( $l );
		if ( ! $u ) {
			return;
		}
		$out[ $l ] = $u;
	}
	$out['x-default'] = $out['lv'];
	foreach ( $out as $l => $u ) {
		printf( "<link rel=\"alternate\" hreflang=\"%s\" href=\"%s\" />\n", esc_attr( $l ), esc_url( ivs_seo_fix_host( $u ) ) );
	}
}, 3 );

/** Обрезка по слову. */
function ivs_seo_cut( $s, $max ) {
	$s = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $s ) ) );
	if ( mb_strlen( $s ) <= $max ) {
		return $s;
	}
	$s = mb_substr( $s, 0, $max + 1 );
	$s = preg_replace( '/\s+\S*$/u', '', $s );
	return rtrim( $s, " ,;:-–—" );
}

function ivs_seo_name( $name ) {
	return trim( str_replace( array( '«', '»', '"', '“', '”' ), '', html_entity_decode( (string) $name ) ) );
}

/** Цена за сутки для заголовков (целая, если без копеек). */
function ivs_seo_price( $product ) {
	$p = $product ? $product->get_price() : '';
	return '' === $p ? '' : rtrim( rtrim( number_format( (float) $p, 2, '.', '' ), '0' ), '.' );
}

/** Title: приборы, категории, каталог, главная. Лимит 60 знаков. */
add_filter( 'wpseo_title', function ( $title ) {
	$brand = ' | IVANCOVS';
	$l     = ivs_lang();
	if ( ivs_is_any_home() ) {
		return ivs_home_title();
	}
	if ( is_singular( 'product' ) ) {
		$name = ivs_seo_name( get_the_title() );
		$p    = ivs_seo_price( wc_get_product( get_the_ID() ) );
		$tail = ivs_t( ' noma Rīgā', ' аренда в Риге', ' rental in Riga' );
		$full = $name . $tail . ( $p ? ivs_t( ' no ' . $p . ' €/dienā', ' от ' . $p . ' €/сутки', ' from €' . $p . '/day' ) : '' ) . $brand;
		if ( mb_strlen( $full ) > 60 ) {
			$full = $name . $tail . $brand;
		}
		if ( mb_strlen( $full ) > 60 ) {
			$full = $name . $tail;
		}
		return $full;
	}
	if ( is_post_type_archive( 'product' ) && ! is_tax() ) {
		return ivs_t( 'Mērinstrumentu noma Rīgā: 46 instrumenti', 'Аренда измерительных приборов в Риге: 46 приборов', 'Measuring instrument rental in Riga: 46 tools' ) . $brand;
	}
	if ( is_tax( 'product_cat' ) ) {
		$cat = ivs_seo_name( single_term_title( '', false ) );
		return $cat . ivs_t( ' noma Rīgā', ': аренда в Риге', ' rental in Riga' ) . $brand;
	}
	// Страницы, записи и рубрики: короткие названия дополняем, длинные сокращаем (30–60 знаков).
	if ( is_singular() || is_category() || ( is_home() && ! ivs_is_lang_home() ) ) {
		$base = is_singular() ? get_the_title( get_queried_object_id() ) : single_cat_title( '', false );
		$base = ivs_seo_name( $base );
		if ( is_category() ) {
			$base .= ivs_t( ' – raksti par ventilāciju', ' – статьи о вентиляции', ' – ventilation articles' );
		}
		if ( $base && ! is_category() && mb_strlen( $base . $brand ) < 30 ) {
			$base .= ivs_t( ' – ventilācija un mērījumi', ' – вентиляция и измерения', ' – ventilation and measurements' );
		}
		if ( $base ) {
			if ( mb_strlen( $base . $brand ) > 60 ) {
				$base = ivs_seo_cut( $base, 60 - mb_strlen( $brand ) );
			}
			return $base . $brand;
		}
	}
	return $title;
}, 98 );

/** Description: приборы, категории, каталог, главная; запасной вариант из текста страницы. Цель 140–160 знаков. */
add_filter( 'wpseo_metadesc', function ( $desc ) {
	if ( ivs_is_any_home() ) {
		return ivs_home_desc();
	}
	if ( is_singular( 'product' ) ) {
		$name = ivs_seo_name( get_the_title() );
		$p    = ivs_seo_price( wc_get_product( get_the_ID() ) );
		if ( 'ru' === ivs_lang() ) {
			$d = $name . ' — аренда в Риге' . ( $p ? ' от ' . $p . ' € в сутки' : '' ) . '. Выберите даты на сайте и забронируйте онлайн, прибор выдаём в Риге. Залог и условия указаны на странице.';
		} elseif ( 'en' === ivs_lang() ) {
			$d = $name . ' rental in Riga' . ( $p ? ' from €' . $p . ' per day' : '' ) . '. Pick your dates and book online, pick up the instrument in Riga. Deposit and terms are shown on the page.';
		} else {
			$d = $name . ' noma Rīgā' . ( $p ? ' no ' . $p . ' € dienā' : '' ) . '. Izvēlieties datumus un rezervējiet tiešsaistē, instrumentu saņemsiet Rīgā. Depozīts un nosacījumi norādīti lapā.';
		}
		return ivs_seo_cut( $d, 158 );
	}
	if ( is_post_type_archive( 'product' ) && ! is_tax() ) {
		return ivs_t(
			'Mērinstrumentu noma Rīgā: termokameras, anemometri, manometri, CO₂ un gāzes detektori. Cena par dienu, rezervācija tiešsaistē.',
			'Аренда измерительных приборов в Риге: тепловизоры, анемометры, манометры, CO₂ и газоанализаторы. Цена за сутки, бронь онлайн.',
			'Measuring instrument rental in Riga: thermal cameras, anemometers, manometers, CO₂ and gas detectors. Daily prices, online booking.'
		);
	}
	if ( is_tax( 'product_cat' ) ) {
		$cat = ivs_seo_name( single_term_title( '', false ) );
		return ivs_seo_cut( ivs_t(
			$cat . ': mērinstrumentu noma Rīgā. Cena par dienu, depozīts, rezervācija tiešsaistē. Izvēlieties atsevišķu instrumentu vai gatavu komplektu uzdevumam.',
			$cat . ': аренда приборов в Риге. Цена за сутки, залог, бронь онлайн. Выберите отдельный прибор или готовый комплект под задачу.',
			$cat . ': instrument rental in Riga. Daily price, deposit, online booking. Choose a single instrument or a ready kit for your task.'
		), 158 );
	}
	if ( ! $desc && ( is_singular() || is_category() || ( is_home() && ! ivs_is_lang_home() ) ) ) {
		$post = is_singular() ? get_queried_object() : null;
		$txt  = $post ? ( $post->post_excerpt ? $post->post_excerpt : strip_shortcodes( $post->post_content ) ) : '';
		$txt  = ivs_seo_cut( $txt, 158 );
		if ( mb_strlen( $txt ) >= 60 ) {
			return $txt;
		}
		// Текста мало (страница собирается шаблоном): описание по названию страницы.
		$t = ivs_seo_name( is_singular() ? get_the_title( get_queried_object_id() ) : wp_strip_all_tags( get_the_archive_title() ) );
		if ( $t ) {
			return ivs_seo_cut( ivs_t(
				$t . ': ventilācijas regulēšana, auditi, ekspertīzes un apmācības Rīgā. Dmitrijs Ivancovs, praktiska pieredze un mērinstrumenti.',
				$t . ': регулировка вентиляции, аудиты, экспертизы и обучение в Риге. Дмитрий Иванцов, практический опыт и измерительные приборы.',
				$t . ': ventilation balancing, audits, expert assessments and training in Riga. Dmitrijs Ivancovs, hands-on experience and instruments.'
			), 158 );
		}
	}
	return $desc;
}, 98 );

/** og:image: общая картинка по умолчанию, если у страницы своей нет. */
add_action( 'wpseo_add_opengraph_images', function ( $images ) {
	if ( is_object( $images ) && method_exists( $images, 'add_image' ) && ! is_singular( 'product' ) && ! has_post_thumbnail() ) {
		$images->add_image( array( 'url' => content_url( '/themes/ivancovs/assets/dmitrijs.jpg' ) ) );
	}
} );

/** Пустой alt у картинок: берём название прибора или файла. */
add_filter( 'wp_get_attachment_image_attributes', function ( $attr, $attachment ) {
	if ( empty( $attr['alt'] ) && ! is_admin() ) {
		$parent = $attachment && $attachment->post_parent ? get_the_title( $attachment->post_parent ) : '';
		$alt    = $parent ? $parent : ( $attachment ? $attachment->post_title : '' );
		if ( $alt ) {
			$attr['alt'] = ivs_seo_name( $alt );
		}
	}
	return $attr;
}, 20, 2 );

/** Корзина, оформление, кабинет и условия покупки не нужны в карте сайта. */
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', function ( $ids ) {
	$ids  = is_array( $ids ) ? $ids : array();
	$base = array();
	if ( function_exists( 'wc_get_page_id' ) ) {
		foreach ( array( 'cart', 'checkout', 'myaccount' ) as $p ) {
			$base[] = (int) wc_get_page_id( $p );
		}
	}
	$pv = get_page_by_path( 'pirkuma-veiksana' );
	if ( $pv ) {
		$base[] = (int) $pv->ID;
	}
	foreach ( array_filter( $base ) as $id ) {
		$ids[] = $id;
		if ( function_exists( 'pll_get_post_translations' ) ) {
			foreach ( (array) pll_get_post_translations( $id ) as $tid ) {
				$ids[] = (int) $tid;
			}
		}
	}
	return array_values( array_unique( $ids ) );
} );

/** Разметка Product + Offer (цена за сутки) на странице прибора и LocalBusiness на главной. */
add_action( 'wp_head', function () {
	$data = null;
	if ( is_singular( 'product' ) ) {
		$product = wc_get_product( get_the_ID() );
		if ( ! $product || '' === $product->get_price() ) {
			return;
		}
		$url   = ivs_seo_fix_host( ivs_catalog_lang_url( ivs_lang() ) ? ivs_catalog_lang_url( ivs_lang() ) : get_permalink() );
		$img   = get_the_post_thumbnail_url( get_the_ID(), 'large' );
		$data  = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'Product',
			'name'        => ivs_seo_name( get_the_title() ),
			'description' => ivs_seo_cut( get_the_excerpt() ? get_the_excerpt() : get_the_title(), 300 ),
			'url'         => $url,
			'inLanguage'  => ivs_lang(),
			'offers'      => array(
				'@type'              => 'Offer',
				'url'                => $url,
				'priceCurrency'      => 'EUR',
				'price'              => (string) $product->get_price(),
				'availability'       => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
				'priceSpecification' => array(
					'@type'         => 'UnitPriceSpecification',
					'price'         => (string) $product->get_price(),
					'priceCurrency' => 'EUR',
					'unitCode'      => 'DAY',
				),
			),
		);
		if ( $product->get_sku() ) {
			$data['sku'] = $product->get_sku();
		}
		if ( $img ) {
			$data['image'] = ivs_seo_fix_host( $img );
		}
	} elseif ( ivs_is_any_home() ) {
		$data = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'LocalBusiness',
			'@id'         => 'https://ivancovs.com/#business',
			'name'        => 'IVANCOVS',
			'legalName'   => 'SIA Delumix',
			'url'         => 'https://ivancovs.com/',
			'telephone'   => '+37125553244',
			'email'       => 'dmitrijs@ivancovs.com',
			'image'       => content_url( '/themes/ivancovs/assets/dmitrijs.jpg' ),
			'address'     => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => 'Lastādijas iela 32',
				'addressLocality' => 'Rīga',
				'postalCode'      => 'LV-1050',
				'addressCountry'  => 'LV',
			),
			'areaServed'  => 'Rīga',
		);
	}
	if ( $data ) {
		echo '<script type="application/ld+json">' . wp_json_encode( ivs_seo_fix_host( $data ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
	}
}, 20 );

/** Постоянные редиректы (301) со старых адресов на новые. Ключ: путь в нижнем регистре без слэшей по краям. */
add_action( 'template_redirect', function () {
	$map  = array(
		'en/blog-3'                                       => '/en/the-blog/',
		'en/blog'                                         => '/en/the-blog/',
		'apmacibas/gaisa_attirisana'                      => '/apmacibas/gaisa-attirisana/',
		'apmacibas/gaisa_kondicionesana'                  => '/apmacibas/gaisa-kondicionesana/',
		'apmacibas/ventilacijas_parbaude'                 => '/apmacibas/ventilacijas-parbaude/',
		'%d0%b8%d0%b7%d0%bc%d0%b5%d1%80%d0%b5%d0%bd%d0%b8%d1%8f-%d0%bf%d0%be-iso-7730' => '/izmereniya-po-iso-7730/',
		'%d0%b8%d0%b7%d0%bc%d0%b5%d1%80%d0%b5%d0%bd%d0%b8%d0%b5-%d0%bc%d0%b5%d1%82%d0%be%d0%b4%d0%be%d0%bc-%d0%ba-%d1%84%d0%b0%d0%ba%d1%82%d0%be%d1%80' => '/izmerenie-metodom-k-faktor/',
		'product-category/co%e2%82%82-un-gaisa-kvalitate' => '/product-category/co2-un-gaisa-kvalitate/',
	);
	$path = strtolower( trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ) ); // phpcs:ignore
	if ( isset( $map[ $path ] ) ) {
		wp_safe_redirect( home_url( $map[ $path ] ), 301 );
		exit;
	}
}, 1 );

/**
 * Карта сайта для RU и EN версий каталога (адреса с ?ivl=): подключается к индексу Yoast как ivl-sitemap.xml.
 * Версии приборов и категорий переведены, у каждой свой canonical и hreflang, поэтому Google их показываем явно.
 */
function ivs_ivl_sitemap_urls() {
	$urls = array();
	$add  = function ( $base, $mod ) use ( &$urls ) {
		if ( ! $base || is_wp_error( $base ) ) {
			return;
		}
		foreach ( array( 'ru', 'en' ) as $l ) {
			$urls[] = array( add_query_arg( 'ivl', $l, remove_query_arg( 'ivl', $base ) ), $mod );
		}
	};
	$add( ivs_shop_url(), '' );
	$ids = get_posts( array( 'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true ) );
	foreach ( $ids as $id ) {
		$add( get_permalink( $id ), get_post_modified_time( 'c', true, $id ) );
	}
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true ) );
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $t ) {
			$add( get_term_link( $t ), '' );
		}
	}
	return $urls;
}

add_action( 'init', function () {
	global $wpseo_sitemaps;
	if ( ! isset( $wpseo_sitemaps ) || ! is_object( $wpseo_sitemaps ) || ! method_exists( $wpseo_sitemaps, 'register_sitemap' ) ) {
		return;
	}
	$wpseo_sitemaps->register_sitemap( 'ivl', function () use ( $wpseo_sitemaps ) {
		$xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		foreach ( ivs_ivl_sitemap_urls() as $u ) {
			$xml .= '<url><loc>' . esc_url( ivs_seo_fix_host( $u[0] ) ) . '</loc>' . ( $u[1] ? '<lastmod>' . esc_html( $u[1] ) . '</lastmod>' : '' ) . '</url>';
		}
		$xml .= '</urlset>';
		$wpseo_sitemaps->set_sitemap( $xml );
	} );
}, 20 );

add_filter( 'wpseo_sitemap_index', function ( $xml ) {
	return $xml . '<sitemap><loc>' . esc_url( home_url( '/ivl-sitemap.xml' ) ) . '</loc></sitemap>';
} );

/** Языковая главная (/ru/, /en/): Yoast на ней метатеги не выводит, поэтому здесь canonical, hreflang и Open Graph выводим сами. */
add_filter( 'pre_get_document_title', function ( $t ) {
	return ivs_is_lang_home() ? ivs_home_title() : $t;
}, 99 );
add_filter( 'wpseo_canonical', function ( $c ) {
	return ivs_is_lang_home() ? '' : $c;
}, 99 );
add_action( 'wp_head', function () {
	if ( ! ivs_is_lang_home() ) {
		return;
	}
	$l   = ivs_lang();
	$url = home_url( '/' . $l . '/' );
	$loc = array( 'ru' => 'ru_RU', 'en' => 'en_GB' );
	echo '<link rel="canonical" href="' . esc_url( ivs_seo_fix_host( $url ) ) . '" />' . "\n";
	foreach ( array( 'lv' => '/', 'ru' => '/ru/', 'en' => '/en/', 'x-default' => '/' ) as $code => $path ) {
		echo '<link rel="alternate" hreflang="' . esc_attr( $code ) . '" href="' . esc_url( ivs_seo_fix_host( home_url( $path ) ) ) . '" />' . "\n";
	}
	echo '<meta property="og:locale" content="' . esc_attr( $loc[ $l ] ?? 'lv_LV' ) . '" />' . "\n";
	echo '<meta property="og:type" content="website" />' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( ivs_home_title() ) . '" />' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( ivs_home_desc() ) . '" />' . "\n";
	echo '<meta property="og:url" content="' . esc_url( ivs_seo_fix_host( $url ) ) . '" />' . "\n";
	echo '<meta property="og:site_name" content="IVANCOVS" />' . "\n";
	echo '<meta property="og:image" content="' . esc_url( content_url( '/themes/ivancovs/assets/dmitrijs.jpg' ) ) . '" />' . "\n";
	echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
}, 2 );
