<?php
/**
 * Запасной шаблон: записи блога и обычные страницы.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
?>
<div class="wrap entry">
<?php if ( have_posts() ) : ?>
	<?php while ( have_posts() ) : the_post(); ?>
		<article>
			<h1><?php the_title(); ?></h1>
			<?php the_content(); ?>
		</article>
	<?php endwhile; ?>
	<div class="pager"><?php echo wp_kses_post( paginate_links() ); ?></div>
<?php else : ?>
	<h1><?php ivs_e( 'Nekas netika atrasts', 'Ничего не найдено', 'Nothing found' ); ?></h1>
<?php endif; ?>
</div>
<?php
get_footer();
