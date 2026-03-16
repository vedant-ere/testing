<?php
/**
 * Movie Manager custom role management.
 *
 * @package RT_Movie_Library
 * @since   1.0.0
 */

namespace RT_Movie_Library\Classes\Roles;

defined( 'ABSPATH' ) || exit;

/**
 * Class Movie_Manager_Role
 */
class Movie_Manager_Role {

	/**
	 * Role slug.
	 *
	 * @var string
	 */
	public const ROLE_SLUG = 'movie-manager';

	/**
	 * Role display name.
	 *
	 * @var string
	 */
	public const ROLE_NAME = 'Movie Manager';

	/**
	 * Register movie-manager role.
	 *
	 * @return void
	 */
	public static function activate(): void {
		add_role(
			self::ROLE_SLUG,
			self::ROLE_NAME,
			self::get_capabilities()
		);

		self::grant_caps_to_administrator();
	}

	/**
	 * Remove movie-manager role.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		remove_role( self::ROLE_SLUG );

		self::revoke_caps_from_administrator();
	}

	/**
	 * Define all capabilities for the movie-manager role.
	 *
	 * Taxonomy capabilities are explicitly scoped per plugin taxonomy
	 * to avoid broad access via generic category capabilities.
	 *
	 * @return array<string, bool>
	 */
	private static function get_capabilities(): array {
		return array(
			// Base WordPress capabilities.
			'read'                               => true,
			'upload_files'                       => true,

			// --------------------------------------------------------------
			// Movie CPT (capability_type: rt-movie / rt-movies)
			// --------------------------------------------------------------
			'edit_rt-movie'                      => true,
			'read_rt-movie'                      => true,
			'delete_rt-movie'                    => true,
			'edit_rt-movies'                     => true,
			'edit_others_rt-movies'              => true,
			'publish_rt-movies'                  => true,
			'read_private_rt-movies'             => true,
			'delete_rt-movies'                   => true,
			'delete_private_rt-movies'           => true,
			'delete_published_rt-movies'         => true,
			'delete_others_rt-movies'            => true,
			'edit_private_rt-movies'             => true,
			'edit_published_rt-movies'           => true,

			// --------------------------------------------------------------
			// Person CPT (capability_type: rt-person / rt-persons)
			// --------------------------------------------------------------
			'edit_rt-person'                     => true,
			'read_rt-person'                     => true,
			'delete_rt-person'                   => true,
			'edit_rt-persons'                    => true,
			'edit_others_rt-persons'             => true,
			'publish_rt-persons'                 => true,
			'read_private_rt-persons'            => true,
			'delete_rt-persons'                  => true,
			'delete_private_rt-persons'          => true,
			'delete_published_rt-persons'        => true,
			'delete_others_rt-persons'           => true,
			'edit_private_rt-persons'            => true,
			'edit_published_rt-persons'          => true,

			// --------------------------------------------------------------
			// Taxonomy capabilities.
			// --------------------------------------------------------------

			// Genre taxonomy (rt-movie-genre).
			'manage_rt-movie-genre'              => true,
			'assign_rt-movie-genre'              => true,

			// Career taxonomy (rt-person-career).
			'manage_rt-person-career'            => true,
			'assign_rt-person-career'            => true,

			// Label taxonomy (rt-movie-label).
			'manage_rt-movie-label'              => true,
			'assign_rt-movie-label'              => true,

			// Language taxonomy (rt-movie-language).
			'manage_rt-movie-language'           => true,
			'assign_rt-movie-language'           => true,

			// Tag taxonomy (rt-movie-tag).
			'manage_rt-movie-tag'                => true,
			'assign_rt-movie-tag'                => true,

			// Production Company taxonomy (rt-movie-production-company).
			'manage_rt-movie-production-company' => true,
			'assign_rt-movie-production-company' => true,

			// Movie-Person shadow taxonomy (_rt-movie-person).
			'manage_rt-movie-person'             => true,
			'assign_rt-movie-person'             => true,
		);
	}

	/**
	 * Grant plugin CPT capabilities to administrators.
	 *
	 * @return void
	 */
	private static function grant_caps_to_administrator(): void {
		$admin = get_role( 'administrator' );

		if ( ! $admin instanceof \WP_Role ) {
			return;
		}

		foreach ( self::get_administrator_caps() as $cap => $grant ) {
			$admin->add_cap( $cap, $grant );
		}
	}

	/**
	 * Revoke plugin CPT capabilities from administrators.
	 *
	 * @return void
	 */
	private static function revoke_caps_from_administrator(): void {
		$admin = get_role( 'administrator' );

		if ( ! $admin instanceof \WP_Role ) {
			return;
		}

		foreach ( self::get_administrator_caps() as $cap => $grant ) {
			$admin->remove_cap( $cap );
		}
	}

	/**
	 * Get plugin-specific custom capabilities that should be synced to admins.
	 *
	 * Excludes core caps like `read` / `upload_files` and taxonomy defaults.
	 *
	 * @return array<string, bool>
	 */
	private static function get_administrator_caps(): array {
		$capabilities = array();

		foreach ( self::get_capabilities() as $cap => $grant ) {
			if ( false !== strpos( $cap, 'rt-movie' ) || false !== strpos( $cap, 'rt-person' ) ) {
				$capabilities[ $cap ] = $grant;
			}
		}

		return $capabilities;
	}
}
