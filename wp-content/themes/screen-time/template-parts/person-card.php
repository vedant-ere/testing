<?php
/**
 * Static person card.
 *
 * @package ScreenTime
 */

$person_name  = isset( $args['name'] ) ? $args['name'] : 'Actor Name';
$person_dob   = isset( $args['dob'] ) ? $args['dob'] : 'Born - 01 Jan 1970';
$person_bio   = isset( $args['bio'] ) ? $args['bio'] : 'Biography text placeholder.';
$person_image = isset( $args['image'] ) ? $args['image'] : 'assets/images/people/person-default.jpg';
?>
<article class="person-card">
	<img class="person-card__image" src="<?php echo esc_url( get_template_directory_uri() . '/' . $person_image ); ?>" alt="<?php echo esc_attr( $person_name ); ?> portrait" width="80" height="80">
	<div>
		<h3 class="movie-card__title"><?php echo esc_html( $person_name ); ?></h3>
		<p class="movie-card__subtitle"><?php echo esc_html( $person_dob ); ?></p>
		<p class="movie-card__subtitle"><?php echo esc_html( $person_bio ); ?></p>
	</div>
</article>
