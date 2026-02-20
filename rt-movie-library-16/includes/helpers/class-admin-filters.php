<?php
/**
 * Admin Filters for Movie CPT.
 *
 * Adds taxonomy filter dropdowns to the Movies list table.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class Admin_Filters.
 *
 * Handles adding taxonomy filters to the Movies admin list table.
 */
class Admin_Filters {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'restrict_manage_posts', array( __CLASS__, 'add_taxonomy_filters' ) );
	}

	/**
	 * Adds taxonomy dropdowns to Movies admin list.
	 *
	 * @param string $post_type Current post type.
	 *
	 * @return void
	 */
	public static function add_taxonomy_filters( $post_type ) {

		if ( 'rt-movie' !== $post_type ) {
			return;
		}

		// Add nonce for filter processing.
		wp_nonce_field(
			'rt_movie_admin_filters',
			'rt_movie_admin_filters_nonce'
		);

		$taxonomies = array(
			'rt-movie-genre'    => 'Genre',
			'rt-movie-label'    => 'Label',
			'rt-movie-language' => 'Language',
		);

		foreach ( $taxonomies as $taxonomy => $label ) {

			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				)
			);

			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				continue;
			}

			$current = '';

			if (
				isset( $_GET[ $taxonomy ] ) &&
				isset( $_GET['rt_movie_admin_filters_nonce'] ) &&
				wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_GET['rt_movie_admin_filters_nonce'] ) ),
					'rt_movie_admin_filters'
				)
			) {
				$current = sanitize_text_field(
					wp_unslash( $_GET[ $taxonomy ] )
				);
			}

			echo '<select name="' . esc_attr( $taxonomy ) . '" class="postform">';
			echo '<option value="">' . esc_html(
				sprintf(
					/* translators: %s: taxonomy label (plural). */
					__( 'All %s', 'rt-movie-library' ),
					$label . 's'
				)
			) . '</option>';

			foreach ( $terms as $term ) {
				printf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $term->slug ),
					selected( $current, $term->slug, false ),
					esc_html( $term->name )
				);
			}

			echo '</select>';
		}
	}
}
