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
	$tool  = array( 'Тепловизор Tooltop T3/T7', 'Tooltop T3/T7 thermal imager', 'Termokamera Tooltop T3/T7' );
	$smoke = array( 'Дым-генератор Telesin C40', 'Telesin C40 smoke generator', 'Dūmu ģenerators Telesin C40' );
	return array(
		'Ventilācijas Nodošana Ekspluatācijā PRO' => array( 'Testo 420', 'Testo 417', $smoke ),
		'Gaisa Vadu Noplūžu un Spiediena Tests' => array( 'Fluke 922', 'Eurolite NSF-150A DMX', 'Testo 510i' ),
		'Komforta Mikroklimats un Caurvējš' => array( 'Swema 03+', 'Testo 535', 'Testo 605i' ),
		'Ventilācijas Ātrā Pārbaude DUO' => array( 'Testo 405i', 'Testo 410i' ),
		'Siltumsūkņu & Chiller Master Kit' => array( '2× Testo 549i', '2× Testo 115i', 'Testo 510i' ),
		'F-Gāzu Noplūžu & Spiediena Tests' => array( 'SuperStars ST-B168DL', $refr ),
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

/** Подписи комплектов, заменяющие те, что лежат в базе: [RU, EN, LV]. */
function ivs_kit_captions() {
	return array(
		'Ventilācijas Nodošana Ekspluatācijā PRO' => array( 'Расход и скорость воздуха, дым для видимости потоков.', 'Airflow and air velocity, smoke to visualise air movement.', 'Gaisa plūsma un ātrums, dūmi gaisa kustības redzamībai.' ),
		'Gaisa Vadu Noplūžu un Spiediena Tests' => array( 'Давление в воздуховодах и дым для поиска утечек.', 'Duct pressure and smoke for tracing leaks.', 'Spiediens gaisa vados un dūmi noplūžu meklēšanai.' ),
		'Komforta Mikroklimats un Caurvējš' => array( 'Скорость воздуха, CO₂, температура и влажность.', 'Air velocity, CO₂, temperature and humidity.', 'Gaisa ātrums, CO₂, temperatūra un mitrums.' ),
		'Ventilācijas Ātrā Pārbaude DUO' => array( 'Скорость и температура воздуха.', 'Air velocity and temperature.', 'Gaisa ātrums un temperatūra.' ),
		'Siltumsūkņu & Chiller Master Kit' => array( 'Давление и температура в системах отопления и холода.', 'Pressure and temperature in heating and refrigeration systems.', 'Spiediens un temperatūra apkures un aukstuma sistēmās.' ),
		'F-Gāzu Noplūžu & Spiediena Tests' => array( 'Давление в системах холода и кондиционирования, проверка антифриза.', 'Pressure in refrigeration and A/C systems, antifreeze check.', 'Spiediens aukstuma un gaisa kondicionēšanas sistēmās, antifrīza pārbaude.' ),
		'Cauruļu Ultraskaņas Plūsmas & Siltuma Audits' => array( 'Расход в трубах без врезки и толщина стенок.', 'Pipe flow without cutting in, and wall thickness.', 'Plūsma caurulēs bez iegriešanās un sieniņu biezums.' ),
		'Katlu Mājas Hidrauliskā Balansēšana' => array( 'Перепад давления, температура, проверка антифриза.', 'Differential pressure, temperature and antifreeze check.', 'Spiediena starpība, temperatūra, antifrīza pārbaude.' ),
		'Mājas Siltuma Zudumu Pilnais Audits' => array( 'Тепловизор, влажность материалов, температура и влажность.', 'Thermal imaging, material moisture, temperature and humidity.', 'Termokamera, materiālu mitrums, temperatūra un gaisa mitrums.' ),
		'Dūmu Pārbaude & Blower Door Trace' => array( 'Дым и давление: герметичность помещений и каналов.', 'Smoke and pressure: airtightness of rooms and ducts.', 'Dūmi un spiediens: telpu un kanālu hermētiskums.' ),
		'Auto Pircēja Super-Check' => array( 'Толщина краски, осмотр эндоскопом, проверка антифриза.', 'Paint thickness, borescope inspection, antifreeze check.', 'Krāsas biezums, apskate ar endoskopu, antifrīza pārbaude.' ),
		'Dzinēja & Turbīnas Padziļinātā Diagnostika' => array( 'Осмотр эндоскопом, нагрев и ток.', 'Borescope inspection, heat and current.', 'Apskate ar endoskopu, uzsilšana un strāva.' ),
		'Darba Vides Pilnā Sertifikācija' => array( 'Освещённость, шум, CO₂, температура и влажность.', 'Light, noise, CO₂, temperature and humidity.', 'Apgaismojums, troksnis, CO₂, temperatūra un mitrums.' ),
		'Aku, Tvertņu un Šahtu Drošības Komplekts' => array( 'Газы (кислород, CO, H₂S, горючие) и электромагнитные поля.', 'Gas detection (O₂, CO, H₂S, combustible) and electromagnetic fields.', 'Gāzes (skābeklis, CO, H₂S, degošās) un elektromagnētiskie lauki.' ),
		'Grīdas Ieklāšanas & Mitruma Drošība' => array( 'Влажность материалов, температура и влажность воздуха.', 'Material moisture, air temperature and humidity.', 'Materiālu mitrums, gaisa temperatūra un mitrums.' ),
		'Tīrtelpu & Cleanroom Daļiņu Audits' => array( 'Частицы в воздухе, давление и скорость воздуха.', 'Airborne particles, pressure and air velocity.', 'Daļiņas gaisā, spiediens un gaisa ātrums.' ),
		'Cauruļu Noplūžu & Aizsērējumu Detektīvs' => array( 'Осмотр эндоскопом и тепловизором.', 'Borescope and thermal imaging inspection.', 'Apskate ar endoskopu un termokameru.' ),
		'Elektromotoru & Gultņu Veselības Tests' => array( 'Вибрация, обороты и нагрев.', 'Vibration, rotation speed and heat.', 'Vibrācija, griešanās ātrums un uzsilšana.' ),
		'Elektrosadales & Jaudas Parametru Audits' => array( 'Ток, форма сигнала и нагрев.', 'Current, waveform and heat.', 'Strāva, signāla forma un uzsilšana.' ),
		'Noliktavu Temperatūras Kartēšana' => array( 'Температура и влажность с записью данных.', 'Temperature and humidity data logging.', 'Temperatūra un mitrums ar datu ierakstu.' ),
	);
}

function ivs_kit_key( $title ) {
	return trim( html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES ), "«» \t\n\r" );
}

