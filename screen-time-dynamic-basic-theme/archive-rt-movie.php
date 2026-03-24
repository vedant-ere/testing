<?php
/**
 * Movie archive template.
 *
 * @package ScreenTime
 */

get_header();

global $wp_query;
?>
<main class="page-archive-movie">
	<section class="movie-section">
		<div class="container" data-movie-archive data-movie-archive-context="post-type" data-movie-archive-term="">
			<h1 class="section-title"><a href="<?php echo esc_url( get_post_type_archive_link( 'rt-movie' ) ); ?>"><?php post_type_archive_title(); ?></a></h1>

			<div class="movie-grid" data-movie-archive-grid>
				<?php if ( have_posts() ) : ?>
					<?php while ( have_posts() ) : ?>
						<?php the_post(); ?>
						<?php get_template_part( 'template-parts/movie-card' ); ?>
					<?php endwhile; ?>
				<?php else : ?>
					<p><?php esc_html_e( 'No movies found.', 'screen-time' ); ?></p>
				<?php endif; ?>
			</div>

			<?php
			$pagination_links = screentime_get_archive_pagination_links(
				(int) $wp_query->max_num_pages,
				max( 1, (int) get_query_var( 'paged' ) )
			);
			?>
			<?php if ( ! empty( $pagination_links ) ) : ?>
				<nav class="archive-pagination" data-movie-archive-pagination aria-label="<?php esc_attr_e( 'Movie pagination', 'screen-time' ); ?>">
					<?php foreach ( $pagination_links as $pagination_link ) : ?>
						<?php echo wp_kses_post( $pagination_link ); ?>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php get_footer(); ?>
