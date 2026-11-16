<?php
/**
 * Distances līgums (договор дистанционной продажи / условия аренды) и согласие с ним при заказе.
 * Структура разделов по требованию Citadele (Klix): 7 пунктов. Тексты LV, RU, EN.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Разделы договора: массив [заголовок, [абзацы]] для языка lv, ru или en. */
function ivs_terms_sections( $lang ) {
	$t = array(
		'lv' => array(
			array( '1. Vispārīgie veikala tirdzniecības noteikumi', array(
				'Pārdevējs (iznomātājs): SIA “Delumix”, reģistrācijas Nr. 40203480127, PVN Nr. LV40203480127, juridiskā adrese: Lastādijas iela 32, Rīga, LV-1050. Tālrunis: ' . IVS_PHONE . ', e-pasts: ' . IVS_EMAIL . '. Mājaslapa: ivancovs.com.',
				'Pircējs (nomnieks) ir fiziska vai juridiska persona, kas veic pasūtījumu mājaslapā. Pasūtījumā pircējs norāda savu vārdu, uzvārdu (juridiskai personai: nosaukumu un reģistrācijas numuru), e-pastu, tālruni un personas vārdu, uzvārdu, kas saņems instrumentu.',
				'Pakalpojums: mērinstrumentu un ierīču noma (īre) uz izvēlētajiem datumiem. Katra instrumenta vai komplekta apraksts un nomas cena par diennakti, nedēļu un mēnesi ir norādīta tā lapā mājaslapā. Cenas ir norādītas eiro (EUR). Pasūtījuma kopsummā tiek parādīts PVN 21%.',
				'Papildus nomas maksai tiek ņemta atgriežama drošības nauda (depozīts) atsevišķā rindā. Tā ir 15% no instrumenta tirgus vērtības, ne vairāk kā 200 EUR; instrumentiem ar iegādes vērtību zem 500 EUR drošības nauda nav jāmaksā.',
				'Līgums ir noslēgts, kad pircējs ir apstiprinājis pasūtījumu, atzīmējis piekrišanu šiem noteikumiem un pasūtījums ir apmaksāts.',
			) ),
			array( '2. Apmaksas noteikumi', array(
				'Pircējs saņem pasūtījuma apstiprinājumu e-pastā uz norādīto adresi uzreiz pēc pasūtījuma noformēšanas. Pēc apmaksas pircējs saņem rēķinu ar PVN 21% un instrumenta izsniegšanas aktu.',
				'Apmaksas iespējas: maksājumu karte un bankas saite (internetbankas maksājums) ar Klix maksājumu risinājumu (AS “Citadele banka”). Maksājuma kartes dati netiek nodoti pārdevējam un tiek apstrādāti drošā maksājumu lapā.',
				'Pasūtījums tiek saglabāts uz izvēlētajiem datumiem. Ja maksājums netiek veikts noteiktajā laikā, pasūtījums tiek atcelts un datumi tiek atbrīvoti.',
			) ),
			array( '3. Preču vai pakalpojumu piegādes noteikumi', array(
				'Piegāde nav paredzēta. Instruments tiek izsniegts un atgriezts klātienē Rīgā, Lastādijas ielā 32, vai citā vietā, par kuru pušu vienojas pasūtījumā.',
				'Izsniegšana un atgriešana: katru dienu 9:00–18:00 pēc pasūtījumā izvēlētajiem datumiem un laika. Pēc 18:00 izsniegšana un atgriešana nav iespējama. Brīvdienās: tikai pēc iepriekšējas vienošanās ar pārdevēju.',
				'Izsniedzot instrumentu, saņēmējs uzrāda vienu dokumentu (pase, vadītāja apliecība vai ID karte). Instruments tiek izsniegts personai, kuras vārds un uzvārds norādīts pasūtījumā. Izsniedzot tiek parakstīts izsniegšanas akts ar instrumenta sērijas numuru.',
				'Pakalpojuma izpildes termiņš ir pasūtījumā izvēlētais nomas sākuma datums un laiks.',
			) ),
			array( '4. Preču atgriešanas noteikumi', array(
				'Atteikuma tiesības. Patērētājs (fiziska persona) var atteikties no distances līguma 14 dienu laikā no līguma noslēgšanas, nenorādot iemeslu, saskaņā ar Latvijas Republikas Patērētāju tiesību aizsardzības likumu un Ministru kabineta noteikumiem par distances līgumu. Lai atteiktos, jānosūta brīvas formas paziņojums uz ' . IVS_EMAIL . ' vai jāzvana pa tālruni ' . IVS_PHONE . ', norādot pasūtījuma numuru.',
				'Ja pircējs pieprasījis, lai noma sākas atteikuma termiņa laikā, un instruments jau ir izmantots, pircējs maksā par faktiski izmantoto laiku proporcionāli un atgriež instrumentu.',
				'Instruments jāatgriež pārdevējam līdz pasūtījumā norādītajam atgriešanas datumam un laikam (9:00–18:00) uz adresi: Lastādijas iela 32, Rīga, LV-1050, tādā pašā stāvoklī un pilnā komplektācijā (ar somu, kabeļiem, uzlādes ierīci un citiem piederumiem, kas izsniegti aktā).',
				'Atgriešanas izmaksas (nogādāšana pārdevējam) sedz pircējs. Piegādes izmaksu nav, jo piegāde netiek veikta.',
				'Kas var un nevar tikt atgriezts: nomas instrumenti pēc nomas laika beigām tiek atgriezti vienmēr. Instrumenti, kas atgriezti bojāti, bez piederumiem vai nepilnā komplektācijā, tiek novērtēti; bojājumu remonta vai trūkstošo daļu izmaksas tiek ieturētas no drošības naudas, bet, ja tās to pārsniedz, starpību apmaksā pircējs. Kavējot atgriešanu, par katru kavēto diennakti tiek rēķināta nomas maksa pēc diennakts cenas.',
			) ),
			array( '5. Naudas atmaksas noteikumi', array(
				'Drošības nauda tiek atmaksāta pēc instrumenta atgriešanas un apskates, ne vēlāk kā 14 dienu laikā, atņemot summas par bojājumiem vai kavējumu, ja tādas ir.',
				'Nomas maksa atteikuma gadījumā tiek atmaksāta ne vēlāk kā 14 dienu laikā no atteikuma saņemšanas dienas.',
				'Atmaksa tiek veikta ar to pašu maksāšanas līdzekli, ar kuru tika veikts maksājums (tai pašai kartei vai uz to pašu bankas kontu), ja pircējs nav vienojies par citu veidu.',
			) ),
			array( '6. Pasūtījuma atcelšanas noteikumi', array(
				'Pircējs var atcelt pasūtījumu pirms nomas sākuma, nosūtot paziņojumu uz ' . IVS_EMAIL . ' vai piezvanot pa tālruni ' . IVS_PHONE . '. Atcelšana ne vēlāk kā 24 stundas pirms nomas sākuma ir bez maksas, un pircējs saņem visu samaksāto summu atpakaļ šo noteikumu 5. punktā norādītajā kārtībā.',
				'Pārdevējs var atcelt pasūtījumu, ja instruments nav pieejams (piemēram, bojājuma dēļ); šādā gadījumā pircējs saņem visu samaksāto summu atpakaļ vai piedāvājumu citam datumam.',
			) ),
			array( '7. Strīdu risināšanas kārtība', array(
				'Visi strīdi tiek risināti sarunu ceļā. Pretenzijas var nosūtīt uz ' . IVS_EMAIL . '; pārdevējs atbild 14 dienu laikā.',
				'Ja vienošanās netiek panākta, strīds tiek izskatīts Latvijas Republikas tiesā saskaņā ar Latvijas Republikas normatīvajiem aktiem. Patērētājs var vērsties Patērētāju tiesību aizsardzības centrā (www.ptac.gov.lv) vai Eiropas Komisijas strīdu izšķiršanas platformā tiešsaistē: ec.europa.eu/consumers/odr.',
				'Personas datu apstrāde: skatiet mājaslapas Privātuma politiku.',
			) ),
		),
		'ru' => array(
			array( '1. Общие условия торговли магазина', array(
				'Продавец (арендодатель): SIA “Delumix”, рег. № 40203480127, номер плательщика PVN LV40203480127, юридический адрес: Lastādijas iela 32, Rīga, LV-1050. Телефон: ' . IVS_PHONE . ', e-mail: ' . IVS_EMAIL . '. Сайт: ivancovs.com.',
				'Покупатель (арендатор) — физическое или юридическое лицо, оформившее заказ на сайте. В заказе покупатель указывает имя, фамилию (юрлицо: название и регистрационный номер), e-mail, телефон, а также имя и фамилию того, кто заберёт прибор.',
				'Услуга: аренда измерительных приборов и устройств на выбранные даты. Описание каждого прибора или комплекта и цена аренды за сутки, неделю и месяц указаны на его странице. Цены в евро (EUR). В итоговой сумме заказа показан PVN 21%.',
				'Помимо арендной платы отдельной строкой берётся возвращаемый залог. Он равен 15% рыночной стоимости прибора, но не более 200 EUR; за приборы со стоимостью закупки до 500 EUR залог не берётся.',
				'Договор считается заключённым, когда покупатель подтвердил заказ, поставил отметку о согласии с этими условиями и заказ оплачен.',
			) ),
			array( '2. Условия оплаты', array(
				'Подтверждение заказа приходит на указанный e-mail сразу после оформления. После оплаты покупатель получает счёт с PVN 21% и акт выдачи прибора.',
				'Способы оплаты: банковская карта и банковская ссылка (интернет-банк) через платёжное решение Klix (AS “Citadele banka”). Данные карты продавцу не передаются и обрабатываются на защищённой платёжной странице.',
				'Заказ резервируется на выбранные даты. Если оплата не поступила в установленное время, заказ отменяется, а даты освобождаются.',
			) ),
			array( '3. Условия доставки товаров или оказания услуг', array(
				'Доставка не предусмотрена. Прибор выдаётся и принимается лично в Риге, Lastādijas iela 32, либо в другом месте, о котором стороны договорились в заказе.',
				'Выдача и возврат: ежедневно с 9:00 до 18:00 в выбранные в заказе даты и время. После 18:00 выдача и возврат невозможны. В выходные дни только по предварительной договорённости с продавцом.',
				'При выдаче получатель показывает один документ (паспорт, водительские права или ID-карту). Прибор выдаётся тому, чьё имя и фамилия указаны в заказе. При выдаче подписывается акт с серийным номером прибора.',
				'Срок исполнения услуги — дата и время начала аренды, выбранные в заказе.',
			) ),
			array( '4. Условия возврата товаров', array(
				'Право на отказ. Потребитель (физическое лицо) может отказаться от договора, заключённого дистанционно, в течение 14 дней со дня заключения, без объяснения причин, в соответствии с Законом о защите прав потребителей Латвии и правилами Кабинета министров о дистанционном договоре. Для этого нужно отправить уведомление в свободной форме на ' . IVS_EMAIL . ' или позвонить по телефону ' . IVS_PHONE . ', указав номер заказа.',
				'Если покупатель попросил начать аренду в течение срока отказа и прибор уже использовался, он оплачивает фактически использованное время пропорционально и возвращает прибор.',
				'Прибор нужно вернуть продавцу не позднее даты и времени возврата, указанных в заказе (9:00–18:00), по адресу: Lastādijas iela 32, Rīga, LV-1050, в том же состоянии и в полной комплектации (сумка, кабели, зарядное устройство и другие принадлежности, указанные в акте).',
				'Расходы на возврат (доставка продавцу) несёт покупатель. Расходов на доставку нет, так как доставка не производится.',
				'Что можно и нельзя вернуть: арендованные приборы по окончании аренды возвращаются всегда. Приборы, возвращённые повреждёнными, без принадлежностей или в неполной комплектации, оцениваются; стоимость ремонта или недостающих частей удерживается из залога, а если она больше, разницу оплачивает покупатель. За просрочку возврата за каждые лишние сутки начисляется аренда по суточной цене.',
			) ),
			array( '5. Условия возврата денег', array(
				'Залог возвращается после возврата и осмотра прибора, не позднее чем в течение 14 дней, за вычетом сумм за повреждения или просрочку, если они есть.',
				'Арендная плата при отказе возвращается не позднее чем в течение 14 дней со дня получения отказа.',
				'Возврат производится тем же способом оплаты, которым был сделан платёж (на ту же карту или тот же банковский счёт), если покупатель не договорился о другом.',
			) ),
			array( '6. Условия отмены заказа', array(
				'Покупатель может отменить заказ до начала аренды, отправив уведомление на ' . IVS_EMAIL . ' или позвонив по телефону ' . IVS_PHONE . '. Отмена не позднее чем за 24 часа до начала аренды бесплатна, вся уплаченная сумма возвращается в порядке, указанном в разделе 5.',
				'Продавец может отменить заказ, если прибор недоступен (например, из-за поломки); тогда покупатель получает всю уплаченную сумму обратно или предложение другой даты.',
			) ),
			array( '7. Порядок разрешения споров', array(
				'Все споры решаются путём переговоров. Претензии можно отправить на ' . IVS_EMAIL . '; продавец отвечает в течение 14 дней.',
				'Если договориться не удалось, спор рассматривается в суде Латвийской Республики по законам Латвии. Потребитель может обратиться в Центр защиты прав потребителей (www.ptac.gov.lv) или на онлайн-платформу Еврокомиссии по разрешению споров: ec.europa.eu/consumers/odr.',
				'Обработка персональных данных: см. Политику конфиденциальности на сайте.',
			) ),
		),
		'en' => array(
			array( '1. General terms of sale', array(
				'Seller (lessor): SIA “Delumix”, registration No. 40203480127, VAT No. LV40203480127, registered address: Lastādijas iela 32, Rīga, LV-1050. Phone: ' . IVS_PHONE . ', e-mail: ' . IVS_EMAIL . '. Website: ivancovs.com.',
				'The buyer (renter) is a private person or a company placing an order on the website. In the order the buyer gives their name (a company: its name and registration number), e-mail, phone, and the name of the person who will collect the instrument.',
				'Service: rental of measuring instruments and devices for the chosen dates. The description of each instrument or kit and its rental price per day, week and month are on its page. Prices are in euro (EUR). The order total shows VAT 21%.',
				'In addition to the rental fee, a refundable deposit is charged on a separate line. It is 15% of the market value of the instrument, but no more than 200 EUR; no deposit is charged for instruments with a purchase value under 500 EUR.',
				'The contract is concluded when the buyer has confirmed the order, ticked the box agreeing to these terms and the order has been paid.',
			) ),
			array( '2. Payment terms', array(
				'The buyer receives an order confirmation by e-mail immediately after placing the order. After payment the buyer receives an invoice with VAT 21% and the instrument handover record.',
				'Payment options: payment card and bank link (internet banking) through the Klix payment solution (AS “Citadele banka”). Card details are not passed to the seller and are processed on a secure payment page.',
				'The order is reserved for the chosen dates. If payment is not made in the set time, the order is cancelled and the dates are released.',
			) ),
			array( '3. Delivery and performance terms', array(
				'There is no delivery. The instrument is handed over and returned in person in Rīga, Lastādijas iela 32, or at another place agreed in the order.',
				'Handover and return: every day 9:00–18:00 on the dates and time chosen in the order. Handover and return after 18:00 are not possible. At weekends only by prior agreement with the seller.',
				'At handover the recipient shows one document (passport, driving licence or ID card). The instrument is handed to the person named in the order. A handover record with the instrument’s serial number is signed.',
				'The service is performed on the rental start date and time chosen in the order.',
			) ),
			array( '4. Return terms', array(
				'Right of withdrawal. A consumer (private person) may withdraw from a distance contract within 14 days of its conclusion without giving a reason, under the Latvian Consumer Rights Protection Law and the Cabinet regulations on distance contracts. To withdraw, send a free-form notice to ' . IVS_EMAIL . ' or call ' . IVS_PHONE . ', stating the order number.',
				'If the buyer asked for the rental to start within the withdrawal period and the instrument has already been used, the buyer pays proportionally for the time actually used and returns the instrument.',
				'The instrument must be returned to the seller by the return date and time given in the order (9:00–18:00) at: Lastādijas iela 32, Rīga, LV-1050, in the same condition and complete (case, cables, charger and other accessories listed in the handover record).',
				'The cost of returning the instrument to the seller is paid by the buyer. There are no delivery costs because no delivery is made.',
				'What can and cannot be returned: rented instruments are always returned at the end of the rental. Instruments returned damaged, without accessories or incomplete are assessed; the cost of repair or missing parts is deducted from the deposit, and if it is higher, the buyer pays the difference. For late return, rent at the daily price is charged for each extra day.',
			) ),
			array( '5. Refund terms', array(
				'The deposit is refunded after the instrument is returned and inspected, within 14 days at the latest, less any amounts for damage or delay.',
				'In case of withdrawal the rental fee is refunded within 14 days of receiving the withdrawal.',
				'The refund is made by the same payment method used for the payment (the same card or the same bank account), unless the buyer agrees another way.',
			) ),
			array( '6. Order cancellation terms', array(
				'The buyer may cancel the order before the rental starts by writing to ' . IVS_EMAIL . ' or calling ' . IVS_PHONE . '. Cancellation at least 24 hours before the rental starts is free of charge and the full amount paid is refunded as set out in section 5.',
				'The seller may cancel an order if the instrument is unavailable (for example due to a fault); in that case the buyer gets the full amount back or an offer of another date.',
			) ),
			array( '7. Dispute resolution', array(
				'All disputes are settled by negotiation. Complaints can be sent to ' . IVS_EMAIL . '; the seller replies within 14 days.',
				'If no agreement is reached, the dispute is heard by a court of the Republic of Latvia under Latvian law. A consumer may contact the Consumer Rights Protection Centre (www.ptac.gov.lv) or the European Commission online dispute resolution platform: ec.europa.eu/consumers/odr.',
				'Processing of personal data: see the Privacy policy on the website.',
			) ),
		),
	);
	return isset( $t[ $lang ] ) ? $t[ $lang ] : $t['lv'];
}

