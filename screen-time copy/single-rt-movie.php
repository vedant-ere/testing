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

		$movie_post_id = get_the_ID();
		$hero_image    = screentime_get_movie_image_url( $movie_post_id, 'large', true );
		$rating        = screentime_get_movie_rating( $movie_post_id );
		$release_year  = screentime_get_movie_release_year( $movie_post_id );
		$content_rating = screentime_get_movie_content_rating( $movie_post_id );
		$runtime_label  = screentime_get_movie_runtime_label( $movie_post_id );
		$genres        = screentime_get_movie_genre_names( $movie_post_id );
		$languages     = screentime_get_movie_language_names( $movie_post_id );
		$directors     = screentime_get_movie_people_by_role( $movie_post_id, 'rt-movie-meta-crew-director' );
		$photo_gallery = screentime_get_movie_photo_gallery( $movie_post_id );
		$video_gallery = screentime_get_movie_video_gallery( $movie_post_id );
		$cast_crew_cards   = screentime_get_movie_cast_crew_cards( $movie_post_id );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only query arg only toggles presentation mode.
		$view_mode          = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : '';
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
			<?php
			continue;
		endif;
		?>

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

					<?php
					get_template_part(
						'template-parts/movie/metadata',
						null,
						array(
							'genres' => $genres,
						)
					);
					?>

					<?php if ( has_excerpt() ) : ?>
						<p class="movie-single-hero__description"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>



					<?php if ( ! empty( $directors ) ) : ?>
						<p class="movie-single-hero__directors"><strong><?php esc_html_e( 'Directors:', 'screen-time' ); ?></strong> <?php echo esc_html( implode( ' • ', wp_list_pluck( $directors, 'post_title' ) ) ); ?></p>
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
				<h2 class="section-title--page">Reviews</h2>
				<div class="movie-review-grid">
					<?php
					$movie_comments = get_comments(
						array(
							'post_id' => $movie_post_id,
							'status'  => 'approve',
							'parent'  => 0,
							'order'   => 'DESC',
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
			</div>
		</section>

		<section class="movie-single-form">
			<div class="container">
				<?php $commenter = wp_get_current_commenter(); ?>
				<form class="movie-review-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
					<input type="hidden" name="action" value="screentime_submit_review">
					<?php wp_nonce_field( 'screentime_submit_review', 'screentime_review_nonce' ); ?>
					<h2>Leave a Review</h2>
					<p>Your Email Address will not be published. Required fields are marked *</p>
					<label for="review-comment">Comment*</label>
					<textarea id="review-comment" name="comment" rows="6" required="required"></textarea>
					<div class="movie-review-form__row">
						<div><label for="review-name">Name*</label><input id="review-name" name="author" type="text" value="<?php echo esc_attr( $commenter['comment_author'] ); ?>" required="required"></div>
						<div><label for="review-email">Email*</label><input id="review-email" name="email" type="email" value="<?php echo esc_attr( $commenter['comment_author_email'] ); ?>" required="required"></div>
					</div>
					<label for="review-website">Website</label>
					<input id="review-website" name="url" type="text" value="<?php echo esc_attr( $commenter['comment_author_url'] ); ?>">
					<label class="movie-review-form__checkbox"><input type="checkbox" name="wp-comment-cookies-consent" value="yes" <?php checked( ! empty( $commenter['comment_author_email'] ) ); ?>> Save my name and email in this browser for the next time I comment.</label>
					<?php comment_id_fields( $movie_post_id ); ?>
					<button type="submit">Post Review</button>
				</form>
			</div>
		</section>

	<?php endwhile; ?>
</main>

<?php get_footer(); ?>
