<?php
/**
 * Static person card.
 *
 * @package ScreenTime
 */

$person_name  = isset( $args['name'] ) ? $args['name'] : 'Actor Name';
$person_role  = isset( $args['role'] ) ? $args['role'] : '';
$person_dob   = isset( $args['dob'] ) ? $args['dob'] : 'Born - 01 Jan 1970';
$person_bio   = isset( $args['bio'] ) ? $args['bio'] : 'Biography text placeholder.';
$person_image = isset( $args['image'] ) ? $args['image'] : 'assets/images/people/person-default.jpg';
?>
<article class="person-card">
	<img class="person-card__image" src="<?php echo esc_url( get_template_directory_uri() . '/' . $person_image ); ?>" alt="<?php echo esc_attr( $person_name ); ?> portrait" width="153" height="224">
	<div class="person-card__content">
		<h3 class="person-card__name">
			<?php echo esc_html( $person_name ); ?>
			<?php if ( $person_role ) : ?>
				<span class="person-card__role">(<?php echo esc_html( $person_role ); ?>)</span>
			<?php endif; ?>
		</h3>
		<p class="person-card__dob"><?php echo esc_html( $person_dob ); ?></p>
		<p class="person-card__excerpt"><?php echo esc_html( $person_bio ); ?></p>
		<a class="person-card__link" href="#">Learn more <span aria-hidden="true">→</span></a>
	</div>
</article>
