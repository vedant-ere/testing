/**
 * Edit component for Post Embedder block.
 *
 * @package rt-post-embedder
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Notice, PanelBody, ToggleControl } from '@wordpress/components';

import EmbeddedPost from './components/EmbeddedPost';
import PostSearch from './components/PostSearch';

/**
 * Build a new embedded-post payload from REST result data.
 *
 * @param {Object} post Search result object.
 * @returns {Object} Block attribute item.
 */
function buildEmbeddedPostPayload( post ) {
	return {
		postId: post.id,
		title: post.title || '',
		excerpt: post.excerpt || '',
		date: post.date || '',
		thumbnailId: post.thumbnail_id || 0,
		thumbnailUrl: post.thumbnail_url || '',
		imageLeft: true,
		showExcerpt: true,
		showDate: true,
		syncChanges: false,
	};
}

/**
 * Edit component.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @returns {JSX.Element} Editor UI.
 */
export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'rt-pe-block' } );
	const embeddedPosts = Array.isArray( attributes.embeddedPosts )
		? attributes.embeddedPosts
		: [];

	/**
	 * Persist embeddedPosts array.
	 *
	 * @param {Array} nextItems Updated list.
	 * @returns {void}
	 */
	function updateEmbeddedPosts( nextItems ) {
		setAttributes( { embeddedPosts: nextItems } );
	}

	/**
	 * Add a newly selected post to block state.
	 *
	 * @param {Object} selectedPost Selected search result.
	 * @returns {void}
	 */
	function handleSelectPost( selectedPost ) {
		const alreadyEmbedded = embeddedPosts.some(
			function hasSamePostId( item ) {
				return item.postId === selectedPost.id;
			}
		);

		if ( alreadyEmbedded ) {
			return;
		}

		updateEmbeddedPosts( [
			...embeddedPosts,
			buildEmbeddedPostPayload( selectedPost ),
		] );
	}

	/**
	 * Update one embedded post by ID.
	 *
	 * @param {number} postId      Target post ID.
	 * @param {Object} updatedPost Updated post payload.
	 * @returns {void}
	 */
	function handleUpdatePost( postId, updatedPost ) {
		const nextItems = embeddedPosts.map( function mapPost( item ) {
			if ( item.postId !== postId ) {
				return item;
			}

			return updatedPost;
		} );

		updateEmbeddedPosts( nextItems );
	}

	/**
	 * Remove one embedded post by ID.
	 *
	 * @param {number} postId Target post ID.
	 * @returns {void}
	 */
	function handleRemovePost( postId ) {
		const nextItems = embeddedPosts.filter( function keepOthers( item ) {
			return item.postId !== postId;
		} );

		updateEmbeddedPosts( nextItems );
	}

	/**
	 * Update one display setting for an embedded post.
	 *
	 * @param {number} postId Target post ID.
	 * @param {string} key    Field name.
	 * @param {*}      value  New value.
	 * @returns {void}
	 */
	function updatePostSetting( postId, key, value ) {
		const nextItems = embeddedPosts.map( function mapPost( item ) {
			if ( item.postId !== postId ) {
				return item;
			}

			return { ...item, [ key ]: value };
		} );

		updateEmbeddedPosts( nextItems );
	}

	return (
		<>
			<InspectorControls>
				{ embeddedPosts.map( function renderPanel( post ) {
					return (
						<PanelBody
							key={ post.postId }
							title={
								post.title ||
								__( 'Embedded post', 'rt-post-embedder' )
							}
							initialOpen={ false }
						>
							<ToggleControl
								label={ __(
									'Image on left',
									'rt-post-embedder'
								) }
								checked={ !! post.imageLeft }
								onChange={ function onImageSideChange( value ) {
									updatePostSetting(
										post.postId,
										'imageLeft',
										value
									);
								} }
							/>
							<ToggleControl
								label={ __(
									'Show excerpt',
									'rt-post-embedder'
								) }
								checked={ !! post.showExcerpt }
								onChange={ function onShowExcerptChange(
									value
								) {
									updatePostSetting(
										post.postId,
										'showExcerpt',
										value
									);
								} }
							/>
							<ToggleControl
								label={ __(
									'Show publish date',
									'rt-post-embedder'
								) }
								checked={ !! post.showDate }
								onChange={ function onShowDateChange( value ) {
									updatePostSetting(
										post.postId,
										'showDate',
										value
									);
								} }
							/>
						</PanelBody>
					);
				} ) }
			</InspectorControls>

			<div { ...blockProps }>
				<div className="rt-pe-block__search">
					<PostSearch
						onSelect={ handleSelectPost }
						placeholder={ __(
							'Search by post title…',
							'rt-post-embedder'
						) }
					/>
				</div>

				{ 0 === embeddedPosts.length && (
					<Notice status="info" isDismissible={ false }>
						{ __(
							'Add at least one embedded post to publish this Custom Post.',
							'rt-post-embedder'
						) }
					</Notice>
				) }

				<div className="rt-pe-block__items">
					{ embeddedPosts.map( function renderEmbeddedPost( post ) {
						return (
							<EmbeddedPost
								key={ post.postId }
								post={ post }
								onChange={ function onPostChange(
									updatedPost
								) {
									handleUpdatePost(
										post.postId,
										updatedPost
									);
								} }
								onRemove={ function onPostRemove() {
									handleRemovePost( post.postId );
								} }
							/>
						);
					} ) }
				</div>
			</div>
		</>
	);
}
