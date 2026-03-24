<?php
/**
 * Plugin Autoloader.
 *
 * Registers a custom SPL autoloader responsible for dynamically loading
 * all plugin classes and traits based on their namespace and naming
 * conventions.
 *
 * This autoloader maps the `RT_Movie_Library` namespace to the plugin's
 * internal directory structure and ensures that class files are loaded
 * only when required.
 *
 * @package RT_Movie_Library
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the plugin autoloader.
 *
 * Resolves fully qualified class names within the RT_Movie_Library
 * namespace to their corresponding file paths inside the plugin,
 * supporting both class and trait loading.
 */
spl_autoload_register(
	/**
	 * Autoload callback.
	 *
	 * Converts a fully qualified class name into a file path by:
	 * - Removing the root namespace prefix
	 * - Translating namespaces to directories
	 * - Normalizing naming conventions (hyphens, lowercase)
	 * - Loading class or trait files from the plugin includes directory
	 *
	 * @param string $class_name Fully qualified class name.
	 * @return void
	 */
	function ( $class_name ) {

		$prefix = 'RT_Movie_Library\\';

		if ( strpos( $class_name, $prefix ) !== 0 ) {
			return;
		}

		// Remove root namespace.
		$relative = substr( $class_name, strlen( $prefix ) );

		// Namespace → path.
		$relative = str_replace( '\\', '/', $relative );

		// Convert underscores to hyphens and lowercase.
		$relative = strtolower( str_replace( '_', '-', $relative ) );

		$base = RT_MOVIE_LIBRARY_PATH . 'includes/';

		// Traits.
		if ( str_starts_with( $relative, 'traits/' ) ) {
			$file = $base . 'traits/trait-' . basename( $relative ) . '.php';
		} else { 
			// Classes.
			$file = $base . $relative;
			$dir  = dirname( $file );
			$name = basename( $file );

			$file = ( '.' === $dir )
				? $base . 'class-' . $name . '.php'
				: $dir . '/class-' . $name . '.php';
		}

		if ( file_exists( $file ) ) {
			// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable -- Autoloader requires dynamic file loading.
			require_once $file;
		}
	}
);
