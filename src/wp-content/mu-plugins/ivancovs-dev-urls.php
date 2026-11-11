<?php
/**
 * Plugin Name: Ivancovs: адреса тестового сайта
 * Description: В базе тестовой установки адрес сайта боевой, из-за этого тема, стили и ссылки грузятся с ivancovs.com и страницы перенаправляются. Плагин подменяет адрес на dev.ivancovs.com, только в папке тестовой установки.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( false !== strpos( wp_normalize_path( ABSPATH ), '/dev.ivancovs.com/' ) ) {
	$ivs_dev_url = static function () {
		return 'https://dev.ivancovs.com';
	};
	add_filter( 'pre_option_home', $ivs_dev_url );
	add_filter( 'pre_option_siteurl', $ivs_dev_url );

	// Константа WP_CONTENT_URL считается до загрузки плагинов и остаётся боевой: чиним адреса файлов.
	$ivs_dev_fix = static function ( $url ) {
		return is_string( $url ) ? str_replace( array( 'https://ivancovs.com', '//ivancovs.com' ), array( 'https://dev.ivancovs.com', '//dev.ivancovs.com' ), $url ) : $url;
	};
	foreach ( array( 'theme_root_uri', 'content_url', 'plugins_url', 'includes_url', 'style_loader_src', 'script_loader_src', 'template_directory_uri', 'stylesheet_directory_uri', 'stylesheet_uri', 'wp_get_attachment_url', 'upload_dir_url' ) as $hook ) {
		add_filter( $hook, $ivs_dev_fix, 99 );
	}
	add_filter( 'upload_dir', static function ( $d ) {
		$d['baseurl'] = str_replace( 'https://ivancovs.com', 'https://dev.ivancovs.com', $d['baseurl'] );
		$d['url']     = str_replace( 'https://ivancovs.com', 'https://dev.ivancovs.com', $d['url'] );
		return $d;
	}, 99 );
}
