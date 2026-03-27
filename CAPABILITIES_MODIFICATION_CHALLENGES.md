# 5 Movie Manager Role & Capabilities Modification Challenges

## Interview-Level Challenges to Test WordPress Roles & Capabilities Knowledge

---

## Challenge #1: Add Capability Audit Logging
**Difficulty:** Intermediate  
**What it tests:** Understanding of capability lifecycle, WordPress hooks, and audit trails

**Problem Statement:**
When capabilities are granted/revoked, there's no record of WHO did it and WHEN. A reviewer might ask: "Add logging so we can track capability changes for security and compliance purposes."

**Current Issue:**
```php
public static function activate(): void {
    add_role( self::ROLE_SLUG, self::ROLE_NAME, self::get_capabilities() );
    self::grant_caps_to_administrator();
    // No logging - when was this run? Who ran it?
}
```

**Task:** Add audit logging that records:
- When capabilities are granted/revoked
- Which user performed the action
- Which capabilities were affected
- Success/failure status

**Step-by-Step Implementation:**

1. **Add logging method to class:**

```php
/**
 * Log capability changes for audit trail.
 *
 * @param string $action Action type: 'grant', 'revoke', 'activate', 'deactivate'.
 * @param string $role_slug Role being modified.
 * @param array<string, bool> $capabilities Capabilities affected.
 * @return void
 */
private static function log_capability_action( string $action, string $role_slug, array $capabilities = array() ): void {
    $audit_log = get_option( 'rt_movie_manager_audit_log', array() );

    $log_entry = array(
        'timestamp' => current_time( 'mysql' ),
        'action' => $action,
        'role' => $role_slug,
        'user_id' => get_current_user_id(),
        'user_login' => get_userdata( get_current_user_id() )->user_login ?? 'unknown',
        'cap_count' => count( $capabilities ),
        'ip_address' => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : 'unknown',
    );

    $audit_log[] = $log_entry;

    // Keep only last 500 entries to avoid bloating wp_options
    if ( count( $audit_log ) > 500 ) {
        $audit_log = array_slice( $audit_log, -500 );
    }

    update_option( 'rt_movie_manager_audit_log', $audit_log );
}
```

2. **Update activate() method to log:**

```php
public static function activate(): void {
    add_role(
        self::ROLE_SLUG,
        self::ROLE_NAME,
        self::get_capabilities()
    );

    self::grant_caps_to_administrator();
    
    // Log the activation
    self::log_capability_action( 'activate', self::ROLE_SLUG, self::get_capabilities() );
}
```

3. **Update deactivate() method to log:**

```php
public static function deactivate(): void {
    remove_role( self::ROLE_SLUG );
    self::revoke_caps_from_administrator();
    
    // Log the deactivation
    self::log_capability_action( 'deactivate', self::ROLE_SLUG, self::get_capabilities() );
}
```

4. **Update grant_caps_to_administrator() to log:**

```php
private static function grant_caps_to_administrator(): void {
    $admin = get_role( 'administrator' );

    if ( ! $admin instanceof \WP_Role ) {
        return;
    }

    $granted_caps = array();

    foreach ( self::get_administrator_caps() as $cap => $grant ) {
        $admin->add_cap( $cap, $grant );
        $granted_caps[ $cap ] = $grant;
    }

    // Log the capability grants
    self::log_capability_action( 'grant_admin_caps', 'administrator', $granted_caps );
}
```

5. **Create helper method to retrieve audit log:**

```php
/**
 * Get the capability audit log.
 *
 * @param int $limit Number of recent entries to return.
 * @return array<int, array> Audit log entries.
 */
public static function get_audit_log( int $limit = 50 ): array {
    $full_log = get_option( 'rt_movie_manager_audit_log', array() );
    
    return array_slice( $full_log, -$limit );
}

/**
 * Clear the audit log.
 *
 * @return void
 */
public static function clear_audit_log(): void {
    delete_option( 'rt_movie_manager_audit_log' );
}
```

**How to Test:**

1. Activate the plugin and check `wp_options` table for `rt_movie_manager_audit_log`
2. Deactivate and reactivate, verify log entries appear
3. Check that `user_id` and `timestamp` are recorded correctly
4. Retrieve log: `Movie_Manager_Role::get_audit_log()`

