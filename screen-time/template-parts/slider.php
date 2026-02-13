<?php
/**
 * Static featured slider (Phase 1).
 *
 * Expects front-page JavaScript (`assets/js/slider.js`) to read `data-*`
 * attributes and control active slide state, dots, and arrow navigation.
 *
 * @package ScreenTime
 */

/**
 * Static slide payload for the home hero.
 *
 * @var array<int, array<string, string>> $slides
 */
$slides = array(
	array(
		'image'       => 'assets/images/slider/avengers-endgame.png',
		'alt'         => 'Avengers Endgame poster collage',
		'title'       => 'Avengers: Endgame',
		'description' => 'After the devastating events of Avengers: Infinity War (2018), the universe is in ruins. With the help of remaining allies, the Avengers assemble once more in order to reverse Thanos',
	),
	array(
		'image'       => 'assets/images/slider/john-wick.png',
		'alt'         => 'John Wick hero artwork',
		'title'       => 'John Wick',
		'description' => 'Legendary hitman John Wick is forced out of retirement once again and faces a dangerous web of assassins.',
	),
	array(
		'image'       => 'assets/images/slider/the-witcher.png',
		'alt'         => 'The Witcher character hero image',
		'title'       => 'The Witcher',
		'description' => 'Geralt of Rivia, a mutated monster hunter for hire, journeys toward his destiny in a turbulent world.',
	),
	array(
		'image'       => 'assets/images/slider/black-adam.png',
		'alt'         => 'Black Adam hero image',
		'title'       => 'Black Adam',
		'description' => 'a powerful, empowered by gods who protects using brutal justice rather than traditional heroism',
	),
);
?>
<section class="hero-slider" role="region" aria-label="Featured movies slider" data-slider>
	<div class="hero-slider__track">
		<?php foreach ( $slides as $index => $slide ) : ?>
			<article class="hero-slider__slide <?php echo 0 === $index ? 'is-active' : ''; ?>" aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>" data-slide>
				<div class="hero-slider__image">
					<img src="<?php echo esc_url( get_template_directory_uri() . '/' . $slide['image'] ); ?>" alt="<?php echo esc_attr( $slide['alt'] ); ?>" width="1440" height="620">
				</div>
				<div class="hero-slider__overlay" aria-hidden="true"></div>
				<div class="container hero-slider__content">
					<div class="hero-slider__panel">
						<h1 class="hero-slider__title"><?php echo esc_html( $slide['title'] ); ?></h1>
						<p class="hero-slider__description"><?php echo esc_html( $slide['description'] ); ?></p>
						<div class="hero-slider__meta">
							<span>2019</span>•
							<span>PG-13</span>•
							<span>3h 1m</span>
						</div>
				
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
	<div class="hero-slider__controls">
		<div class="hero-slider__dots" role="tablist" aria-label="Slider navigation dots">
			<?php foreach ( $slides as $index => $slide ) : ?>
				<button class="hero-slider__dot" type="button" role="tab" aria-label="Go to slide <?php echo esc_attr( (string) ( $index + 1 ) ); ?>" aria-current="<?php echo 0 === $index ? 'true' : 'false'; ?>" data-slider-dot="<?php echo esc_attr( (string) $index ); ?>"></button>
			<?php endforeach; ?>
		</div>
	</div>
</section>
