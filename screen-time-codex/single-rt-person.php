<?php
/**
 * Person single template (Phase 1 static).
 *
 * @package ScreenTime
 */

get_header();

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

$snapshots = array(
	array( 'src' => 'assets/images/people/snapshot-1.png', 'alt' => 'Chris Evans snapshot 1' ),
	array( 'src' => 'assets/images/people/snapshot-2.png', 'alt' => 'Chris Evans snapshot 2' ),
	array( 'src' => 'assets/images/people/snapshot-3.png', 'alt' => 'Chris Evans snapshot 3' ),
	array( 'src' => 'assets/images/people/snapshot-4.png', 'alt' => 'Chris Evans snapshot 4' ),
	array( 'src' => 'assets/images/people/snapshot-5.png', 'alt' => 'Chris Evans snapshot 5' ),
	array( 'src' => 'assets/images/people/snapshot-6.png', 'alt' => 'Chris Evans snapshot 6' ),
);

$videos = array(
	array( 'src' => 'assets/images/people/video-1.png', 'alt' => 'Chris Evans video 1' ),
	array( 'src' => 'assets/images/people/video-2.png', 'alt' => 'Chris Evans video 2' ),
	array( 'src' => 'assets/images/people/video-3.png', 'alt' => 'Chris Evans video 3' ),
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
					<div class="person-hero__meta-item"><span>Socials:</span><span>◎&nbsp;&nbsp;◔</span></div>
				</div>
			</div>
		</div>
	</section>

	<section class="person-about" id="about">
		<div class="container person-about__grid">
			<article class="person-about__content">
				<h2 class="section-title">About</h2>
				<p>Christopher Robert Evans (born June 13, 1981) is an American actor. He began his career with roles in television series such as Opposite Sex in 2000. Following appearances in several teen films, including 2001's Not Another Teen Movie, he gained attention for his portrayal of Marvel Comics character the Human Torch in Fantastic Four (2005) and Fantastic Four: Rise of the Silver Surfer (2007).</p>
				<p>Evans gained wider recognition for his portrayal of Steve Rogers / Captain America in several Marvel Cinematic Universe films, from Captain America: The First Avenger (2011) to Avengers: Endgame (2019). His work in the franchise established him as one of the world's highest-paid actors.</p>
				<p>Aside from comic book roles, Evans has starred in the drama Gifted (2017), the mystery film Knives Out (2019), and the television miniseries Defending Jacob (2020). He made his directorial debut in 2014 with the romantic drama Before We Go.</p>
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
