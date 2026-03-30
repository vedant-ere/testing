<?php
/**
 * Sync handling for embedded post references.
 *
 * @package RT_Post_Embedder
 */

namespace RT_Post_Embedder;

use RT_Post_Embedder\Traits\Singleton;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Class Sync_Handler
 */
class Sync_Handler {

	use Singleton;

	/**
	 * Meta key storing where a post is embedded.
	 *
	 * @var string
	 */
	public const META_KEY_EMBEDDED_IN = '_rt_pe_embedded_in';

	/**
	 * Meta key storing post IDs currently embedded by a custom-post.
	 *
	 * @var string
	 */
	private const META_KEY_EMBEDDED_IDS = '_rt_pe_embedded_ids';

	/**
	 * Block name used to discover block instances.
	 *
	 * @var string
	 */
	private const BLOCK_NAME = 'rt-post-embedder/post-embedder';

	/**
	 * Last sync error meta key.
	 *
	 * @var string
	 */
	private const META_KEY_LAST_SYNC_ERROR = '_rt_pe_last_sync_error';

	/**
	 * Register hooks.
	 */
	protected function __construct() {
		add_action( 'save_post_' . Custom_Posts_Cpt::POST_TYPE, array( $this, 'on_custom_post_save' ), 20, 2 );
	}

	/**
	 * Handle custom-post save.
	 *
	 * @param int     $post_id Current custom-post ID.
	 * @param WP_Post $post    Post object.
	 * @return void
	 */
	public function on_custom_post_save( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$embedded_items        = $this->extract_embedded_items( $post->post_content );
		$current_embedded_ids  = array_values( array_unique( array_filter( array_map( array( $this, 'extract_item_post_id' ), $embedded_items ) ) ) );
		$previous_embedded_ids = $this->get_previously_embedded_ids( $post_id );

		$meta_prefetch_ids = array_values( array_unique( array_merge( $current_embedded_ids, $previous_embedded_ids ) ) );
		if ( ! empty( $meta_prefetch_ids ) ) {
			update_meta_cache( 'post', $meta_prefetch_ids );
		}

		$removed_ids = array_diff( $previous_embedded_ids, $current_embedded_ids );

		foreach ( $removed_ids as $removed_id ) {
			$this->remove_embedding_reference( (int) $removed_id, $post_id );
		}

		foreach ( $current_embedded_ids as $embedded_id ) {
			$this->add_embedding_reference( (int) $embedded_id, $post_id );
		}

		if ( empty( $current_embedded_ids ) ) {
			delete_post_meta( $post_id, self::META_KEY_EMBEDDED_IDS );
		} else {
			update_post_meta( $post_id, self::META_KEY_EMBEDDED_IDS, $current_embedded_ids );
		}

		$this->sync_embedded_items_to_original_posts( $embedded_items );
	}

	/**
	 * Extract embedded post ID from an item.
	 *
	 * @param array<string, mixed> $item Embedded item data.
	 * @return int
	 */
	private function extract_item_post_id( array $item ): int {
		return (int) ( $item['post_id'] ?? 0 );
	}

	/**
	 * Parse content and extract embedded post payloads.
	 *
	 * @param string $post_content Serialized block content.
	 * @return array<int, array<string, mixed>>
	 */
	private function extract_embedded_items( string $post_content ): array {
		if ( '' === $post_content ) {
			return array();
		}

		$blocks         = parse_blocks( $post_content );
		$embedder_items = array();
		$this->collect_embedder_items( $blocks, $embedder_items );

		$deduped = array();
		foreach ( $embedder_items as $item ) {
			$post_id = absint( $item['post_id'] ?? 0 );
			if ( $post_id < 1 ) {
				continue;
			}

			$deduped[ $post_id ] = array(
				'post_id'      => $post_id,
				'title'        => sanitize_text_field( (string) ( $item['title'] ?? '' ) ),
				'excerpt'      => (string) ( $item['excerpt'] ?? '' ),
				'date'         => sanitize_text_field( (string) ( $item['date'] ?? '' ) ),
				'thumbnail_id' => absint( $item['thumbnail_id'] ?? 0 ),
				'sync'         => ! empty( $item['sync'] ),
			);
		}

		return array_values( $deduped );
	}

