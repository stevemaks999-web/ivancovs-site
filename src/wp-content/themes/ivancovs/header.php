<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site"><div class="wrap">
	<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">IVAN<i>COVS</i></a>
	<nav><?php ivs_primary_nav(); ?></nav>
	<span class="sp"></span>
	<?php ivs_lang_switch(); ?>
	<a class="btn wa" href="<?php echo ivs_wa_link(); // phpcs:ignore ?>">WhatsApp</a>
	<button class="theme" id="theme" type="button" aria-label="<?php echo esc_attr( ivs_t( 'Tēma', 'Тема', 'Theme' ) ); ?>">🌙</button>
	<button class="burger" id="burger" type="button" aria-label="<?php echo esc_attr( ivs_t( 'Izvēlne', 'Меню', 'Menu' ) ); ?>">☰</button>
</div></header>
<main>
