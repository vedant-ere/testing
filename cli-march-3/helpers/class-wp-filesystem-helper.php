<?php
/**
 * WP_Filesystem helper for movie CLI import/export.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Cli\Helpers;

use RT_Movie_Library\Classes\Cli\Exceptions\Cli_File_Exception;

defined( 'ABSPATH' ) || exit;

/**
 * Class Wp_Filesystem_Helper
 */
class Wp_Filesystem_Helper {

	/**
	 * Initialized filesystem.
	 *
	 * @var \WP_Filesystem_Base
	 */
	private \WP_Filesystem_Base $filesystem;

	/**
	 * Constructor.
	 *
	 * @throws Cli_File_Exception When initialization fails.
	 */
	public function __construct() {
		$this->filesystem = $this->init_filesystem();
	}

	/**
	 * Resolve export file path.
	 *
	 * @param string $path Provided path.
	 * @return string
	 */
	public function resolve_export_path( string $path ): string {
		$path = trim( $path );
		if ( '' !== $path ) {
			return $path;
		}

		$upload_dir = wp_upload_dir();
		$basedir    = $upload_dir['basedir'] ?? WP_CONTENT_DIR . '/uploads';

		return trailingslashit( (string) $basedir ) . 'rt-movies-export-' . gmdate( 'Ymd-His' ) . '.csv';
	}

	/**
	 * Check file existence.
	 *
	 * @param string $path File path.
	 * @return bool
	 */
	public function exists( string $path ): bool {
		return $this->filesystem->exists( $path );
	}

	/**
	 * Read file contents.
	 *
	 * @param string $path File path.
	 * @return string
	 * @throws Cli_File_Exception When read fails.
	 */
	public function read( string $path ): string {
		$contents = $this->filesystem->get_contents( $path );
		if ( false === $contents ) {
			throw new Cli_File_Exception(
				sprintf(
					/* translators: %s: file path. */
					esc_html__( 'Failed to read file: %s', 'rt-movie-library' ),
					esc_html( $path )
				)
			);
		}

		return $contents;
	}

	/**
	 * Write file contents.
	 *
	 * @param string $path File path.
	 * @param string $contents Content.
	 * @return void
	 * @throws Cli_File_Exception When write fails.
	 */
	public function write( string $path, string $contents ): void {
		$result = $this->filesystem->put_contents( $path, $contents, FS_CHMOD_FILE );

		if ( ! $result ) {
			throw new Cli_File_Exception(
				sprintf(
					/* translators: %s: file path. */
					esc_html__( 'Failed to write file: %s', 'rt-movie-library' ),
					esc_html( $path )
				)
			);
		}
	}

	/**
	 * Initialize WP_Filesystem.
	 *
	 * @return \WP_Filesystem_Base
	 * @throws Cli_File_Exception When filesystem is unavailable.
	 */
	private function init_filesystem(): \WP_Filesystem_Base {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		if ( ! $wp_filesystem instanceof \WP_Filesystem_Base ) {
			throw new Cli_File_Exception( esc_html__( 'Unable to initialize WP_Filesystem.', 'rt-movie-library' ) );
		}

		return $wp_filesystem;
	}
}