**Why This Matters:**
- Tests understanding of WordPress audit/logging patterns
- Shows you know about capability lifecycle
- Demonstrates security awareness (who changed permissions?)
- Tests hook understanding and when code executes

---

## Challenge #2: Create Role Hierarchy with Capability Inheritance
**Difficulty:** Advanced  
**What it tests:** Role architecture, capability inheritance, and design patterns

**Problem Statement:**
Currently there's only one "Movie Manager" role. A reviewer might ask: "We need a hierarchy: Movie Viewer → Movie Editor → Movie Manager. Each role should inherit capabilities from the one below it. Can you implement this?"

**Task:** Create a role hierarchy where:
- **Movie Viewer** — Read-only access (view movies/persons, view taxonomies)
- **Movie Editor** — Can edit content (edit/publish movies, manage some taxonomies)
- **Movie Manager** — Full access (everything Movie Editor has + delete permissions)

**Step-By-Step Implementation:**

1. **Define role hierarchy as constant:**

```php
/**
 * Role hierarchy definition.
 *
 * @var array
 */
private const ROLE_HIERARCHY = array(
    'movie-viewer' => array(
        'display_name' => 'Movie Viewer',
        'parent' => null,
        'capabilities' => array(
            'read',
            'read_rt-movie',
            'read_rt-person',
        ),
    ),
    'movie-editor' => array(
        'display_name' => 'Movie Editor',
        'parent' => 'movie-viewer',
        'capabilities' => array(
            'read',
            'upload_files',
            'edit_rt-movie',
            'read_rt-movie',
            'edit_rt-movies',
            'publish_rt-movies',
            'edit_rt-person',
            'read_rt-person',
            'edit_rt-persons',
            'publish_rt-persons',
            'assign_rt-movie-genre',
            'assign_rt-movie-label',
            'assign_rt-person-career',
        ),
    ),
    'movie-manager' => array(
        'display_name' => 'Movie Manager',
        'parent' => 'movie-editor',
        'capabilities' => 'use_get_capabilities', // Existing method
    ),
);
```

2. **Create method to get capabilities with inheritance:**

```php
/**
 * Get all capabilities for a role, including inherited ones.
 *
 * @param string $role_slug Role to get capabilities for.
 * @return array<string, bool> Capabilities including inherited.
 */
private static function get_capabilities_with_inheritance( string $role_slug ): array {
    $capabilities = array();

    // Recursively get parent capabilities
    $current_role = $role_slug;
    while ( $current_role ) {
        if ( ! isset( self::ROLE_HIERARCHY[ $current_role ] ) ) {
            break;
        }

        $role_config = self::ROLE_HIERARCHY[ $current_role ];
        
        // Add this role's capabilities
        foreach ( $role_config['capabilities'] as $cap ) {
            $capabilities[ $cap ] = true;
        }

        // Move to parent role
        $current_role = $role_config['parent'] ?? null;
    }

    return $capabilities;
}
```

3. **Create method to activate all roles:**

```php
/**
 * Activate all movie manager roles with hierarchy.
 *
 * @return void
 */
public static function activate_hierarchy(): void {
    foreach ( self::ROLE_HIERARCHY as $role_slug => $config ) {
        $capabilities = 'use_get_capabilities' === $config['capabilities']
            ? self::get_capabilities()
            : self::get_capabilities_with_inheritance( $role_slug );

        add_role(
            $role_slug,
            $config['display_name'],
            $capabilities
        );

        self::log_capability_action( 'activate', $role_slug, $capabilities );
    }

    // Grant all custom capabilities to admin
    self::grant_caps_to_administrator();
}
```

4. **Create method to deactivate all roles:**

```php
/**
 * Deactivate all movie manager roles.
 *
 * @return void
 */
public static function deactivate_hierarchy(): void {
    foreach ( array_keys( self::ROLE_HIERARCHY ) as $role_slug ) {
        remove_role( $role_slug );
        self::log_capability_action( 'deactivate', $role_slug );
    }

    self::revoke_caps_from_administrator();
}
```

5. **Create method to check if user can do action:**

