<?php
/**
 * Pattern: Hero Featured Movie
 *
 * Title: Hero — Featured Movie
 * Slug: screen-time-fse/hero-featured
 * Description: Static hero section for the featured movie (no slider dots).
 * Categories: screen-time-fse
 * Viewport Width: 1440
 * Inserter: true
 *
 * @package ScreenTimeFSE
 * @since   1.0.0
 */

/*
 * Resolve the first movie with the "slider" label so the hero shows a real
 * movie poster by default. Falls back to a dark gradient placeholder when no
 * movie is tagged.
 */
$hero_post_id  = 0;
$hero_bg       = '';
$hero_title    = __( 'Featured Movie Title', 'screen-time-fse' );
$hero_desc     = __( 'An epic story that will keep you on the edge of your seat.', 'screen-time-fse' );
$hero_year     = '2024';
$hero_rating   = 'U/A';
$hero_runtime  = '2h 15min';
$hero_genres   = array( 'Action', 'Thriller' );
$hero_link     = '#';

if ( taxonomy_exists( 'rt-movie-label' ) ) {
	$slider_query = new WP_Query(
		array(
			'post_type'              => 'rt-movie',
			'post_status'            => 'publish',
			'posts_per_page'         => 1,
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query'              => array(
				array(
					'taxonomy' => 'rt-movie-label',
					'field'    => 'slug',
					'terms'    => 'slider',
				),
			),
		)
	);

	if ( $slider_query->have_posts() ) {
		$slider_query->the_post();
		$hero_post_id = get_the_ID();
		$hero_title   = get_the_title();
		$hero_link    = get_permalink();
		$hero_desc    = has_excerpt() ? get_the_excerpt() : '';

		// Background image (large thumbnail).
		$thumb_id = get_post_thumbnail_id( $hero_post_id );
		if ( $thumb_id ) {
			$img_src = wp_get_attachment_image_url( $thumb_id, 'full' );
			if ( $img_src ) {
				$hero_bg = $img_src;
			}
		}

		// Meta.
		$raw_runtime = absint( get_post_meta( $hero_post_id, 'rt-movie-meta-basic-runtime', true ) );
		if ( $raw_runtime > 0 ) {
			$hrs          = (int) floor( $raw_runtime / 60 );
			$mins         = $raw_runtime % 60;
			$hero_runtime = ( $hrs > 0 ? $hrs . 'h ' : '' ) . $mins . 'min';
		}

		$release_date = get_post_meta( $hero_post_id, 'rt-movie-meta-basic-release-date', true );
		if ( $release_date ) {
			$hero_year = date( 'Y', strtotime( (string) $release_date ) );
		}

		$hero_rating = (string) get_post_meta( $hero_post_id, 'rt-movie-meta-basic-content-rating', true );

		$genre_terms = get_the_terms( $hero_post_id, 'rt-movie-genre' );
		if ( $genre_terms && ! is_wp_error( $genre_terms ) ) {
			$hero_genres = array_map(
				static function ( $t ) {
					return $t->name;
				},
				array_slice( $genre_terms, 0, 3 )
			);
		}

		wp_reset_postdata();
	}
}

$has_bg    = ! empty( $hero_bg );
$bg_style  = $has_bg ? ' style="background-image:url(' . esc_url( $hero_bg ) . ')"' : '';
$genre_str = implode( ' &bull; ', array_map( 'esc_html', $hero_genres ) );
?>

<!-- wp:group {"className":"hero-featured","layout":{"type":"default"},"style":{"spacing":{"padding":{"top":"0","bottom":"0"}}}} -->
<div class="wp-block-group hero-featured">

	<!-- wp:html -->
	<section
		class="hero-section<?php echo $has_bg ? ' hero-section--has-bg' : ''; ?>"
		aria-label="<?php echo esc_attr( $hero_title ); ?> — Featured Movie"
		<?php echo $bg_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
	>
		<div class="hero-section__overlay" aria-hidden="true"></div>
		<div class="hero-section__content container">
			<div class="hero-section__panel">

				<?php if ( ! empty( $genre_str ) ) : ?>
					<p class="hero-section__tags" aria-label="Genres">
						<?php echo wp_kses_post( $genre_str ); ?>
					</p>
				<?php endif; ?>

				<h1 class="hero-section__title">
					<a href="<?php echo esc_url( $hero_link ); ?>"><?php echo esc_html( $hero_title ); ?></a>
				</h1>

				<p class="hero-section__meta" aria-label="Movie details">
					<?php if ( ! empty( $hero_year ) ) : ?>
						<span><?php echo esc_html( $hero_year ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $hero_rating ) ) : ?>
						<span aria-hidden="true"> &bull; </span>
						<span><?php echo esc_html( $hero_rating ); ?></span>
					<?php endif; ?>
					<?php if ( ! empty( $hero_runtime ) ) : ?>
						<span aria-hidden="true"> &bull; </span>
						<span><?php echo esc_html( strtoupper( $hero_runtime ) ); ?></span>
					<?php endif; ?>
				</p>

				<?php if ( ! empty( $hero_desc ) ) : ?>
					<p class="hero-section__description"><?php echo esc_html( $hero_desc ); ?></p>
				<?php endif; ?>

			</div>
		</div>
	</section>
	<!-- /wp:html -->

</div>
<!-- /wp:group -->