add_filter( 'get_the_excerpt', function ( $excerpt, $post ) {
	$lang = ivs_lang();
	if ( ! $post || 'product' !== $post->post_type ) {
		return $excerpt;
	}
	$map = ivs_kit_captions();
	$key = ivs_kit_key( $post->post_title );
	$idx = array( 'ru' => 0, 'en' => 1, 'lv' => 2 );
	return isset( $map[ $key ] ) ? $map[ $key ][ $idx[ $lang ] ?? 2 ] : $excerpt;
}, 30, 2 );

/** Название комплекта показываем оригинальным и без кавычек (как у приборов). */
add_filter( 'the_title', function ( $title, $id = 0 ) {
	if ( ! ivs_kit_front_context() || ! $id || 'product' !== get_post_type( $id ) ) {
		return $title;
	}
	$clean = trim( $title );
	$clean = preg_replace( '/^(«|&laquo;|&#171;)\s*/u', '', $clean );
	$clean = preg_replace( '/\s*(»|&raquo;|&#187;)$/u', '', $clean );
	$real  = ivs_kit_device_title( ivs_kit_key( $clean ) );
	return '' !== $real ? $real : $clean;
}, 10, 2 );

/** Корзина и оформление берут название и описание из товара (Store API), поэтому подменяем и там. */
function ivs_kit_front_context() {
	if ( is_admin() ) {
		return false;
	}
	return ! ( defined( 'REST_REQUEST' ) && REST_REQUEST && current_user_can( 'edit_products' ) );
}

add_filter( 'woocommerce_product_get_name', function ( $name, $product ) {
	if ( ! ivs_kit_front_context() ) {
		return $name;
	}
	$real = ivs_kit_device_title( ivs_kit_key( $name ) );
	return '' !== $real ? $real : $name;
}, 20, 2 );

add_filter( 'woocommerce_product_get_short_description', function ( $desc, $product ) {
	if ( ! ivs_kit_front_context() ) {
		return $desc;
	}
	$map = ivs_kit_captions();
	$key = ivs_kit_key( $product->get_name( 'edit' ) );
	if ( ! isset( $map[ $key ] ) ) {
		return $desc;
	}
	$idx = array( 'ru' => 0, 'en' => 1, 'lv' => 2 );
	return $map[ $key ][ $idx[ ivs_lang() ] ?? 2 ];
}, 20, 2 );