```php
/**
 * Check if user has movie capability.
 *
 * @param int $user_id User to check.
 * @param string $capability Capability to check.
 * @return bool
 */
public static function user_can( int $user_id, string $capability ): bool {
    $user = get_userdata( $user_id );

    if ( ! $user ) {
        return false;
    }

    return in_array( $capability, array_keys( $user->allcaps ), true );
}
```

**How to Test:**

1. Activate plugin, check that 3 roles are created
2. Assign a user to `movie-viewer` role, verify they can't publish
3. Assign a user to `movie-editor` role, verify they can publish but not delete
4. Assign a user to `movie-manager` role, verify they have all permissions
5. Check WordPress Users page, see all three roles available

**Why This Matters:**
- Tests understanding of role inheritance and hierarchy
- Shows knowledge of capability structure
- Demonstrates scalable architecture
- Tests design pattern knowledge

---

## Challenge #3: Add Capability Validation & Runtime Checks
**Difficulty:** Intermediate-Advanced  
**What it tests:** Defensive programming, validation, and error handling

**Problem Statement:**
There's no validation that all defined capabilities are actually valid. A reviewer might ask: "What if someone typos a capability name? Add validation to ensure all capabilities are properly formatted and valid."

**Task:** Add validation methods that:
- Check capability names follow WordPress conventions
- Ensure capabilities are assigned to valid post types/taxonomies
- Validate that required capabilities are present
- Throw errors on invalid configurations

**Step-By-Step Implementation:**

1. **Add validation constants:**

```php
/**
 * Supported post types.
 *
 * @var array
 */
private const SUPPORTED_POST_TYPES = array( 'rt-movie', 'rt-person' );

/**
 * Supported taxonomies.
 *
 * @var array
 */
private const SUPPORTED_TAXONOMIES = array(
    'rt-movie-genre',
    'rt-movie-label',
    'rt-movie-language',
    'rt-movie-production-company',
    'rt-movie-tag',
    'rt-person-career',
    'rt-movie-person',
);
```

2. **Create validation method:**

```php
/**
 * Validate capability name format.
 *
 * @param string $capability Capability to validate.
 * @return array Array with 'valid' (bool) and 'error' (string) keys.
 */
private static function validate_capability( string $capability ): array {
    // Check if empty
    if ( empty( $capability ) ) {
        return array(
            'valid' => false,
            'error' => 'Capability name is empty',
        );
    }

    // Check if contains only allowed characters
    if ( ! preg_match( '/^[a-z0-9_-]+$/', $capability ) ) {
        return array(
            'valid' => false,
            'error' => sprintf(
                'Capability "%s" contains invalid characters. Only a-z, 0-9, underscore, and hyphen allowed.',
                $capability
            ),
        );
    }

    // Check if it matches expected patterns
    $valid_patterns = array(
        '/^(read|edit|delete|publish)_rt-(movie|person)s?$/',  // CPT capabilities
        '/^(manage|assign)_rt-movie-(genre|label|language|tag|production-company)$/',  // Taxonomy capabilities
        '/^(manage|assign)_rt-person-career$/',  // Career taxonomy
        '/^(manage|assign)_rt-movie-person$/',  // Movie-Person taxonomy
        '/^(read|upload_files)$/',  // Base capabilities
    );

    $matches = false;
    foreach ( $valid_patterns as $pattern ) {
        if ( preg_match( $pattern, $capability ) ) {
            $matches = true;
            break;
        }
    }

    if ( ! $matches ) {
        return array(
            'valid' => false,
            'error' => sprintf(
                'Capability "%s" does not match expected WordPress capability patterns.',
                $capability
            ),
        );
    }

    return array(
        'valid' => true,
        'error' => '',
    );
}
```

3. **Create method to validate all capabilities:**

```php
/**
 * Validate all capabilities in the role.
 *
 * @return array Array of validation results with 'valid' (bool), 'errors' (array).
 */
public static function validate_all_capabilities(): array {
    $capabilities = self::get_capabilities();
    $errors = array();
    $validated = 0;

    foreach ( array_keys( $capabilities ) as $capability ) {
        $result = self::validate_capability( $capability );

        if ( ! $result['valid'] ) {
            $errors[] = $result['error'];
        } else {
            $validated++;
        }
    }

    return array(
        'valid' => empty( $errors ),
        'validated' => $validated,
        'total' => count( $capabilities ),
        'errors' => $errors,
    );
}
```

