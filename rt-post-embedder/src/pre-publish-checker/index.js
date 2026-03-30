/**
 * Pre-publish guard plugin for Custom Posts.
 *
 * @package rt-post-embedder
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { PluginPrePublishPanel } from '@wordpress/edit-post';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
import { store as noticesStore } from '@wordpress/notices';
import { useEffect } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';

const config = window.rtPePrePublishConfig || {};
const REQUIRED_BLOCK_NAME =
	config.blockName || 'rt-post-embedder/post-embedder';
const SAVING_LOCK_KEY = config.lockKey || 'rt-pe-required-block-lock';
const REQUIRED_POST_TYPE = 'custom-post';
const NOTICE_ID = 'rt-pe-block-required-notice';

/**
 * Check if block attributes include at least one embedded post.
 *
 * @param {Object} attributes Block attributes.
 * @returns {boolean} Whether embeddedPosts has items.
 */
function hasEmbeddedPosts( attributes ) {
	if ( ! attributes || ! Array.isArray( attributes.embeddedPosts ) ) {
		return false;
	}

	return attributes.embeddedPosts.length > 0;
}

/**
 * Recursively inspect block tree for a valid embedder block.
 *
 * @param {Array} blocks    Block list.
 * @param {string} blockName Required block name.
 * @returns {boolean} Whether valid block exists.
 */
function treeHasRequiredBlock( blocks, blockName ) {
	if ( ! Array.isArray( blocks ) ) {
		return false;
	}

	for ( let index = 0; index < blocks.length; index += 1 ) {
		const block = blocks[ index ];

		if (
			blockName === block.name &&
			hasEmbeddedPosts( block.attributes )
		) {
			return true;
		}

		if ( treeHasRequiredBlock( block.innerBlocks, blockName ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Pre-publish guard component.
 *
 * @returns {JSX.Element|null} Panel or null.
 */
function PrePublishGuard() {
	const postType = useSelect( function selectPostType( select ) {
		return select( editorStore ).getCurrentPostType();
	}, [] );

	const blocks = useSelect( function selectBlocks( select ) {
		return select( blockEditorStore ).getBlocks();
	}, [] );

	const editorDispatch = useDispatch( editorStore );
	const noticesDispatch = useDispatch( noticesStore );
	const hasRequiredBlock = treeHasRequiredBlock(
		blocks,
		REQUIRED_BLOCK_NAME
	);

	/**
	 * Sync lock and notice state with block presence.
	 *
	 * @returns {Function|undefined} Cleanup callback when needed.
	 */
	useEffect(
		function syncGuardState() {
			if ( REQUIRED_POST_TYPE !== postType ) {
				return undefined;
			}

			if ( hasRequiredBlock ) {
				editorDispatch.unlockPostSaving( SAVING_LOCK_KEY );
				noticesDispatch.removeNotice( NOTICE_ID );
			} else {
				editorDispatch.lockPostSaving( SAVING_LOCK_KEY );
				noticesDispatch.createWarningNotice(
					__(
						'This Custom Post must include the Post Embedder block with at least one embedded post before publishing.',
						'rt-post-embedder'
					),
					{
						id: NOTICE_ID,
						isDismissible: false,
					}
				);
			}

			return function cleanup() {
				editorDispatch.unlockPostSaving( SAVING_LOCK_KEY );
				noticesDispatch.removeNotice( NOTICE_ID );
			};
		},
		[ hasRequiredBlock, postType, editorDispatch, noticesDispatch ]
	);

	if ( REQUIRED_POST_TYPE !== postType || hasRequiredBlock ) {
		return null;
	}

	return (
		<PluginPrePublishPanel
			name="rt-pe-pre-publish-panel"
			title={ __( 'Post Embedder Required', 'rt-post-embedder' ) }
			initialOpen={ true }
		>
			<Notice status="warning" isDismissible={ false }>
				{ __(
					'You must add the Post Embedder block and embed at least one post before publishing.',
					'rt-post-embedder'
				) }
			</Notice>
		</PluginPrePublishPanel>
	);
}

registerPlugin( 'rt-pe-pre-publish-checker', {
	render: PrePublishGuard,
} );
