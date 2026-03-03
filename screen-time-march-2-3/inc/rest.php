<?php
/**
 * REST API helpers and routes.
 *
 * @package ScreenTime
 */

add_action( 'rest_api_init', 'screentime_register_rest_fields' );

/**
 * Registers extra REST fields for theme use-cases.
 *
 * @return void
 */
function screentime_register_rest_fields() {
	register_rest_field(
		'rt-person',
		'birthdate',
		array(
			'get_callback' => function ( $rest_item ) {
				$birthdate_raw = get_post_meta( (int) $rest_item['id'], 'rt-person-meta-basic-birth-date', true );

				if ( ! is_string( $birthdate_raw ) || '' === $birthdate_raw ) {
					return '';
				}

				$timestamp = strtotime( $birthdate_raw );
				if ( false === $timestamp ) {
					return $birthdate_raw;
				}

				return wp_date( get_option( 'date_format' ), $timestamp );
			},
			'schema'       => array(
				'description' => __( 'Person birth date.', 'screen-time' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			),
		)
	);

	register_rest_route(
		'screentime/v1',
		'/movie-archive',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'screentime_rest_movie_archive',
			'permission_callback' => '__return_true',
			'args'                => array(
				'page'    => array(
					'type'              => 'integer',
					'default'           => 1,
					'sanitize_callback' => 'absint',
				),
				'context' => array(
					'type'              => 'string',
					'default'           => 'post-type',
					'sanitize_callback' => 'sanitize_key',
				),
				'term'    => array(
					'type'              => 'string',
					'default'           => '',
					'sanitize_callback' => 'sanitize_title',
				),
			),
		)
	);
}

/**
 * Returns movie archive HTML fragments for async pagination.
 *
 * @param WP_REST_Request $request REST request object.
 * @return WP_REST_Response
 */
function screentime_rest_movie_archive( WP_REST_Request $request ) {
	$page    = max( 1, absint( $request->get_param( 'page' ) ) );
	$context = sanitize_key( (string) $request->get_param( 'context' ) );
	$term    = sanitize_title( (string) $request->get_param( 'term' ) );

	$query_args = array(
		'post_type'              => 'rt-movie',
		'post_status'            => 'publish',
		'paged'                  => $page,
		'posts_per_page'         => (int) get_option( 'posts_per_page' ),
		'ignore_sticky_posts'    => true,
		'update_post_meta_cache' => true,
		'update_post_term_cache' => true,
	);

	if ( 'genre' === $context && '' !== $term ) {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required to filter movies by selected genre term.
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => 'rt-movie-genre',
				'field'    => 'slug',
				'terms'    => $term,
			),
		);
	}

	$query = new WP_Query( $query_args );

	ob_start();
	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			get_template_part( 'template-parts/movie-card' );
		}
	} elseif ( 'genre' === $context ) {
		echo '<p>' . esc_html__( 'No movies found in this genre.', 'screen-time' ) . '</p>';
	} else {
		echo '<p>' . esc_html__( 'No movies found.', 'screen-time' ) . '</p>';
	}
	$cards_html = ob_get_clean();

	$pagination_html = screentime_get_movie_archive_pagination_html( $page, (int) $query->max_num_pages );

	wp_reset_postdata();

	return rest_ensure_response(
		array(
			'cards_html'      => $cards_html,
			'pagination_html' => $pagination_html,
			'page'            => $page,
			'max_pages'       => (int) $query->max_num_pages,
		)
	);
}

/**
 * Builds archive pagination HTML using existing pagination classes.
 *
 * @param int $current_page Current page number.
 * @param int $max_pages    Maximum page count.
 * @return string
 */
function screentime_get_movie_archive_pagination_html( $current_page, $max_pages ) {
	if ( $max_pages <= 1 ) {
		return '';
	}

	$processed_links = screentime_get_archive_pagination_links( $max_pages, $current_page );

	if ( empty( $processed_links ) ) {
		return '';
	}

	ob_start();
	?>
	<nav class="archive-pagination" data-movie-archive-pagination aria-label="<?php esc_attr_e( 'Movie pagination', 'screen-time' ); ?>">
		<?php
		foreach ( $processed_links as $link ) :
			echo wp_kses_post( $link );
		endforeach;
		?>
	</nav>
	<?php

	return (string) ob_get_clean();
}
