<?php
/**
 * Single person template.
 *
 * @package ScreenTime
 */

get_header();

?>

<main class="page-single-person">
	<?php
	while ( have_posts() ) :
		the_post();

		$person_post_id = get_the_ID();
		$full_name      = (string) get_post_meta( $person_post_id, 'rt-person-meta-full-name', true );
		$birth_date_raw = (string) get_post_meta( $person_post_id, 'rt-person-meta-basic-birth-date', true );
		$birth_place    = (string) get_post_meta( $person_post_id, 'rt-person-meta-basic-birth-place', true );
		$careers        = screentime_get_person_career_names( $person_post_id );
		$social_links   = screentime_get_person_social_links( $person_post_id );
		$photo_gallery  = screentime_get_movie_photo_gallery( $person_post_id );
		$video_gallery  = screentime_get_movie_video_gallery( $person_post_id );
		$popular_movies = screentime_get_popular_movies_for_person( $person_post_id, 3 );
		$profile_image  = get_the_post_thumbnail_url( $person_post_id, 'large' );
		$birth_date     = '';

		if ( '' !== $birth_date_raw ) {
			$birth_ts = strtotime( $birth_date_raw );
			if ( false !== $birth_ts ) {
				$birth_date = wp_date( get_option( 'date_format' ), $birth_ts );
			}
		}
		?>
		<section class="person-hero" id="top">
			<div class="container person-hero__inner">
				<div class="person-hero__image-wrap">
					<?php if ( ! empty( $profile_image ) ) : ?>
						<img class="person-hero__image" src="<?php echo esc_url( $profile_image ); ?>" alt="<?php the_title_attribute(); ?>" width="488" height="572">
					<?php endif; ?>
				</div>

				<div class="person-hero__content">
					<div class="person-hero__title-row">
						<h1><?php the_title(); ?></h1>
						<?php if ( '' !== $full_name ) : ?>
							<p class="person-hero__full-name"><?php echo esc_html( $full_name ); ?></p>
						<?php endif; ?>
					</div>

					<div class="person-hero__meta-list" aria-label="<?php esc_attr_e( 'Person details', 'screen-time' ); ?>">
						<?php if ( ! empty( $careers ) ) : ?>
							<div class="person-hero__meta-item"><span><?php esc_html_e( 'Occupation:', 'screen-time' ); ?></span><span><?php echo esc_html( implode( ', ', $careers ) ); ?></span></div>
						<?php endif; ?>
						<?php if ( '' !== $birth_date ) : ?>
							<div class="person-hero__meta-item"><span><?php esc_html_e( 'Born:', 'screen-time' ); ?></span><span><?php echo esc_html( $birth_date ); ?></span></div>
						<?php endif; ?>
						<?php if ( '' !== $birth_place ) : ?>
							<div class="person-hero__meta-item"><span><?php esc_html_e( 'Birthplace:', 'screen-time' ); ?></span><span><?php echo esc_html( $birth_place ); ?></span></div>
						<?php endif; ?>
						<?php if ( ! empty( $social_links ) ) : ?>
							<div class="person-hero__meta-item"><span><?php esc_html_e( 'Socials:', 'screen-time' ); ?></span>
								<span class="person-hero__socials">
									<?php if ( ! empty( $social_links['instagram'] ) ) : ?>
										<a href="<?php echo esc_url( $social_links['instagram'] ); ?>" aria-label="<?php esc_attr_e( 'Instagram', 'screen-time' ); ?>" target="_blank" rel="noopener noreferrer">
											<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" id="instagram"><path d="M17.34,5.46h0a1.2,1.2,0,1,0,1.2,1.2A1.2,1.2,0,0,0,17.34,5.46Zm4.6,2.42a7.59,7.59,0,0,0-.46-2.43,4.94,4.94,0,0,0-1.16-1.77,4.7,4.7,0,0,0-1.77-1.15,7.3,7.3,0,0,0-2.43-.47C15.06,2,14.72,2,12,2s-3.06,0-4.12.06a7.3,7.3,0,0,0-2.43.47A4.78,4.78,0,0,0,3.68,3.68,4.7,4.7,0,0,0,2.53,5.45a7.3,7.3,0,0,0-.47,2.43C2,8.94,2,9.28,2,12s0,3.06.06,4.12a7.3,7.3,0,0,0,.47,2.43,4.7,4.7,0,0,0,1.15,1.77,4.78,4.78,0,0,0,1.77,1.15,7.3,7.3,0,0,0,2.43.47C8.94,22,9.28,22,12,22s3.06,0,4.12-.06a7.3,7.3,0,0,0,2.43-.47,4.7,4.7,0,0,0,1.77-1.15,4.85,4.85,0,0,0,1.16-1.77,7.59,7.59,0,0,0,.46-2.43c0-1.06.06-1.4.06-4.12S22,8.94,21.94,7.88ZM20.14,16a5.61,5.61,0,0,1-.34,1.86,3.06,3.06,0,0,1-.75,1.15,3.19,3.19,0,0,1-1.15.75,5.61,5.61,0,0,1-1.86.34c-1,.05-1.37.06-4,.06s-3,0-4-.06A5.73,5.73,0,0,1,6.1,19.8,3.27,3.27,0,0,1,5,19.05a3,3,0,0,1-.74-1.15A5.54,5.54,0,0,1,3.86,16c0-1-.06-1.37-.06-4s0-3,.06-4A5.54,5.54,0,0,1,4.21,6.1,3,3,0,0,1,5,5,3.14,3.14,0,0,1,6.1,4.2,5.73,5.73,0,0,1,8,3.86c1,0,1.37-.06,4-.06s3,0,4,.06a5.61,5.61,0,0,1,1.86.34A3.06,3.06,0,0,1,19.05,5,3.06,3.06,0,0,1,19.8,6.1,5.61,5.61,0,0,1,20.14,8c.05,1,.06,1.37.06,4S20.19,15,20.14,16ZM12,6.87A5.13,5.13,0,1,0,17.14,12,5.12,5.12,0,0,0,12,6.87Zm0,8.46A3.33,3.33,0,1,1,15.33,12,3.33,3.33,0,0,1,12,15.33Z"></path></svg>
										</a>
									<?php endif; ?>
									<?php if ( ! empty( $social_links['twitter'] ) ) : ?>
										<a href="<?php echo esc_url( $social_links['twitter'] ); ?>" aria-label="<?php esc_attr_e( 'Twitter', 'screen-time' ); ?>" target="_blank" rel="noopener noreferrer">
											<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" id="twitter"><path d="M22,5.8a8.49,8.49,0,0,1-2.36.64,4.13,4.13,0,0,0,1.81-2.27,8.21,8.21,0,0,1-2.61,1,4.1,4.1,0,0,0-7,3.74A11.64,11.64,0,0,1,3.39,4.62a4.16,4.16,0,0,0-.55,2.07A4.09,4.09,0,0,0,4.66,10.1,4.05,4.05,0,0,1,2.8,9.59v.05a4.1,4.1,0,0,0,3.3,4A3.93,3.93,0,0,1,5,13.81a4.9,4.9,0,0,1-.77-.07,4.11,4.11,0,0,0,3.83,2.84A8.22,8.22,0,0,1,3,18.34a7.93,7.93,0,0,1-1-.06,11.57,11.57,0,0,0,6.29,1.85A11.59,11.59,0,0,0,20,8.45c0-.17,0-.35,0-.53A8.43,8.43,0,0,0,22,5.8Z"></path></svg>
										</a>
									<?php endif; ?>
									<?php if ( ! empty( $social_links['website'] ) ) : ?>
										<a href="<?php echo esc_url( $social_links['website'] ); ?>" aria-label="<?php esc_attr_e( 'Website', 'screen-time' ); ?>" target="_blank" rel="noopener noreferrer">
											<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" id="web"><path d="M12 2a10 10 0 100 20 10 10 0 000-20zm6.93 9h-3.01a15.8 15.8 0 00-1.17-5.03A8.03 8.03 0 0118.93 11zM12 4.07c.9 1.17 1.96 3.32 2.42 6.93H9.58C10.04 7.39 11.1 5.24 12 4.07zM4.07 13h3.01c.17 1.77.61 3.53 1.17 5.03A8.03 8.03 0 014.07 13zm3.01-2H4.07a8.03 8.03 0 014.18-5.03A15.8 15.8 0 007.08 11zm1.89 2h6.06c-.2 1.68-.63 3.36-1.24 4.82-.61 1.48-1.3 2.47-1.79 3.11-.49-.64-1.18-1.63-1.79-3.11A14.19 14.19 0 018.97 13zm6.78 5.03A15.8 15.8 0 0016.92 13h3.01a8.03 8.03 0 01-4.18 5.03z"></path></svg>
										</a>
									<?php endif; ?>
								</span>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</section>

		<section class="person-about" id="about">
			<div class="container person-about__grid">
				<article class="person-about__content">
					<h2 class="section-title--page"><?php esc_html_e( 'About', 'screen-time' ); ?></h2>
					<?php the_content(); ?>
				</article>

				<aside class="person-about__quick-links" aria-label="<?php esc_attr_e( 'Quick Links', 'screen-time' ); ?>">
					<h2><?php esc_html_e( 'Quick Links', 'screen-time' ); ?></h2>
					<ul>
						<li><a href="#about"><?php esc_html_e( 'About', 'screen-time' ); ?></a></li>
						<li><a href="#popular-movies"><?php esc_html_e( 'Popular Movies', 'screen-time' ); ?></a></li>
						<li><a href="#snapshots"><?php esc_html_e( 'Snapshots', 'screen-time' ); ?></a></li>
						<li><a href="#videos"><?php esc_html_e( 'Videos', 'screen-time' ); ?></a></li>
					</ul>
				</aside>
			</div>
		</section>

		<section class="person-section" id="popular-movies">
			<div class="container">
				<h2 class="section-title--page"><?php esc_html_e( 'Popular Movies', 'screen-time' ); ?></h2>
				<div class="person-popular-grid">
					<?php if ( $popular_movies->have_posts() ) : ?>
						<?php while ( $popular_movies->have_posts() ) : ?>
							<?php
							$popular_movies->the_post();
							$popular_movie_id = get_the_ID();
							$subtitle         = screentime_get_movie_genre_label( $popular_movie_id, 2, ' • ' );

							if ( '' === $subtitle ) {
								$subtitle = screentime_get_movie_release_label( $popular_movie_id );
							}

							get_template_part(
								'template-parts/movie-card',
								null,
								array(
									'title'     => get_the_title( $popular_movie_id ),
									'runtime'   => screentime_get_movie_runtime_label( $popular_movie_id ),
									'subtitle'  => $subtitle,
									'image_url' => screentime_get_movie_image_url( $popular_movie_id, 'screentime-movie-card', false ),
									'link'      => get_permalink( $popular_movie_id ),
								)
							);
							?>
						<?php endwhile; ?>
						<?php wp_reset_postdata(); ?>
					<?php endif; ?>
				</div>
			</div>
		</section>
		

		<section class="person-section" id="snapshots">
			<div class="container">
				<h2 class="section-title--page"><?php esc_html_e( 'Snapshots', 'screen-time' ); ?></h2>
				<div class="person-snapshot-grid">
						<?php foreach ( $photo_gallery as $index => $snapshot ) : ?>
							<?php
							/* translators: %d: snapshot number. */
							$snapshot_label = sprintf( __( 'Snapshot %d', 'screen-time' ), $index + 1 );
							?>
							<img src="<?php echo esc_url( (string) $snapshot['url'] ); ?>" alt="<?php echo esc_attr( ! empty( $snapshot['alt'] ) ? (string) $snapshot['alt'] : $snapshot_label ); ?>" width="592" height="419">
					<?php endforeach; ?>
				</div>
			</div>
		</section>

		<section class="person-section" id="videos">
			<div class="container">
				<h2 class="section-title--page"><?php esc_html_e( 'Videos', 'screen-time' ); ?></h2>
				<div class="person-video-grid">
					<?php foreach ( $video_gallery as $index => $video ) : ?>
						<?php
						$poster = ! empty( $video['thumb'] ) ? (string) $video['thumb'] : '';
						if ( '' === $poster ) {
							$fallback_index = ( $index % 3 ) + 1;
							$poster         = get_template_directory_uri() . '/assets/images/people/video-' . $fallback_index . '.png';
						}
						?>
							<?php
							/* translators: %d: video number. */
							$play_video_label = sprintf( __( 'Play video %d', 'screen-time' ), $index + 1 );
							/* translators: %d: video number. */
							$video_label = sprintf( __( 'Video %d', 'screen-time' ), $index + 1 );
							?>
							<button type="button" class="person-video-card" data-video-url="<?php echo esc_url( (string) $video['url'] ); ?>" aria-label="<?php echo esc_attr( $play_video_label ); ?>">
								<img src="<?php echo esc_url( $poster ); ?>" alt="<?php echo esc_attr( $video_label ); ?>" width="384" height="246">
							<span class="person-video-card__play" aria-hidden="true">▶</span>
						</button>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endwhile; ?>
</main>

<?php get_footer(); ?>
