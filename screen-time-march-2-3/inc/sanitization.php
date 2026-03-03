<?php
/**
 * Sanitization helpers.
 *
 * @package ScreenTime
 */

/**
 * Sanitizes a movie rating value.
 *
 * @param mixed $value Raw input.
 * @return float
 */
function screentime_sanitize_rating( $value ) {
	$rating = (float) $value;

	if ( $rating < 0 ) {
		return 0.0;
	}

	if ( $rating > 10 ) {
		return 10.0;
	}

	return $rating;
}
