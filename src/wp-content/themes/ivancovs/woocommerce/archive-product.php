<?php
/**
 * Каталог аренды: поиск, ряд «Комплекты по задачам», ряд категорий, карточки приборов.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();

$shop      = wc_get_page_permalink( 'shop' );
$kits      = ivs_kits_term();
$current   = get_queried_object();
$cur_id    = ( $current instanceof WP_Term ) ? $current->term_id : 0;
$kit_ids   = array();
$kit_terms = array();
if ( $kits ) {
	$kit_terms = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $kits->term_id, 'hide_empty' => false ) );
	$kit_terms = is_wp_error( $kit_terms ) ? array() : $kit_terms;
	foreach ( $kit_terms as $k ) {
		$kit_ids[] = $k->term_id;
	}
}
$cat_terms = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => false, 'exclude' => array_filter( array( (int) get_option( 'default_product_cat' ), $kits ? $kits->term_id : 0 ) ) ) );
$cat_terms = is_wp_error( $cat_terms ) ? array() : $cat_terms;
?>
<div class="wrap">
	<div class="crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php ivs_e( 'Sākums', 'Главная', 'Home' ); ?></a> › <?php ivs_e( 'Noma', 'Аренда', 'Rental' ); ?></div>
	<h1 style="margin:0 0 16px;font-size:32px"><?php ivs_e( 'Mērinstrumentu noma Rīgā', 'Аренда измерительных приборов в Риге', 'Measuring instrument rental in Riga' ); ?></h1>

	<form class="bar" role="search" method="get" action="<?php echo esc_url( $shop ); ?>">
		<?php if ( 'lv' !== ivs_lang() ) : ?><input type="hidden" name="ivl" value="<?php echo esc_attr( ivs_lang() ); ?>"><?php endif; ?>
		<label class="sr" for="ivs-s"><?php ivs_e( 'Meklēt', 'Поиск', 'Search' ); ?></label>
		<input id="ivs-s" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php echo esc_attr( ivs_t( 'Meklēt: testo 417, termokamera, CO₂…', 'Поиск: testo 417, тепловизор, CO₂…', 'Search: testo 417, thermal camera, CO₂…' ) ); ?>">
		<input type="hidden" name="post_type" value="product">
	</form>

	<?php if ( $kit_terms ) : ?>
	<div class="krow"><b><?php ivs_e( 'Komplekti:', 'Комплекты:', 'Kits:' ); ?></b>
		<?php foreach ( $kit_terms as $k ) : ?>
			<a href="<?php echo esc_url( ivs_lang_url( get_term_link( $k ) ) ); ?>"<?php echo $cur_id === $k->term_id ? ' class="on"' : ''; ?>><?php echo esc_html( $k->name ); ?></a>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>

	<div class="chipbar">
		<a href="<?php echo esc_url( ivs_lang_url( $shop ) ); ?>"<?php echo 0 === $cur_id ? ' class="on"' : ''; ?>><?php ivs_e( 'Visi instrumenti', 'Все приборы', 'All instruments' ); ?></a>
		<?php foreach ( $cat_terms as $c ) : ?>
			<a href="<?php echo esc_url( ivs_lang_url( get_term_link( $c ) ) ); ?>"<?php echo $cur_id === $c->term_id ? ' class="on"' : ''; ?>><?php echo esc_html( $c->name ); ?></a>
		<?php endforeach; ?>
	</div>

	<?php if ( woocommerce_product_loop() ) : ?>
		<div class="grid g3">
		<?php
		while ( have_posts() ) {
			the_post();
			$product = wc_get_product( get_the_ID() );
			if ( ! $product ) {
				continue;
			}
			?>
			<a class="card" href="<?php echo esc_url( ivs_lang_url( get_permalink() ) ); ?>">
				<div class="ph"><?php echo $product->get_image_id() ? wp_get_attachment_image( $product->get_image_id(), 'woocommerce_thumbnail' ) : esc_html( ivs_t( 'foto', 'фото', 'photo' ) ); // phpcs:ignore ?></div>
				<div class="b"><h3><?php the_title(); ?></h3>
					<?php if ( has_excerpt() ) : ?><div class="mut"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 8 ) ); ?></div><?php endif; ?>
					<?php echo ivs_price_html( $product ); // phpcs:ignore ?>
				</div>
			</a>
			<?php
		}
		?>
		</div>
		<div class="pager"><?php echo wp_kses_post( paginate_links() ); ?></div>
	<?php else : ?>
		<p class="mut"><?php ivs_e( 'Nekas netika atrasts.', 'Ничего не найдено.', 'Nothing found.' ); ?></p>
	<?php endif; ?>
</div>
<?php
get_footer();
