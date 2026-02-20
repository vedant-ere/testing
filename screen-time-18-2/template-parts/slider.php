<?php
/**
 * Featured Movies Slider Template.
 *
 * Renders the hero carousel on the front page.
 *
 * Displays up to 4 `rt-movie` posts assigned to the "slider" label,
 * each slide showing:
 * - Background poster image
 * - Movie title and summary
 * - Release year, content rating, runtime
 * - Up to three genre tags
 *
 * @package ScreenTime
 */

 /**
  * Query for slider movies.
  *
  * Returns a WP_Query containing up to 4 published
  * `rt-movie` posts with the "slider" label.
  *
  * @var WP_Query $slider_query
  */
$slider_query = screentime_get_movies_by_label( 'slider', 4 );
?>

<?php if ( $slider_query->have_posts() ) : ?>
	<section
		class="hero-slider"
		role="region"
		aria-label="<?php esc_attr_e( 'Featured movies slider', 'screen-time' ); ?>"
		data-slider
	>
		<div class="hero-slider__track">
			<?php
			/**
			 * Slide index counter.
			 *
			 * Used to mark the first slide as active and
			 * generate matching navigation dots.
			 *
			 * @var int $slide_index
			 */
			$slide_index = 0;

			while ( $slider_query->have_posts() ) :
				$slider_query->the_post();

				/**
				 * Movie data for the current slide.
				 *
				 * These values are precomputed to keep
				 * template markup clean and readable.
				 */
				$post_id        = get_the_ID();
				$image_url      = screentime_get_movie_image_url( $post_id, 'full', true );
				$title          = get_the_title();
				$description    = screentime_get_movie_summary( $post_id );
				$release_year   = screentime_get_movie_release_year( $post_id );
				$content_rating = screentime_get_movie_content_rating( $post_id );
				$runtime_label  = screentime_get_movie_runtime_label( $post_id );
				$genres         = screentime_get_movie_genre_names( $post_id );
				?>
				<article
					class="hero-slider__slide <?php echo 0 === $slide_index ? 'is-active' : ''; ?>"
					aria-hidden="<?php echo 0 === $slide_index ? 'false' : 'true'; ?>"
					data-slide
				>
					<div class="hero-slider__image">
						<?php if ( ! empty( $image_url ) ) : ?>
							<img
								src="<?php echo esc_url( $image_url ); ?>"
								alt="<?php echo esc_attr( $title ); ?>"
								width="1440"
								height="616"
								loading="<?php echo 0 === $slide_index ? 'eager' : 'lazy'; ?>"
							>
						<?php endif; ?>
					</div>

					<div class="hero-slider__overlay" aria-hidden="true"></div>

					<div class="container hero-slider__content">
						<div class="hero-slider__panel">
							<h1 class="hero-slider__title">
								<a href="<?php the_permalink(); ?>">
									<?php the_title(); ?>
								</a>
							</h1>

							<p class="hero-slider__description">
								<?php echo esc_html( $description ); ?>
							</p>

							<div class="hero-slider__meta">
								<?php if ( ! empty( $release_year ) ) : ?>
									<span><?php echo esc_html( $release_year ); ?></span>
								<?php endif; ?>

								<?php if ( ! empty( $content_rating ) ) : ?>
									<span>•</span>
									<span><?php echo esc_html( $content_rating ); ?></span>
								<?php endif; ?>

								<?php if ( ! empty( $runtime_label ) ) : ?>
									<span>•</span>
									<span><?php echo esc_html( $runtime_label ); ?></span>
								<?php endif; ?>
							</div>

							<?php if ( ! empty( $genres ) ) : ?>
								<div class="hero-slider__tags">
									<?php foreach ( $genres as $genre ) : ?>
										<button
											class="chip chip--outline chip--sm"
											type="button"
										>
											<?php echo esc_html( $genre ); ?>
										</button>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</article>
				<?php
				$slide_index++;
			endwhile;
			?>
		</div>

		<?php
		/**
		 * Slider navigation controls.
		 *
		 * Generates one dot per slide and marks the
		 * first dot as active by default.
		 */
		?>
		<div class="hero-slider__controls">
			<div
				class="hero-slider__dots"
				role="tablist"
				aria-label="<?php esc_attr_e( 'Slider navigation dots', 'screen-time' ); ?>"
			>
				<?php for ( $index = 0; $index < $slide_index; $index++ ) : ?>
					<button
						class="hero-slider__dot"
						type="button"
						role="tab"
						aria-label="<?php echo esc_attr( sprintf( __( 'Go to slide %d', 'screen-time' ), $index + 1 ) ); ?>"
						aria-current="<?php echo 0 === $index ? 'true' : 'false'; ?>"
						data-slider-dot="<?php echo esc_attr( (string) $index ); ?>"
					></button>
				<?php endfor; ?>
			</div>
		</div>
	</section>

	<?php
	/**
	 * Restore global post data after custom query loop.
	 */
	wp_reset_postdata();
	?>
<?php endif; ?>
