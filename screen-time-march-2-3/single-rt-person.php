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
		$age_label      = '';

		if ( '' !== $birth_date_raw ) {
			$birth_ts = strtotime( $birth_date_raw );
			if ( false !== $birth_ts ) {
				$birth_date = wp_date( get_option( 'date_format' ), $birth_ts );

				try {
					$birth_date_time = new DateTimeImmutable( '@' . (string) $birth_ts );
					$birth_date_time = $birth_date_time->setTimezone( wp_timezone() );
					$current_time    = new DateTimeImmutable( 'now', wp_timezone() );
					$age_years       = (int) $birth_date_time->diff( $current_time )->y;
					/* translators: %d: age in years. */
					$age_label = sprintf( __( ' (age %d years)', 'screen-time' ), $age_years );
				} catch ( Exception $exception ) {
					$age_label = '';
				}
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
							<div class="person-hero__meta-item"><span><?php esc_html_e( 'Born:', 'screen-time' ); ?></span><span><?php echo esc_html( $birth_date . $age_label ); ?></span></div>
						<?php endif; ?>
						<?php if ( '' !== $birth_place ) : ?>
							<div class="person-hero__meta-item"><span><?php esc_html_e( 'Birthplace:', 'screen-time' ); ?></span><span><?php echo esc_html( $birth_place ); ?></span></div>
						<?php endif; ?>
						<?php if ( ! empty( $social_links ) ) : ?>
							<div class="person-hero__meta-item"><span><?php esc_html_e( 'Socials:', 'screen-time' ); ?></span>
								<span class="person-hero__socials">
									<?php if ( ! empty( $social_links['instagram'] ) ) : ?>
										<a href="<?php echo esc_url( $social_links['instagram'] ); ?>" aria-label="<?php esc_attr_e( 'Instagram', 'screen-time' ); ?>" target="_blank" rel="noopener noreferrer">
											<img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/icons/instagram.svg' ) ); ?>" alt="" aria-hidden="true" />
										</a>
									<?php endif; ?>
									<?php if ( ! empty( $social_links['twitter'] ) ) : ?>
										<a href="<?php echo esc_url( $social_links['twitter'] ); ?>" aria-label="<?php esc_attr_e( 'Twitter', 'screen-time' ); ?>" target="_blank" rel="noopener noreferrer">
											<img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/icons/twitter.svg' ) ); ?>" alt="" aria-hidden="true" />
										</a>
									<?php endif; ?>
									<?php if ( ! empty( $social_links['website'] ) ) : ?>
										<a href="<?php echo esc_url( $social_links['website'] ); ?>" aria-label="<?php esc_attr_e( 'Website', 'screen-time' ); ?>" target="_blank" rel="noopener noreferrer">
											<img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/icons/web.svg' ) ); ?>" alt="" aria-hidden="true" />
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
							$video_url = isset( $video['url'] ) ? (string) $video['url'] : '';
							$poster    = screentime_get_video_thumbnail_url(
								$video_url,
								isset( $video['thumb'] ) ? (string) $video['thumb'] : ''
							);
							?>
							<?php
							/* translators: %d: video number. */
							$play_video_label = sprintf( __( 'Play video %d', 'screen-time' ), $index + 1 );
							/* translators: %d: video number. */
							$video_label = sprintf( __( 'Video %d', 'screen-time' ), $index + 1 );
							?>
							<button
								type="button"
								class="person-video-card"
								data-video-url="<?php echo esc_url( $video_url ); ?>"
								data-thumbnail-time="<?php echo esc_attr( 2 === $index ? '16' : '15.5' ); ?>"
								aria-label="<?php echo esc_attr( $play_video_label ); ?>">
								<?php if ( '' !== $poster ) : ?>
									<img src="<?php echo esc_url( $poster ); ?>" alt="<?php echo esc_attr( $video_label ); ?>" width="384" height="246">
								<?php endif; ?>
								<span class="person-video-card__play" aria-hidden="true">▶</span>
							</button>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
	<?php endwhile; ?>
</main>

<?php get_footer(); ?>
