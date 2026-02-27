<?php
/**
 * Attachment repository for movie CLI.
 *
 * @package RT_Movie_Library
 */

namespace RT_Movie_Library\Classes\Cli\Repositories;

defined( 'ABSPATH' ) || exit;

/**
 * Class Attachment_Repository
 */
class Attachment_Repository {

	/**
	 * Get URL for attachment ID.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string
	 */
	public function url_from_id( int $attachment_id ): string {
		$url = wp_get_attachment_url( $attachment_id );
		return is_string( $url ) ? $url : '';
	}

	/**
	 * Resolve ID from attachment URL.
	 *
	 * @param string $url Attachment URL.
	 * @return int
	 */
	public function id_from_url( string $url ): int {
		if ( '' === $url ) {
			return 0;
		}

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.attachment_url_to_postid_attachment_url_to_postid -- Required for mapping exported attachment URLs back to local IDs.
		return absint( attachment_url_to_postid( $url ) );
	}

	/**
	 * Resolve attachment IDs to unique URLs.
	 *
	 * @param array<int> $ids Attachment IDs.
	 * @return array<string>
	 */
	public function attachment_ids_to_urls( array $ids ): array {
		$urls = array();

		foreach ( $ids as $id ) {
			$url = $this->url_from_id( absint( $id ) );
			if ( '' !== $url ) {
				$urls[] = $url;
			}
		}

		return array_values( array_unique( $urls ) );
	}
}