function ivs_terms_title( $lang ) {
	$m = array(
		'lv' => 'Distances līgums (lietošanas noteikumi)',
		'ru' => 'Договор дистанционной продажи (условия использования)',
		'en' => 'Distance contract (terms of use)',
	);
	return $m[ $lang ];
}

/** Шорткод [ivs_terms]: текст договора на языке страницы. */
add_shortcode( 'ivs_terms', function () {
	$out = '<div class="ivs-terms">';
	foreach ( ivs_terms_sections( ivs_lang() ) as $s ) {
		$out .= '<h2>' . esc_html( $s[0] ) . '</h2>';
		foreach ( $s[1] as $p ) {
			$out .= '<p>' . esc_html( $p ) . '</p>';
		}
	}
	return $out . '</div>';
} );

/** Создаём страницы договора на трёх языках один раз (Polylang связывает их как переводы). */
add_action( 'init', function () {
	if ( get_option( 'ivs_terms_pages_v1' ) || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	if ( ! function_exists( 'pll_set_post_language' ) ) {
		return; // ждём Polylang, чтобы страницы получили язык
	}
	$slugs = array(
		'lv' => 'distances-ligums',
		'ru' => 'dogovor-distantsionnoj-prodazhi',
		'en' => 'distance-contract',
	);
	$ids = array();
	foreach ( $slugs as $lang => $slug ) {
		$id = wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => ivs_terms_title( $lang ),
			'post_name'    => $slug,
			'post_content' => '[ivs_terms]',
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			pll_set_post_language( $id, $lang );
			$ids[ $lang ] = $id;
		}
	}
	if ( count( $ids ) === 3 ) {
		if ( function_exists( 'pll_save_post_translations' ) ) {
			pll_save_post_translations( $ids );
		}
		update_option( 'ivs_terms_page_id', $ids['lv'] );
		update_option( 'woocommerce_terms_page_id', $ids['lv'] );
		update_option( 'ivs_terms_pages_v1', 1 );
	}
}, 30 );

