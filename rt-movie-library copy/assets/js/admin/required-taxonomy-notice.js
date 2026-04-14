/**
 * Display Gutenberg error notice when required taxonomy blocks publishing.
 *
 * Reads runtime config from `window.rtRequiredTaxonomyNoticeConfig` and checks
 * a custom REST field after each completed manual save.
 *
 * @package
 * @since   1.0.0
 */

( function() {
	'use strict';

	const wpEditor = window.wp;

	if ( ! wpEditor || ! wpEditor.data || ! wpEditor.apiFetch ) {
		return;
	}

	const config = window.rtRequiredTaxonomyNoticeConfig;

	if (
		! config ||
		! config.postType ||
		! config.restBase ||
		! config.noticeField
	) {
		return;
	}

	const { subscribe, select, dispatch } = wpEditor.data;
	const { addQueryArgs } = wpEditor.url;
	const { __ } = wpEditor.i18n;

	let wasSaving = false;
	let isCheckingState = false;

	/**
	 * Build fallback message when localized message is not provided.
	 *
	 * @return {string} Fallback translated error message.
	 */
	function getFallbackMessage() {
		return __(
			'This item could not be published because required taxonomy is missing.',
			'rt-movie-library'
		);
	}

	/**
	 * Store subscription callback — fires on every editor state change.
	 * Checks whether a manual save just completed, then queries the REST
	 * API to see if the server flagged a missing required taxonomy.
	 */
	subscribe( function() {
		const editor = select( 'core/editor' );

		if ( ! editor ) {
			return;
		}

		const isSaving = editor.isSavingPost();
		const isAutosaving = editor.isAutosavingPost();
		const didFinishSave = wasSaving && ! isSaving && ! isAutosaving;

		wasSaving = isSaving;

		if ( ! didFinishSave || isCheckingState ) {
			return;
		}

		if ( config.postType !== editor.getCurrentPostType() ) {
			return;
		}

		const postId = editor.getCurrentPostId();

		if ( ! postId ) {
			return;
		}

		isCheckingState = true;

		const path = addQueryArgs( `/wp/v2/${ config.restBase }/${ postId }`, {
			context: 'edit',
			_fields: config.noticeField,
			rt_notice_check: '1',
		} );

		const releaseLock = function() {
			isCheckingState = false;
		};

		wpEditor
			.apiFetch( { path } )
			.then( function( post ) {
				if ( ! post || true !== post[ config.noticeField ] ) {
					return;
				}

				dispatch( 'core/notices' ).createErrorNotice(
					config.noticeMessage || getFallbackMessage(),
					{
						id: config.noticeId || 'rt-required-taxonomy-notice',
						isDismissible: true,
					}
				);
			} )
			.catch( function( error ) {
				// Log to console so developers can diagnose notice-fetch failures
				// without needing to add breakpoints; does not block editor UX.
				// eslint-disable-next-line no-console
				console.error(
					'[RT Movie Library] Failed to check required taxonomy notice:',
					error
				);
			} )
			.then( releaseLock );
	} );
}() );
