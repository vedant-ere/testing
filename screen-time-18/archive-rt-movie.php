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
			<h1 class="section-title section-title--page">Movies</h1>

			<div class="movie-grid">
				<?php
				/**
				 * Static movie cards rendered for the archive grid.
				 *
				 * @var array<int, array<string, string>> $movie_archive_items
				 */
				$movie_archive_items = array(
					array(
						'title'    => 'The Silence of the Lambs',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/the-silence-of-the-lambs.png',
					),
					array(
						'title'    => 'The Shawshank Redemption',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/the-shawshank-redemption.png',
					),
					array(
						'title'    => 'Schindler’s List',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/schindlers-list.png',
					),

					array(
						'title'    => 'Casablanca',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/casablanca.png',
					),
					array(
						'title'    => 'Forrest Gump',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/forrest-gump.png',
					),
					array(
						'title'    => 'The Dark Knight',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/the-dark-knight.png',
					),

					array(
						'title'    => 'Pulp Fiction',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/pulp-fiction.png',
					),
					array(
						'title'    => 'Inception',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/inception.png',
					),
					array(
						'title'    => 'Lawrence of Arabia',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/lawrence-of-arabia.png',
					),

					array(
						'title'    => 'The Godfather',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/the-godfather.png',
					),
					array(
						'title'    => 'Raging Bull',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/raging-bull.png',
					),
					array(
						'title'    => 'Star Wars',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/star-wars.png',
					),

					array(
						'title'    => 'Gladiator',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/gladiator.png',
					),
					array(
						'title'    => 'Saving Private Ryan',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/saving-private-ryan.png',
					),
					array(
						'title'    => 'The Shining',
						'runtime'  => '1 hr 14 min',
						'subtitle' => 'Release: 12 Dec 2022',
						'image'    => 'assets/images/movies/the-shining.png',
					),
				);


				// Render each archive item using the shared movie card partial.
				foreach ( $movie_archive_items as $movie ) {
					get_template_part( 'template-parts/movie-card', null, $movie );
				}
				?>
			</div>

			<nav class="archive-pagination" aria-label="Movie pagination">
				<a href="#" class="archive-pagination__link archive-pagination__link--active">1</a>
				<a href="#" class="archive-pagination__link">2</a>
				<a href="#" class="archive-pagination__link">3</a>
			</nav>
		</div>
	</section>
</main>
<?php get_footer(); ?>
