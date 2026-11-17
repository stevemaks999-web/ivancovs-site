<?php
/**
 * Страница прибора: текст «Где применяют», вопросы и ответы (FAQ с разметкой), похожие приборы.
 * Всё строится из данных самого прибора (цены, залог, состав, описание) и общего текста категории.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Общий текст категории: где применяют такие приборы. Ключ: слаг категории (для комплектов 'komplekti'). */
function ivs_category_usage() {
	return array(
		'gaisa-plusma-un-ventilacija'   => array(
			'lv' => 'Gaisa plūsmas un ātruma mērījumi ir pamats ventilācijas regulēšanai un nodošanai ekspluatācijā. Ar tiem pārbauda, vai gaisa daudzums uz restēm, difuzoriem un kanālos atbilst projektam, atrod disbalansu starp zariem un novērtē caurvēju telpās.',
			'ru' => 'Измерения скорости и расхода воздуха нужны для регулировки и сдачи вентиляции. Ими проверяют, соответствует ли расход на решётках, диффузорах и в воздуховодах проекту, находят дисбаланс между ветками и оценивают сквозняки в помещениях.',
			'en' => 'Air velocity and flow measurements are the basis of ventilation balancing and commissioning. They show whether the airflow at grilles, diffusers and in ducts matches the design, reveal imbalance between branches and help assess draughts in rooms.',
		),
		'spiediens'                     => array(
			'lv' => 'Spiediena starpības mērījumi vajadzīgi ventilācijas kanālu un filtru stāvokļa pārbaudei, ēkas spiediena režīma novērtēšanai, kanālu blīvuma testiem un apkures vai gāzes sistēmu kontrolei.',
			'ru' => 'Измерение перепада давления нужно для проверки воздуховодов и фильтров, оценки режима давления в здании, испытаний плотности каналов и контроля систем отопления или газа.',
			'en' => 'Pressure difference measurements are used to check ducts and filters, assess the pressure regime of a building, test duct tightness and inspect heating or gas systems.',
		),
		'temperatura-un-mitrums'        => array(
			'lv' => 'Temperatūras un mitruma mērījumi palīdz kontrolēt mikroklimatu telpās, pārbaudīt pamatni pirms grīdu un apdares darbiem, meklēt mitruma avotus konstrukcijās un koksnē, kā arī uzraudzīt noliktavas un dzīvojamās telpas.',
			'ru' => 'Измерения температуры и влажности помогают контролировать микроклимат, проверять основание перед укладкой полов и отделкой, искать источники влаги в конструкциях и древесине, следить за складами и жилыми помещениями.',
			'en' => 'Temperature and humidity measurements help control indoor climate, check a base before flooring and finishing, trace moisture sources in structures and timber and monitor warehouses and living spaces.',
		),
		'co2-un-gaisa-kvalitate'        => array(
			'lv' => 'CO₂ un gaisa kvalitātes mērījumi parāda, vai ventilācija telpā tiek galā ar cilvēku skaitu: birojos, mācību telpās, sanāksmju zālēs un dzīvokļos. Daļiņu skaitītājus izmanto tīrtelpām un filtru efektivitātes pārbaudei.',
			'ru' => 'Измерения CO₂ и качества воздуха показывают, справляется ли вентиляция с числом людей: в офисах, классах, переговорных и квартирах. Счётчики частиц используют для чистых помещений и проверки эффективности фильтров.',
			'en' => 'CO₂ and air quality measurements show whether ventilation copes with the number of people in offices, classrooms, meeting rooms and homes. Particle counters are used for cleanrooms and filter efficiency checks.',
		),
		'gazes-un-dumi'                 => array(
			'lv' => 'Gāzes detektori palīdz pārbaudīt tvana gāzi, degošu gāzu noplūdes un skābekļa trūkumu slēgtās vietās, piemēram, akās un tvertnēs. Dūmu ģenerators padara redzamas gaisa plūsmas un noplūdes.',
			'ru' => 'Газоанализаторы помогают проверить угарный газ, утечки горючих газов и нехватку кислорода в замкнутых местах, например в колодцах и резервуарах. Дым-генератор делает видимыми потоки воздуха и утечки.',
			'en' => 'Gas detectors help check carbon monoxide, combustible gas leaks and oxygen deficiency in confined spaces such as wells and tanks. A smoke generator makes airflows and leaks visible.',
		),
		'termokameras-un-endoskopi'     => array(
			'lv' => 'Termokamera atklāj siltuma zudumus, mitruma un izolācijas defektus un pārkarsētus elektrosavienojumus. Endoskops ļauj apskatīt vietas, kurām citādi nevar tikt klāt: caurules, dzinēju, starpsienas un šahtas.',
			'ru' => 'Тепловизор выявляет теплопотери, влагу и дефекты изоляции, перегретые электросоединения. Эндоскоп позволяет осмотреть места, куда иначе не добраться: трубы, двигатель, перегородки и шахты.',
			'en' => 'A thermal camera reveals heat loss, moisture and insulation defects and overheated electrical connections. An endoscope lets you inspect places you cannot otherwise reach: pipes, engines, partitions and shafts.',
		),
		'biezums-vibracija-ultraskana'  => array(
			'lv' => 'Biezuma, vibrācijas un ultraskaņas mērījumus izmanto krāsas slāņa pārbaudei pirms auto pirkuma, motoru un ventilatoru stāvokļa novērtēšanai un plūsmas mērīšanai caurulēs, tās neatverot.',
			'ru' => 'Измерения толщины, вибрации и ультразвуком применяют для проверки слоя краски перед покупкой авто, оценки состояния двигателей и вентиляторов и измерения потока в трубах без их вскрытия.',
			'en' => 'Thickness, vibration and ultrasonic measurements are used to check paint layers before buying a car, assess the condition of motors and fans and measure flow in pipes without opening them.',
		),
		'skana-gaisma-elektriba'        => array(
			'lv' => 'Skaņas līmeņa, apgaismojuma un elektrisko parametru mērījumi vajadzīgi darba vides novērtēšanai, elektroietaišu pārbaudei un iekārtu diagnostikai.',
			'ru' => 'Измерения уровня шума, освещённости и электрических параметров нужны для оценки рабочей среды, проверки электроустановок и диагностики оборудования.',
			'en' => 'Sound level, illuminance and electrical measurements are needed to assess working environments, inspect electrical installations and diagnose equipment.',
		),
		'komplekti'                     => array(
			'lv' => 'Gatavs komplekts apvieno instrumentus vienam uzdevumam, lai nav jāizvēlas tie pa vienam. Visi komplekta instrumenti tiek izīrēti kopā par vienu cenu.',
			'ru' => 'Готовый комплект собирает приборы под одну задачу, чтобы не подбирать их по одному. Все приборы комплекта сдаются вместе по одной цене.',
			'en' => 'A ready-made kit brings together instruments for one task so you do not have to pick them one by one. All instruments in the kit are rented together at one price.',
		),
	);
}

