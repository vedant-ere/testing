<?php
/**
 * Template helper functions.
 *
 * @package ScreenTime
 */

/**
 * Prints the site logo markup with a styled fallback.
 *
 * When a Customizer logo is not set, the site name is rendered so header and
 * footer remain usable without additional setup.
 *
 * @param string $class CSS class applied to logo link.
 * @return void
 */
function screentime_the_site_logo( $class = 'site-logo' ) {
	$logo_id = absint( get_theme_mod( 'custom_logo' ) );

	if ( $logo_id > 0 ) {
		$logo_image = wp_get_attachment_image(
			$logo_id,
			'full',
			false,
			array(
				'class'   => 'site-logo__image',
				'loading' => 'eager',
				'alt'     => get_bloginfo( 'name' ),
			)
		);

		if ( ! empty( $logo_image ) ) {
			printf(
				'<a class="%1$s" href="%2$s" rel="home">%3$s</a>',
				esc_attr( $class ),
				esc_url( home_url( '/' ) ),
				$logo_image // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image() returns safe HTML.
			);
			return;
		}
	}

	printf(
		'<a class="%1$s" href="%2$s" rel="home"><span class="site-logo__screen">%3$s</span><span class="site-logo__time">%4$s</span></a>',
		esc_attr( $class ),
		esc_url( home_url( '/' ) ),
		esc_html__( 'Screen', 'screen-time' ),
		esc_html__( 'Time', 'screen-time' )
	);
}

/**
 * Returns a WP_Query of published movies filtered by a label term.
 *
 * @param string $term_slug Label term slug.
 * @param int    $limit     Number of posts to return.
 * @return WP_Query
 */
function screentime_get_movies_by_label( $term_slug, $limit = 6 ) {
	$args = array(
		'post_type'              => 'rt-movie',
		'post_status'            => 'publish',
		'posts_per_page'         => max( 1, absint( $limit ) ),
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => true,
		'tax_query'              => array(
			array(
				'taxonomy' => 'rt-movie-label',
				'field'    => 'slug',
				'terms'    => sanitize_key( $term_slug ),
			),
		),
	);

	return new WP_Query( $args );
}

/**
 * Returns formatted runtime like "2 hr 15 min".
 *
 * @param int $post_id Movie post ID.
 * @return string
 */
function screentime_get_movie_runtime_label( $post_id ) {
	$runtime = absint( get_post_meta( $post_id, 'rt-movie-meta-basic-runtime', true ) );

	if ( 0 === $runtime ) {
		return '';
	}

	$hours   = (int) floor( $runtime / 60 );
	$minutes = $runtime % 60;

	if ( $hours > 0 && $minutes > 0 ) {
		return sprintf(
			/* translators: 1: hours, 2: minutes. */
			__( '%1$d hr %2$d min', 'screen-time' ),
			$hours,
			$minutes
		);
	}

	if ( $hours > 0 ) {
		return sprintf(
			/* translators: %d: hours. */
			__( '%d hr', 'screen-time' ),
			$hours
		);
	}

	return sprintf(
		/* translators: %d: minutes. */
		__( '%d min', 'screen-time' ),
		$minutes
	);
}

/**
 * Returns release date text prefixed with "Release:".
 *
 * @param int $post_id Movie post ID.
 * @return string
 */
function screentime_get_movie_release_label( $post_id ) {
	$release_date = get_post_meta( $post_id, 'rt-movie-meta-basic-release-date', true );

	if ( empty( $release_date ) || ! is_string( $release_date ) ) {
		return '';
	}

	$timestamp = strtotime( $release_date );

	if ( false === $timestamp ) {
		return '';
	}

	return sprintf(
		/* translators: %s: formatted date. */
		__( 'Release: %s', 'screen-time' ),
		wp_date( get_option( 'date_format' ), $timestamp )
	);
}

/**
 * Returns release year derived from release date.
 *
 * @param int $post_id Movie post ID.
 * @return string
 */
function screentime_get_movie_release_year( $post_id ) {
	$release_date = get_post_meta( $post_id, 'rt-movie-meta-basic-release-date', true );

	if ( empty( $release_date ) || ! is_string( $release_date ) ) {
		return '';
	}

	$timestamp = strtotime( $release_date );
	return false !== $timestamp ? wp_date( 'Y', $timestamp ) : '';
}

/**
 * Returns movie content rating value.
 *
 * @param int $post_id Movie post ID.
 * @return string
 */
function screentime_get_movie_content_rating( $post_id ) {
	$content_rating = get_post_meta( $post_id, 'rt-movie-meta-basic-content-rating', true );
	return is_string( $content_rating ) ? $content_rating : '';
}

/**
 * Returns movie rating value from basic meta.
 *
 * @param int $post_id Movie post ID.
 * @return string
 */
