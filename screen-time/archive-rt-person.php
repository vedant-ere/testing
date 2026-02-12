<?php
/**
 * Person archive template (Phase 1 static).
 *
 * @package ScreenTime
 */

get_header();
?>
<main class="page-archive-person">
	<section class="movie-section">
		<div class="container">
			<h1 class="section-title">Celebrities</h1>

			<div class="person-list" aria-label="Celebrities list">
				<?php
				$people = array(
					array( 'name' => 'Robert Downey Jr.', 'dob' => 'Born • 14 April 1965', 'bio' => 'Robert Downey Jr. has evolved into one of the most respected actors in Hollywood.', 'image' => 'assets/images/people/robert-downey-jr.jpg' ),
					array( 'name' => 'Chris Evans', 'dob' => 'Born • 13 June 1981', 'bio' => 'Chris Evans is an American actor known globally for Captain America.', 'image' => 'assets/images/people/chris-evans.jpg' ),
					array( 'name' => 'Mark Ruffalo', 'dob' => 'Born • 22 November 1967', 'bio' => 'Mark Ruffalo is known for playing Bruce Banner in the MCU.', 'image' => 'assets/images/people/mark-ruffalo.jpg' ),
					array( 'name' => 'Chris Hemsworth', 'dob' => 'Born • 11 August 1983', 'bio' => 'Chris Hemsworth is known for his role as Thor.', 'image' => 'assets/images/people/chris-hemsworth.jpg' ),
					array( 'name' => 'Scarlett Johansson', 'dob' => 'Born • 22 November 1984', 'bio' => 'Scarlett Johansson is one of the highest-paid actresses globally.', 'image' => 'assets/images/people/scarlett-johansson.jpg' ),
					array( 'name' => 'Tom Hiddleston', 'dob' => 'Born • 9 February 1981', 'bio' => 'Tom Hiddleston is known for portraying Loki.', 'image' => 'assets/images/people/tom-hiddleston.jpg' ),
				);

				foreach ( $people as $person ) {
					get_template_part( 'template-parts/person-card', null, $person );
				}
				?>
			</div>

			<div class="load-more-wrap">
				<button class="chip chip--outline" type="button">Load More</button>
			</div>
		</div>
	</section>
</main>
<?php get_footer(); ?>