/** Текст категории для товара: по слагу категории или её родителя (комплекты). */
function ivs_product_usage_text( $post_id ) {
	$map   = ivs_category_usage();
	$terms = get_the_terms( $post_id, 'product_cat' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}
	foreach ( $terms as $t ) {
		$slug = $t->slug;
		if ( $t->parent ) {
			$p = get_term( $t->parent, 'product_cat' );
			if ( $p && ! is_wp_error( $p ) && 'komplekti' === $p->slug ) {
				$slug = 'komplekti';
			}
		}
		if ( isset( $map[ $slug ] ) ) {
			return $map[ $slug ][ ivs_lang() ];
		}
	}
	return '';
}

function ivs_plain_price( $v ) {
	return html_entity_decode( wp_strip_all_tags( wc_price( $v ) ), ENT_QUOTES, 'UTF-8' );
}

/** Вопросы и ответы по прибору: [ [вопрос, ответ], ... ]. Только факты со страницы. */
function ivs_product_faq( $product ) {
	$id      = $product->get_id();
	$name    = ivs_seo_name( get_the_title( $id ) );
	$day     = ivs_plain_price( $product->get_price() );
	$week    = (float) get_post_meta( $id, '_ivs_price_week', true );
	$month   = (float) get_post_meta( $id, '_ivs_price_month', true );
	$dep     = (float) get_post_meta( $id, '_ivs_deposit', true );
	$kit     = trim( (string) get_post_meta( $id, '_ivs_kit_items', true ) );
	$excerpt = trim( wp_strip_all_tags( get_the_excerpt( $id ) ) );
	$faq     = array();

	if ( $excerpt ) {
		$faq[] = array( ivs_t( "Kam paredzēts $name?", "Для чего нужен $name?", "What is $name used for?" ), $excerpt );
	}

	$prices = $day . ivs_t( ' dienā', ' за сутки', ' per day' );
	if ( $week > 0 ) {
		$prices .= ivs_t( ', ', ', ', ', ' ) . ivs_plain_price( $week ) . ivs_t( ' nedēļā (7 dienas)', ' за неделю (7 суток)', ' per week (7 days)' );
	}
	if ( $month > 0 ) {
		$prices .= ', ' . ivs_plain_price( $month ) . ivs_t( ' mēnesī', ' за месяц', ' per month' );
	}
	$faq[] = array(
		ivs_t( "Cik maksā $name noma Rīgā?", "Сколько стоит аренда $name в Риге?", "How much does $name rental in Riga cost?" ),
		$prices . ivs_t( '. Rēķins tiek izrakstīts ar PVN 21%.', '. Счёт выставляется с PVN 21%.', '. The invoice is issued with 21% VAT.' ),
	);

	$faq[] = array(
		ivs_t( 'Vai nepieciešama drošības nauda?', 'Нужен ли залог?', 'Is a deposit required?' ),
		$dep > 0
			? ivs_t( 'Jā, drošības nauda ir ' . ivs_plain_price( $dep ) . '. To iemaksā rezervējot un atgriež pēc instrumenta atdošanas un apskates.', 'Да, залог составляет ' . ivs_plain_price( $dep ) . '. Его вносят при бронировании и возвращают после возврата и осмотра прибора.', 'Yes, the deposit is ' . ivs_plain_price( $dep ) . '. It is paid when booking and returned after the instrument is returned and inspected.' )
			: ivs_t( 'Šim instrumentam drošības nauda nav nepieciešama.', 'Для этого прибора залог не нужен.', 'No deposit is required for this instrument.' ),
	);

	if ( $kit ) {
		$faq[] = array(
			ivs_t( 'Kādi instrumenti ir komplektā?', 'Какие приборы входят в комплект?', 'Which instruments are in the kit?' ),
			$kit . ivs_t( '. Komplekts tiek izīrēts kopā, atsevišķus instrumentus no tā rezervēt nevar.', '. Комплект сдаётся целиком, отдельные приборы из него забронировать нельзя.', '. The kit is rented as a whole; its instruments cannot be booked separately.' ),
		);
	}

	$faq[] = array(
		ivs_t( 'Kā saņemt un atdot instrumentu?', 'Как получить и вернуть прибор?', 'How do I pick up and return the instrument?' ),
		ivs_t(
			'Instruments tiek izsniegts un pieņemts Rīgā darba dienās no 9:00 līdz 18:00. Brīvdienās pēc vienošanās. Jāuzrāda dokuments (pase, tiesības vai ID).',
			'Прибор выдаётся и принимается в Риге в рабочие дни с 9:00 до 18:00. В выходные по договорённости. Нужен документ (паспорт, права или ID).',
			'The instrument is handed over and taken back in Riga on working days from 9:00 to 18:00. Weekends by arrangement. An ID is required (passport, licence or ID card).'
		),
	);

	$faq[] = array(
		ivs_t( 'Kā rezervēt?', 'Как забронировать?', 'How do I book?' ),
		ivs_t(
			'Izvēlieties saņemšanas un atgriešanas datumus un laiku lapā, instruments tiks rezervēts bez dubultām rezervācijām. Jautājumus var uzdot WhatsApp.',
			'Выберите даты и время получения и возврата на странице, двойных броней не будет. Вопросы можно задать в WhatsApp.',
			'Choose pickup and return dates and times on the page; double bookings are not possible. Questions can be asked on WhatsApp.'
		),
	);
	return $faq;
}

