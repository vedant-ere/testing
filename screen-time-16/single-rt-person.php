<?php
/**
 * Person single template (Phase 1 static).
 *
 * @package ScreenTime
 */

get_header();

/**
 * Static movie cards shown in the "Popular Movies" section.
 *
 * @var array<int, array<string, string>> $popular_movies
 */
$popular_movies = array(
	array(
		'title'    => 'Captain America',
		'runtime'  => '1 hr 14 min',
		'subtitle' => 'Crime • Thriller',
		'image'    => 'assets/images/movies/captain-america.png',
	),
	array(
		'title'    => 'Fantastic Four',
		'runtime'  => '1 hr 14 min',
		'subtitle' => 'Crime • Thriller',
		'image'    => 'assets/images/movies/fantastic-four.png',
	),
	array(
		'title'    => 'Avengers',
		'runtime'  => '1 hr 14 min',
		'subtitle' => 'Crime • Thriller',
		'image'    => 'assets/images/movies/avengers.png',
	),
);

/**
 * Static gallery images for the snapshots section.
 *
 * @var array<int, array<string, string>> $snapshots
 */
$snapshots = array(
	array(
		'src' => 'assets/images/people/snapshot-1.png',
		'alt' => 'Chris Evans snapshot 1',
	),
	array(
		'src' => 'assets/images/people/snapshot-2.png',
		'alt' => 'Chris Evans snapshot 2',
	),
	array(
		'src' => 'assets/images/people/snapshot-3.png',
		'alt' => 'Chris Evans snapshot 3',
	),
	array(
		'src' => 'assets/images/people/snapshot-4.png',
		'alt' => 'Chris Evans snapshot 4',
	),
	array(
		'src' => 'assets/images/people/snapshot-5.png',
		'alt' => 'Chris Evans snapshot 5',
	),
	array(
		'src' => 'assets/images/people/snapshot-6.png',
		'alt' => 'Chris Evans snapshot 6',
	),
);

/**
 * Static video thumbnails rendered as playable cards.
 *
 * @var array<int, array<string, string>> $videos
 */
$videos = array(
	array(
		'src' => 'assets/images/people/video-1.png',
		'alt' => 'Chris Evans video 1',
	),
	array(
		'src' => 'assets/images/people/video-2.png',
		'alt' => 'Chris Evans video 2',
	),
	array(
		'src' => 'assets/images/people/video-3.png',
		'alt' => 'Chris Evans video 3',
	),
);
?>

