<?php
/**
 * Названия комплектов на RU и EN. В базе они латышские (утверждены Дмитрием), поэтому подменяем при показе.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function ivs_kit_titles() {
	return array(
		'Ventilācijas Nodošana Ekspluatācijā PRO' => array( 'Сдача вентиляции в эксплуатацию PRO', 'Ventilation Commissioning PRO' ),
		'Gaisa Vadu Noplūžu un Spiediena Tests' => array( 'Тест герметичности и давления воздуховодов', 'Duct Leakage and Pressure Test' ),
		'Komforta Mikroklimats un Caurvējš' => array( 'Комфортный микроклимат и сквозняки', 'Comfort Microclimate and Draughts' ),
		'Ventilācijas Ātrā Pārbaude DUO' => array( 'Экспресс-проверка вентиляции DUO', 'Quick Ventilation Check DUO' ),
		'Siltumsūkņu & Chiller Master Kit' => array( 'Мастер-комплект: тепловые насосы и чиллеры', 'Heat Pump & Chiller Master Kit' ),
		'F-Gāzu Noplūžu & Spiediena Tests' => array( 'Тест утечек F-газов и давления', 'F-Gas Leak & Pressure Test' ),
		'Cauruļu Ultraskaņas Plūsmas & Siltuma Audits' => array( 'Ультразвуковой аудит расхода и тепла в трубах', 'Pipe Ultrasonic Flow & Heat Audit' ),
		'Katlu Mājas Hidrauliskā Balansēšana' => array( 'Гидравлическая балансировка котельной', 'Boiler Room Hydraulic Balancing' ),
		'Mājas Siltuma Zudumu Pilnais Audits' => array( 'Полный аудит теплопотерь дома', 'Full Home Heat Loss Audit' ),
		'Dūmu Pārbaude & Blower Door Trace' => array( 'Проверка дымом и Blower Door', 'Smoke Test & Blower Door Trace' ),
		'Auto Pircēja Super-Check' => array( 'Супер-проверка для покупателя авто', 'Car Buyer Super-Check' ),
		'Dzinēja & Turbīnas Padziļinātā Diagnostika' => array( 'Углублённая диагностика двигателя и турбины', 'Engine & Turbine Deep Diagnostics' ),
		'Darba Vides Pilnā Sertifikācija' => array( 'Полная аттестация рабочей среды', 'Full Workplace Environment Certification' ),
		'Aku, Tvertņu un Šahtu Drošības Komplekts' => array( 'Комплект безопасности для колодцев, резервуаров и шахт', 'Well, Tank and Shaft Safety Kit' ),
		'Grīdas Ieklāšanas & Mitruma Drošība' => array( 'Укладка пола и защита от влаги', 'Floor Installation & Moisture Safety' ),
		'Tīrtelpu & Cleanroom Daļiņu Audits' => array( 'Аудит частиц в чистых помещениях', 'Cleanroom Particle Audit' ),
		'Cauruļu Noplūžu & Aizsērējumu Detektīvs' => array( 'Детектив утечек и засоров труб', 'Pipe Leak & Blockage Detective' ),
		'Elektromotoru & Gultņu Veselības Tests' => array( 'Тест состояния электромоторов и подшипников', 'Electric Motor & Bearing Health Test' ),
		'Elektrosadales & Jaudas Parametru Audits' => array( 'Аудит электрощитов и параметров мощности', 'Switchboard & Power Parameter Audit' ),
		'Noliktavu Temperatūras Kartēšana' => array( 'Температурное картирование складов', 'Warehouse Temperature Mapping' ),
	);
}

add_filter( 'the_title', function ( $title, $id = 0 ) {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! $id || 'product' !== get_post_type( $id ) ) {
		return $title;
	}
	$lang = ivs_lang();
	if ( 'lv' === $lang ) {
		return $title;
	}
	$key = trim( html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES ), "«» \t\n\r" );
	$map = ivs_kit_titles();
	if ( ! isset( $map[ $key ] ) ) {
		return $title;
	}
	return '«' . $map[ $key ][ 'ru' === $lang ? 0 : 1 ] . '»';
}, 10, 2 );
