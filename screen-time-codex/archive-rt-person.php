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
			<h1 class="section-title section-title--person-archive">Celebrities</h1>

			<div class="person-list" aria-label="Celebrities list">
				<?php
				$people = array(
					array( 'name' => 'Robert Downey Jr.', 'role' => 'Iron Man', 'dob' => 'Born - 14 April 1965', 'bio' => 'Robert Downey Jr. has evolved into one of the most respected actors in Hollywood. With an amazing body of work spanning decades, he remains a fan favorite.', 'image' => 'assets/images/people/robert-downey-jr.png' ),
					array( 'name' => 'Chris Evans', 'role' => 'Captain America', 'dob' => 'Born - 13 June 1981', 'bio' => 'Christopher Robert Evans is an American actor. He began his career with roles in television series such as Opposite Sex and rose to global fame through Marvel films.', 'image' => 'assets/images/people/chris-evans.png' ),
					array( 'name' => 'Mark Ruffalo', 'role' => 'The Hulk', 'dob' => 'Born - 22 November 1967', 'bio' => 'Mark Alan Ruffalo is an American actor and producer. He began acting in the early 1990s and first gained recognition for his stage and film performances.', 'image' => 'assets/images/people/mark-ruffalo.png' ),
					array( 'name' => 'Chris Hemsworth', 'role' => 'Thor', 'dob' => 'Born - 11 August 1983', 'bio' => 'Chris Hemsworth AM is an Australian actor. He rose to prominence playing Kim Hyde and later became internationally known as Thor in the Marvel Cinematic Universe.', 'image' => 'assets/images/people/chris-hemsworth.png' ),
					array( 'name' => 'Jeremy Renner', 'role' => 'Hawkeye', 'dob' => 'Born - 7 January 1971', 'bio' => 'Jeremy Lee Renner is an American actor and musician. He began his career by appearing in independent films before receiving mainstream recognition for intense performances.', 'image' => 'assets/images/people/jeremy-renner.png' ),
					array( 'name' => 'Scarlett Johansson', 'role' => 'Black Widow', 'dob' => 'Born - 22 November 1984', 'bio' => 'Scarlett Ingrid Johansson is an American actress. The world\'s highest-paid actress in 2018 and 2019, she is known for critically acclaimed and blockbuster roles alike.', 'image' => 'assets/images/people/scarlett-johansson.png' ),
					array( 'name' => 'Elizabeth Olsen', 'role' => 'Wanda Maximov', 'dob' => 'Born - 16 February 1989', 'bio' => 'Elizabeth Chase Olsen is an American actress. Born in Sherman Oaks, California, Olsen began acting at age four and gained international acclaim as Wanda Maximoff.', 'image' => 'assets/images/people/elizabeth-olsen.png' ),
					array( 'name' => 'Tom Hiddleston', 'role' => 'Loki', 'dob' => 'Born - 9 February 1981', 'bio' => 'Thomas William Hiddleston is an English actor. He gained international fame portraying Loki in the MCU and is praised for his stage work and dramatic range.', 'image' => 'assets/images/people/tom-hiddleston.png' ),
					array( 'name' => 'Brie Larson', 'role' => 'Captain Marvel', 'dob' => 'Born - 1 October 1989', 'bio' => 'Brianne Sidonie Desaulniers, known professionally as Brie Larson, is an American actress. Known for her nuanced performances, she also stars as Captain Marvel.', 'image' => 'assets/images/people/brie-larson.png' ),
					array( 'name' => 'Paul Rudd', 'role' => 'Ant Man', 'dob' => 'Born - 6 April 1969', 'bio' => 'Paul Stephen Rudd is an American actor. He studied theater at the University of Kansas and the American Academy of Dramatic Arts before starting his screen career.', 'image' => 'assets/images/people/paul-rudd.png' ),
					array( 'name' => 'Chadwick Boseman', 'role' => 'Black Panther', 'dob' => 'Born - 29 November 1976', 'bio' => 'Chadwick Aaron Boseman was an American actor. During his two-decade career, Boseman received major acclaim for portraying iconic historical and superhero figures.', 'image' => 'assets/images/people/chadwick-boseman.png' ),
					array( 'name' => 'Benedict Cumberbatch', 'role' => 'Doctor Strange', 'dob' => 'Born - 19 July 1976', 'bio' => 'Benedict Timothy Carlton Cumberbatch CBE is an English actor. Known for his work on screen and stage, he is celebrated globally for his role as Doctor Strange.', 'image' => 'assets/images/people/benedict-cumberbatch.png' ),
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
