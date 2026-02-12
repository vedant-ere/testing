<?php
/**
 * Movie single template (Phase 1 static).
 *
 * @package ScreenTime
 */

get_header();
?>
<main class="page-single-movie">
	<section class="movie-single-hero">
		<div class="container movie-single-hero__inner">
			<div class="movie-single-hero__poster">
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/movies/avengers-endgame-poster.jpg' ); ?>" alt="Avengers Endgame poster" width="393" height="590">
			</div>
			<div class="movie-single-hero__content">
				<h1>Avengers: Endgame</h1>
				<p class="movie-single-hero__meta">2019 • PG-13 • 3h 1m</p>
				<div class="hero-slider__tags">
					<span>Action</span>
					<span>Adventure</span>
					<span>Drama</span>
				</div>
				<p class="movie-single-hero__description">After the devastating events of Avengers: Infinity War (2018), the universe is in ruins. The Avengers assemble once more in order to undo Thanos' actions and restore balance to the universe.</p>
			</div>
		</div>
	</section>

	<section class="movie-section">
		<div class="container">
			<h2 class="section-title">Cast & Crew</h2>
			<div class="person-scroll" aria-label="Cast and crew list">
				<?php
				$crew_items = array(
					array( 'name' => 'Robert Downey Jr.', 'dob' => 'Role • Tony Stark', 'bio' => 'Lead actor in the Avengers franchise.', 'image' => 'assets/images/people/robert-downey-jr.jpg' ),
					array( 'name' => 'Chris Evans', 'dob' => 'Role • Steve Rogers', 'bio' => 'Plays Captain America.', 'image' => 'assets/images/people/chris-evans.jpg' ),
					array( 'name' => 'Scarlett Johansson', 'dob' => 'Role • Natasha Romanoff', 'bio' => 'Key member of the Avengers team.', 'image' => 'assets/images/people/scarlett-johansson.jpg' ),
				);

				foreach ( $crew_items as $person ) {
					get_template_part( 'template-parts/person-card', null, $person );
				}
				?>
			</div>
		</div>
	</section>

	<section class="movie-section">
		<div class="container">
			<h2 class="section-title">Photo Gallery</h2>
			<div class="gallery-grid" aria-label="Movie gallery">
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/movies/gallery-1.jpg' ); ?>" alt="Gallery still 1" width="460" height="260">
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/movies/gallery-2.jpg' ); ?>" alt="Gallery still 2" width="460" height="260">
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/movies/gallery-3.jpg' ); ?>" alt="Gallery still 3" width="460" height="260">
			</div>
		</div>
	</section>
</main>
<?php get_footer(); ?>
