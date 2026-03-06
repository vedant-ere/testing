<?php
/**
 * WP_Filesystem helper for movie CLI import/export.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Cli\Helpers;


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
	 * @throws \RuntimeException When initialization fails.
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
	 * @throws \RuntimeException When read fails.
	 */
	public function read( string $path ): string {
		$contents = $this->filesystem->get_contents( $path );
		if ( false === $contents ) {
			throw new \RuntimeException(
				sprintf(
					/* translators: %s: file path. */
					__( 'Failed to read file: %s', 'rt-movie-library' ),
					(string) $path
				)
			);
		}

		return $contents;
	}

	/**
	 * Open a local file stream for reading.
	 *
	 * WP_Filesystem does not expose a streaming read API, so for large imports
	 * we open a read-only stream after validating file existence through
	 * WP_Filesystem.
	 *
	 * @param string $path File path.
	 * @return resource
	 * @throws \RuntimeException When stream open fails.
	 */
	public function open_read_stream( string $path ) {
		if ( ! $this->filesystem->exists( $path ) ) {
			throw new \RuntimeException(
				sprintf(
					/* translators: %s: file path. */
					__( 'File not found: %s', 'rt-movie-library' ),
					(string) $path
				)
			);
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Required for memory-safe streaming reads in WP-CLI import.
		$handle = fopen( $path, 'rb' );

		if ( false === $handle ) {
			throw new \RuntimeException(
				sprintf(
					/* translators: %s: file path. */
					__( 'Failed to open file stream: %s', 'rt-movie-library' ),
					(string) $path
				)
			);
		}

		return $handle;
	}

	/**
	 * Open a local file stream for writing CSV rows.
	 *
	 * @param string $path File path.
	 * @param bool   $append Whether to append.
	 * @return resource
	 * @throws \RuntimeException When stream open fails.
	 */
	public function open_write_stream( string $path, bool $append = false ) {
		$mode = $append ? 'ab' : 'wb';

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Required for constant-memory streaming CSV writes in WP-CLI export.
		$handle = fopen( $path, $mode );
		if ( false === $handle ) {
			throw new \RuntimeException(
				sprintf(
					/* translators: %s: file path. */
					__( 'Failed to open CSV file for writing: %s', 'rt-movie-library' ),
					(string) $path
				)
			);
		}

		return $handle;
	}

	/**
	 * Close an open file stream.
	 *
	 * @param resource $handle Stream handle.
	 * @param string   $path File path.
	 * @return void
	 * @throws \RuntimeException When stream close fails.
	 */
	public function close_stream( $handle, string $path ): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Required counterpart to fopen stream lifecycle.
		$result = fclose( $handle );

		if ( false === $result ) {
			throw new \RuntimeException(
				sprintf(
					/* translators: %s: file path. */
					__( 'Failed to close file stream: %s', 'rt-movie-library' ),
					(string) $path
				)
			);
		}
	}

	/**
	 * Write file contents.
	 *
	 * @param string $path File path.
	 * @param string $contents Content.
	 * @return void
	 * @throws \RuntimeException When write fails.
	 */
	public function write( string $path, string $contents ): void {
		$result = $this->filesystem->put_contents( $path, $contents, FS_CHMOD_FILE );

		if ( ! $result ) {
			throw new \RuntimeException(
				sprintf(
					/* translators: %s: file path. */
					__( 'Failed to write file: %s', 'rt-movie-library' ),
					(string) $path
				)
			);
		}
	}

	/**
	 * Append file contents.
	 *
	 * @param string $path File path.
	 * @param string $contents Content.
	 * @return void
	 * @throws \RuntimeException When append fails.
	 */
	public function append( string $path, string $contents ): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Required for constant-memory append writes in WP-CLI export.
		$handle = fopen( $path, 'ab' );
		if ( false === $handle ) {
			throw new \RuntimeException(
				sprintf(
					/* translators: %s: file path. */
					__( 'Failed to append file: %s', 'rt-movie-library' ),
					(string) $path
				)
			);
		}

		try {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite,WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_fwrite -- Intentional: CLI export can process very large datasets, so we append each CSV row directly to disk in constant memory. WP_Filesystem does not provide a streaming append API; using get_contents()+put_contents() would repeatedly re-read/re-write the whole file and scale poorly.
			$written = fwrite( $handle, $contents );
			if ( false === $written || strlen( $contents ) !== $written ) {
				throw new \RuntimeException(
					sprintf(
						/* translators: %s: file path. */
						__( 'Failed to append file: %s', 'rt-movie-library' ),
						(string) $path
					)
				);
			}
		} finally {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Required counterpart to fopen append lifecycle.
			fclose( $handle );
		}
	}

	/**
	 * Write one CSV row to an already-open stream.
	 *
	 * @param resource     $handle Open stream handle.
	 * @param array<mixed> $row CSV row values.
	 * @param string       $path File path.
	 * @return void
	 * @throws \RuntimeException When row write fails.
	 */
	public function write_csv_row_to_stream( $handle, array $row, string $path ): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputcsv -- Intentional: streaming one row at a time keeps memory constant for large exports.
		$result = fputcsv( $handle, $row );
		if ( false === $result ) {
			throw new \RuntimeException(
				sprintf(
					/* translators: %s: file path. */
					__( 'Failed to write CSV row to: %s', 'rt-movie-library' ),
					(string) $path
				)
			);
		}
	}

	/**
	 * Initialize WP_Filesystem.
	 *
	 * @return \WP_Filesystem_Base
	 * @throws \RuntimeException When filesystem is unavailable.
	 */
	private function init_filesystem(): \WP_Filesystem_Base {
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		WP_Filesystem();

		if ( ! $wp_filesystem instanceof \WP_Filesystem_Base ) {
			throw new \RuntimeException( __( 'Unable to initialize WP_Filesystem.', 'rt-movie-library' ) );
		}

		return $wp_filesystem;
	}
}
