<?php
/**
 * Fallback single template for Custom Posts.
 *
 * @package RT_Post_Embedder
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main class="page-custom-post">
	<section class="movie-section">
		<div class="container">
			<?php if ( have_posts() ) : ?>
				<?php while ( have_posts() ) : ?>
					<?php the_post(); ?>
					<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
						<h1 class="section-title">
							<span><?php the_title(); ?></span>
						</h1>
						<div class="rt-pe-content">
							<?php the_content(); ?>
						</div>
					</article>
				<?php endwhile; ?>
			<?php else : ?>
				<p><?php esc_html_e( 'No content found.', 'rt-post-embedder' ); ?></p>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php
get_footer();
