<?php
/**
 * Movie Crew Meta Box.
 *
 * Handles Crew Information for Movie post type.
 *
 * Meta Box ID: rt-movie-meta-crew
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Meta_Boxes;

use RT_Movie_Library\Traits\Singleton;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movie_Crew_Meta_Box
 *
 * Handles the registration, rendering, and saving of crew information (directors, producers, writers, actors) for Movie post type.
 */
class Movie_Crew_Meta_Box {


	use Singleton;

	/**
	 * Constructor.
	 *
	 * Registers hooks for meta box registration, saving, and asset enqueueing.
	 */
	protected function __construct() {
		add_action( 'add_meta_boxes_rt-movie', array( $this, 'register' ) );
		add_action( 'save_post_rt-movie', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Enqueue CSS and JS for the meta box.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		global $post_type;

		// Only load on movie edit screens.
		if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && 'rt-movie' === $post_type ) {
			wp_enqueue_style(
				'rt-movie-crew-meta-box',
				plugin_dir_url( RT_MOVIE_LIBRARY_PATH . 'rt-movie-library.php' ) . 'assets/css/admin/movie-crew-meta-box.css',
				array(),
				RT_MOVIE_LIBRARY_VERSION
			);

			wp_enqueue_script(
				'rt-movie-crew-meta-box',
				plugin_dir_url( RT_MOVIE_LIBRARY_PATH . 'rt-movie-library.php' ) . 'assets/js/admin/movie-crew-meta-box.js',
				array( 'jquery' ),
				RT_MOVIE_LIBRARY_VERSION,
				true
			);
		}
	}

	/**
	 * Register the meta box for crew information.
	 */
	public function register(): void {
		add_meta_box(
			'rt-movie-meta-crew',
			__( 'Crew Information', 'rt-movie-library' ),
			array( $this, 'render' ),
			'rt-movie',
			'normal',
			'high'
		);
	}

