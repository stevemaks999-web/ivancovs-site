<?php
/**
 * Письма клиенту: заказ принят / подтверждён, приборы возвращены, напоминание за сутки.
 * Тексты утверждены Никитой (docs/emails-3lang.md). Язык письма = язык страницы, откуда клиент добавил прибор.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- Язык заказа ---------- */

/** Язык страницы, с которой прибор добавили в корзину (по адресу /ru/..., /en/...). */
add_filter( 'woocommerce_add_cart_item_data', function ( $data ) {
	// Поле _wp_http_referer в форме всегда латышский адрес, поэтому смотрим на заголовок Referer: это страница прибора.
	$ref  = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : wp_get_referer(); // phpcs:ignore
	$path = $ref ? (string) wp_parse_url( $ref, PHP_URL_PATH ) : '';
	$lang = 'lv';
	if ( 0 === strpos( $path, '/ru/' ) ) {
		$lang = 'ru';
	} elseif ( 0 === strpos( $path, '/en/' ) ) {
		$lang = 'en';
	}
	$data['ivs_lang'] = $lang;
	return $data;
}, 20 );

/** Язык заказа записываем при оформлении (блочное оформление: язык берём из корзины). */
add_action( 'woocommerce_store_api_checkout_order_processed', function ( $order ) {
	$lang = 'lv';
	if ( function_exists( 'WC' ) && WC()->cart ) {
		foreach ( WC()->cart->get_cart() as $ci ) {
			if ( ! empty( $ci['ivs_lang'] ) ) {
				$lang = $ci['ivs_lang'];
				break;
			}
		}
	}
	$order->update_meta_data( '_ivs_lang', in_array( $lang, array( 'ru', 'en' ), true ) ? $lang : 'lv' );
	$order->save();
}, 5 );

function ivs_order_lang( $order ) {
	$l = $order->get_meta( '_ivs_lang' );
	return in_array( $l, array( 'ru', 'en' ), true ) ? $l : 'lv';
}

/** Текст на языке заказа. */
function ivs_em( $order, $lv, $ru, $en ) {
	$l = ivs_order_lang( $order );
	return 'ru' === $l ? $ru : ( 'en' === $l ? $en : $lv );
}

function ivs_em_locale( $order ) {
	$l = ivs_order_lang( $order );
	return 'ru' === $l ? 'ru_RU' : ( 'en' === $l ? 'en_US' : 'lv' );
}

function ivs_em_date( $ymd ) {
	return $ymd ? gmdate( 'd.m.Y', strtotime( $ymd . ' UTC' ) ) : '';
}

/* ---------- Данные заказа для писем ---------- */

function ivs_em_data( $order ) {
	list( $rent, $deposit ) = ivs_order_lines( $order );
	$total = (float) $order->get_total();
	$pm    = $order->get_payment_method();
	if ( 'cod' === $pm ) {
		$kind = 'cod';
	} elseif ( 'bacs' === $pm ) {
		$kind = $order->is_paid() ? 'paid' : 'bacs';
	} else {
		$kind = 'paid';
	}
	return array(
		'rent'    => $rent,
		'deposit' => $deposit,
		'rentsum' => max( 0, $total - $deposit ),
		'kind'    => $kind,
	);
}

function ivs_em_pay_label( $order, $kind ) {
	if ( 'cod' === $kind ) {
		return ivs_em( $order, 'Skaidrā naudā saņemot', 'Наличными при получении', 'Cash on pickup' );
	}
	if ( 'paid' === $kind ) {
		return ivs_em( $order, 'Apmaksāts tiešsaistē', 'Оплачено онлайн', 'Paid online' );
	}
	return ivs_em( $order, 'Bankas pārskaitījums', 'Банковский перевод', 'Bank transfer' );
}