	/**
	 * Recursively collect embedder block items.
	 *
	 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
	 * @param array<int, array<string, mixed>> $items  Collector.
	 * @return void
	 */
	private function collect_embedder_items( array $blocks, array &$items ): void {
		foreach ( $blocks as $block ) {
			$block_name = $block['blockName'] ?? '';

			if ( self::BLOCK_NAME === $block_name ) {
				$embedded_posts = $block['attrs']['embeddedPosts'] ?? array();
				if ( is_array( $embedded_posts ) ) {
					foreach ( $embedded_posts as $embedded_post ) {
						if ( ! is_array( $embedded_post ) ) {
							continue;
						}

						$items[] = array(
							'post_id'      => absint( $embedded_post['postId'] ?? 0 ),
							'title'        => (string) ( $embedded_post['title'] ?? '' ),
							'excerpt'      => (string) ( $embedded_post['excerpt'] ?? '' ),
							'date'         => (string) ( $embedded_post['date'] ?? '' ),
							'thumbnail_id' => absint( $embedded_post['thumbnailId'] ?? 0 ),
							'sync'         => ! empty( $embedded_post['syncChanges'] ),
						);
					}
				}
			}

			$inner_blocks = $block['innerBlocks'] ?? array();
			if ( is_array( $inner_blocks ) && ! empty( $inner_blocks ) ) {
				$this->collect_embedder_items( $inner_blocks, $items );
			}
		}
	}

