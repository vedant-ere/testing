<?php
/**
 * Static featured slider (Phase 1).
 *
 * @package ScreenTime
 */

$slides = array(
	array(
		'image'       => 'assets/images/slider/avengers-endgame.jpg',
		'alt'         => 'Avengers Endgame poster collage',
		'title'       => 'Avengers: Endgame',
		'description' => 'After the devastating events of Avengers: Infinity War (2018), the universe is in ruins. With the help of remaining allies, the Avengers assemble once more.',
	),
	array(
		'image'       => 'assets/images/slider/john-wick.jpg',
		'alt'         => 'John Wick hero artwork',
		'title'       => 'John Wick',
		'description' => 'Legendary hitman John Wick is forced out of retirement once again and faces a dangerous web of assassins.',
	),
	array(
		'image'       => 'assets/images/slider/the-witcher.jpg',
		'alt'         => 'The Witcher character hero image',
		'title'       => 'The Witcher',
		'description' => 'Geralt of Rivia, a mutated monster hunter for hire, journeys toward his destiny in a turbulent world.',
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
					<h1 class="hero-slider__title"><?php echo esc_html( $slide['title'] ); ?></h1>
					<p class="hero-slider__description"><?php echo esc_html( $slide['description'] ); ?></p>
					<div class="hero-slider__meta">
						<span>2019</span>
						<span>PG-13</span>
						<span>3h 1m</span>
					</div>
					<div class="hero-slider__tags">
						<span>Action</span>
						<span>Adventure</span>
						<span>Drama</span>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
	</div>
	<div class="hero-slider__controls">
		<button class="hero-slider__arrow" type="button" data-slider-prev aria-label="Previous slide">←</button>
		<div class="hero-slider__dots" role="tablist" aria-label="Slider navigation dots">
			<?php foreach ( $slides as $index => $slide ) : ?>
				<button class="hero-slider__dot" type="button" role="tab" aria-label="Go to slide <?php echo esc_attr( (string) ( $index + 1 ) ); ?>" aria-current="<?php echo 0 === $index ? 'true' : 'false'; ?>" data-slider-dot="<?php echo esc_attr( (string) $index ); ?>"></button>
			<?php endforeach; ?>
		</div>
		<button class="hero-slider__arrow" type="button" data-slider-next aria-label="Next slide">→</button>
	</div>
</section>
