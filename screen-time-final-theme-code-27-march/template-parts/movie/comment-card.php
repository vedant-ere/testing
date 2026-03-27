<?php
/**
 * Movie review card template part.
 *
 * Expected args:
 * - comment_id (int)
 * - comment_classes (string)
 * - initial (string)
 * - author (string)
 * - text (string)
 * - date (string)
 *
 * @package ScreenTime
 */

$comment_id      = isset( $args['comment_id'] ) ? (int) $args['comment_id'] : 0;
$comment_classes = isset( $args['comment_classes'] ) ? (string) $args['comment_classes'] : 'movie-review-card';
$initial         = isset( $args['initial'] ) ? (string) $args['initial'] : '';
$author          = isset( $args['author'] ) ? (string) $args['author'] : '';
$text            = isset( $args['text'] ) ? (string) $args['text'] : '';
$date            = isset( $args['date'] ) ? (string) $args['date'] : '';
?>

<article id="comment-<?php echo esc_attr( (string) $comment_id ); ?>" class="<?php echo esc_attr( $comment_classes ); ?>">
	<p class="movie-review-card__author">
		<span class="movie-review-card__icon" aria-hidden="true"><?php echo esc_html( $initial ); ?></span>
		<?php echo esc_html( $author ); ?>
	</p>
	<div class="movie-review-card__text"><?php echo wp_kses_post( $text ); ?></div>
	<p class="movie-review-card__date"><?php echo esc_html( $date ); ?></p>
</article>
