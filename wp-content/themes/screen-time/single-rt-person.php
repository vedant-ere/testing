<?php
/**
 * Person single template (Phase 1 static).
 *
 * @package ScreenTime
 */

get_header();
?>
<main class="page-single-person">
	<section class="person-hero">
		<div class="container person-hero__inner">
			<img class="person-hero__image" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/people/robert-downey-jr.jpg' ); ?>" alt="Robert Downey Jr portrait" width="280" height="280">
			<div class="person-hero__content">
				<h1>Robert Downey Jr.</h1>
				<p class="person-hero__meta">Born • 14 April 1965</p>
				<p>Robert Downey Jr. is an American actor and producer. His career has been characterized by critical and popular success in his youth and then a resurgence in middle age.</p>
			</div>
		</div>
	</section>

	<section class="movie-section">
		<div class="container">
			<h2 class="section-title">Known For</h2>
			<div class="movie-grid movie-grid--scroll-mobile">
				<?php
				$known_for = array(
					array( 'title' => 'Avengers: Endgame', 'runtime' => '3 hr 1 min', 'subtitle' => 'Action • Adventure', 'image' => 'assets/images/movies/avengers-endgame-poster.jpg' ),
					array( 'title' => 'Iron Man', 'runtime' => '2 hr 6 min', 'subtitle' => 'Action • Sci-Fi', 'image' => 'assets/images/movies/iron-man.jpg' ),
					array( 'title' => 'Sherlock Holmes', 'runtime' => '2 hr 8 min', 'subtitle' => 'Mystery • Action', 'image' => 'assets/images/movies/sherlock-holmes.jpg' ),
				);

				foreach ( $known_for as $movie ) {
					get_template_part( 'template-parts/movie-card', null, $movie );
				}
				?>
			</div>
		</div>
	</section>
</main>
<?php get_footer(); ?>
