<?php
/**
 * Single movie template.
 *
 * Renders all assignment-required movie sections from live WordPress data:
 * poster, synopsis, metadata, crew, media galleries, and movie reviews.
 *
 * @package ScreenTime
 */

get_header();
?>

<main class="page-single-movie">
	<?php
	while ( have_posts() ) :
		the_post();

		$movie_post_id   = get_the_ID();
		$hero_image      = screentime_get_movie_image_url( $movie_post_id, 'large', true );
		$rating          = screentime_get_movie_rating( $movie_post_id );
		$release_year    = screentime_get_movie_release_year( $movie_post_id );
		$content_rating  = screentime_get_movie_content_rating( $movie_post_id );
		$runtime_label   = screentime_get_movie_runtime_label( $movie_post_id );
		$genre_terms     = screentime_get_movie_genre_terms( $movie_post_id, 3 );
		$languages       = screentime_get_movie_language_names( $movie_post_id );
		$directors       = screentime_get_movie_people_by_role( $movie_post_id, 'rt-movie-meta-crew-director' );
		$photo_gallery   = screentime_get_movie_photo_gallery( $movie_post_id );
		$video_gallery   = screentime_get_movie_video_gallery( $movie_post_id );
		$cast_crew_cards = screentime_get_movie_cast_crew_cards( $movie_post_id );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only query arg only toggles presentation mode.
		$view_mode         = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';
		$is_cast_crew_view = 'cast-crew' === $view_mode;
		?>

			<?php if ( $is_cast_crew_view ) : ?>
				<div class="page-archive-person page-archive-person--cast-crew">
					<?php
					get_template_part(
						'template-parts/movie/cast-crew-archive',
						null,
						array(
							'cards' => $cast_crew_cards,
						)
					);
					?>
				</div>
			<?php else : ?>

		<section class="movie-single-hero" id="top">
			<div class="container movie-single-hero__inner">
				<div class="movie-single-hero__poster-wrap">
					<?php if ( ! empty( $hero_image ) ) : ?>
						<img class="movie-single-hero__poster" src="<?php echo esc_url( $hero_image ); ?>" alt="<?php the_title_attribute(); ?>" width="552" height="876">
					<?php endif; ?>
				</div>
				<div class="movie-single-hero__content">
					<h1><?php the_title(); ?></h1>
					<?php
					get_template_part(
						'template-parts/movie/metadata',
						null,
						array(
							'rating'         => $rating,
							'year'           => $release_year,
							'content_rating' => $content_rating,
							'runtime'        => $runtime_label,
						)
					);
					?>
					<?php if ( has_excerpt() ) : ?>
						<p class="movie-single-hero__description"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>

					<?php
					get_template_part(
						'template-parts/movie/metadata',
						null,
						array(
							'genre_terms' => $genre_terms,
						)
					);
					?>

					<?php if ( ! empty( $directors ) ) : ?>
						<p class="movie-single-hero__directors">
							<strong><?php esc_html_e( 'Directors:', 'screen-time' ); ?></strong>
							<?php foreach ( $directors as $director_index => $director_post ) : ?>
								<?php if ( $director_index > 0 ) : ?>
									<span aria-hidden="true"> • </span>
								<?php endif; ?>
								<a href="<?php echo esc_url( get_permalink( $director_post ) ); ?>">
									<?php echo esc_html( get_the_title( $director_post ) ); ?>
								</a>
							<?php endforeach; ?>
						</p>
					<?php endif; ?>
				</div>
			</div>
		</section>

		<section class="movie-single-body" id="synopsis">
			<div class="container movie-single-body__grid">
				<article class="movie-single-body__copy">
					<h2 class="section-title--page"><?php esc_html_e( 'Synopsis', 'screen-time' ); ?></h2>
					<?php the_content(); ?>
				</article>
				<aside class="movie-single-body__quick-links" aria-label="<?php esc_attr_e( 'Quick Links', 'screen-time' ); ?>">
					<h2><?php esc_html_e( 'Quick Links', 'screen-time' ); ?></h2>
					<ul>
						<li><a href="#synopsis"><?php esc_html_e( 'Synopsis', 'screen-time' ); ?></a></li>
						<li><a href="#cast-crew"><?php esc_html_e( 'Cast & Crew', 'screen-time' ); ?></a></li>
						<li><a href="#snapshots"><?php esc_html_e( 'Snapshots', 'screen-time' ); ?></a></li>
						<li><a href="#trailers"><?php esc_html_e( 'Trailer & Clips', 'screen-time' ); ?></a></li>
						<li><a href="#reviews"><?php esc_html_e( 'Reviews', 'screen-time' ); ?></a></li>
					</ul>
				</aside>
			</div>
		</section>

				<?php
				get_template_part(
					'template-parts/movie/crew',
					null,
					array(
						'movie_id' => $movie_post_id,
						'cards'    => $cast_crew_cards,
					)
				);

				get_template_part(
					'template-parts/movie/gallery-photo',
					null,
					array(
						'items' => $photo_gallery,
					)
				);

				get_template_part(
					'template-parts/movie/gallery-video',
					null,
					array(
						'items' => $video_gallery,
					)
				);
				?>

			<section class="movie-single-section" id="reviews">
				<div class="container">
					<h2 class="section-title--page"><?php esc_html_e( 'Reviews', 'screen-time' ); ?></h2>
					<div class="movie-review-grid">
						<?php
						$reviews_per_page = 4;
						$movie_comments   = get_comments(
							array(
								'post_id' => $movie_post_id,
								'status'  => 'approve',
								'parent'  => 0,
								'order'   => 'DESC',
								'number'  => $reviews_per_page,
							)
						);

						foreach ( $movie_comments as $movie_comment ) :
							$comment_author = get_comment_author( $movie_comment );
							$comment_icon   = strtoupper( substr( wp_strip_all_tags( $comment_author ), 0, 1 ) );
							$comment_text   = wp_strip_all_tags( $movie_comment->comment_content );
							$comment_date   = get_comment_date( 'F j, Y', $movie_comment );
							?>
							<article class="movie-review-card">
								<p class="movie-review-card__author"><span class="movie-review-card__icon" aria-hidden="true"><?php echo esc_html( $comment_icon ); ?></span><?php echo esc_html( $comment_author ); ?></p>
								<p class="movie-review-card__text"><?php echo esc_html( $comment_text ); ?></p>
								<p class="movie-review-card__date"><?php echo esc_html( $comment_date ); ?></p>
							</article>
						<?php endforeach; ?>
					</div>
					<?php
					$total_reviews = get_comments(
						array(
							'post_id' => $movie_post_id,
							'status'  => 'approve',
							'parent'  => 0,
							'count'   => true,
						)
					);
					?>
					<?php if ( $total_reviews > $reviews_per_page ) : ?>
						<div class="load-more-wrap">
							<button
								type="button"
								class="chip chip--outline"
								data-load-more-reviews
								data-post-id="<?php echo esc_attr( (string) $movie_post_id ); ?>"
							>
								<?php esc_html_e( 'Load more reviews', 'screen-time' ); ?>
							</button>
						</div>
					<?php endif; ?>
				</div>
			</section>

			<section class="movie-single-form">
				<div class="container">
					<?php
					comment_form(
						screentime_get_movie_review_form_args(),
						$movie_post_id
					);
					?>
				</div>
			</section>

			<?php endif; ?>
		<?php endwhile; ?>
</main>

<?php get_footer(); ?>