<main class="page-single-person">
	<section class="person-hero" id="top">
		<div class="container person-hero__inner">
			<div class="person-hero__image-wrap">
				<img class="person-hero__image" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/people/chris-evans.png' ); ?>" alt="Chris Evans portrait" width="488" height="572">
			</div>

			<div class="person-hero__content">
				<div class="person-hero__title-row">
					<h1>Chris Evans</h1>
					<p class="person-hero__full-name">Christopher Robert Evans</p>
				</div>

				<div class="person-hero__meta-list" aria-label="Person details">
					<div class="person-hero__meta-item"><span>Occupation:</span><span>Actor, Director, Producer</span></div>
					<div class="person-hero__meta-item"><span>Born:</span><span>13 June 1981 (age 41 years)</span></div>
					<div class="person-hero__meta-item"><span>Birthplace:</span><span>Boston, Massachusetts, United States</span></div>
						<div class="person-hero__meta-item"><span>Socials:</span>
							<span class="person-hero__socials">
								<a href="#" aria-label="Instagram">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" id="instagram">
										<path d="M17.34,5.46h0a1.2,1.2,0,1,0,1.2,1.2A1.2,1.2,0,0,0,17.34,5.46Zm4.6,2.42a7.59,7.59,0,0,0-.46-2.43,4.94,4.94,0,0,0-1.16-1.77,4.7,4.7,0,0,0-1.77-1.15,7.3,7.3,0,0,0-2.43-.47C15.06,2,14.72,2,12,2s-3.06,0-4.12.06a7.3,7.3,0,0,0-2.43.47A4.78,4.78,0,0,0,3.68,3.68,4.7,4.7,0,0,0,2.53,5.45a7.3,7.3,0,0,0-.47,2.43C2,8.94,2,9.28,2,12s0,3.06.06,4.12a7.3,7.3,0,0,0,.47,2.43,4.7,4.7,0,0,0,1.15,1.77,4.78,4.78,0,0,0,1.77,1.15,7.3,7.3,0,0,0,2.43.47C8.94,22,9.28,22,12,22s3.06,0,4.12-.06a7.3,7.3,0,0,0,2.43-.47,4.7,4.7,0,0,0,1.77-1.15,4.85,4.85,0,0,0,1.16-1.77,7.59,7.59,0,0,0,.46-2.43c0-1.06.06-1.4.06-4.12S22,8.94,21.94,7.88ZM20.14,16a5.61,5.61,0,0,1-.34,1.86,3.06,3.06,0,0,1-.75,1.15,3.19,3.19,0,0,1-1.15.75,5.61,5.61,0,0,1-1.86.34c-1,.05-1.37.06-4,.06s-3,0-4-.06A5.73,5.73,0,0,1,6.1,19.8,3.27,3.27,0,0,1,5,19.05a3,3,0,0,1-.74-1.15A5.54,5.54,0,0,1,3.86,16c0-1-.06-1.37-.06-4s0-3,.06-4A5.54,5.54,0,0,1,4.21,6.1,3,3,0,0,1,5,5,3.14,3.14,0,0,1,6.1,4.2,5.73,5.73,0,0,1,8,3.86c1,0,1.37-.06,4-.06s3,0,4,.06a5.61,5.61,0,0,1,1.86.34A3.06,3.06,0,0,1,19.05,5,3.06,3.06,0,0,1,19.8,6.1,5.61,5.61,0,0,1,20.14,8c.05,1,.06,1.37.06,4S20.19,15,20.14,16ZM12,6.87A5.13,5.13,0,1,0,17.14,12,5.12,5.12,0,0,0,12,6.87Zm0,8.46A3.33,3.33,0,1,1,15.33,12,3.33,3.33,0,0,1,12,15.33Z"></path>
									</svg>
								</a>
								<a href="#" aria-label="Twitter">
									<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" id="twitter">
										<path d="M22,5.8a8.49,8.49,0,0,1-2.36.64,4.13,4.13,0,0,0,1.81-2.27,8.21,8.21,0,0,1-2.61,1,4.1,4.1,0,0,0-7,3.74A11.64,11.64,0,0,1,3.39,4.62a4.16,4.16,0,0,0-.55,2.07A4.09,4.09,0,0,0,4.66,10.1,4.05,4.05,0,0,1,2.8,9.59v.05a4.1,4.1,0,0,0,3.3,4A3.93,3.93,0,0,1,5,13.81a4.9,4.9,0,0,1-.77-.07,4.11,4.11,0,0,0,3.83,2.84A8.22,8.22,0,0,1,3,18.34a7.93,7.93,0,0,1-1-.06,11.57,11.57,0,0,0,6.29,1.85A11.59,11.59,0,0,0,20,8.45c0-.17,0-.35,0-.53A8.43,8.43,0,0,0,22,5.8Z"></path>
									</svg>
								</a>
							</span>
						</div>
				</div>
			</div>
		</div>
	</section>

	<section class="person-about" id="about">
		<div class="container person-about__grid">
			<article class="person-about__content">
				<h2 class="section-title">About</h2>
				<p>Christopher Robert Evans (born June 13, 1981) is an American actor. He began his career with roles in television series such as <a href="https://en.wikipedia.org/wiki/Opposite_Sex_(TV_series)">Opposite Sex</a> in 2000. Following appearances in several teen films, including 2001's <a href="https://en.wikipedia.org/wiki/Not_Another_Teen_Movie">Not Another Teen Movie</a>, he gained attention for his portrayal of Marvel Comics character the <a href="https://en.wikipedia.org/wiki/Human_Torch">Human Torch in Fantastic Four</a> (2005) and <a href="#">Fantastic Four: Rise of the Silver Surfer</a> (2007). Evans made further appearances in film adaptations of <a href="https://en.wikipedia.org/wiki/Fantastic_Four:_Rise_of_the_Silver_Surfer">comic books</a> and graphic novels: TMNT (2007), <a href="#">Scott Pilgrim vs. the World</a> (2010), and <a href="#">Snowpiercer</a> (2013).</p>
				<p>Evans gained wider recognition for his portrayal of <a href="https://en.wikipedia.org/wiki/Steve_Rogers_(Marvel_Cinematic_Universe)">Steve Rogers / Captain America</a> in several Marvel Cinematic Universe films, from <a href="https://en.wikipedia.org/wiki/Steve_Rogers_(Marvel_Cinematic_Universe)">Captain America: The First Avenger</a> (2011) to <a href="https://en.wikipedia.org/wiki/Avengers:_Endgame">Avengers: Endgame</a> (2019). His work in the franchise established him as one of the world's highest-paid actors.</p>
				<p>Aside from comic book roles, Evans has starred in the drama <a href="https://en.wikipedia.org/wiki/Gifted_(2017_film)">Gifted</a> (2017), the mystery film <a href="https://en.wikipedia.org/wiki/Knives_Out">Knives Out</a> (2019), and the television miniseries <a href="#">Defending Jacob</a> (2020). He made his directorial debut in 2014 with the romantic drama Before We Go, which he also produced and starred in. Evans made his Broadway debut in the 2018 revival of Kenneth Lonergan's play Lobby Hero, which earned him a <a href="https://en.wikipedia.org/wiki/Drama_League_Award">Drama League Award</a> nomination.</p>
			</article>

			<aside class="person-about__quick-links" aria-label="Quick Links">
				<h2>Quick Links</h2>
				<ul>
					<li><a href="#about">About</a></li>
					<li><a href="#popular-movies">Popular Movies</a></li>
					<li><a href="#snapshots">Snapshots</a></li>
					<li><a href="#videos">Videos</a></li>
				</ul>
			</aside>
		</div>
	</section>

	<section class="person-section" id="popular-movies">
		<div class="container">
			<h2 class="section-title">Popular Movies</h2>
			<div class="person-popular-grid">
				<?php foreach ( $popular_movies as $movie ) : ?>
					<?php get_template_part( 'template-parts/movie-card', null, $movie ); ?>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="person-section" id="snapshots">
		<div class="container">
			<h2 class="section-title">Snapshots</h2>
			<div class="person-snapshot-grid">
				<?php foreach ( $snapshots as $snapshot ) : ?>
					<img src="<?php echo esc_url( get_template_directory_uri() . '/' . $snapshot['src'] ); ?>" alt="<?php echo esc_attr( $snapshot['alt'] ); ?>" width="592" height="419">
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="person-section" id="videos">
		<div class="container">
			<h2 class="section-title">Videos</h2>
			<div class="person-video-grid">
				<?php foreach ( $videos as $index => $video ) : ?>
					<button type="button" class="person-video-card" aria-label="Play video <?php echo esc_attr( (string) ( $index + 1 ) ); ?>">
						<img src="<?php echo esc_url( get_template_directory_uri() . '/' . $video['src'] ); ?>" alt="<?php echo esc_attr( $video['alt'] ); ?>" width="384" height="246">
						<span class="person-video-card__play" aria-hidden="true">▶</span>
					</button>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
</main>

<?php get_footer(); ?>
