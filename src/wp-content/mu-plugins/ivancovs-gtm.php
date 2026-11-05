<?php
/**
 * Plugin Name: Ivancovs Google Tag Manager
 * Description: Подключает контейнер Tag Manager после Consent Mode (баннер cookie ставит denied с приоритетом 0). Только на боевом домене, без входов администраторов.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const IVC_GTM_ID    = 'GTM-M4VVLG3D';
const IVC_GTM_HOSTS = array( 'ivancovs.com', 'www.ivancovs.com' );

function ivc_gtm_enabled() {
	if ( is_admin() || is_user_logged_in() ) {
		return false;
	}
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	return in_array( $host, IVC_GTM_HOSTS, true );
}

// Приоритет 1: после Consent Mode default (приоритет 0), до остальных скриптов.
add_action( 'wp_head', function () {
	if ( ! ivc_gtm_enabled() ) {
		return;
	}
	?>
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','<?php echo esc_js( IVC_GTM_ID ); ?>');</script>
<!-- End Google Tag Manager -->
	<?php
}, 1 );

add_action( 'wp_body_open', function () {
	if ( ! ivc_gtm_enabled() ) {
		return;
	}
	?>
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr( IVC_GTM_ID ); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
	<?php
} );