4. **Add validation to activate method:**

```php
public static function activate(): void {
    // Validate before activating
    $validation = self::validate_all_capabilities();

    if ( ! $validation['valid'] ) {
        error_log( 'Movie Manager Role activation failed validation: ' . implode( ', ', $validation['errors'] ) );
        throw new \Exception( 'Invalid role configuration: ' . implode( ', ', $validation['errors'] ) );
    }

    add_role(
        self::ROLE_SLUG,
        self::ROLE_NAME,
        self::get_capabilities()
    );

    self::grant_caps_to_administrator();
    self::log_capability_action( 'activate', self::ROLE_SLUG, self::get_capabilities() );
}
```

5. **Create debug method:**

```php
/**
 * Get debug information about the role.
 *
 * @return array Debug information.
 */
public static function get_debug_info(): array {
    $validation = self::validate_all_capabilities();
    $role = get_role( self::ROLE_SLUG );
    $admin = get_role( 'administrator' );

    return array(
        'role_exists' => $role instanceof \WP_Role,
        'role_caps_count' => $role ? count( $role->capabilities ) : 0,
        'validation' => $validation,
        'admin_has_custom_caps' => $admin ? count( array_filter(
            $admin->capabilities,
            function( $cap ) {
                return strpos( $cap, 'rt-' ) === 0;
            },
            ARRAY_FILTER_USE_KEY
        )) : 0,
    );
}
```

**How to Test:**

1. Call `Movie_Manager_Role::validate_all_capabilities()` and check results
2. Intentionally add invalid capability to test validation catches it
3. Call `Movie_Manager_Role::get_debug_info()` to see full role state
4. Check error logs when invalid capabilities are found

**Why This Matters:**
- Tests understanding of WordPress capability naming conventions
- Shows defensive programming practices
- Demonstrates validation and error handling
- Tests regex knowledge

---

## Challenge #4: Implement Capability Usage Restrictions
**Difficulty:** Advanced  
**What it tests:** Advanced capability mapping, business logic, and WordPress internals

**Problem Statement:**
Currently, a movie-manager can edit ANY movie. A reviewer might ask: "Add business logic so movie managers can ONLY edit movies they created, unless they have special permission. Also add a capability that limits them to editing only PUBLISHED movies, not drafts."

**Task:** Implement:
- **Restrict editing:** Movie managers can only edit movies they authored (except admins)
- **Restrict deletion:** Movie managers can only delete their own movies
- **Draft restriction:** Can optionally restrict movie managers from editing draft movies
- **Add override capability:** `edit_others_rt-movies` bypasses these restrictions

**Step-By-Step Implementation:**

1. **Create restriction configuration:**

```php
/**
 * Capability restrictions for roles.
 *
 * @var array
 */
private const CAPABILITY_RESTRICTIONS = array(
    'edit_rt-movies' => array(
        'restrict_to_own_posts' => true,
        'allow_draft_editing' => true,
        'override_capability' => 'edit_others_rt-movies',
    ),
    'delete_rt-movies' => array(
        'restrict_to_own_posts' => true,
        'allow_draft_deletion' => false,
        'override_capability' => 'delete_others_rt-movies',
    ),
    'publish_rt-movies' => array(
        'restrict_to_own_posts' => false,  // Anyone can publish
        'allow_draft_publishing' => true,
        'override_capability' => null,
    ),
);
```

2. **Create method to apply restrictions:**

