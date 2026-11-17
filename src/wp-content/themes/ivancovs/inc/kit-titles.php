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


/** Реальные названия приборов в комплекте: [RU, EN, LV]. Общие слова переведены, модели нет. */
function ivs_kit_device_titles() {
	$endo  = array( 'Эндоскоп Anesok', 'Anesok borescope', 'Anesok endoskops' );
	$refr  = array( 'Рефрактометр RHA-503ATC', 'Refractometer RHA-503ATC', 'Refraktometrs RHA-503ATC' );
	$tool  = array( 'Тепловизор Tooltop T7', 'Tooltop T7 thermal imager', 'Termokamera Tooltop T7' );
	$smoke = array( 'Дым-генератор Telesin C40', 'Telesin C40 smoke generator', 'Dūmu ģenerators Telesin C40' );
	return array(
		'Ventilācijas Nodošana Ekspluatācijā PRO' => array( 'Testo 420', 'Testo 417', $smoke ),
		'Gaisa Vadu Noplūžu un Spiediena Tests' => array( 'Fluke 922', 'Eurolite NSF-150A DMX', 'Testo 510i' ),
		'Komforta Mikroklimats un Caurvējš' => array( 'Swema 03+', 'Testo 535', 'Testo 605i' ),
		'Ventilācijas Ātrā Pārbaude DUO' => array( 'Testo 405i', 'Testo 410i' ),
		'Siltumsūkņu & Chiller Master Kit' => array( '2× Testo 549i', '2× Testo 115i', 'Testo 510i' ),
		'F-Gāzu Noplūžu & Spiediena Tests' => array( 'Snakol SK-8202', $refr ),
		'Cauruļu Ultraskaņas Plūsmas & Siltuma Audits' => array( 'TUF-3000L', 'Benetech GM100' ),
		'Katlu Mājas Hidrauliskā Balansēšana' => array( 'Meokon MD-S220', 'Hantek HTM208B', $refr ),
		'Mājas Siltuma Zudumu Pilnais Audits' => array( $tool, 'HoldPeak HP-5000T', 'Testo 605i' ),
		'Dūmu Pārbaude & Blower Door Trace' => array( $smoke, 'Benetech GT5101' ),
		'Auto Pircēja Super-Check' => array( 'Nohawk NK-200', $endo, $refr ),
		'Dzinēja & Turbīnas Padziļinātā Diagnostika' => array( $endo, $tool, 'Peakmeter PM2019A' ),
		'Darba Vides Pilnā Sertifikācija' => array( 'PCE-318', 'Snakol SK-8202', 'Testo 535', 'Testo 605i' ),
		'Aku, Tvertņu un Šahtu Drošības Komplekts' => array( array( 'Газоанализатор Bosean BH-4S', 'Bosean BH-4S gas detector', 'Gāzes analizators Bosean BH-4S' ), 'FNIRSI ERD-10' ),
		'Grīdas Ieklāšanas & Mitruma Drošība' => array( 'HoldPeak HP-5000T', 'Testo 605i' ),
		'Tīrtelpu & Cleanroom Daļiņu Audits' => array( 'SISCO PC-HZ011C', 'Testo 440 dP', 'Swema 03+' ),
		'Cauruļu Noplūžu & Aizsērējumu Detektīvs' => array( $endo, $tool ),
		'Elektromotoru & Gultņu Veselības Tests' => array( 'Landtek VM-6370', 'HoldPeak HP-DT-10S', $tool ),
		'Elektrosadales & Jaudas Parametru Audits' => array( 'Hanmatek CO601', $tool ),
		'Noliktavu Temperatūras Kartēšana' => array( '20× Freshliance BlueTag TH20', '6× Xiaomi LYWSD03MMC' ),
	);
}

function ivs_kit_device_title( $key ) {
	$map = ivs_kit_device_titles();
	if ( ! isset( $map[ $key ] ) ) {
		return '';
	}
	$i     = array( 'ru' => 0, 'en' => 1, 'lv' => 2 );
	$idx   = $i[ ivs_lang() ] ?? 0;
	$parts = array();
	foreach ( $map[ $key ] as $item ) {
		$parts[] = is_array( $item ) ? $item[ $idx ] : $item;
	}
	return implode( ' + ', $parts );
}

