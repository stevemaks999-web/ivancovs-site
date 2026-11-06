<?php
/**
 * Главная: личный бренд Дмитрия и аренда приборов. Тексты с действующего сайта.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

$rent = ivs_url( '/noma/' );
if ( function_exists( 'wc_get_page_permalink' ) && wc_get_page_permalink( 'shop' ) ) {
	$rent = esc_url( wc_get_page_permalink( 'shop' ) );
}
$portrait = get_theme_file_uri( 'assets/dmitrijs.jpg' );
$has_portrait = file_exists( get_theme_file_path( 'assets/dmitrijs.jpg' ) );
?>
<section class="hero"><div class="wrap">
	<div class="who">
		<div class="ph av"><?php if ( $has_portrait ) : ?><img src="<?php echo esc_url( $portrait ); ?>" alt="Dmitrijs Ivancovs"><?php endif; ?></div>
		<div>
			<h1><?php ivs_e( 'Ventilācijas sistēmu regulēšana, auditi un ekspertīzes', 'Регулировка, аудиты и экспертизы вентиляционных систем', 'Ventilation balancing, audits and expert assessments' ); ?></h1>
			<p><?php ivs_e( 'Mg.sc.ing. Dmitrijs Ivancovs · 25 gadu pieredze · apmācības un konsultācijas · tekstilaudumu gaisa sadalītāju pārstāvis Latvijā', 'Mg.sc.ing. Дмитрий Иванцов · 25 лет опыта · обучение и консультации · представитель в Латвии производителя текстильных воздухораспределителей', 'Mg.sc.ing. Dmitrijs Ivancovs · 25 years of experience · training and consulting · Latvian representative of a textile air diffuser manufacturer' ); ?></p>
		</div>
	</div>
	<div class="duo">
		<a class="duocard" href="#kontakti"><b><?php ivs_e( 'Pakalpojumi', 'Услуги', 'Services' ); ?></b><span><?php ivs_e( 'Regulēšana, auditi, ekspertīzes, apmācības.', 'Регулировка, аудиты, экспертизы, обучение.', 'Balancing, audits, expert assessments, training.' ); ?></span><i><?php ivs_e( 'Nosūtīt ziņu →', 'Написать →', 'Send a message →' ); ?></i></a>
		<a class="duocard" href="<?php echo $rent; // phpcs:ignore ?>"><b><?php ivs_e( 'Mērinstrumentu noma', 'Аренда приборов', 'Instrument rental' ); ?></b><span><?php ivs_e( 'Mani darba mērinstrumenti: rezervācija un apmaksa online.', 'Мои рабочие измерительные приборы: бронь и оплата онлайн.', 'My working measuring instruments: book and pay online.' ); ?></span><i><?php ivs_e( 'Izvēlēties →', 'Выбрать прибор →', 'Choose →' ); ?></i></a>
	</div>
	<a class="newsline" href="<?php echo ivs_url( '/eksperta-tehniskais-atbalsts-hvac-sistemu-ekspluatacijai/' ); // phpcs:ignore ?>"><?php ivs_e( 'Jaunums: Eksperta tehniskais atbalsts HVAC sistēmu ekspluatācijai →', 'Новое: экспертная техническая поддержка эксплуатации HVAC-систем →', 'New: expert technical support for HVAC system operation →' ); ?></a>
</div></section>

<section class="blk"><div class="wrap">
	<h2><?php ivs_e( 'Pakalpojumi', 'Услуги', 'Services' ); ?></h2>
	<div class="grid g4">
		<div class="card cat"><b><?php ivs_e( 'Regulēšana', 'Регулировка', 'Balancing' ); ?></b></div>
		<div class="card cat"><b><?php ivs_e( 'Auditi', 'Аудиты', 'Audits' ); ?></b></div>
		<div class="card cat"><b><?php ivs_e( 'Ekspertīzes', 'Экспертизы', 'Expert assessments' ); ?></b></div>
		<a class="card cat" href="<?php echo ivs_url( '/apmacibas/' ); // phpcs:ignore ?>"><b><?php ivs_e( 'Apmācības', 'Обучение', 'Training' ); ?></b></a>
	</div>
	<p class="mut" style="margin:14px 0 0"><?php ivs_e( 'Arī: būvuzraudzība, projektēšana, montāža.', 'Также: строительный надзор, проектирование, монтаж.', 'Also: construction supervision, design, installation.' ); ?></p>
</div></section>

<section class="blk" style="padding-top:0"><div class="wrap">
	<div class="card about">
		<div class="ph av2"><?php if ( $has_portrait ) : ?><img src="<?php echo esc_url( $portrait ); ?>" alt=""><?php endif; ?></div>
		<div>
			<h2 style="margin-bottom:8px"><?php ivs_e( 'Kompetence', 'Компетенция', 'Competence' ); ?></h2>
			<p class="mut" style="font-size:15px;margin:0 0 6px">Mg.sc.ing. Dmitrijs Ivancovs</p>
			<p class="mut" style="font-size:14px;margin:0"><?php ivs_e( 'Būvekspertīzes sert.nr.: 6-00205', 'Сертификат строительной экспертизы № 6-00205', 'Construction expert certificate no. 6-00205' ); ?><br><?php ivs_e( 'Būvuzraudzības sert.nr.: 4-06068', 'Сертификат строительного надзора № 4-06068', 'Construction supervision certificate no. 4-06068' ); ?><br><?php ivs_e( 'Būvkomersanta reg.nr.: 18743', 'Рег. № строительной компании 18743', 'Construction company reg. no. 18743' ); ?></p>
		</div>
	</div>
	<p style="font-size:17px;margin:18px 0 0"><b><?php ivs_e( 'Misija.', 'Миссия.', 'Mission.' ); ?></b> <span class="mut"><?php ivs_e( 'Ja ventilācijas sistēma ir uzstādīta, tai ir jādarbojas nevainojami. Iztērētajiem resursiem uzstādīšanai un ekspluatācijai jāatmaksājas ar komfortu.', 'Если вентиляционная система установлена, она должна работать безупречно. Потраченные на монтаж и эксплуатацию ресурсы должны окупаться комфортом.', 'If a ventilation system is installed, it must work flawlessly. The resources spent on installation and operation must pay off in comfort.' ); ?></span></p>
</div></section>

<section class="blk" style="padding-top:0"><div class="wrap">
	<h2><?php ivs_e( 'Mērinstrumentu noma Rīgā', 'Аренда приборов в Риге', 'Instrument rental in Riga' ); ?></h2>
	<div class="grid g4">
	<?php
	$cats = function_exists( 'get_terms' ) ? get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => false, 'exclude' => array( get_option( 'default_product_cat' ) ) ) ) : array();
	if ( ! is_wp_error( $cats ) ) {
		foreach ( $cats as $c ) {
			if ( 'komplekti' === $c->slug ) {
				continue;
			}
			printf( '<a class="card cat" href="%s"><b>%s</b></a>', esc_url( get_term_link( $c ) ), esc_html( $c->name ) ); // phpcs:ignore
		}
	}
	?>
	</div>
	<p style="margin:16px 0 0"><a class="btn" href="<?php echo $rent; // phpcs:ignore ?>"><?php ivs_e( 'Visi instrumenti', 'Все приборы', 'All instruments' ); ?></a></p>
</div></section>

<section class="blk" style="padding-top:0"><div class="wrap">
	<h2><?php ivs_e( 'Kā tas darbojas', 'Как это работает', 'How it works' ); ?></h2>
	<div class="grid g4 steps">
		<div class="card"><b><?php ivs_e( 'Izvēlieties', 'Выберите', 'Choose' ); ?></b><p class="mut"><?php ivs_e( 'Instruments vai gatavs komplekts', 'Прибор или готовый комплект', 'An instrument or a ready kit' ); ?></p></div>
		<div class="card"><b><?php ivs_e( 'Rezervējiet', 'Забронируйте', 'Book' ); ?></b><p class="mut"><?php ivs_e( 'Datumi 9:00–18:00, bez dubultām rezervācijām', 'Даты 9:00–18:00, без двойных броней', 'Dates 9:00–18:00, no double bookings' ); ?></p></div>
		<div class="card"><b><?php ivs_e( 'Samaksājiet', 'Оплатите', 'Pay' ); ?></b><p class="mut"><?php ivs_e( 'Karte vai banka, drošības nauda instrumentiem no 500 €', 'Карта или банк, залог для приборов от 500 €', 'Card or bank, deposit for instruments from 500 €' ); ?></p></div>
		<div class="card"><b><?php ivs_e( 'Saņemiet', 'Получите', 'Collect' ); ?></b><p class="mut"><?php ivs_e( 'Ar dokumentu, akts un rēķins e-pastā', 'С документом, акт и счёт на почту', 'With an ID, handover act and invoice by email' ); ?></p></div>
	</div>
</div></section>

<section class="blk" style="padding-top:0"><div class="wrap">
	<h2><?php ivs_e( 'Apmācības, noderīgi, blogs', 'Обучение, полезное, блог', 'Training, useful, blog' ); ?></h2>
	<div class="grid g3">
		<a class="card cat" href="<?php echo ivs_url( '/apmacibas/' ); // phpcs:ignore ?>"><b><?php ivs_e( 'Apmācības', 'Обучение', 'Training' ); ?></b></a>
		<a class="card cat" href="<?php echo ivs_url( '/noderigi/' ); // phpcs:ignore ?>"><b><?php ivs_e( 'Noderīga informācija', 'Полезная информация', 'Useful information' ); ?></b></a>
		<a class="card cat" href="<?php echo ivs_url( '/blog-2/' ); // phpcs:ignore ?>"><b><?php ivs_e( 'Blogs', 'Блог', 'Blog' ); ?></b></a>
	</div>
</div></section>

<section class="blk" style="padding-top:0"><div class="wrap">
	<h2><?php ivs_e( 'Atsauksmes', 'Отзывы', 'Testimonials' ); ?></h2>
	<div class="grid g3">
	<?php foreach ( ivs_testimonials() as $tm ) : $lg = ivs_lang(); ?>
		<div class="card cat">
			<?php foreach ( $tm['text'][ $lg ] as $para ) : ?><p class="mut" style="margin:0 0 10px"><?php echo esc_html( $para ); ?></p><?php endforeach; ?>
			<b style="margin-top:6px"><?php echo esc_html( $tm['name'] ); ?></b>
			<span class="mut"><?php echo esc_html( $tm['role'][ $lg ] ); ?></span>
		</div>
	<?php endforeach; ?>
	</div>
</div></section>

<section class="blk" style="padding-top:0"><div class="wrap">
	<h2><?php ivs_e( 'Darba pieredze', 'Опыт работы', 'Work experience' ); ?></h2>
	<div class="grid">
	<?php foreach ( ivs_objects() as $o ) : ?>
		<div class="card cat"><span class="mut"><?php echo esc_html( $o[0] ); ?></span><b><?php echo esc_html( $o[1] ); ?></b><?php if ( $o[2] ) : ?><span class="mut"><?php echo esc_html( $o[2] ); ?></span><?php endif; ?></div>
	<?php endforeach; ?>
	</div>
</div></section>

<section class="blk" style="padding-top:0" id="kontakti"><div class="wrap">
	<h2><?php ivs_e( 'Kontakti', 'Контакты', 'Contacts' ); ?></h2>
	<div class="card cat">
		<b>SIA “Delumix”</b>
		<span class="mut">Lastādijas iela 32, Rīga, LV-1050 · <a href="tel:+37125553244"><?php echo esc_html( IVS_PHONE ); ?></a> · <a href="mailto:<?php echo esc_attr( IVS_EMAIL ); ?>"><?php echo esc_html( IVS_EMAIL ); ?></a></span>
		<div class="soc" style="margin-top:10px">
			<a href="https://www.facebook.com/d.ivancovs/" rel="noopener"><span>Facebook</span></a>
			<a href="https://www.instagram.com/d.ivancovs/" rel="noopener"><span>Instagram</span></a>
			<a href="https://www.linkedin.com/in/dmitrijs-ivancovs-099863181/" rel="noopener"><span>LinkedIn</span></a>
			<a href="https://www.youtube.com/@dmitrijsivancovs7397" rel="noopener"><span>YouTube</span></a>
			<a href="https://www.tiktok.com/@ivancovs" rel="noopener"><span>TikTok</span></a>
			<a href="https://www.pinterest.com/Artivancovs/" rel="noopener"><span>Pinterest</span></a>
		</div>
	</div>
</div></section>
<?php
get_footer();