```php
/**
 * Apply capability restrictions based on post and user.
 *
 * @param array<string> $allcaps User's capabilities.
 * @param string $cap Capability being checked.
 * @param array $args Arguments (post_id, etc).
 * @return array Modified capabilities.
 */
public static function apply_capability_restrictions( array $allcaps, string $cap, array $args ): array {
    // Only apply to our custom movie capabilities
    if ( strpos( $cap, 'rt-movie' ) === false && strpos( $cap, 'rt-person' ) === false ) {
        return $allcaps;
    }

    // Check if this capability has restrictions
    if ( ! isset( self::CAPABILITY_RESTRICTIONS[ $cap ] ) ) {
        return $allcaps;
    }

    $restriction = self::CAPABILITY_RESTRICTIONS[ $cap ];
    $user_id = isset( $args[0] ) ? intval( $args[0] ) : get_current_user_id();
    $post_id = isset( $args[1] ) ? intval( $args[1] ) : 0;

    // Check if user is admin (always allowed)
    if ( user_can( $user_id, 'manage_options' ) ) {
        return $allcaps;
    }

    // Check if user can override (e.g., edit_others_rt-movies)
    if ( $restriction['override_capability'] && isset( $allcaps[ $restriction['override_capability'] ] ) ) {
        return $allcaps;
    }

    // Apply post ownership restriction
    if ( $restriction['restrict_to_own_posts'] && $post_id ) {
        $post = get_post( $post_id );

        if ( $post && intval( $post->post_author ) !== $user_id ) {
            // User doesn't own this post
            unset( $allcaps[ $cap ] );
            return $allcaps;
        }

        // Check draft restriction
        if ( ! $restriction['allow_draft_editing'] && 'draft' === $post->post_status ) {
            unset( $allcaps[ $cap ] );
            return $allcaps;
        }
    }

    return $allcaps;
}
```

3. **Hook into WordPress capability system:**

```php
/**
 * Register capability restrictions.
 *
 * @return void
 */
public static function register_restrictions(): void {
    add_filter( 'user_has_cap', array( self::class, 'apply_capability_restrictions' ), 10, 3 );
}
```

4. **Create helper to check if user can edit post:**

```php
/**
 * Check if user can edit a specific post with restrictions applied.
 *
 * @param int $user_id User to check.
 * @param int $post_id Post to check.
 * @return bool
 */
public static function user_can_edit_post( int $user_id, int $post_id ): bool {
    $post = get_post( $post_id );

    if ( ! $post ) {
        return false;
    }

    // Admin always can
    if ( user_can( $user_id, 'manage_options' ) ) {
        return true;
    }

    // Check override capability
    if ( user_can( $user_id, 'edit_others_rt-movies' ) ) {
        return true;
    }

    // Check ownership
    if ( intval( $post->post_author ) === $user_id ) {
        // Check if user can edit posts in this status
        $restriction = self::CAPABILITY_RESTRICTIONS['edit_rt-movies'] ?? array();

        if ( 'draft' === $post->post_status && ! ( $restriction['allow_draft_editing'] ?? true ) ) {
            return false;
        }

        return true;
    }

    return false;
}
```

**How to Test:**

1. Create two users: both with movie-manager role
2. User A creates movie "Movie A"
3. User B tries to edit "Movie A" - should be DENIED
4. Admin can edit "Movie A" - should be ALLOWED
5. Grant User B `edit_others_rt-movies` capability - now they can edit
6. Create draft movie, deny editing for movie-managers - only editors/admins can edit

**Why This Matters:**
- Tests understanding of WordPress `user_has_cap` filter
- Shows knowledge of capability mapping
- Demonstrates business logic implementation
- Tests post/user authorization patterns

---

## Challenge #5: Add Dynamic Capability Management UI
**Difficulty:** Expert  
**What it tests:** Settings UI, capability management, and WordPress admin integration

**Problem Statement:**
All capabilities are hardcoded. A reviewer might ask: "Create an admin page where we can enable/disable specific capabilities for the movie-manager role, and also manage which roles have which capabilities. Make it user-friendly."

**Task:** Build an admin interface that:
- Shows all available capabilities grouped by category
- Allows toggling capabilities ON/OFF per role
- Shows which users have each role
- Validates changes before saving
- Shows audit log of capability changes

**Step-By-Step Implementation:**

1. **Create admin menu page method:**

```php
/**
 * Register admin menu page for capability management.
 *
 * @return void
 */
public static function register_admin_page(): void {
    add_menu_page(
        'Movie Manager Roles',
        'Movie Roles',
        'manage_options',
        'rt_movie_manager_roles',
        array( self::class, 'render_admin_page' ),
        'dashicons-admin-users',
        30
    );

    add_submenu_page(
        'rt_movie_manager_roles',
        'Capability Audit Log',
        'Audit Log',
        'manage_options',
        'rt_movie_manager_audit_log',
        array( self::class, 'render_audit_log_page' )
    );
}
```

