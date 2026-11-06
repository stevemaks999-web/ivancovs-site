<?php
/**
 * Страница прибора: фото, бронь (календарь появится в Фазе 6), для чего нужен, технические данные.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

while ( have_posts() ) :
	the_post();
	$product = wc_get_product( get_the_ID() );
	if ( ! $product ) {
		continue;
	}
	$ids = array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) );
	$shop = wc_get_page_permalink( 'shop' );
	$cats = get_the_terms( get_the_ID(), 'product_cat' );
	$cat  = ( $cats && ! is_wp_error( $cats ) ) ? $cats[0] : null;
	$wa_text = sprintf( ivs_t( 'Sveiki! Interesē noma: %s', 'Здравствуйте! Интересует аренда: %s', 'Hello! I am interested in renting: %s' ), get_the_title() );
	?>
<div class="wrap">
	<div class="crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php ivs_e( 'Sākums', 'Главная', 'Home' ); ?></a> › <a href="<?php echo esc_url( $shop ); ?>"><?php ivs_e( 'Noma', 'Аренда', 'Rental' ); ?></a><?php if ( $cat ) : ?> › <a href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a><?php endif; ?> › <?php the_title(); ?></div>
	<div class="prod">
		<div class="gal">
			<div class="ph" style="aspect-ratio:4/3;border-radius:12px;border:1px solid var(--line)"><?php echo $ids ? wp_get_attachment_image( $ids[0], 'large' ) : esc_html( ivs_t( 'foto', 'фото', 'photo' ) ); // phpcs:ignore ?></div>
			<?php if ( count( $ids ) > 1 ) : ?>
			<div class="grid g4" style="margin-top:12px;grid-template-columns:repeat(4,1fr)">
				<?php foreach ( array_slice( $ids, 1, 4 ) as $id ) : ?>
					<div class="ph" style="border-radius:8px"><?php echo wp_get_attachment_image( $id, 'woocommerce_thumbnail' ); // phpcs:ignore ?></div>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>
		<div>
			<h1><?php the_title(); ?> <?php ivs_e( 'noma Rīgā', 'аренда в Риге', 'rental in Riga' ); ?></h1>

			<div class="card book" id="booking">
				<div class="price-line"><?php echo wp_kses_post( wc_price( $product->get_price() ) ); ?> <small>/ <?php ivs_e( 'diena', 'сутки', 'day' ); ?></small></div>
				<?php
				// Фаза 6: календарь выбора дат и бронь.
				do_action( 'ivs_booking_calendar', $product );
				?>
				<div class="row"><span><?php ivs_e( 'Izsniegšana / atgriešana', 'Выдача / возврат', 'Pickup / return' ); ?></span><span>9:00–18:00</span></div>
				<ul class="rules">
					<li><?php ivs_e( 'Jāuzrāda dokuments (pase, tiesības vai ID)', 'Нужен документ (паспорт, права или ID)', 'An ID is required (passport, licence or ID card)' ); ?></li>
					<li><?php ivs_e( 'Brīvdienas: pēc vienošanās', 'Выходные: по договорённости', 'Weekends: by arrangement' ); ?></li>
					<li><?php ivs_e( 'Rēķins ar PVN 21%', 'Счёт с PVN 21%', 'Invoice with 21% VAT' ); ?></li>
				</ul>
				<a class="btn wa block" href="<?php echo ivs_wa_link( $wa_text ); // phpcs:ignore ?>"><?php ivs_e( 'Jautāt WhatsApp', 'Спросить в WhatsApp', 'Ask on WhatsApp' ); ?></a>
			</div>

			<?php if ( has_excerpt() ) : ?>
				<h2 style="margin-top:24px"><?php ivs_e( 'Kam nepieciešams', 'Для чего нужен', 'What it is for' ); ?></h2>
				<p class="mut"><?php echo wp_kses_post( get_the_excerpt() ); ?></p>
			<?php endif; ?>
			<?php if ( get_the_content() ) : ?>
				<h2 style="margin-top:20px"><?php ivs_e( 'Tehniskie dati', 'Технические данные', 'Technical data' ); ?></h2>
				<div class="entry-body"><?php the_content(); ?></div>
			<?php endif; ?>
		</div>
	</div>
</div>
<div class="stick">
	<b><?php echo wp_kses_post( wc_price( $product->get_price() ) ); ?> <small class="mut" style="font-weight:400">/ <?php ivs_e( 'diena', 'сутки', 'day' ); ?></small></b>
	<a class="btn" href="#booking"><?php ivs_e( 'Rezervēt', 'Забронировать', 'Book' ); ?></a>
	<a class="btn wa" href="<?php echo ivs_wa_link( $wa_text ); // phpcs:ignore ?>" aria-label="WhatsApp">WhatsApp</a>
</div>
	<?php
endwhile;
get_footer();
