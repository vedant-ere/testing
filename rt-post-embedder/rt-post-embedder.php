<?php
/**
 * Plugin Name: RT Post Embedder
 * Description: Provides a Custom Posts post type and block-based post embedding workflow.
 * Version: 1.0.0
 * Author: Vedant Ere
 * Text Domain: rt-post-embedder
 * Requires PHP: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 *
 * @package RT_Post_Embedder
 */

namespace RT_Post_Embedder;

defined( 'ABSPATH' ) || exit;

define( 'RT_POST_EMBEDDER_PATH', plugin_dir_path( __FILE__ ) );
define( 'RT_POST_EMBEDDER_URL', plugin_dir_url( __FILE__ ) );
define( 'RT_POST_EMBEDDER_VERSION', '1.0.0' );

require_once RT_POST_EMBEDDER_PATH . 'includes/traits/trait-singleton.php';

/**
 * Register plugin autoloader.
 *
 * The plugin keeps classes in includes/class-*.php and traits in
 * includes/traits/trait-*.php so this resolver mirrors that layout.
 *
 * @param string $class_name Fully-qualified class name.
 * @return void
 */
spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'RT_Post_Embedder\\';

		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( $prefix ) );
		$relative = str_replace( '\\', '/', $relative );
		$relative = strtolower( str_replace( '_', '-', $relative ) );

		if ( str_starts_with( $relative, 'traits/' ) ) {
			$file = RT_POST_EMBEDDER_PATH . 'includes/traits/trait-' . basename( $relative ) . '.php';
		} else {
			$file = RT_POST_EMBEDDER_PATH . 'includes/class-' . basename( $relative ) . '.php';
		}

		if ( file_exists( $file ) ) {
			// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable -- Dynamic autoload mapping is intentional.
			require_once $file;
		}
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		load_plugin_textdomain(
			'rt-post-embedder',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);

		Plugin::get_instance();
	}
);

register_activation_hook(
	__FILE__,
	array( Activator::class, 'activate' )
);
