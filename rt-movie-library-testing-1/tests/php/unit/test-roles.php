<?php
/**
 * Test Custom Roles functionality.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Tests\Unit;

use WP_UnitTestCase;
use RT_Movie_Library\Classes\Roles\Movie_Manager_Role;

class Test_Roles extends WP_UnitTestCase {

	public function setUp(): void {
		parent::setUp();
		// Ensure role is activated for tests.
		Movie_Manager_Role::activate();
	}

	public function test_movie_manager_role_is_registered() {
		$role = get_role( Movie_Manager_Role::ROLE_SLUG );
		$this->assertInstanceOf( \WP_Role::class, $role );
		
		$all_roles = wp_roles()->role_names;
		$this->assertSame( 'Movie Manager', $all_roles[ Movie_Manager_Role::ROLE_SLUG ], 'Role display name should be "Movie Manager".' );
	}

	public function test_movie_manager_user_crud() {
		// Create User (CRUD - Create)
		$user_id = $this->factory->user->create( array(
			'role' => Movie_Manager_Role::ROLE_SLUG,
		) );
		
		$this->assertIsInt( $user_id );
		$this->assertGreaterThan( 0, $user_id );

		$user = get_userdata( $user_id );
		
		// Read User & Capabilities (CRUD - Read)
		$this->assertTrue( in_array( Movie_Manager_Role::ROLE_SLUG, (array) $user->roles, true ) );
		
		// Assert movie capabilities
		$this->assertTrue( $user->has_cap( 'edit_rt-movies' ) );
		$this->assertTrue( $user->has_cap( 'publish_rt-movies' ) );
		$this->assertTrue( $user->has_cap( 'delete_rt-movies' ) );
		$this->assertTrue( $user->has_cap( 'upload_files' ) );

		// Senior check: Assert taxonomy capabilities
		$this->assertTrue( $user->has_cap( 'manage_rt-movie-genre' ) );
		$this->assertTrue( $user->has_cap( 'assign_rt-movie-language' ) );

		// Assert standard capabilities they shouldn't have
		$this->assertFalse( $user->has_cap( 'manage_options' ) );
		$this->assertFalse( $user->has_cap( 'edit_users' ) );

		// Update User (CRUD - Update)
		$updated = wp_update_user( array(
			'ID'         => $user_id,
			'first_name' => 'John',
		) );
		$this->assertSame( $user_id, $updated );
		$updated_user = get_userdata( $user_id );
		$this->assertSame( 'John', $updated_user->first_name );

		// Delete User (CRUD - Delete)
		$deleted = wp_delete_user( $user_id );
		$this->assertTrue( $deleted );
		$this->assertFalse( get_userdata( $user_id ) );
	}

	public function test_administrator_inherits_capabilities() {
		// Admin should have the capabilities after activation
		$admin_id = $this->factory->user->create( array(
			'role' => 'administrator',
		) );

		$admin = get_userdata( $admin_id );
		$this->assertTrue( $admin->has_cap( 'edit_rt-movies' ), 'Administrator should have edit_rt-movies capability.' );
		$this->assertTrue( $admin->has_cap( 'manage_rt-movie-genre' ), 'Administrator should have manage_rt-movie-genre capability.' );
	}

	public function test_editor_inherits_capabilities() {
		// Senior check: Editor should also inherit capabilities as per the Movie_Manager_Role class
		$editor_id = $this->factory->user->create( array(
			'role' => 'editor',
		) );

		$editor = get_userdata( $editor_id );
		$this->assertTrue( $editor->has_cap( 'edit_rt-movies' ), 'Editor should have edit_rt-movies capability inherited.' );
	}

	public function test_deactivation_removes_role_and_caps() {
		Movie_Manager_Role::deactivate();
		
		$role = get_role( Movie_Manager_Role::ROLE_SLUG );
		$this->assertNull( $role );

		// Check admins lost it
		$admin_id = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
		$admin = get_userdata( $admin_id );
		$this->assertFalse( $admin->has_cap( 'edit_rt-movies' ) );

		// Reactivate for other tests
		Movie_Manager_Role::activate();
	}
}