/** Подпись и контакты в конце письма. */
function ivs_em_contacts( $order, $intro_lv, $intro_ru, $intro_en, $bye_lv, $bye_ru, $bye_en ) {
	$wa  = 'https://wa.me/' . IVS_PHONE_WA;
	$tel = 'tel:+' . IVS_PHONE_WA;
	$num = '+371 25 553 244';
	$h   = '<p>' . esc_html( ivs_em( $order, $intro_lv, $intro_ru, $intro_en ) ) . '<br>';
	$h  .= '💬 WhatsApp: <a href="' . esc_url( $wa ) . '">' . esc_html( $num ) . '</a><br>';
	$h  .= '📞 ' . esc_html( ivs_em( $order, 'Tālrunis', 'Телефон', 'Phone' ) ) . ': <a href="' . esc_url( $tel ) . '">' . esc_html( $num ) . '</a></p>';
	$h  .= '<p>' . esc_html( ivs_em( $order, $bye_lv, $bye_ru, $bye_en ) ) . '<br><strong>' . esc_html( ivs_em( $order, 'Ivancovs komanda', 'Команда Ivancovs', 'Ivancovs Team' ) ) . '</strong><br>';
	$h  .= '<a href="https://ivancovs.com">ivancovs.com</a></p>';
	return $h;
}

/* ---------- Письмо о заказе (принят / подтверждён) ---------- */

/** Тема и заголовок письма. $kind: confirmed | hold */
function ivs_em_order_subject( $order, $kind ) {
	$d    = ivs_em_data( $order );
	$line = $d['rent'] ? $d['rent'][0] : array();
	$n    = $order->get_order_number();
	$date = ivs_em_date( $line['from'] ?? '' );
	$time = $line['t_from'] ?? '';
	if ( 'confirmed' === $kind ) {
		return ivs_em(
			$order,
			sprintf( 'Pasūtījums Nr. %s apstiprināts: gaidām Jūs %s plkst. %s', $n, $date, $time ),
			sprintf( 'Заказ №%s подтверждён: ждём вас %s к %s', $n, $date, $time ),
			sprintf( 'Order #%s confirmed: see you on %s at %s', $n, $date, $time )
		);
	}
	return ivs_em(
		$order,
		sprintf( 'Pasūtījums Nr. %s pieņemts, drīz apstiprināsim', $n ),
		sprintf( 'Заказ №%s принят, скоро подтвердим', $n ),
		sprintf( 'Order #%s received, we will confirm it shortly', $n )
	);
}