/** Подписи комплектов, исправленные вместо тех, что лежат в базе: [RU, EN]. */
function ivs_kit_captions() {
	return array(
		'Ventilācijas Nodošana Ekspluatācijā PRO' => array( 'Сдача вентиляции в эксплуатацию.', 'Ventilation commissioning and handover.' ),
		'Gaisa Vadu Noplūžu un Spiediena Tests' => array( 'Плотность вентиляционных каналов.', 'Air duct leakage testing.' ),
		'Komforta Mikroklimats un Caurvējš' => array( 'Аудит сквозняков в офисах.', 'Draught audit in offices.' ),
		'Ventilācijas Ātrā Pārbaude DUO' => array( 'Экспресс-проверка тяги.', 'Quick draught check.' ),
		'Siltumsūkņu & Chiller Master Kit' => array( 'Теплонасосы и холод.', 'Heat pumps and refrigeration.' ),
		'F-Gāzu Noplūžu & Spiediena Tests' => array( 'Опрессовка азотом.', 'Nitrogen pressure testing.' ),
		'Cauruļu Ultraskaņas Plūsmas & Siltuma Audits' => array( 'Накладной теплосчётчик.', 'Clamp-on heat meter.' ),
		'Katlu Mājas Hidrauliskā Balansēšana' => array( 'Балансировка отопления.', 'Heating system balancing.' ),
		'Mājas Siltuma Zudumu Pilnais Audits' => array( 'Утечки тепла и холода.', 'Heat and cold leaks.' ),
		'Dūmu Pārbaude & Blower Door Trace' => array( 'Герметичность каркасных домов.', 'Airtightness of timber-frame houses.' ),
		'Auto Pircēja Super-Check' => array( 'Проверка авто перед покупкой.', 'Checking a car before purchase.' ),
		'Dzinēja & Turbīnas Padziļinātā Diagnostika' => array( 'Мотор и турбина.', 'Engine and turbocharger.' ),
		'Darba Vides Pilnā Sertifikācija' => array( 'Аттестация рабочих мест.', 'Workplace assessment.' ),
		'Aku, Tvertņu un Šahtu Drošības Komplekts' => array( 'Колодцы и резервуары.', 'Wells and tanks.' ),
		'Grīdas Ieklāšanas & Mitruma Drošība' => array( 'Паркет, ламинат, эпоксид.', 'Parquet, laminate, epoxy.' ),
		'Tīrtelpu & Cleanroom Daļiņu Audits' => array( 'Чистые комнаты, HEPA.', 'Clean rooms, HEPA.' ),
		'Cauruļu Noplūžu & Aizsērējumu Detektīvs' => array( 'Сантехнический сыщик.', 'Plumbing detective.' ),
		'Elektromotoru & Gultņu Veselības Tests' => array( 'Вибрация и состояние подшипников моторов.', 'Motor and bearing vibration check.' ),
		'Elektrosadales & Jaudas Parametru Audits' => array( 'Электроаудит щитовых.', 'Switchboard electrical audit.' ),
		'Noliktavu Temperatūras Kartēšana' => array( 'Склады GDP.', 'GDP warehouses.' ),
	);
}

function ivs_kit_key( $title ) {
	return trim( html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES ), "«» \t\n\r" );
}

add_filter( 'get_the_excerpt', function ( $excerpt, $post ) {
	$lang = ivs_lang();
	if ( 'lv' === $lang || ! $post || 'product' !== $post->post_type ) {
		return $excerpt;
	}
	$map = ivs_kit_captions();
	$key = ivs_kit_key( $post->post_title );
	return isset( $map[ $key ] ) ? $map[ $key ][ 'ru' === $lang ? 0 : 1 ] : $excerpt;
}, 30, 2 );

/** Название комплекта показываем оригинальным и без кавычек (как у приборов). */
add_filter( 'the_title', function ( $title, $id = 0 ) {
	if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ! $id || 'product' !== get_post_type( $id ) ) {
		return $title;
	}
	$clean = trim( $title );
	$clean = preg_replace( '/^(«|&laquo;|&#171;)\s*/u', '', $clean );
	$clean = preg_replace( '/\s*(»|&raquo;|&#187;)$/u', '', $clean );
	$real  = ivs_kit_device_title( ivs_kit_key( $clean ) );
	return '' !== $real ? $real : $clean;
}, 10, 2 );
