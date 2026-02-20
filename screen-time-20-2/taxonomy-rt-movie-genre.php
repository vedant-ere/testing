<?php
/**
 * Movie genre taxonomy archive template.
 *
 * Reuses the movie archive layout and card styling for rt-movie-genre terms.
 *
 * @package ScreenTime
 */

get_header();
?>
<main class="page-archive-movie">
	<section class="movie-section">
		<div class="container">
			<h1 class="section-title"><?php single_term_title(); ?></h1>

			<div class="movie-grid">
				<?php if ( have_posts() ) : ?>
					<?php while ( have_posts() ) : ?>
						<?php the_post(); ?>
						<?php get_template_part( 'template-parts/movie-card' ); ?>
					<?php endwhile; ?>
				<?php else : ?>
					<p><?php esc_html_e( 'No movies found in this genre.', 'screen-time' ); ?></p>
				<?php endif; ?>
			</div>

			<?php
			$pagination_links = paginate_links(
				array(
					'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
					'format'    => '?paged=%#%',
					'current'   => max( 1, get_query_var( 'paged' ) ),
					'total'     => (int) $wp_query->max_num_pages,
					'type'      => 'array',
					'prev_next' => false,
				)
			);
			?>
			<?php if ( ! empty( $pagination_links ) ) : ?>
				<nav class="archive-pagination" aria-label="<?php esc_attr_e( 'Movie pagination', 'screen-time' ); ?>">
					<?php foreach ( $pagination_links as $pagination_link ) : ?>
						<?php
						$link_class = 'archive-pagination__link';
						if ( false !== strpos( $pagination_link, 'current' ) ) {
							$link_class .= ' archive-pagination__link--active';
						}
						$pagination_link = str_replace( 'page-numbers', $link_class, $pagination_link );
						echo wp_kses_post( $pagination_link );
						?>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>
		</div>
	</section>
</main>
<?php get_footer(); ?>