/** Текст письма до таблицы заказа и после неё. Выводит HTML. */
function ivs_em_order_intro( $order, $kind ) {
	$d    = ivs_em_data( $order );
	$name = $order->get_billing_first_name();
	echo '<p>' . esc_html( ivs_em( $order, 'Labdien, ' . $name . '!', 'Здравствуйте, ' . $name . '!', 'Hello ' . $name . ',' ) ) . '</p>';
	if ( 'confirmed' === $kind ) {
		$t = ivs_em(
			$order,
			'Jūsu pasūtījums ir pieņemts, un aprīkojums ir rezervēts tieši Jūsu izvēlētajā laikā.',
			'Ваш заказ принят, прибор(ы) забронированы точно под ваше время.',
			'Your order is confirmed, and your equipment has been reserved for your exact schedule.'
		);
	} else {
		$t = ivs_em(
			$order,
			'Jūsu pasūtījums ir pieņemts, un aprīkojums ir rezervēts izvēlētajām dienām. Drīz to apstiprināsim.',
			'Ваш заказ принят, прибор(ы) забронированы на выбранные даты. Скоро мы его подтвердим.',
			'We have received your order and reserved the equipment for your dates. We will confirm it shortly.'
		);
	}
	echo '<p>' . esc_html( $t ) . '</p>';
	echo '<p><strong>📅 ' . esc_html( ivs_em( $order, 'Nomas grafiks', 'График аренды', 'Rental schedule' ) ) . ':</strong></p><ul>';
	$many = count( $d['rent'] ) > 1;
	foreach ( $d['rent'] as $r ) {
		$pre = $many ? '<strong>' . esc_html( $r['name'] ) . '</strong>: ' : '';
		echo '<li>' . $pre . esc_html( ivs_em( $order, 'Saņemšana', 'Получение', 'Pickup' ) . ': ' . ivs_em_date( $r['from'] ) . ' ' . ivs_em( $order, 'plkst.', 'к', 'at' ) . ' ' . $r['t_from'] ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<br>' . esc_html( ivs_em( $order, 'Atgriešana', 'Возврат', 'Return' ) . ': ' . ivs_em_date( $r['to'] ) . ' ' . ivs_em( $order, 'līdz plkst.', 'до', 'by' ) . ' ' . $r['t_to'] ) . '</li>';
	}
	echo '<li>' . esc_html( ivs_em( $order, 'Saņēmējs', 'Прибор заберёт', 'Pickup person' ) . ': ' . ivs_book_pickup_name( $order ) ) . '</li>';
	echo '<li>' . esc_html( ivs_em( $order, 'Apmaksa', 'Оплата', 'Payment' ) . ': ' . ivs_em_pay_label( $order, $d['kind'] ) ) . '</li>';
	echo '<li>' . esc_html( ivs_em( $order, 'Rēķins', 'Счёт', 'Invoice' ) ) . ': <a href="' . esc_url( ivs_doc_url( $order, 'invoice' ) ) . '">' . esc_html( ivs_em( $order, 'atvērt rēķinu (PVN 21%)', 'открыть счёт (PVN 21%)', 'open invoice (21% VAT)' ) ) . '</a></li></ul>';
}

function ivs_em_order_notes( $order, $kind ) {
	$d    = ivs_em_data( $order );
	$line = $d['rent'] ? $d['rent'][0] : array();
	$time = $line['t_from'] ?? '';
	$dep  = ivs_money( $d['deposit'] );
	$rent = ivs_money( $d['rentsum'] );
	echo '<hr><p><strong>' . esc_html( ivs_em( $order, 'Svarīgi pirms ierašanās:', 'Что важно перед визитом:', 'Important details before your visit:' ) ) . '</strong></p><ol>';
	echo '<li>' . esc_html( ivs_em(
		$order,
		'Ierašanās laiks: mēs sagatavojam un pārbaudām komplektu tieši uz Jūsu norādīto laiku, plkst. ' . $time . '. Ja plāni mainās, kavējaties vai ierašanās iekrīt brīvdienā, lūdzu, uzrakstiet mums WhatsApp. Brīvdienās izsniedzam tikai pēc vienošanās ar Dmitriju.',
		'К какому времени подходить: мы готовим и проверяем комплект к выбранному вами времени, ' . $time . '. Если планы изменились, вы задерживаетесь или визит выпадает на выходной, напишите нам в WhatsApp. В выходные выдаём только по договорённости с Дмитрием.',
		'Arrival time: we test and prepare the equipment for your scheduled time, ' . $time . '. If your plans change, you are running late, or your visit falls on a weekend, please message us on WhatsApp. On weekends we hand out equipment only by arrangement with Dmitrijs.'
	) ) . '</li>';
	echo '<li>' . esc_html( ivs_em(
		$order,
		'Dokumenti: paņemiet līdzi personu apliecinoša dokumenta oriģinālu (pasi, ID karti vai vadītāja apliecību). Aprīkojumu izsniedzam personīgi pasūtījumā norādītajam cilvēkam.',
		'Что взять с собой: оригинал документа (паспорт, ID-карту или права). Выдаём оборудование лично в руки человеку, указанному в заказе.',
		'What to bring: an original ID (passport, ID card or driving licence). We release equipment only to the person named in the order.'
	) ) . '</li>';

	if ( 'cod' === $d['kind'] ) {
		$pay = $d['deposit'] > 0
			? ivs_em(
				$order,
				'Apmaksa un drošības nauda: saņemot jāmaksā ' . $rent . ' (noma) + ' . $dep . ' (drošības nauda). Būsim pateicīgi, ja sagatavosiet precīzu naudu.',
				'Оплата и залог: к оплате при получении ' . $rent . ' (аренда) + ' . $dep . ' (залог). Будем благодарны, если подготовите сумму без сдачи.',
				'Payment and deposit: due on pickup, ' . $rent . ' (rental) + ' . $dep . ' (deposit). Exact change is greatly appreciated.'
			)
			: ivs_em(
				$order,
				'Apmaksa: saņemot jāmaksā ' . $rent . ' (noma). Būsim pateicīgi, ja sagatavosiet precīzu naudu.',
				'Оплата: к оплате при получении ' . $rent . ' (аренда). Будем благодарны, если подготовите сумму без сдачи.',
				'Payment: due on pickup, ' . $rent . ' (rental). Exact change is greatly appreciated.'
			);
	} elseif ( 'paid' === $d['kind'] ) {
		$pay = $d['deposit'] > 0
			? ivs_em(
				$order,
				'Apmaksa un drošības nauda: noma un drošības nauda ' . $dep . ' jau ir apmaksāta, uz vietas papildus nekas nav jāmaksā.',
				'Оплата и залог: аренда и залог ' . $dep . ' уже оплачены, на месте доплачивать не нужно.',
				'Payment and deposit: the rental and the deposit of ' . $dep . ' are paid, nothing extra to pay on site.'
			)
			: ivs_em( $order, 'Apmaksa: noma jau ir apmaksāta.', 'Оплата: аренда уже оплачена.', 'Payment: the rental is already paid.' );
	} else {
		$pay = ivs_em(
			$order,
			'Apmaksa: ar bankas pārskaitījumu. Instrumentu izsniedzam pēc tam, kad nauda ir ieskaitīta mūsu kontā.',
			'Оплата: банковским переводом. Прибор выдаём после того, как деньги поступят на наш счёт.',
			'Payment: by bank transfer. We hand over the equipment once the money has reached our account.'
		);
	}
	echo '<li>' . esc_html( $pay ) . '</li></ol>';

	if ( $d['deposit'] > 0 ) {
		if ( 'cod' === $d['kind'] ) {
			$ref = ivs_em(
				$order,
				'Drošības naudu atdodam skaidrā naudā uzreiz pēc instrumenta atgriešanas un pārbaudes.',
				'Залог возвращаем наличными сразу после возврата и проверки прибора.',
				'The deposit is returned in cash right after the equipment is returned and checked.'
			);
		} else {
			$ref = ivs_em(
				$order,
				'Drošības naudu atgriežam uz karti pēc instrumenta atgriešanas un pārbaudes, ne vēlāk kā 14 dienu laikā.',
				'Залог возвращаем на карту после возврата и проверки прибора, не позднее 14 дней.',
				'The deposit is returned to your card after the equipment is returned and checked, within 14 days at most.'
			);
		}
		echo '<p><em>' . esc_html( $ref ) . '</em></p>';
	}
	echo '<hr>';
	echo ivs_em_contacts( // phpcs:ignore WordPress.Security.EscapeOutput
		$order,
		'Ja nepieciešams precizēt laiku vai ir jautājumi:',
		'Если нужно скорректировать время или есть вопросы:',
		'If you need to adjust your time or have any questions:',
		'confirmed' === $kind ? 'Gaidīsim Jūs norādītajā laikā,' : 'Līdz tikšanās reizei,',
		'confirmed' === $kind ? 'Ждём вас к назначенному времени,' : 'До встречи,',
		'confirmed' === $kind ? 'See you at the scheduled time,' : 'See you soon,'
	);
}

/** Тема и заголовок для двух писем. */
foreach ( array(
	'customer_processing_order' => 'confirmed',
	'customer_on_hold_order'    => 'hold',
) as $ivs_em_id => $ivs_em_kind ) {
	$ivs_cb = function ( $text, $order ) use ( $ivs_em_kind ) {
		return ( $order instanceof WC_Order && ivs_order_has_booking( $order ) ) ? ivs_em_order_subject( $order, $ivs_em_kind ) : $text;
	};
	add_filter( 'woocommerce_email_subject_' . $ivs_em_id, $ivs_cb, 10, 2 );
	add_filter( 'woocommerce_email_heading_' . $ivs_em_id, $ivs_cb, 10, 2 );
}

// Имя того, кто заберёт, уже в письме выше: стандартную строку клиенту не дублируем.
add_filter( 'woocommerce_email_order_meta_fields', function ( $fields, $sent_to_admin ) {
	return $sent_to_admin ? $fields : array();
}, 99, 2 );

// Стандартную строку наличной оплаты в письмо не берём: всё сказано в тексте письма, на языке заказа.
add_action( 'woocommerce_init', function () {
	$gw = WC()->payment_gateways()->payment_gateways();
	if ( isset( $gw['cod'] ) ) {
		remove_action( 'woocommerce_email_before_order_table', array( $gw['cod'], 'email_instructions' ), 10 );
	}
}, 99 );

/** Внутри письма: способ оплаты на языке заказа, служебные строки позиции (даты) не повторяем, они уже в графике. */
function ivs_em_enter( $order ) {
	$GLOBALS['ivs_em_order'] = $order;
	add_filter( 'woocommerce_order_get_payment_method_title', 'ivs_em_pay_title_filter' );
	add_filter( 'woocommerce_order_item_get_formatted_meta_data', '__return_empty_array' );
}

function ivs_em_leave() {
	remove_filter( 'woocommerce_order_get_payment_method_title', 'ivs_em_pay_title_filter' );
	remove_filter( 'woocommerce_order_item_get_formatted_meta_data', '__return_empty_array' );
	unset( $GLOBALS['ivs_em_order'] );
}

function ivs_em_pay_title_filter( $title ) {
	$o = $GLOBALS['ivs_em_order'] ?? null;
	return $o ? ivs_em_pay_label( $o, ivs_em_data( $o )['kind'] ) : $title;
}

/* ---------- Письмо после возврата ---------- */

function ivs_send_return_email( $order ) {
	$to = $order->get_billing_email();
	if ( ! $to ) {
		return;
	}
	$d    = ivs_em_data( $order );
	$name = $order->get_billing_first_name();
	$n    = $order->get_order_number();
	$dep  = ivs_money( $d['deposit'] );
	$h    = '<p>' . esc_html( ivs_em( $order, 'Labdien, ' . $name . '!', 'Здравствуйте, ' . $name . '!', 'Hello ' . $name . ',' ) ) . '</p>';
	$h   .= '<p>' . esc_html( ivs_em(
		$order,
		'Aprīkojums ir atgriezts. Mēs visu pārbaudījām un notestējām, tehnika ir kārtībā. Paldies par precizitāti un saudzīgu attieksmi pret aprīkojumu!',
		'Оборудование вернулось на базу. Мы всё проверили и протестировали, техника в порядке. Спасибо за аккуратность и пунктуальность!',
		'The equipment has been returned. Everything was inspected and tested, and all tools are in good condition. Thank you for your careful handling!'
	) ) . '</p>';
	if ( $d['deposit'] > 0 ) {
		if ( 'cod' === $d['kind'] ) {
			$t = ivs_em(
				$order,
				'Par drošības naudu (' . $dep . '): drošības nauda tika atgriezta Jums skaidrā naudā uz vietas pārbaudes laikā.',
				'По поводу залога (' . $dep . '): залог вернули вам наличными на руки при осмотре на месте.',
				'About your security deposit (' . $dep . '): it was returned to you in cash during the on-site inspection.'
			);
		} else {
			$t = ivs_em(
				$order,
				'Par drošības naudu (' . $dep . '): atgriešana jau ir nosūtīta uz Jūsu karti. Parasti tas aizņem 1–3 darba dienas atkarībā no bankas, ne ilgāk kā 14 dienas.',
				'По поводу залога (' . $dep . '): возврат уже отправлен на вашу карту. Зачисление обычно занимает 1–3 рабочих дня в зависимости от банка, не дольше 14 дней.',
				'About your security deposit (' . $dep . '): the refund has been sent to your card. It usually takes 1–3 business days depending on your bank, 14 days at most.'
			);
		}
		$h .= '<p>' . esc_html( $t ) . '</p>';
	}
	$h .= '<p>' . esc_html( ivs_em(
		$order,
		'Kad darbiem atkal būs nepieciešams uzticams instruments, vērsieties pie mums, labprāt palīdzēsim!',
		'Когда для работы снова понадобится надёжный инструмент, заглядывайте, будем рады помочь!',
		'Whenever you need reliable professional tools again, we will be glad to help!'
	) ) . '</p>';
	$h .= ivs_em_contacts( $order, 'Ja rodas jautājumi:', 'По любым вопросам:', 'If you have any questions:', 'Veiksmīgu darbu,', 'Продуктивной работы,', 'Best regards,' );
	$subject = ivs_em(
		$order,
		'Instrumenti pieņemti, pasūtījums Nr. ' . $n . ' slēgts. Paldies!',
		'Приборы приняты, заказ №' . $n . ' закрыт. Спасибо!',
		'Equipment returned, order #' . $n . ' completed. Thank you!'
	);
	$head = ivs_em( $order, 'Paldies!', 'Спасибо!', 'Thank you!' );
	$mailer = WC()->mailer();
	$mailer->send( $to, $subject, $mailer->wrap_message( $head, $h ) );
}

/* ---------- Напоминание за сутки до возврата ---------- */

add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'ivs_return_reminder' ) ) {
		$first = strtotime( 'tomorrow 10:00', current_time( 'timestamp' ) ) - (int) ( current_time( 'timestamp' ) - time() ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
		wp_schedule_event( $first, 'daily', 'ivs_return_reminder' );
	}
} );

