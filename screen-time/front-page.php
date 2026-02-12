<?php
/**
 * Front page template (Phase 1 static).
 *
 * @package ScreenTime
 */

get_header();
?>

<main class="page-home">
	<?php get_template_part( 'template-parts/slider' ); ?>

	<section class="movie-section">
		<div class="container">
			<h2 class="section-title">Upcoming Movies</h2>
			<div class="movie-grid movie-grid--scroll-mobile">
				<?php
				$upcoming_movies = array(
					array(
						'title'    => 'Spiderman: Far From Home',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/spiderman.jpg',
					),
					array(
						'title'    => 'Joker',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/joker.jpg',
					),
					array(
						'title'    => 'Black Panther',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/black-panther.jpg',
					),
					array(
						'title'    => 'Blade Runner 2049',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/blade-runner.jpg',
					),
					array(
						'title'    => 'Black Adam',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/black-adam.jpg',
					),
					array(
						'title'    => 'Baby Driver',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/baby-driver.jpg',
					),
				);

				foreach ( $upcoming_movies as $movie ) {
					get_template_part( 'template-parts/movie-card', null, $movie );
				}
				?>
			</div>
		</div>
	</section>

	<section class="movie-section">
		<div class="container">
			<h2 class="section-title">Trending Now</h2>
			<div class="movie-grid movie-grid--scroll-mobile">
				<?php
				$trending_movies = array(
					array(
						'title'    => 'Once Upon a Time',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Crime • Thriller',
						'image'    => 'assets/images/movies/once-upon-a-time.jpg',
					),
					array(
						'title'    => 'John Wick',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Crime • Thriller',
						'image'    => 'assets/images/movies/john-wick.jpg',
					),
					array(
						'title'    => 'The Witcher',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Crime • Thriller',
						'image'    => 'assets/images/movies/the-witcher.jpg',
					),
					array(
						'title'    => 'The Hunger Games',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Crime • Thriller',
						'image'    => 'assets/images/movies/hunger-games.jpg',
					),
					array(
						'title'    => 'Sicario',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Crime • Thriller',
						'image'    => 'assets/images/movies/sicario.jpg',
					),
					array(
						'title'    => 'Shazam',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Crime • Thriller',
						'image'    => 'assets/images/movies/shazam.jpg',
					),
				);

				foreach ( $trending_movies as $movie ) {
					get_template_part( 'template-parts/movie-card', null, $movie );
				}
				?>
			</div>
		</div>
	</section>
</main>

<?php
get_footer();