	/**
	 * Render the meta box fields for directors, producers, writers, and actors.
	 *
	 * @param WP_Post $post The current post object.
	 */
	public function render( WP_Post $post ): void {

		// Security nonce.
		wp_nonce_field(
			'rt_movie_meta_crew_action',
			'rt_movie_meta_crew_nonce'
		);

		/**
		 * Helper: get saved JSON meta as array.
		 */
		$get_saved = function ( string $key ) use ( $post ): array {
			$value = get_post_meta( $post->ID, $key, true );

			if ( empty( $value ) ) {
				return array();
			}

			$decoded = json_decode( $value, true );

			return is_array( $decoded ) ? $decoded : array();
		};



		$saved_directors = $get_saved( 'rt-movie-meta-crew-director' );
		$saved_producers = $get_saved( 'rt-movie-meta-crew-producer' );
		$saved_writers   = $get_saved( 'rt-movie-meta-crew-writer' );
		$saved_actors    = $get_saved( 'rt-movie-meta-crew-actor' );
		$saved_chars     = get_post_meta(
			$post->ID,
			'rt-movie-meta-crew-actor-characters',
			true
		);
		if ( empty( $saved_chars ) ) {
			$saved_chars = array();
		} else {
			$saved_chars = json_decode( $saved_chars, true );
			if ( ! is_array( $saved_chars ) ) {
				$saved_chars = array();
			}
		}

		/**
		 * Helper: fetch persons by career with fallback.
		 */
		$get_people = function ( string $career_slug ): array {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_posts -- Needed for meta box functionality with manageable data set.
			$persons = get_posts(
				array(
					'post_type'              => 'rt-person',
					'posts_per_page'         => 100,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for filtering persons by career taxonomy.
					'tax_query'              => array(
						array(
							'taxonomy' => 'rt-person-career',
							'field'    => 'slug',
							'terms'    => $career_slug,
						),
					),
					'orderby'                => 'title',
					'order'                  => 'ASC',
				)
			);

			// If no results, try fallback slugs.
			if ( empty( $persons ) ) {
				$fallback_map = array(
					'director'   => array( 'directors' ),
					'producer'   => array( 'producers' ),
					'writer'     => array( 'writers', 'screenwriter', 'screenwriters' ),
					'actor-star' => array( 'actor', 'actors', 'star', 'stars' ),
				);

				if ( isset( $fallback_map[ $career_slug ] ) ) {
					foreach ( $fallback_map[ $career_slug ] as $fallback_slug ) {
						// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_posts -- Needed for meta box functionality with manageable data set.
						$persons = get_posts(
							array(
								'post_type'              => 'rt-person',
								'posts_per_page'         => 100,
								'no_found_rows'          => true,
								'update_post_meta_cache' => false,
								// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Required for filtering persons by career taxonomy.
								'tax_query'              => array(
									array(
										'taxonomy' => 'rt-person-career',
										'field'    => 'slug',
										'terms'    => $fallback_slug,
									),
								),
								'orderby'                => 'title',
								'order'                  => 'ASC',
							)
						);

						if ( ! empty( $persons ) ) {
							break; // Found results, stop trying.
						}
					}
				}
			}

			return $persons;
		};


		$directors = $get_people( 'director' );
		$producers = $get_people( 'producer' );
		$writers   = $get_people( 'writer' );
		$actors    = $get_people( 'actor-star' );
		?>

		<div class="rt-crew-meta-wrapper">

			<!-- ================= DIRECTOR ================= -->
			<div class="rt-crew-section">
				<h3><?php esc_html_e( 'Director(s)', 'rt-movie-library' ); ?></h3>

				<!-- Display selected directors -->
				<div class="rt-crew-selected-list" data-crew-type="director">
					<?php
					if ( ! empty( $saved_directors ) ) :
						foreach ( $saved_directors as $person_id ) :
							$person = get_post( $person_id );
							if ( $person ) :
								?>
								<div class="rt-crew-item" data-person-id="<?php echo esc_attr( $person_id ); ?>">
									<span class="rt-crew-name"><?php echo esc_html( $person->post_title ); ?></span>
									<input type="hidden" name="rt_movie_director[]" value="<?php echo esc_attr( $person_id ); ?>">
									<button type="button" class="button-link rt-crew-remove" aria-label="<?php esc_attr_e( 'Remove', 'rt-movie-library' ); ?>">
										<span class="dashicons dashicons-no-alt"></span>
									</button>
								</div>
								<?php
							endif;
						endforeach;
					else :
						?>
						<p class="rt-crew-empty"><?php esc_html_e( 'No directors added yet.', 'rt-movie-library' ); ?></p>
						<?php
					endif;
					?>
				</div>

				<!-- Add new director -->
				<div class="rt-crew-add-row">
					<select class="rt-crew-dropdown" data-crew-type="director">
						<option value=""><?php esc_html_e( '— Select Director —', 'rt-movie-library' ); ?></option>
						<?php foreach ( $directors as $person ) : ?>
							<option value="<?php echo esc_attr( $person->ID ); ?>" data-name="<?php echo esc_attr( $person->post_title ); ?>">
								<?php echo esc_html( $person->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="button rt-crew-add-btn" data-crew-type="director">
						<?php esc_html_e( 'Add Director', 'rt-movie-library' ); ?>
					</button>
				</div>
			</div>

			<!-- ================= PRODUCER ================= -->
			<div class="rt-crew-section">
				<h3><?php esc_html_e( 'Producer(s)', 'rt-movie-library' ); ?></h3>

				<div class="rt-crew-selected-list" data-crew-type="producer">
					<?php
					if ( ! empty( $saved_producers ) ) :
						foreach ( $saved_producers as $person_id ) :
							$person = get_post( $person_id );
							if ( $person ) :
								?>
								<div class="rt-crew-item" data-person-id="<?php echo esc_attr( $person_id ); ?>">
									<span class="rt-crew-name"><?php echo esc_html( $person->post_title ); ?></span>
									<input type="hidden" name="rt_movie_producer[]" value="<?php echo esc_attr( $person_id ); ?>">
									<button type="button" class="button-link rt-crew-remove" aria-label="<?php esc_attr_e( 'Remove', 'rt-movie-library' ); ?>">
										<span class="dashicons dashicons-no-alt"></span>
									</button>
								</div>
								<?php
							endif;
						endforeach;
					else :
						?>
						<p class="rt-crew-empty"><?php esc_html_e( 'No producers added yet.', 'rt-movie-library' ); ?></p>
						<?php
					endif;
					?>
				</div>

				<div class="rt-crew-add-row">
					<select class="rt-crew-dropdown" data-crew-type="producer">
						<option value=""><?php esc_html_e( '— Select Producer —', 'rt-movie-library' ); ?></option>
						<?php foreach ( $producers as $person ) : ?>
							<option value="<?php echo esc_attr( $person->ID ); ?>" data-name="<?php echo esc_attr( $person->post_title ); ?>">
								<?php echo esc_html( $person->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="button rt-crew-add-btn" data-crew-type="producer">
						<?php esc_html_e( 'Add Producer', 'rt-movie-library' ); ?>
					</button>
				</div>
			</div>

			<!-- ================= WRITER ================= -->
			<div class="rt-crew-section">
				<h3><?php esc_html_e( 'Writer(s)', 'rt-movie-library' ); ?></h3>

				<div class="rt-crew-selected-list" data-crew-type="writer">
					<?php
					if ( ! empty( $saved_writers ) ) :
						foreach ( $saved_writers as $person_id ) :
							$person = get_post( $person_id );
							if ( $person ) :
								?>
								<div class="rt-crew-item" data-person-id="<?php echo esc_attr( $person_id ); ?>">
									<span class="rt-crew-name"><?php echo esc_html( $person->post_title ); ?></span>
									<input type="hidden" name="rt_movie_writer[]" value="<?php echo esc_attr( $person_id ); ?>">
									<button type="button" class="button-link rt-crew-remove" aria-label="<?php esc_attr_e( 'Remove', 'rt-movie-library' ); ?>">
										<span class="dashicons dashicons-no-alt"></span>
									</button>
								</div>
								<?php
							endif;
						endforeach;
					else :
						?>
						<p class="rt-crew-empty"><?php esc_html_e( 'No writers added yet.', 'rt-movie-library' ); ?></p>
						<?php
					endif;
					?>
				</div>

				<div class="rt-crew-add-row">
					<select class="rt-crew-dropdown" data-crew-type="writer">
						<option value=""><?php esc_html_e( '— Select Writer —', 'rt-movie-library' ); ?></option>
						<?php foreach ( $writers as $person ) : ?>
							<option value="<?php echo esc_attr( $person->ID ); ?>" data-name="<?php echo esc_attr( $person->post_title ); ?>">
								<?php echo esc_html( $person->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="button rt-crew-add-btn" data-crew-type="writer">
						<?php esc_html_e( 'Add Writer', 'rt-movie-library' ); ?>
					</button>
				</div>
			</div>

			<!-- ================= ACTOR / STAR ================= -->
			<div class="rt-crew-section">
				<h3><?php esc_html_e( 'Actor / Star', 'rt-movie-library' ); ?></h3>

				<div class="rt-crew-selected-list" data-crew-type="actor">
					<?php
					if ( ! empty( $saved_actors ) ) :
						foreach ( $saved_actors as $person_id ) :
							$person = get_post( $person_id );
							if ( $person ) :
								$char_name = '';

								if ( isset( $saved_chars[ $person_id ] ) ) {
									$char_name = $saved_chars[ $person_id ];
								}

								?>
								<div class="rt-crew-item rt-crew-actor-item" data-person-id="<?php echo esc_attr( $person_id ); ?>">
									<div class="rt-crew-actor-info">
										<span class="rt-crew-name"><?php echo esc_html( $person->post_title ); ?></span>
										<input
											type="text"
											name="rt_movie_actor_character[<?php echo esc_attr( $person_id ); ?>]"
											value="<?php echo esc_attr( $char_name ); ?>"
											placeholder="<?php esc_attr_e( 'Character name (optional)', 'rt-movie-library' ); ?>"
											class="rt-character-input">
									</div>
									<input type="hidden" name="rt_movie_actor[]" value="<?php echo esc_attr( $person_id ); ?>">
									<button type="button" class="button-link rt-crew-remove" aria-label="<?php esc_attr_e( 'Remove', 'rt-movie-library' ); ?>">
										<span class="dashicons dashicons-no-alt"></span>
									</button>
								</div>
								<?php
							endif;
						endforeach;
					else :
						?>
						<p class="rt-crew-empty"><?php esc_html_e( 'No actors added yet.', 'rt-movie-library' ); ?></p>
						<?php
					endif;
					?>
				</div>

				<div class="rt-crew-add-row">
					<select class="rt-crew-dropdown" data-crew-type="actor">
						<option value=""><?php esc_html_e( '— Select Actor —', 'rt-movie-library' ); ?></option>
						<?php foreach ( $actors as $person ) : ?>
							<option value="<?php echo esc_attr( $person->ID ); ?>" data-name="<?php echo esc_attr( $person->post_title ); ?>">
								<?php echo esc_html( $person->post_title ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<button type="button" class="button rt-crew-add-btn" data-crew-type="actor">
						<?php esc_html_e( 'Add Actor', 'rt-movie-library' ); ?>
					</button>
				</div>
			</div>

		</div>

		<?php
	}

	/**
	 * Save meta box data by validating, sanitizing, and updating post meta.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save( int $post_id ): void {
		/*
		 * SECURITY CHECKS (MANDATORY)
		 */
		// 1. Nonce check.
		if (
			! isset( $_POST['rt_movie_meta_crew_nonce'] ) ||
			! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['rt_movie_meta_crew_nonce'] ) ),
				'rt_movie_meta_crew_action'
			)
		) {
			return;
		}

		// 2. Autosave check.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// 3. Correct post type.
		if ( 'rt-movie' !== get_post_type( $post_id ) ) {
			return;
		}

		// 4. Capability check.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		/*
		 * SAVE HELPERS
		 */
		$save_people_meta = function ( string $post_key, string $meta_key ) use ( $post_id ) {
			// Verify nonce before processing.
			if (
				! isset( $_POST['rt_movie_meta_crew_nonce'] ) ||
				! wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_POST['rt_movie_meta_crew_nonce'] ) ),
					'rt_movie_meta_crew_action'
				)
			) {
				return array();
			}

			if ( isset( $_POST[ $post_key ] ) && is_array( $_POST[ $post_key ] ) ) {
				$ids = array_map(
					'absint',
					wp_unslash( $_POST[ $post_key ] )
				);

				// Remove any zero values and duplicates.
				$ids = array_values( array_unique( array_filter( $ids ) ) );

				if ( ! empty( $ids ) ) {
					update_post_meta(
						$post_id,
						$meta_key,
						wp_json_encode( $ids )
					);

					return $ids;
				}
			}

			delete_post_meta( $post_id, $meta_key );
			return array();
		};

		/*
		 * SAVE CREW META
		 */
		$directors = $save_people_meta(
			'rt_movie_director',
			'rt-movie-meta-crew-director'
		);

		$producers = $save_people_meta(
			'rt_movie_producer',
			'rt-movie-meta-crew-producer'
		);

		$writers = $save_people_meta(
			'rt_movie_writer',
			'rt-movie-meta-crew-writer'
		);

		$actors = $save_people_meta(
			'rt_movie_actor',
			'rt-movie-meta-crew-actor'
		);

		/*
		 * SAVE ACTOR → CHARACTER NAMES
		 */

		$characters = array();

		// Additional nonce check for PHPCS compliance (already verified at method start).
		if (
			isset( $_POST['rt_movie_meta_crew_nonce'] ) &&
			wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_POST['rt_movie_meta_crew_nonce'] ) ),
				'rt_movie_meta_crew_action'
			) &&
			isset( $_POST['rt_movie_actor_character'] ) &&
			is_array( $_POST['rt_movie_actor_character'] )
		) {
			$raw_characters = map_deep( wp_unslash( $_POST['rt_movie_actor_character'] ), 'sanitize_text_field' );

			foreach ( $raw_characters as $person_id => $name ) {
				$person_id = absint( $person_id );

				if ( in_array( $person_id, $actors, true ) && ! empty( $name ) ) {
					$characters[ $person_id ] = $name;
				}
			}
		}
		if ( ! empty( $characters ) ) {
			update_post_meta(
				$post_id,
				'rt-movie-meta-crew-actor-characters',
				wp_json_encode( $characters )
			);
		} else {
			delete_post_meta(
				$post_id,
				'rt-movie-meta-crew-actor-characters'
			);
		}

		/*
		 * SHADOW TAXONOMY (PERSON ↔ MOVIE)
		 */
		$all_people = array_unique(
			array_merge(
				$directors,
				$producers,
				$writers,
				$actors
			)
		);

		$term_ids = array();

		foreach ( $all_people as $person_id ) {

			$person = get_post( $person_id );

			if ( ! $person || 'rt-person' !== $person->post_type ) {
				continue;
			}

			$slug = sanitize_title( $person->post_name . '-' . $person_id );

			$term = term_exists( $slug, '_rt-movie-person' );

			if ( ! $term ) {
				$term = wp_insert_term(
					$person->post_title,
					'_rt-movie-person',
					array(
						'slug' => $slug,
					)
				);
			}

			if ( ! is_wp_error( $term ) ) {
				$term_ids[] = (int) $term['term_id'];
			}
		}

		// Assign shadow terms to the movie.
		if ( ! empty( $term_ids ) ) {
			wp_set_object_terms(
				$post_id,
				$term_ids,
				'_rt-movie-person',
				false
			);
		} else {
			wp_set_object_terms(
				$post_id,
				array(),
				'_rt-movie-person',
				false
			);
		}
	}
}
