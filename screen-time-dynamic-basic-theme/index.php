<?php
/**
 * Main template.
 *
 * @package ScreenTime
 */

get_header();
?>

<main class="page-generic">
	<section class="container">
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : ?>
				<?php the_post(); ?>
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php the_excerpt(); ?>
				</article>
			<?php endwhile; ?>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'No posts found.', 'screen-time' ); ?></p>
		<?php endif; ?>
	</section>

	<section class="container">
		<h2><?php esc_html_e( 'Books', 'screen-time' ); ?></h2>
		<?php
		$books = new WP_Query(
			array(
				'post_type'      => 'book',
				'post_status'    => 'publish',
				'posts_per_page' => 5,
			)
		);
		?>
		<?php if ( $books->have_posts() ) : ?>
			<?php while ( $books->have_posts() ) : ?>
				<?php $books->the_post(); ?>
				<article id="book-<?php the_ID(); ?>" <?php post_class(); ?>>
					<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
					<?php the_excerpt(); ?>
				</article>
			<?php endwhile; ?>
			<?php wp_reset_postdata(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'No books available yet.', 'screen-time' ); ?></p>
		<?php endif; ?>
	</section>
</main>

<?php
get_footer();
