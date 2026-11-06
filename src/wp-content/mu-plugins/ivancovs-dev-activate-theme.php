<?php
/**
 * Plugin Name: Ivancovs: включить тему на тестовом сайте
 * Description: Один раз включает тему Ivancovs, только в папке тестовой установки dev.ivancovs.com. На боевой домен не выкладывается и не срабатывает. После включения можно переключить тему обратно: плагин второй раз не сработает.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	// Адрес сайта в базе тестовой установки боевой (редирект на ivancovs.com), поэтому узнаём её по папке.
	if ( false === strpos( wp_normalize_path( ABSPATH ), '/dev.ivancovs.com/' ) ) {
		return;
	}
	if ( get_option( 'ivs_dev_theme_activated' ) ) {
		return;
	}
	if ( ! wp_get_theme( 'ivancovs' )->exists() ) {
		return;
	}
	update_option( 'ivs_dev_theme_activated', gmdate( 'c' ), false );
	switch_theme( 'ivancovs' );
} );
