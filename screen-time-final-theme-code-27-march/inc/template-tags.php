<?php
/**
 * Theme template tags.
 *
 * @package ScreenTime
 */

if ( ! function_exists( 'screentime_posted_on' ) ) {
	/**
	 * Prints formatted post date.
	 *
	 * @return void
	 */
	function screentime_posted_on() {
		printf(
			'<time class="entry-date" datetime="%1$s">%2$s</time>',
			esc_attr( get_the_date( DATE_W3C ) ),
			esc_html( get_the_date() )
		);
	}
}
