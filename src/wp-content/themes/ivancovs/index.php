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
<?php $ivs_list = is_home() || is_archive() || is_search(); ?>
<?php if ( have_posts() ) : ?>
	<?php if ( $ivs_list ) : ?><h1><?php echo wp_kses_post( is_search() ? get_search_query() : get_the_archive_title() ); ?></h1><?php endif; ?>
	<?php while ( have_posts() ) : the_post(); ?>
		<article>
			<?php if ( $ivs_list ) : ?><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php else : ?><h1><?php the_title(); ?></h1><?php endif; ?>
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