add_action( 'ivs_return_reminder', function () {
	$tomorrow = wp_date( 'Y-m-d', time() + DAY_IN_SECONDS );
	$orders   = wc_get_orders( array(
		'status' => 'processing',
		'limit'  => 200,
	) );
	foreach ( $orders as $order ) {
		if ( ! $order->get_meta( '_ivs_issued_at' ) || $order->get_meta( '_ivs_returned_at' ) || $order->get_meta( '_ivs_reminder_sent' ) || ! $order->get_billing_email() ) {
			continue;
		}
		$due = array();
		foreach ( ivs_order_lines( $order )[0] as $r ) {
			if ( $r['to'] === $tomorrow ) {
				$due[] = $r;
			}
		}
		if ( ! $due ) {
			continue;
		}
		ivs_send_reminder_email( $order, $due );
		$order->update_meta_data( '_ivs_reminder_sent', wp_date( 'd.m.Y H:i' ) );
		$order->save();
	}
} );

function ivs_send_reminder_email( $order, $due ) {
	$name  = $order->get_billing_first_name();
	$date  = ivs_em_date( $due[0]['to'] );
	$time  = $due[0]['t_to'];
	$names = implode( ', ', wp_list_pluck( $due, 'name' ) );
	$wa    = 'https://wa.me/' . IVS_PHONE_WA;
	$num   = '+371 25 553 244';
	$h     = '<p>' . esc_html( ivs_em( $order, 'Labdien, ' . $name . '!', 'Здравствуйте, ' . $name . '!', 'Hello ' . $name . ',' ) ) . '</p>';
	$h    .= '<p>' . esc_html( ivs_em(
		$order,
		'Atgādinām, ka rīt, ' . $date . ' līdz plkst. ' . $time . ', beidzas aprīkojuma nomas termiņš: ' . $names . '.',
		'Напоминаем, что завтра, ' . $date . ' до ' . $time . ', заканчивается срок аренды прибора: ' . $names . '.',
		'This is a friendly reminder that the rental period for ' . $names . ' ends tomorrow, ' . $date . ' by ' . $time . '.'
	) ) . '</p>';
	$h    .= '<p>' . esc_html( ivs_em(
		$order,
		'Ja paspējat laikā, vienkārši nogādājiet instrumentu pie mums. Ja darbi ir iekavējušies un nepieciešams pagarināt nomu, uzrakstiet mums WhatsApp. Ja instruments ir pieejams nākamajām dienām, pagarināsim pasūtījumu.',
		'Если успеваете в срок, просто привозите прибор к нам. Если работы затянулись и нужно продлить аренду, напишите нам в WhatsApp. Если прибор свободен на следующие дни, продлим заказ.',
		'If everything is on schedule, simply bring the equipment back to us. If your work took longer and you need to extend the rental, message us on WhatsApp. If the equipment has no upcoming reservations, we will be happy to extend your order.'
	) ) . ' <a href="' . esc_url( $wa ) . '">WhatsApp ' . esc_html( $num ) . '</a></p>';
	$h    .= '<p>' . esc_html( ivs_em( $order, 'Jauku dienu,', 'Хорошего дня,', 'Have a great day,' ) ) . '<br><strong>' . esc_html( ivs_em( $order, 'Ivancovs komanda', 'Команда Ivancovs', 'Ivancovs Team' ) ) . '</strong><br><a href="https://ivancovs.com">ivancovs.com</a></p>';
	$subject = ivs_em(
		$order,
		'Atgādinājums: rīt (' . $date . ') līdz plkst. ' . $time . ' gaidām instrumenta atgriešanu',
		'Напоминание: завтра (' . $date . ') до ' . $time . ' ждём возврат прибора',
		'Reminder: return scheduled for tomorrow (' . $date . ') by ' . $time
	);
	$mailer = WC()->mailer();
	$mailer->send( $order->get_billing_email(), $subject, $mailer->wrap_message( ivs_em( $order, 'Atgādinājums', 'Напоминание', 'Reminder' ), $h ) );
}

