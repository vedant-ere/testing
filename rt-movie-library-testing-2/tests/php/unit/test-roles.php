<?php
/**
 * Tests for custom role and capability registration.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Tests\Unit;

use RT_Movie_Library\Classes\Roles\Movie_Manager_Role;
use WP_UnitTestCase;

/**
 * Verifies role lifecycle and capability mapping.
 */
class Test_Roles extends WP_UnitTestCase {

	/**
	 * Activate the role before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		Movie_Manager_Role::activate();
	}

	/**
	 * Confirm role registration and display name.
	 *
	 * @return void
	 */
	public function test_movie_manager_role_is_registered(): void {
		$role = get_role( Movie_Manager_Role::ROLE_SLUG );
		$this->assertInstanceOf( \WP_Role::class, $role );

		$all_roles = wp_roles()->role_names;
		$this->assertSame( 'Movie Manager', $all_roles[ Movie_Manager_Role::ROLE_SLUG ], 'Role display name should be "Movie Manager".' );
	}

	/**
	 * Validate user CRUD and expected role capabilities.
	 *
	 * @return void
	 */
	public function test_movie_manager_user_crud(): void {
		$user_id = $this->factory->user->create(
			array(
				'role' => Movie_Manager_Role::ROLE_SLUG,
			)
		);

		$this->assertIsInt( $user_id );
		$this->assertGreaterThan( 0, $user_id );

		$user = get_userdata( $user_id );
		$this->assertTrue( in_array( Movie_Manager_Role::ROLE_SLUG, (array) $user->roles, true ) );

		$this->assertTrue( $user->has_cap( 'edit_rt-movies' ) );
		$this->assertTrue( $user->has_cap( 'publish_rt-movies' ) );
		$this->assertTrue( $user->has_cap( 'delete_rt-movies' ) );
		$this->assertTrue( $user->has_cap( 'upload_files' ) );
		$this->assertTrue( $user->has_cap( 'manage_rt-movie-genre' ) );
		$this->assertTrue( $user->has_cap( 'assign_rt-movie-language' ) );
		$this->assertFalse( $user->has_cap( 'manage_options' ) );
		$this->assertFalse( $user->has_cap( 'edit_users' ) );

		$updated = wp_update_user(
			array(
				'ID'         => $user_id,
				'first_name' => 'John',
			)
		);
		$this->assertSame( $user_id, $updated );
		$updated_user = get_userdata( $user_id );
		$this->assertSame( 'John', $updated_user->first_name );

		$deleted = wp_delete_user( $user_id );
		$this->assertTrue( $deleted );
		$this->assertFalse( get_userdata( $user_id ) );
	}

	/**
	 * Confirm administrators inherit plugin capabilities.
	 *
	 * @return void
	 */
	public function test_administrator_inherits_capabilities(): void {
		$admin_id = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);

		$admin = get_userdata( $admin_id );
		$this->assertTrue( $admin->has_cap( 'edit_rt-movies' ), 'Administrator should have edit_rt-movies capability.' );
		$this->assertTrue( $admin->has_cap( 'manage_rt-movie-genre' ), 'Administrator should have manage_rt-movie-genre capability.' );
	}

	/**
	 * Confirm editors inherit plugin capabilities.
	 *
	 * @return void
	 */
	public function test_editor_inherits_capabilities(): void {
		$editor_id = $this->factory->user->create(
			array(
				'role' => 'editor',
			)
		);

		$editor = get_userdata( $editor_id );
		$this->assertTrue( $editor->has_cap( 'edit_rt-movies' ), 'Editor should inherit edit_rt-movies capability.' );
	}

	/**
	 * Confirm role and capabilities are removed on deactivation.
	 *
	 * @return void
	 */
	public function test_deactivation_removes_role_and_caps(): void {
		Movie_Manager_Role::deactivate();

		$role = get_role( Movie_Manager_Role::ROLE_SLUG );
		$this->assertNull( $role );

		$admin_id = $this->factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
		$admin    = get_userdata( $admin_id );
		$this->assertFalse( $admin->has_cap( 'edit_rt-movies' ) );

		// Re-enable for isolation across tests in the same process.
		Movie_Manager_Role::activate();
	}
}
