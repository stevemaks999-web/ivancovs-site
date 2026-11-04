<?php
/**
 * Plugin Name: Ivancovs Cookie Consent
 * Description: Cookie banner (LV/RU/EN) with Google Consent Mode v2. Analytics and ads stay denied until the visitor agrees. Adapted from the SapnisMat consent module.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const IVC_STORAGE_KEY = 'ivancovs_cookie_consent';

function ivc_lang() {
	$lang = function_exists( 'pll_current_language' ) ? pll_current_language() : '';
	if ( ! $lang ) {
		$lang = substr( determine_locale(), 0, 2 );
	}
	return in_array( $lang, array( 'lv', 'ru', 'en' ), true ) ? $lang : 'lv';
}

function ivc_privacy_url( $lang ) {
	$id = (int) get_option( 'wp_page_for_privacy_policy' );
	if ( $id && function_exists( 'pll_get_post' ) ) {
		$tr = pll_get_post( $id, $lang );
		if ( $tr ) {
			$id = $tr;
		}
	}
	return $id ? get_permalink( $id ) : home_url( '/' );
}

function ivc_texts( $lang ) {
	$t = array(
		'lv' => array(
			'text'      => 'Mēs izmantojam nepieciešamās sīkdatnes vietnes darbībai. Analītiskās un mārketinga sīkdatnes tiek izmantotas tikai ar jūsu piekrišanu.',
			'policy'    => 'Privātuma politika',
			'reject'    => 'Tikai nepieciešamās',
			'settings'  => 'Pielāgot',
			'accept'    => 'Piekrītu visam',
			'title'     => 'Sīkdatņu iestatījumi',
			'intro'     => 'Jūs varat izvēlēties, kuras sīkdatņu kategorijas atļaut.',
			'c1'        => '1. Nepieciešamās (tehniskās)',
			'c1d'       => 'Rezervācijas sesija, valodas izvēle un maksājumu drošība. Bez tām vietne nevar darboties.',
			'always'    => 'Vienmēr aktīvs',
			'c2'        => '2. Analītiskās (Google Analytics)',
			'c2d'       => 'Anonīma apmeklējumu statistika, lai uzlabotu vietnes lietošanu.',
			'c3'        => '3. Mārketinga (Google Ads)',
			'c3d'       => 'Reklāmas birkas atbilstošu piedāvājumu rādīšanai un kampaņu efektivitātes mērīšanai.',
			'save'      => 'Saglabāt izvēli',
			'footer'    => 'Sīkdatņu iestatījumi',
		),
		'ru' => array(
			'text'      => 'Мы используем обязательные cookie для работы сайта. Аналитические и маркетинговые cookie включаются только с вашего согласия.',
			'policy'    => 'Политика конфиденциальности',
			'reject'    => 'Только обязательные',
			'settings'  => 'Настроить',
			'accept'    => 'Принять все',
			'title'     => 'Настройки cookie',
			'intro'     => 'Вы можете выбрать, какие категории cookie разрешить.',
			'c1'        => '1. Обязательные (технические)',
			'c1d'       => 'Сессия бронирования, выбор языка и защита платежей. Без них сайт не работает.',
			'always'    => 'Всегда активно',
			'c2'        => '2. Аналитические (Google Analytics)',
			'c2d'       => 'Анонимная статистика посещений, чтобы улучшать удобство сайта.',
			'c3'        => '3. Маркетинговые (Google Ads)',
			'c3d'       => 'Рекламные метки для показа подходящих предложений и оценки кампаний.',
			'save'      => 'Сохранить выбор',
			'footer'    => 'Настройки cookie',
		),
		'en' => array(
			'text'      => 'We use necessary cookies to run the site. Analytics and marketing cookies are used only with your consent.',
			'policy'    => 'Privacy policy',
			'reject'    => 'Necessary only',
			'settings'  => 'Customise',
			'accept'    => 'Accept all',
			'title'     => 'Cookie settings',
			'intro'     => 'Choose which cookie categories you allow.',
			'c1'        => '1. Necessary (technical)',
			'c1d'       => 'Booking session, language choice and payment security. The site cannot work without them.',
			'always'    => 'Always on',
			'c2'        => '2. Analytics (Google Analytics)',
			'c2d'       => 'Anonymous visit statistics that help us improve the site.',
			'c3'        => '3. Marketing (Google Ads)',
			'c3d'       => 'Ad tags used to show relevant offers and measure campaigns.',
			'save'      => 'Save choice',
			'footer'    => 'Cookie settings',
		),
	);
	return $t[ $lang ];
}

// Google Consent Mode v2: everything denied until the visitor decides.
add_action( 'wp_head', function () {
	?>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
(function(){
	var a='denied', m='denied';
	try {
		var s = localStorage.getItem('<?php echo IVC_STORAGE_KEY; ?>');
		if (s === 'accepted') { a = m = 'granted'; }
		else if (s) {
			if (localStorage.getItem('<?php echo IVC_STORAGE_KEY; ?>_analytics') === '1') a = 'granted';
			if (localStorage.getItem('<?php echo IVC_STORAGE_KEY; ?>_marketing') === '1') m = 'granted';
		}
	} catch(e) {}
	gtag('consent','default',{analytics_storage:a,ad_storage:m,ad_user_data:m,ad_personalization:m,wait_for_update:500});
})();
</script>
	<?php
}, 0 );

add_action( 'wp_footer', function () {
	$lang = ivc_lang();
	$t    = ivc_texts( $lang );
	$url  = ivc_privacy_url( $lang );
	$key  = IVC_STORAGE_KEY;
	?>
<div id="ivc-cookie-banner" role="dialog" aria-label="<?php echo esc_attr( $t['title'] ); ?>" hidden>
	<p><?php echo esc_html( $t['text'] ); ?> <a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $t['policy'] ); ?></a></p>
	<div class="ivc-actions">
		<button type="button" data-ivc="reject"><?php echo esc_html( $t['reject'] ); ?></button>
		<button type="button" data-ivc="settings" class="ivc-link"><?php echo esc_html( $t['settings'] ); ?></button>
		<button type="button" data-ivc="accept" class="ivc-primary"><?php echo esc_html( $t['accept'] ); ?></button>
	</div>
</div>
<div id="ivc-cookie-modal" hidden>
	<div class="ivc-card" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( $t['title'] ); ?>">
		<div class="ivc-head"><h3><?php echo esc_html( $t['title'] ); ?></h3><button type="button" data-ivc="close" aria-label="Close">&times;</button></div>
		<p><?php echo esc_html( $t['intro'] ); ?></p>
		<div class="ivc-cat"><strong><?php echo esc_html( $t['c1'] ); ?></strong><span class="ivc-on"><?php echo esc_html( $t['always'] ); ?></span><small><?php echo esc_html( $t['c1d'] ); ?></small></div>
		<div class="ivc-cat"><label><strong><?php echo esc_html( $t['c2'] ); ?></strong><input type="checkbox" id="ivc-analytics"></label><small><?php echo esc_html( $t['c2d'] ); ?></small></div>
		<div class="ivc-cat"><label><strong><?php echo esc_html( $t['c3'] ); ?></strong><input type="checkbox" id="ivc-marketing"></label><small><?php echo esc_html( $t['c3d'] ); ?></small></div>
		<div class="ivc-actions"><button type="button" data-ivc="save" class="ivc-primary"><?php echo esc_html( $t['save'] ); ?></button><button type="button" data-ivc="accept"><?php echo esc_html( $t['accept'] ); ?></button></div>
	</div>
</div>
<style>
#ivc-cookie-banner{position:fixed;left:16px;right:16px;bottom:16px;max-width:640px;margin:0 auto;background:#111827;color:#f3f4f6;border-radius:12px;padding:14px 18px;z-index:999999;box-shadow:0 10px 30px rgba(0,0,0,.4);font:14px/1.5 system-ui,sans-serif}
#ivc-cookie-banner[hidden],#ivc-cookie-modal[hidden]{display:none}
#ivc-cookie-banner p{margin:0 0 10px}#ivc-cookie-banner a{color:#7dd3fc}
.ivc-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.ivc-actions button,.ivc-head button{font:inherit;cursor:pointer;border-radius:8px;padding:8px 14px;border:1px solid rgba(255,255,255,.3);background:transparent;color:inherit}
.ivc-actions .ivc-primary{background:#2563eb;border-color:#2563eb;color:#fff;font-weight:600}
.ivc-actions .ivc-link{border:0;text-decoration:underline;padding:8px 4px}
#ivc-cookie-modal{position:fixed;inset:0;background:rgba(0,0,0,.65);z-index:1000000;display:flex;align-items:center;justify-content:center;padding:16px}
#ivc-cookie-modal[hidden]{display:none}
.ivc-card{background:#111827;color:#f3f4f6;border-radius:14px;max-width:520px;width:100%;max-height:90vh;overflow:auto;padding:22px;font:14px/1.5 system-ui,sans-serif}
.ivc-head{display:flex;justify-content:space-between;align-items:center}.ivc-head h3{margin:0;font-size:18px;color:#fff}
.ivc-head button{border:0;font-size:22px;padding:0 6px}
.ivc-cat{background:rgba(255,255,255,.06);border-radius:10px;padding:10px 12px;margin:10px 0}
.ivc-cat label{display:flex;justify-content:space-between;align-items:center;gap:10px}
.ivc-cat small{display:block;color:#9ca3af;margin-top:4px}.ivc-on{float:right;color:#22c55e;font-size:12px}
.ivc-cat input{width:20px;height:20px}
@media(max-width:600px){#ivc-cookie-banner{left:10px;right:10px;bottom:10px}}
</style>
<script>
(function(){
	var K='<?php echo esc_js( $key ); ?>', $=function(i){return document.getElementById(i)};
	var banner=$('ivc-cookie-banner'), modal=$('ivc-cookie-modal'), an=$('ivc-analytics'), mk=$('ivc-marketing');
	function store(a,m,state){
		try{localStorage.setItem(K,state);localStorage.setItem(K+'_analytics',a?'1':'0');localStorage.setItem(K+'_marketing',m?'1':'0');
		document.cookie=K+'='+state+'; max-age=31536000; path=/; SameSite=Lax'+(location.protocol==='https:'?'; Secure':'');}catch(e){}
		if(typeof gtag==='function'){var g=a?'granted':'denied',d=m?'granted':'denied';
			gtag('consent','update',{analytics_storage:g,ad_storage:d,ad_user_data:d,ad_personalization:d});}
		window.dispatchEvent(new CustomEvent('ivc_consent_updated',{detail:{analytics:a,marketing:m}}));
		banner.hidden=true;modal.hidden=true;
	}
	function sync(){try{an.checked=localStorage.getItem(K+'_analytics')==='1';mk.checked=localStorage.getItem(K+'_marketing')==='1';}catch(e){}}
	try{ if(!localStorage.getItem(K)) banner.hidden=false; else sync(); }catch(e){ banner.hidden=false; }
	document.addEventListener('click',function(e){
		var b=e.target.closest('[data-ivc]'); if(!b){ if(e.target===modal) modal.hidden=true; return; }
		var a=b.getAttribute('data-ivc');
		if(a==='accept') store(true,true,'accepted');
		else if(a==='reject') store(false,false,'necessary');
		else if(a==='save') store(an.checked,mk.checked,(an.checked&&mk.checked)?'accepted':((an.checked||mk.checked)?'custom':'necessary'));
		else if(a==='settings'){sync();modal.hidden=false;}
		else if(a==='close') modal.hidden=true;
	});
	document.addEventListener('click',function(e){var l=e.target.closest('.ivc-cookie-settings');if(l){e.preventDefault();sync();modal.hidden=false;}});
})();
</script>
	<?php
}, 999 );

// [ivancovs_cookie_settings] -> link that reopens the settings window (for the footer).
add_shortcode( 'ivancovs_cookie_settings', function () {
	$t = ivc_texts( ivc_lang() );
	return '<a href="#" class="ivc-cookie-settings">' . esc_html( $t['footer'] ) . '</a>';
} );