/** Адрес страницы договора на языке посетителя. */
function ivs_terms_url() {
	$id = (int) get_option( 'ivs_terms_page_id' );
	if ( ! $id ) {
		return '';
	}
	if ( function_exists( 'pll_get_post' ) ) {
		$tr = pll_get_post( $id, ivs_lang() );
		if ( $tr ) {
			$id = (int) $tr;
		}
	}
	return get_permalink( $id );
}

/** Галочка согласия в блочном оформлении заказа (обязательная). */
add_action( 'woocommerce_init', function () {
	if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
		return;
	}
	woocommerce_register_additional_checkout_field( array(
		'id'            => 'ivancovs/terms',
		'label'         => 'Esmu izlasījis Distances līgumu (lietošanas noteikumus) un piekrītu / Я прочитал договор (условия использования) и согласен / I have read and agree to the Distance contract (terms of use)',
		'location'      => 'order',
		'type'          => 'checkbox',
		'required'      => true,
		'error_message' => 'Lai turpinātu, piekrītiet Distances līgumam / Согласитесь с договором / Please agree to the Distance contract',
	) );
} );

/** Ссылка на договор над кнопкой «Оплатить» в блочном оформлении. */
add_filter( 'render_block', function ( $html, $block ) {
	if ( 'woocommerce/checkout-actions-block' !== $block['blockName'] ) {
		return $html;
	}
	$url = ivs_terms_url();
	if ( '' === $url ) {
		return $html;
	}
	$note = '<p class="ivs-terms-link"><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( ivs_t( 'Lasīt Distances līgumu (lietošanas noteikumus)', 'Читать договор (условия использования)', 'Read the Distance contract (terms of use)' ) ) . '</a></p>';
	return $note . $html;
}, 10, 2 );

/** Открытие сайта: один раз выключаем режим «скоро откроется» WooCommerce. */
add_action( 'init', function () {
	if ( get_option( 'ivs_site_live_v1' ) ) {
		return;
	}
	update_option( 'woocommerce_coming_soon', 'no' );
	update_option( 'ivs_site_live_v1', 1 );
}, 40 );
