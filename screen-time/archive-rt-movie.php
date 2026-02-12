<?php
/**
 * Movie archive template (Phase 1 static).
 *
 * @package ScreenTime
 */

get_header();
?>
<main class="page-archive-movie">
	<section class="movie-section">
		<div class="container">
			<h1 class="section-title">Movies</h1>
			<div class="archive-toolbar" aria-label="Movie filters">
				<button type="button" class="chip chip--active">All</button>
				<button type="button" class="chip">Action</button>
				<button type="button" class="chip">Thriller</button>
				<button type="button" class="chip">Drama</button>
			</div>

			<div class="movie-grid">
				<?php
				$movie_archive_items = array(
					array( 'title' => 'Spiderman: Far From Home', 'runtime' => '1 hr 14 min', 'subtitle' => 'Release: 12 Dec 2022', 'image' => 'assets/images/movies/spiderman.jpg' ),
					array( 'title' => 'Joker', 'runtime' => '1 hr 14 min', 'subtitle' => 'Release: 12 Dec 2022', 'image' => 'assets/images/movies/joker.jpg' ),
					array( 'title' => 'Black Panther', 'runtime' => '1 hr 14 min', 'subtitle' => 'Release: 12 Dec 2022', 'image' => 'assets/images/movies/black-panther.jpg' ),
					array( 'title' => 'Blade Runner 2049', 'runtime' => '1 hr 14 min', 'subtitle' => 'Release: 12 Dec 2022', 'image' => 'assets/images/movies/blade-runner.jpg' ),
					array( 'title' => 'Black Adam', 'runtime' => '1 hr 14 min', 'subtitle' => 'Release: 12 Dec 2022', 'image' => 'assets/images/movies/black-adam.jpg' ),
					array( 'title' => 'Baby Driver', 'runtime' => '1 hr 14 min', 'subtitle' => 'Release: 12 Dec 2022', 'image' => 'assets/images/movies/baby-driver.jpg' ),
					array( 'title' => 'John Wick', 'runtime' => '1 hr 14 min', 'subtitle' => 'Release: 12 Dec 2022', 'image' => 'assets/images/movies/john-wick.jpg' ),
					array( 'title' => 'The Witcher', 'runtime' => '1 hr 14 min', 'subtitle' => 'Release: 12 Dec 2022', 'image' => 'assets/images/movies/the-witcher.jpg' ),
					array( 'title' => 'Shazam', 'runtime' => '1 hr 14 min', 'subtitle' => 'Release: 12 Dec 2022', 'image' => 'assets/images/movies/shazam.jpg' ),
				);

				foreach ( $movie_archive_items as $movie ) {
					get_template_part( 'template-parts/movie-card', null, $movie );
				}
				?>
			</div>

			<nav class="archive-pagination" aria-label="Movie pagination">
				<a href="#" class="archive-pagination__link archive-pagination__link--active">1</a>
				<a href="#" class="archive-pagination__link">2</a>
				<a href="#" class="archive-pagination__link">3</a>
				<a href="#" class="archive-pagination__link">Next</a>
			</nav>
		</div>
	</section>
</main>
<?php get_footer(); ?>