function screentime_get_movie_rating( $post_id ) {
	$rating = get_post_meta( $post_id, 'rt-movie-meta-basic-rating', true );
	return is_string( $rating ) ? $rating : '';
}

/**
 * Returns poster URL, optionally prioritizing carousel poster meta.
 *
 * @param int    $post_id         Movie post ID.
 * @param string $size            Image size.
 * @param bool   $prefer_carousel Whether carousel poster should be preferred.
 * @return string
 */
function screentime_get_movie_image_url( $post_id, $size = 'large', $prefer_carousel = false ) {
	if ( $prefer_carousel ) {
		$carousel_poster_id = absint( get_post_meta( $post_id, 'rt-movie-meta-carousel-poster', true ) );
		if ( $carousel_poster_id > 0 ) {
			$poster_url = wp_get_attachment_image_url( $carousel_poster_id, $size );
			if ( $poster_url ) {
				return $poster_url;
			}
		}
	}

	$featured_url = get_the_post_thumbnail_url( $post_id, $size );
	return $featured_url ? $featured_url : '';
}

/**
 * Returns concise summary text for cards/slider.
 *
 * @param int $post_id Movie post ID.
 * @return string
 */
function screentime_get_movie_summary( $post_id ) {
	$excerpt = get_the_excerpt( $post_id );
	if ( ! empty( $excerpt ) ) {
		return wp_strip_all_tags( $excerpt );
	}

	$content = get_post_field( 'post_content', $post_id );
	return wp_trim_words( wp_strip_all_tags( (string) $content ), 32, '...' );
}

/**
 * Returns up to three movie genre names.
 *
 * @param int $post_id Movie post ID.
 * @return array<int, string>
 */
function screentime_get_movie_genre_names( $post_id ) {
	$terms = get_the_terms( $post_id, 'rt-movie-genre' );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	$genres = wp_list_pluck( $terms, 'name' );
	$genres = array_filter( array_map( 'strval', $genres ) );

	return array_slice( array_values( $genres ), 0, 3 );
}

/**
 * Returns language names assigned to a movie.
 *
 * @param int $post_id Movie post ID.
 * @return array<int, string>
 */
function screentime_get_movie_language_names( $post_id ) {
	$terms = get_the_terms( $post_id, 'rt-movie-language' );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	$languages = wp_list_pluck( $terms, 'name' );
	return array_values( array_filter( array_map( 'strval', $languages ) ) );
}

/**
 * Reads a JSON array meta value and returns sanitized integer IDs.
 *
 * The plugin stores gallery and crew IDs as JSON strings, so templates use
 * this helper to safely decode and normalize those lists.
 *
 * @param int    $post_id  Post ID.
 * @param string $meta_key Meta key storing JSON array.
 * @return array<int, int>
 */
function screentime_get_json_id_meta( $post_id, $meta_key ) {
	$raw = get_post_meta( $post_id, $meta_key, true );

	if ( empty( $raw ) || ! is_string( $raw ) ) {
		return array();
	}

	$decoded = json_decode( $raw, true );

	if ( ! is_array( $decoded ) ) {
		return array();
	}

	$ids = array_map( 'absint', $decoded );
	$ids = array_values( array_unique( array_filter( $ids ) ) );

	return $ids;
}

/**
 * Returns crew people posts for a given movie crew meta key.
 *
 * @param int    $post_id  Movie post ID.
 * @param string $meta_key Crew meta key (JSON ID list).
 * @return array<int, WP_Post>
 */
function screentime_get_movie_people_by_role( $post_id, $meta_key ) {
	$ids = screentime_get_json_id_meta( $post_id, $meta_key );

	if ( empty( $ids ) ) {
		return array();
	}

	return get_posts(
		array(
			'post_type'              => 'rt-person',
			'post_status'            => 'publish',
			'post__in'               => $ids,
			'orderby'                => 'post__in',
			'posts_per_page'         => count( $ids ),
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
		)
	);
}

/**
 * Returns actor ID to character-name map from JSON meta.
 *
 * @param int $post_id Movie post ID.
 * @return array<int, string>
 */
function screentime_get_movie_actor_character_map( $post_id ) {
	$raw = get_post_meta( $post_id, 'rt-movie-meta-crew-actor-characters', true );

	if ( empty( $raw ) || ! is_string( $raw ) ) {
		return array();
	}

	$decoded = json_decode( $raw, true );

	if ( ! is_array( $decoded ) ) {
		return array();
	}

	$characters = array();

	foreach ( $decoded as $person_id => $character_name ) {
		$person_id = absint( $person_id );
		$name      = sanitize_text_field( (string) $character_name );

		if ( $person_id > 0 && '' !== $name ) {
			$characters[ $person_id ] = $name;
		}
	}

	return $characters;
}