/* ---------- Просмотр писем без отправки (только для администратора) ---------- */

add_action( 'rest_api_init', function () {
	register_rest_route( 'ivs/v1', '/mail-preview/(?P<id>\d+)', array(
		'methods'             => 'GET',
		'permission_callback' => function () {
			return current_user_can( 'manage_woocommerce' );
		},
		'callback'            => function ( $req ) {
			$order = wc_get_order( (int) $req['id'] );
			if ( ! $order ) {
				return new WP_Error( 'no_order', 'Нет заказа', array( 'status' => 404 ) );
			}
			$type = sanitize_key( $req->get_param( 'type' ) );
			$mailer = WC()->mailer();
			if ( 'return' === $type || 'reminder' === $type ) {
				return array( 'note' => 'Эти письма собираются при отправке, смотрите тексты в коде.' );
			}
			$map = array(
				'confirmed' => 'WC_Email_Customer_Processing_Order',
				'hold'      => 'WC_Email_Customer_On_Hold_Order',
			);
			if ( ! isset( $map[ $type ] ) || ! isset( $mailer->emails[ $map[ $type ] ] ) ) {
				return new WP_Error( 'bad_type', 'type=confirmed|hold', array( 'status' => 400 ) );
			}
			$email         = $mailer->emails[ $map[ $type ] ];
			$email->object = $order;
			$email->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );
			$email->placeholders['{order_number}'] = $order->get_order_number();
			return array(
				'subject' => $email->get_subject(),
				'html'    => $email->get_content(),
			);
		},
	) );
} );
