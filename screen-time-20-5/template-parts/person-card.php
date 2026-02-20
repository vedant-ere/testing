<?php
/**
 * Person card.
 *
 * Accepts `$args` from `get_template_part()` with optional keys:
 * `name`, `role`, `dob`, `bio`, `image`, and `link`.
 *
 * @package ScreenTime
 */

/**
 * Normalize person card fields with fallback placeholders.
 *
 * @var string $person_name
 * @var string $person_role
 * @var string $person_dob
 * @var string $person_bio
 * @var string $person_image
 * @var string $person_link
 * @var string $person_class
 */
$person_name  = isset( $args['name'] ) ? $args['name'] : 'Actor Name';
$person_role  = isset( $args['role'] ) ? $args['role'] : '';
$person_dob   = isset( $args['dob'] ) ? $args['dob'] : 'Born - 01 Jan 1970';
$person_bio   = isset( $args['bio'] ) ? $args['bio'] : 'Biography text placeholder.';
$person_image = isset( $args['image'] ) ? $args['image'] : 'assets/images/people/person-default.jpg';
$person_link  = isset( $args['link'] ) ? $args['link'] : '#';
$person_class = isset( $args['class'] ) ? (string) $args['class'] : 'person-card';

if ( 0 !== strpos( $person_image, 'http://' ) && 0 !== strpos( $person_image, 'https://' ) && 0 !== strpos( $person_image, '/' ) ) {
	$person_image = trailingslashit( get_template_directory_uri() ) . ltrim( $person_image, '/' );
}
?>
<article class="<?php echo esc_attr( $person_class ); ?>">
	<?php
	/* translators: %s: person name. */
	$portrait_alt = sprintf( __( '%s portrait', 'screen-time' ), $person_name );
	?>
	<img class="person-card__image" src="<?php echo esc_url( $person_image ); ?>" alt="<?php echo esc_attr( $portrait_alt ); ?>" width="153" height="224">
	<div class="person-card__content">
		<h3 class="person-card__name">
			<?php echo esc_html( $person_name ); ?>
			<?php if ( $person_role ) : ?>
				<span class="person-card__role">(<?php echo esc_html( $person_role ); ?>)</span>
			<?php endif; ?>
		</h3>
		<p class="person-card__dob"><?php echo esc_html( $person_dob ); ?></p>
		<p class="person-card__excerpt"><?php echo esc_html( $person_bio ); ?></p>
		<a class="person-card__link" href="<?php echo esc_url( $person_link ); ?>"><?php esc_html_e( 'Learn more', 'screen-time' ); ?> <span aria-hidden="true">→</span></a>
	</div>
</article>