/**
 * Returns movie photo gallery items from attachment IDs.
 *
 * @param int $post_id Movie post ID.
 * @return array<int, array<string, string|int>>
 */
function screentime_get_movie_photo_gallery( $post_id ) {
	$ids = screentime_get_json_id_meta( $post_id, 'rt-media-meta-img' );

	if ( empty( $ids ) ) {
		return array();
	}

	$items = array();

	foreach ( $ids as $id ) {
		$url = wp_get_attachment_image_url( $id, 'large' );
		if ( ! $url ) {
			continue;
		}

		$items[] = array(
			'id'  => $id,
			'url' => $url,
			'alt' => get_post_meta( $id, '_wp_attachment_image_alt', true ),
		);
	}

	return $items;
}

/**
 * Returns movie video gallery items from attachment IDs.
 *
 * @param int $post_id Movie post ID.
 * @return array<int, array<string, string|int>>
 */
function screentime_get_movie_video_gallery( $post_id ) {
	$ids = screentime_get_json_id_meta( $post_id, 'rt-media-meta-video' );

	if ( empty( $ids ) ) {
		return array();
	}

	$items = array();

	foreach ( $ids as $id ) {
		$video_url = wp_get_attachment_url( $id );
		if ( ! $video_url ) {
			continue;
		}

		$items[] = array(
			'id'       => $id,
			'url'      => $video_url,
			'title'    => get_the_title( $id ),
			'mime'     => get_post_mime_type( $id ),
			'thumb'    => wp_get_attachment_image_url( $id, 'medium_large' ),
			'filetype' => wp_check_filetype( $video_url ),
		);
	}

	return $items;
}

/**
 * Sets archive query limits for custom post type templates.
 *
 * @param WP_Query $query Query object.
 * @return void
 */
function screentime_adjust_archive_queries( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_post_type_archive( 'rt-movie' ) ) {
		$query->set( 'posts_per_page', 12 );
	}
}
add_action( 'pre_get_posts', 'screentime_adjust_archive_queries' );

/**
 * Returns person career names.
 *
 * @param int $post_id Person post ID.
 * @return array<int, string>
 */
function screentime_get_person_career_names( $post_id ) {
	$terms = wp_get_post_terms( $post_id, 'rt-person-career' );

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return array();
	}

	return array_values( array_filter( array_map( 'strval', wp_list_pluck( $terms, 'name' ) ) ) );
}

/**
 * Returns person social links keyed by platform.
 *
 * @param int $post_id Person post ID.
 * @return array<string, string>
 */
function screentime_get_person_social_links( $post_id ) {
	$map = array(
		'twitter'   => 'rt-person-meta-social-twitter',
		'facebook'  => 'rt-person-meta-social-facebook',
		'instagram' => 'rt-person-meta-social-instagram',
		'website'   => 'rt-person-meta-social-web',
	);

	$social_links = array();

	foreach ( $map as $key => $meta_key ) {
		$url = get_post_meta( $post_id, $meta_key, true );
		if ( is_string( $url ) && '' !== $url ) {
			$social_links[ $key ] = $url;
		}
	}

	return $social_links;
}

/**
 * Returns popular movies query for a person.
 *
 * Popular movies are linked through shadow taxonomy and have rating >= 8.5.
 *
 * @param int $person_id Person post ID.
 * @param int $limit     Number of movies.
 * @return WP_Query
 */
function screentime_get_popular_movies_for_person( $person_id, $limit = 3 ) {
	$person_slug = sanitize_title( get_post_field( 'post_name', $person_id ) . '-' . $person_id );
	$limit       = max( 1, absint( $limit ) );

	$popular_movies = new WP_Query(
		array(
			'post_type'              => 'rt-movie',
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
			'tax_query'              => array(
				array(
					'taxonomy' => '_rt-movie-person',
					'field'    => 'slug',
					'terms'    => $person_slug,
				),
			),
			'meta_query'             => array(
				array(
					'key'     => 'rt-movie-meta-basic-rating',
					'value'   => 8.0,
					'compare' => '>=',
					'type'    => 'NUMERIC',
				),
			),
			'orderby'                => 'meta_value_num',
			'meta_key'               => 'rt-movie-meta-basic-rating',
			'order'                  => 'DESC',
		)
	);

	if ( $popular_movies->have_posts() ) {
		return $popular_movies;
	}

	$fallback_query = new WP_Query(
		array(
			'post_type'              => 'rt-movie',
			'post_status'            => 'publish',
			'posts_per_page'         => 200,
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => false,
			'fields'                 => 'ids',
			'meta_query'             => array(
				array(
					'key'     => 'rt-movie-meta-basic-rating',
					'value'   => 8.5,
					'compare' => '>=',
					'type'    => 'NUMERIC',
				),
			),
		)
	);

	$matched_ids = array();
	$crew_keys   = array(
		'rt-movie-meta-crew-actor',
		'rt-movie-meta-crew-director',
		'rt-movie-meta-crew-producer',
		'rt-movie-meta-crew-writer',
	);

	foreach ( $fallback_query->posts as $movie_id ) {
		foreach ( $crew_keys as $crew_key ) {
			$crew_ids = screentime_get_json_id_meta( (int) $movie_id, $crew_key );
			if ( in_array( (int) $person_id, $crew_ids, true ) ) {
				$matched_ids[] = (int) $movie_id;
				break;
			}
		}

		if ( count( $matched_ids ) >= $limit ) {
			break;
		}
	}

	if ( empty( $matched_ids ) ) {
		return new WP_Query(
			array(
				'post_type'      => 'rt-movie',
				'post_status'    => 'publish',
				'posts_per_page' => 0,
			)
		);
	}

	return new WP_Query(
		array(
			'post_type'              => 'rt-movie',
			'post_status'            => 'publish',
			'post__in'               => $matched_ids,
			'orderby'                => 'post__in',
			'posts_per_page'         => $limit,
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		)
	);
}

