<?php
/**
 * Publishing guard for Custom Posts.
 *
 * @package RT_Post_Embedder
 */

namespace RT_Post_Embedder;

use RT_Post_Embedder\Traits\Singleton;

defined( 'ABSPATH' ) || exit;

/**
 * Class Pre_Publish_Checker
 */
class Pre_Publish_Checker {

	use Singleton;

	/**
	 * Required block name.
	 *
	 * @var string
	 */
	private const BLOCK_NAME = 'rt-post-embedder/post-embedder';

	/**
	 * Register hooks.
	 */
	protected function __construct() {
		add_filter( 'wp_insert_post_data', array( $this, 'enforce_required_block_on_insert' ), 10, 2 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_script' ) );
	}

	/**
	 * Enforce block presence on publish attempts.
	 *
	 * The insert-data filter runs before persistence and is shared by REST and
	 * classic save flows, so this is the most reliable server-side stopgap.
	 *
	 * @param array<string, mixed> $data    Sanitized post data.
	 * @param array<string, mixed> $postarr Raw post payload.
	 * @return array<string, mixed>
	 */
	public function enforce_required_block_on_insert( array $data, array $postarr ): array {
		$post_type = sanitize_key( (string) ( $data['post_type'] ?? '' ) );

		if ( Custom_Posts_Cpt::POST_TYPE !== $post_type ) {
			return $data;
		}

		$post_status = sanitize_key( (string) ( $data['post_status'] ?? '' ) );
		if ( ! in_array( $post_status, $this->get_publish_attempt_statuses(), true ) ) {
			return $data;
		}

		$post_content = (string) ( $data['post_content'] ?? '' );
		if ( $this->has_required_block_with_embedded_posts( $post_content ) ) {
			return $data;
		}

		$data['post_status'] = $this->get_draft_status();

		$post_id = absint( $postarr['ID'] ?? 0 );
		if ( $post_id > 0 ) {
			update_user_meta( get_current_user_id(), '_rt_pe_block_missing_' . $post_id, '1' );
		}

		return $data;
	}

	/**
	 * Enqueue editor script that shows notices and locks publish.
	 *
	 * @return void
	 */
	public function enqueue_editor_script(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen instanceof \WP_Screen || Custom_Posts_Cpt::POST_TYPE !== $screen->post_type ) {
			return;
		}

		$asset_file = RT_POST_EMBEDDER_PATH . 'build/pre-publish-checker/index.asset.php';
		$script_url = RT_POST_EMBEDDER_URL . 'build/pre-publish-checker/index.js';

		if ( ! file_exists( $asset_file ) || ! file_exists( RT_POST_EMBEDDER_PATH . 'build/pre-publish-checker/index.js' ) ) {
			return;
		}

		// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable -- Asset metadata path is controlled by plugin build output.
		$asset = require $asset_file;

		wp_enqueue_script(
			'rt-pe-pre-publish-checker',
			$script_url,
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_set_script_translations(
			'rt-pe-pre-publish-checker',
			'rt-post-embedder',
			RT_POST_EMBEDDER_PATH . 'languages'
		);

		wp_add_inline_script(
			'rt-pe-pre-publish-checker',
			'window.rtPePrePublishConfig = ' . wp_json_encode(
				array(
					'blockName' => self::BLOCK_NAME,
					'lockKey'   => 'rt-pe-required-block-lock',
				)
			) . ';',
			'before'
		);
	}

	/**
	 * Decide which statuses represent publish/update attempts.
	 *
	 * This avoids hardcoding all statuses and adapts to custom status plugins
	 * while still exempting draft-like states.
	 *
	 * @return string[]
	 */
	private function get_publish_attempt_statuses(): array {
		$status_objects = get_post_stati( array(), 'objects' );
		$statuses       = array();

		foreach ( $status_objects as $status_slug => $status_object ) {
			if ( ! is_object( $status_object ) ) {
				continue;
			}

			if ( ! empty( $status_object->internal ) ) {
				continue;
			}

			$is_publicish = ! empty( $status_object->public ) || ! empty( $status_object->private ) || ! empty( $status_object->protected );
			$is_pending   = ! empty( $status_object->show_in_admin_status_list ) && ! empty( $status_object->show_in_admin_all_list ) && empty( $status_object->date_floating );

			if ( $is_publicish || $is_pending ) {
				$statuses[] = (string) $status_slug;
			}
		}

		return array_values( array_unique( $statuses ) );
	}

	/**
	 * Resolve the draft-like status for fallback.
	 *
	 * @return string
	 */
	private function get_draft_status(): string {
		$status_objects = get_post_stati( array(), 'objects' );

		foreach ( $status_objects as $status_slug => $status_object ) {
			if ( ! is_object( $status_object ) ) {
				continue;
			}

			$is_draft_like = empty( $status_object->public )
				&& empty( $status_object->private )
				&& empty( $status_object->protected )
				&& empty( $status_object->internal )
				&& ! empty( $status_object->date_floating );

			if ( $is_draft_like ) {
				return (string) $status_slug;
			}
		}

		return 'draft';
	}

	/**
	 * Check whether content includes our block with at least one embedded post.
	 *
	 * @param string $content Serialized block content.
	 * @return bool
	 */
	private function has_required_block_with_embedded_posts( string $content ): bool {
		if ( '' === $content ) {
			return false;
		}

		$blocks = parse_blocks( $content );
		return $this->blocks_include_embedder_with_posts( $blocks );
	}

	/**
	 * Recursively inspect block trees.
	 *
	 * @param array<int, array<string, mixed>> $blocks Parsed blocks.
	 * @return bool
	 */
	private function blocks_include_embedder_with_posts( array $blocks ): bool {
		foreach ( $blocks as $block ) {
			$block_name = (string) ( $block['blockName'] ?? '' );

			if ( self::BLOCK_NAME === $block_name ) {
				$embedded_posts = $block['attrs']['embeddedPosts'] ?? array();
				if ( is_array( $embedded_posts ) && ! empty( $embedded_posts ) ) {
					return true;
				}
			}

			$inner_blocks = $block['innerBlocks'] ?? array();
			if ( is_array( $inner_blocks ) && ! empty( $inner_blocks ) ) {
				if ( $this->blocks_include_embedder_with_posts( $inner_blocks ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
