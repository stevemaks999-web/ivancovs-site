<?php
/**
 * Plugin Name: Ivancovs: включить тему на тестовом сайте
 * Description: Один раз включает тему Ivancovs, только на dev.ivancovs.com. На боевой домен не выкладывается и не срабатывает. После включения можно переключить тему обратно: плагин второй раз не сработает.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', function () {
	if ( 'dev.ivancovs.com' !== wp_parse_url( home_url(), PHP_URL_HOST ) ) {
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