/** Блоки под техническими данными: где применяют, вопросы и ответы, похожие приборы. */
add_action( 'ivs_after_content', function ( $product ) {
	$usage = ivs_product_usage_text( $product->get_id() );
	if ( $usage ) {
		echo '<h2 style="margin-top:24px">' . esc_html( ivs_t( 'Kur izmanto', 'Где применяют', 'Where it is used' ) ) . '</h2><p class="mut">' . esc_html( $usage ) . '</p>';
	}
	$faq = ivs_product_faq( $product );
	if ( $faq ) {
		echo '<h2 style="margin-top:24px">' . esc_html( ivs_t( 'Biežākie jautājumi', 'Частые вопросы', 'FAQ' ) ) . '</h2>';
		foreach ( $faq as $qa ) {
			echo '<h3 style="margin:14px 0 4px;font-size:17px">' . esc_html( $qa[0] ) . '</h3><p class="mut" style="margin:0">' . esc_html( $qa[1] ) . '</p>';
		}
	}
	$terms = get_the_terms( $product->get_id(), 'product_cat' );
	if ( $terms && ! is_wp_error( $terms ) ) {
		$q = new WP_Query( array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 4,
			'post__not_in'   => array( $product->get_id() ),
			'orderby'        => 'rand',
			'tax_query'      => array( array( 'taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => wp_list_pluck( $terms, 'term_id' ) ) ), // phpcs:ignore
			'no_found_rows'  => true,
		) );
		if ( $q->have_posts() ) {
			echo '<h2 style="margin-top:24px">' . esc_html( ivs_t( 'Citi instrumenti šajā kategorijā', 'Другие приборы этой категории', 'Other instruments in this category' ) ) . '</h2><ul>';
			while ( $q->have_posts() ) {
				$q->the_post();
				echo '<li><a href="' . esc_url( ivs_lang_url( get_permalink() ) ) . '">' . esc_html( ivs_seo_name( get_the_title() ) ) . '</a></li>';
			}
			echo '</ul>';
			wp_reset_postdata();
		}
	}
} );

/** Разметка FAQPage: те же вопросы и ответы, что видны на странице. */
add_action( 'wp_head', function () {
	if ( ! is_singular( 'product' ) ) {
		return;
	}
	$product = wc_get_product( get_the_ID() );
	if ( ! $product ) {
		return;
	}
	$items = array();
	foreach ( ivs_product_faq( $product ) as $qa ) {
		$items[] = array(
			'@type'          => 'Question',
			'name'           => $qa[0],
			'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $qa[1] ),
		);
	}
	if ( $items ) {
		echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $items ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "</script>\n";
	}
}, 21 );