2. **Create admin page renderer:**

```php
/**
 * Render the role management admin page.
 *
 * @return void
 */
public static function render_admin_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }

    // Handle form submission
    if ( isset( $_POST['action'] ) && $_POST['action'] === 'update_capabilities' ) {
        check_admin_referer( 'rt_movie_manager_nonce' );
        self::handle_capability_update();
    }

    $role = get_role( self::ROLE_SLUG );
    $all_capabilities = self::get_capabilities();
    $current_capabilities = $role ? array_keys( array_filter( $role->capabilities ) ) : array();

    ?>
    <div class="wrap">
        <h1><?php echo esc_html( 'Movie Manager Capabilities' ); ?></h1>

        <form method="POST">
            <?php wp_nonce_field( 'rt_movie_manager_nonce' ); ?>
            <input type="hidden" name="action" value="update_capabilities">

            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Capability</th>
                        <th>Type</th>
                        <th>Enabled</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $grouped = self::group_capabilities_by_type( $all_capabilities );
                    foreach ( $grouped as $type => $caps ) {
                        echo '<tr><td colspan="3"><strong>' . esc_html( $type ) . '</strong></td></tr>';

                        foreach ( $caps as $cap ) {
                            $checked = in_array( $cap, $current_capabilities, true ) ? 'checked' : '';
                            ?>
                            <tr>
                                <td><?php echo esc_html( $cap ); ?></td>
                                <td><?php echo esc_html( self::get_capability_type( $cap ) ); ?></td>
                                <td>
                                    <input type="checkbox" 
                                           name="capabilities[]" 
                                           value="<?php echo esc_attr( $cap ); ?>" 
                                           <?php echo esc_html( $checked ); ?>>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>

            <p>
                <input type="submit" class="button button-primary" value="Update Capabilities">
            </p>
        </form>

        <h2><?php echo esc_html( 'Users with Movie Manager Role' ); ?></h2>
        <?php self::render_users_with_role(); ?>
    </div>
    <?php
}
```

3. **Create handler for capability updates:**

```php
/**
 * Handle capability update form submission.
 *
 * @return void
 */
private static function handle_capability_update(): void {
    $role = get_role( self::ROLE_SLUG );

    if ( ! $role ) {
        wp_die( 'Role not found' );
    }

    $new_capabilities = isset( $_POST['capabilities'] ) ? (array) $_POST['capabilities'] : array();
    $new_capabilities = array_map( 'sanitize_text_field', $new_capabilities );

    // Get current capabilities
    $current_capabilities = array_keys( array_filter( $role->capabilities ) );

    // Find added and removed capabilities
    $added = array_diff( $new_capabilities, $current_capabilities );
    $removed = array_diff( $current_capabilities, $new_capabilities );

    // Add new capabilities
    foreach ( $added as $cap ) {
        if ( self::validate_capability( $cap )['valid'] ) {
            $role->add_cap( $cap );
            self::log_capability_action( 'add_cap', self::ROLE_SLUG, array( $cap => true ) );
        }
    }

    // Remove capabilities
    foreach ( $removed as $cap ) {
        $role->remove_cap( $cap );
        self::log_capability_action( 'remove_cap', self::ROLE_SLUG, array( $cap => false ) );
    }

    wp_safe_remote_post( admin_url( 'admin.php?page=rt_movie_manager_roles&updated=1' ) );
}
```

4. **Create capability grouping method:**

