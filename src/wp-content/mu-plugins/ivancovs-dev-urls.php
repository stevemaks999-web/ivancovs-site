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
}
