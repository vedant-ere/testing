<?php
/**
 * Movie single template (Phase 1 static).
 *
 * @package ScreenTime
 */

get_header();
?>

<main class="page-single-movie">
	<section class="movie-single-hero" id="top">
		<div class="container movie-single-hero__inner">
			<div class="movie-single-hero__poster-wrap">
				<img class="movie-single-hero__poster" src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/movies/avengers-endgame.png' ); ?>" alt="Avengers Endgame poster" width="552" height="876">
			</div>
			<div class="movie-single-hero__content">
				<h1>Avengers: Endgame</h1>
				<p class="movie-single-hero__rating-row"><span class="movie-single-hero__star" aria-hidden="true">★</span><strong>8.4/10</strong><span aria-hidden="true">•</span><span>2019</span><span aria-hidden="true">•</span><span>PG-13</span><span aria-hidden="true">•</span><span>3H 1M</span></p>
				<p class="movie-single-hero__description">After the devastating events of Avengers: Infinity War (2018), the universe is in ruins. With the help of remaining allies, the Avengers assemble once more in order to reverse Thanos actions and restore balance to the universe.</p>
				<ul class="movie-single-hero__genres" aria-label="Genres"><li>Action</li><li>Adventure</li><li>Drama</li></ul>
				<p class="movie-single-hero__directors"><strong>Directors:</strong> Anthony Russo <span aria-hidden="true">•</span> Joe Russo</p>
			</div>
		</div>
	</section>

	<section class="movie-single-body" id="synopsis">
		<div class="container movie-single-body__grid">
			<article class="movie-single-body__copy">
				<h2 class="section-title">Synopsis</h2>
				<p>After the devastating events of Avengers: Infinity War (2018), the universe is in ruins. With the help of remaining allies, the Avengers assemble once more in order to reverse Thanos actions and restore balance to the universe.</p>
				<p>After the devastating events of Avengers: Infinity War (2018), the universe is in ruins due to the efforts of the Mad Titan, Thanos. With the help of remaining allies, the Avengers must assemble once more in order to undo Thanos actions and undo the chaos to the universe.</p>
				<p>The grave course of events set in motion by Thanos compels the remaining Avengers to take one final stand in Marvel Studios grand conclusion to twenty-two films.</p>
				<p>Confusion. Loss. The overwhelming devastation caused by the Mad Titan Thanos has left what remains of the Avengers reeling.</p>
			</article>
			<aside class="movie-single-body__quick-links" aria-label="Quick Links">
				<h2>Quick Links</h2>
				<ul>
					<li><a href="#synopsis">Synopsis</a></li>
					<li><a href="#cast-crew">Cast &amp; Crew</a></li>
					<li><a href="#snapshots">Snapshots</a></li>
					<li><a href="#trailers">Trailer &amp; Clips</a></li>
					<li><a href="#reviews">Reviews</a></li>
				</ul>
			</aside>
		</div>
	</section>

	<section class="movie-single-section" id="cast-crew">
		<div class="container">
			<div class="movie-single-section__heading"><h2 class="section-title">Cast &amp; Crew</h2><a href="#" class="movie-single-section__view-all">View All →</a></div>
			<div class="movie-cast-grid">
				<?php $cast = array(
					array('Robert Downey Jr.', 'robert-downey-jr.jpg'),
					array('Chris Evans', 'chris-evans.jpg'),
					array('Mark Ruffalo', 'mark-ruffalo.jpg'),
					array('Chris Hemsworth', 'chris-hemsworth.jpg'),
					array('Jeremy Renner', 'jeremy-renner.jpg'),
					array('Scarlett Johansson', 'scarlett-johansson.jpg'),
					array('Elizabeth Olsen', 'elizabeth-olsen.jpg'),
					array('Tom Hiddleston', 'tom-hiddleston.jpg'),
				); foreach ($cast as $person) : ?>
				<article class="movie-cast-card"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/people/' . $person[1] ); ?>" alt="<?php echo esc_attr($person[0]); ?>" width="280" height="248"><h3><?php echo esc_html($person[0]); ?></h3></article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="movie-single-section" id="snapshots">
		<div class="container">
			<h2 class="section-title">Snapshots</h2>
			<div class="movie-snapshot-grid">
				<?php for ($i=1; $i<=6; $i++) : ?>
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/movies/snapshot-' . $i . '.jpg' ); ?>" alt="Snapshot <?php echo esc_attr((string)$i); ?>" width="592" height="419">
				<?php endfor; ?>
			</div>
		</div>
	</section>

	<section class="movie-single-section" id="trailers">
		<div class="container">
			<h2 class="section-title">Trailer &amp; Clips</h2>
			<div class="movie-trailer-grid">
				<?php for ($i=1; $i<=3; $i++) : ?>
				<button type="button" class="movie-trailer-card" aria-label="Play trailer <?php echo esc_attr((string)$i); ?>"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/images/movies/trailer-' . $i . '.jpg' ); ?>" alt="Trailer <?php echo esc_attr((string)$i); ?>" width="384" height="246"><span class="movie-trailer-card__play" aria-hidden="true">▶</span></button>
				<?php endfor; ?>
			</div>
		</div>
	</section>

	<section class="movie-single-section" id="reviews">
		<div class="container">
			<h2 class="section-title">Reviews</h2>
			<div class="movie-review-grid">
				<?php $names = array('Maria Russo','Nathan Tyler','Natalie Dyer','Anna Harris'); foreach ($names as $name) : ?>
				<article class="movie-review-card"><p class="movie-review-card__author"><span class="movie-review-card__icon" aria-hidden="true">◔</span><?php echo esc_html($name); ?></p><p class="movie-review-card__text">Where to begin, where to begin! You know a movie is outstanding when the end credits alone are more epic than the majority of films released in the last 20 years.</p><p class="movie-review-card__date">12 Dec 2022</p></article>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="movie-single-form">
		<div class="container">
			<form class="movie-review-form" action="#" method="post">
				<h2>Leave a Review</h2>
				<p>Your Email Address will not be published. Required fields are marked *</p>
				<label for="review-comment">Comment*</label>
				<textarea id="review-comment" rows="6"></textarea>
				<div class="movie-review-form__row"><div><label for="review-name">Name*</label><input id="review-name" type="text"></div><div><label for="review-email">Email*</label><input id="review-email" type="email"></div></div>
				<label for="review-website">Website</label>
				<input id="review-website" type="text">
				<label class="movie-review-form__checkbox"><input type="checkbox"> Save my name and email in this browser for the next time I comment.</label>
				<button type="submit">Post Review</button>
			</form>
		</div>
	</section>
</main>

<?php get_footer(); ?>