```php
/**
 * Group capabilities by type (CPT, Taxonomy, Base).
 *
 * @param array<string, bool> $capabilities Capabilities to group.
 * @return array<string, array> Grouped capabilities.
 */
private static function group_capabilities_by_type( array $capabilities ): array {
    $grouped = array(
        'Base' => array(),
        'Movie CPT' => array(),
        'Person CPT' => array(),
        'Taxonomies' => array(),
    );

    foreach ( array_keys( $capabilities ) as $cap ) {
        if ( in_array( $cap, array( 'read', 'upload_files' ), true ) ) {
            $grouped['Base'][] = $cap;
        } elseif ( strpos( $cap, 'rt-movie' ) !== false && strpos( $cap, 'movie-genre' ) === false && strpos( $cap, 'movie-label' ) === false ) {
            $grouped['Movie CPT'][] = $cap;
        } elseif ( strpos( $cap, 'rt-person' ) !== false ) {
            $grouped['Person CPT'][] = $cap;
        } else {
            $grouped['Taxonomies'][] = $cap;
        }
    }

    return array_filter( $grouped );
}

/**
 * Get human-readable capability type.
 *
 * @param string $capability Capability name.
 * @return string
 */
private static function get_capability_type( string $capability ): string {
    if ( in_array( $capability, array( 'read', 'upload_files' ), true ) ) {
        return 'Base';
    } elseif ( strpos( $capability, 'rt-movie' ) !== false ) {
        return 'Movie CPT';
    } elseif ( strpos( $capability, 'rt-person' ) !== false ) {
        return 'Person CPT';
    } else {
        return 'Taxonomy';
    }
}
```

5. **Create audit log page:**

```php
/**
 * Render the audit log page.
 *
 * @return void
 */
public static function render_audit_log_page(): void {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }

    $log = self::get_audit_log( 100 );

    ?>
    <div class="wrap">
        <h1><?php echo esc_html( 'Movie Manager Audit Log' ); ?></h1>

        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Action</th>
                    <th>User</th>
                    <th>Role</th>
                    <th>Capabilities</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ( array_reverse( $log ) as $entry ) {
                    ?>
                    <tr>
                        <td><?php echo esc_html( $entry['timestamp'] ); ?></td>
                        <td><?php echo esc_html( $entry['action'] ); ?></td>
                        <td><?php echo esc_html( $entry['user_login'] ); ?></td>
                        <td><?php echo esc_html( $entry['role'] ); ?></td>
                        <td><?php echo intval( $entry['cap_count'] ); ?></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>

        <p>
            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=clear_audit_log' ), 'clear_log' ) ); ?>" class="button">Clear Log</a>
        </p>
    </div>
    <?php
}
```

**How to Test:**

1. Go to WordPress admin → Movie Roles menu
2. See all capabilities grouped by type
3. Uncheck a capability, save changes
4. Verify the role no longer has that capability
5. Go to Audit Log submenu
6. See all capability changes logged with timestamps and user info

**Why This Matters:**
- Tests understanding of WordPress admin pages and settings
- Shows knowledge of capability management at scale
- Demonstrates user-friendly interface design
- Tests WordPress nonces and form handling
- Shows practical implementation skills

---

## Summary Table

| # | Challenge | Focus Area | Difficulty | Time | Key Skills Tested |
|---|-----------|-----------|-----------|------|------------------|
| 1 | Audit Logging | Security & Tracking | Intermediate | 30-40 min | Hooks, Options API, Logging |
| 2 | Role Hierarchy | Architecture & Inheritance | Advanced | 45-60 min | Role Design, Recursion, OOP |
| 3 | Validation | Defensive Programming | Intermediate-Advanced | 40-50 min | Regex, Error Handling, Patterns |
| 4 | Usage Restrictions | Business Logic | Advanced | 50-60 min | user_has_cap filter, Logic |
| 5 | Admin UI | Integration | Expert | 60-90 min | Admin Pages, Forms, Settings |

---

## Which Challenge to Pick Based on Interview Level

**Junior Dev (0-2 years):**
- Start with Challenge #1 (Audit Logging)
- Good test of basic WordPress knowledge
- ~30 minutes

**Mid-Level Dev (2-5 years):**
- Try Challenge #2 (Role Hierarchy) or #4 (Restrictions)
- Tests architecture and deeper WordPress knowledge
- ~45-60 minutes

**Senior Dev (5+ years):**
- Challenge #5 (Admin UI) is the full test
- Shows complete WordPress integration knowledge
- ~90 minutes for full implementation

**For a 2-3 hour interview:**
- Pick Challenge #1 + #4
- Tests both logging and business logic
- Shows well-rounded capabilities knowledge