	/**
	 * Read previously embedded IDs from current custom-post meta.
	 *
	 * @param int $custom_post_id Custom post ID.
	 * @return int[]
	 */
	private function get_previously_embedded_ids( int $custom_post_id ): array {
		$stored = get_post_meta( $custom_post_id, self::META_KEY_EMBEDDED_IDS, true );

		if ( ! is_array( $stored ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'absint', $stored ) ) );
	}

	/**
	 * Add reverse relation on an embedded source post.
	 *
	 * @param int $source_post_id Source post ID.
	 * @param int $custom_post_id Embedding custom-post ID.
	 * @return void
	 */
	private function add_embedding_reference( int $source_post_id, int $custom_post_id ): void {
		$existing = get_post_meta( $source_post_id, self::META_KEY_EMBEDDED_IN, true );
		$existing = is_array( $existing ) ? array_values( array_filter( array_map( 'absint', $existing ) ) ) : array();

		if ( in_array( $custom_post_id, $existing, true ) ) {
			return;
		}

		$existing[] = $custom_post_id;
		update_post_meta( $source_post_id, self::META_KEY_EMBEDDED_IN, $existing );
	}

	/**
	 * Remove reverse relation when an embed is removed.
	 *
	 * @param int $source_post_id Source post ID.
	 * @param int $custom_post_id Embedding custom-post ID.
	 * @return void
	 */
	private function remove_embedding_reference( int $source_post_id, int $custom_post_id ): void {
		$existing = get_post_meta( $source_post_id, self::META_KEY_EMBEDDED_IN, true );
		$existing = is_array( $existing ) ? array_values( array_filter( array_map( 'absint', $existing ) ) ) : array();
		$updated  = array_values( array_diff( $existing, array( $custom_post_id ) ) );

		if ( empty( $updated ) ) {
			delete_post_meta( $source_post_id, self::META_KEY_EMBEDDED_IN );
			return;
		}

		update_post_meta( $source_post_id, self::META_KEY_EMBEDDED_IN, $updated );
	}

	/**
	 * Sync changed embedded fields back to originals when sync is enabled.
	 *
	 * @param array<int, array<string, mixed>> $embedded_items Embedded data.
	 * @return void
	 */
	private function sync_embedded_items_to_original_posts( array $embedded_items ): void {
		$items_to_sync = array_values(
			array_filter(
				$embedded_items,
				static function ( array $item ): bool {
					return ! empty( $item['sync'] ) && absint( $item['post_id'] ?? 0 ) > 0;
				}
			)
		);

		if ( empty( $items_to_sync ) ) {
			return;
		}

		$target_posts = $this->get_target_post_map( $items_to_sync );

		foreach ( $items_to_sync as $item ) {
			$post_id = absint( $item['post_id'] ?? 0 );
			if ( $post_id < 1 ) {
				continue;
			}

			$target_post = $target_posts[ $post_id ] ?? null;
			if ( ! $target_post instanceof WP_Post ) {
				continue;
			}

			$this->sync_single_item( $item, $target_post );
		}
	}

	/**
	 * Resolve sync targets in one query.
	 *
	 * @param array<int, array<string, mixed>> $items_to_sync Sync-eligible items.
	 * @return array<int, WP_Post>
	 */
	private function get_target_post_map( array $items_to_sync ): array {
		$target_ids = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( array $item ): int {
							return absint( $item['post_id'] ?? 0 );
						},
						$items_to_sync
					)
				)
			)
		);

		if ( empty( $target_ids ) ) {
			return array();
		}

		$query = new WP_Query(
			array(
				'post_type'              => 'any',
				'post__in'               => $target_ids,
				'post_status'            => get_post_stati( array(), 'names' ),
				'posts_per_page'         => count( $target_ids ),
				'no_found_rows'          => true,
				'orderby'                => 'post__in',
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		$post_map = array();
		foreach ( $query->posts as $post ) {
			if ( $post instanceof WP_Post ) {
				$post_map[ (int) $post->ID ] = $post;
			}
		}

		return $post_map;
	}

	/**
	 * Sync one embedded entry to the original post.
	 *
	 * @param array<string, mixed> $item        Embedded post data.
	 * @param WP_Post              $target_post Target source post.
	 * @return void
	 */
	private function sync_single_item( array $item, WP_Post $target_post ): void {
		$post_id = (int) $target_post->ID;

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$update        = array( 'ID' => $post_id );
		$should_update = false;

		$new_title = sanitize_text_field( (string) ( $item['title'] ?? '' ) );
		if ( $new_title !== (string) $target_post->post_title ) {
			$update['post_title'] = $new_title;
			$should_update        = true;
		}

		$new_excerpt = wp_kses_post( (string) ( $item['excerpt'] ?? '' ) );
		if ( $new_excerpt !== (string) $target_post->post_excerpt ) {
			$update['post_excerpt'] = $new_excerpt;
			$should_update          = true;
		}

		$new_date_raw = sanitize_text_field( (string) ( $item['date'] ?? '' ) );
		$timestamp    = strtotime( $new_date_raw );

		if ( false !== $timestamp ) {
			$new_local = wp_date( 'Y-m-d H:i:s', $timestamp );
			$new_gmt   = gmdate( 'Y-m-d H:i:s', $timestamp );

			if ( $new_local !== $target_post->post_date || $new_gmt !== $target_post->post_date_gmt ) {
				$update['post_date']     = $new_local;
				$update['post_date_gmt'] = $new_gmt;
				$update['edit_date']     = true;
				$should_update           = true;
			}
		}

		if ( $should_update ) {
			$result = wp_update_post( wp_slash( $update ), true );

			if ( is_wp_error( $result ) ) {
				update_post_meta( $post_id, self::META_KEY_LAST_SYNC_ERROR, $result->get_error_message() );
			} else {
				delete_post_meta( $post_id, self::META_KEY_LAST_SYNC_ERROR );
			}
		}

		$this->sync_featured_image( $post_id, absint( $item['thumbnail_id'] ?? 0 ) );
	}

	/**
	 * Sync featured image only when value changed.
	 *
	 * @param int $post_id      Target post ID.
	 * @param int $thumbnail_id New thumbnail ID.
	 * @return void
	 */
	private function sync_featured_image( int $post_id, int $thumbnail_id ): void {
		$current_thumbnail_id = (int) get_post_thumbnail_id( $post_id );

		if ( $current_thumbnail_id === $thumbnail_id ) {
			return;
		}

		if ( $thumbnail_id > 0 && 'attachment' === get_post_type( $thumbnail_id ) ) {
			set_post_thumbnail( $post_id, $thumbnail_id );
			return;
		}

		if ( 0 === $thumbnail_id && $current_thumbnail_id > 0 ) {
			delete_post_thumbnail( $post_id );
		}
	}
}