/**
 * Renders a single movie review card for wp_list_comments().
 *
 * @param WP_Comment $comment Comment object.
 * @param array      $args    Display arguments.
 * @param int        $depth   Comment depth.
 * @return void
 */
function screentime_render_movie_review_comment( $comment, $args, $depth ) {
	$comment_author = get_comment_author( $comment );
	$initial        = strtoupper( substr( wp_strip_all_tags( $comment_author ), 0, 1 ) );

	get_template_part(
		'template-parts/movie/comment-card',
		null,
		array(
			'comment_id'      => (int) $comment->comment_ID,
			'comment_classes' => implode( ' ', get_comment_class( 'movie-review-card', $comment ) ),
			'initial'         => $initial,
			'author'          => $comment_author,
			'text'            => get_comment_text( $comment ),
			'date'            => get_comment_date( get_option( 'date_format' ), $comment ),
		)
	);
}

/**
 * Returns comment form arguments styled to movie review design classes.
 *
 * @return array<string, mixed>
 */
function screentime_get_movie_review_form_args() {
	$commenter = wp_get_current_commenter();

	$required_label = ' <span class="required">*</span>';

	$fields = array(
		'author' => sprintf(
			'<label for="author">%1$s%3$s</label><input id="author" name="author" type="text" value="%2$s" maxlength="245" autocomplete="name" required="required">',
			esc_html__( 'Name', 'screen-time' ),
			esc_attr( $commenter['comment_author'] ),
			$required_label // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup.
		),
		'email'  => sprintf(
			'<label for="email">%1$s%3$s</label><input id="email" name="email" type="email" value="%2$s" maxlength="100" autocomplete="email" required="required">',
			esc_html__( 'Email', 'screen-time' ),
			esc_attr( $commenter['comment_author_email'] ),
			$required_label // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup.
		),
		'url'    => sprintf(
			'<label for="url">%1$s</label><input id="url" name="url" type="text" value="%2$s" maxlength="200" autocomplete="url">',
			esc_html__( 'Website', 'screen-time' ),
			esc_attr( $commenter['comment_author_url'] )
		),
	);

	if ( get_option( 'show_comments_cookies_opt_in' ) ) {
		$consent           = empty( $commenter['comment_author_email'] ) ? '' : ' checked="checked"';
		$fields['cookies'] = sprintf(
			'<label class="movie-review-form__checkbox" for="wp-comment-cookies-consent"><input id="wp-comment-cookies-consent" name="wp-comment-cookies-consent" type="checkbox" value="yes"%1$s> %2$s</label>',
			$consent, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Controlled attribute fragment.
			esc_html__( 'Save my name and email in this browser for the next time I comment.', 'screen-time' )
		);
	}

	return array(
		'class_form'           => 'movie-review-form',
		'title_reply'          => esc_html__( 'Leave a Review', 'screen-time' ),
		'title_reply_before'   => '<h2>',
		'title_reply_after'    => '</h2>',
		'comment_notes_before' => '<p class="comment-notes">' . esc_html__( 'Your Email Address will not be published. Required fields are marked *', 'screen-time' ) . '</p>',
		'comment_notes_after'  => '',
		'fields'               => $fields,
		'comment_field'        => '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'Comment', 'screen-time' ) . $required_label . '</label><textarea id="comment" name="comment" rows="6" required="required"></textarea></p>',
		'label_submit'         => esc_html__( 'Post Review', 'screen-time' ),
		'class_submit'         => 'submit',
		'submit_field'         => '%1$s %2$s',
	);
}
