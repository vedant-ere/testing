/**
 * Embedded post editor card.
 *
 * @package rt-post-embedder
 */
import { __ } from '@wordpress/i18n';
import {
	RichText,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import {
	Button,
	DateTimePicker,
	Popover,
	ToggleControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';

/**
 * EmbeddedPost component.
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.post     Embedded post payload.
 * @param {Function} props.onChange Change callback.
 * @param {Function} props.onRemove Remove callback.
 * @returns {JSX.Element} Post card.
 */
export default function EmbeddedPost( { post, onChange, onRemove } ) {
	const [ isDatePickerOpen, setIsDatePickerOpen ] = useState( false );

	/**
	 * Update one post field.
	 *
	 * @param {string} field Field key.
	 * @param {*}      value New value.
	 * @returns {void}
	 */
	function updateField( field, value ) {
		onChange( { ...post, [ field ]: value } );
	}

	/**
	 * Handle title changes.
	 *
	 * @param {string} value Updated title.
	 * @returns {void}
	 */
	function handleTitleChange( value ) {
		updateField( 'title', value );
	}

	/**
	 * Handle excerpt changes.
	 *
	 * @param {string} value Updated excerpt.
	 * @returns {void}
	 */
	function handleExcerptChange( value ) {
		updateField( 'excerpt', value );
	}

	/**
	 * Handle media selection.
	 *
	 * @param {Object} media Selected media object.
	 * @returns {void}
	 */
	function handleImageSelect( media ) {
		updateField( 'thumbnailId', media?.id || 0 );
		updateField( 'thumbnailUrl', media?.url || '' );
	}

	/**
	 * Toggle sync mode.
	 *
	 * @param {boolean} value Toggle state.
	 * @returns {void}
	 */
	function handleSyncToggle( value ) {
		updateField( 'syncChanges', value );
	}

	/**
	 * Open date picker.
	 *
	 * @returns {void}
	 */
	function openDatePicker() {
		setIsDatePickerOpen( true );
	}

	/**
	 * Close date picker.
	 *
	 * @returns {void}
	 */
	function closeDatePicker() {
		setIsDatePickerOpen( false );
	}

	/**
	 * Handle date updates.
	 *
	 * @param {string} value Selected date.
	 * @returns {void}
	 */
	function handleDateChange( value ) {
		updateField( 'date', value );
		closeDatePicker();
	}

	/**
	 * Trigger remove callback.
	 *
	 * @returns {void}
	 */
	function handleRemoveClick() {
		onRemove();
	}

	const layoutClassName = post.imageLeft
		? 'rt-pe-embedded-post rt-pe-embedded-post--image-left'
		: 'rt-pe-embedded-post rt-pe-embedded-post--image-right';

	return (
		<div className={ layoutClassName }>
			<div className="rt-pe-embedded-post__image">
				<MediaUploadCheck>
					<MediaUpload
						allowedTypes={ [ 'image' ] }
						value={ post.thumbnailId }
						onSelect={ handleImageSelect }
						render={ function renderUploader( { open } ) {
							return (
								<>
									{ post.thumbnailUrl ? (
										<img
											src={ post.thumbnailUrl }
											alt={
												post.title ||
												__(
													'Embedded post image',
													'rt-post-embedder'
												)
											}
											className="rt-pe-embedded-post__image-tag"
										/>
									) : (
										<div className="rt-pe-embedded-post__image-placeholder">
											{ __(
												'No image selected',
												'rt-post-embedder'
											) }
										</div>
									) }
									<Button
										variant="secondary"
										onClick={ open }
									>
										{ post.thumbnailUrl
											? __(
													'Replace image',
													'rt-post-embedder'
											  )
											: __(
													'Add image',
													'rt-post-embedder'
											  ) }
									</Button>
								</>
							);
						} }
					/>
				</MediaUploadCheck>
			</div>

			<div className="rt-pe-embedded-post__content">
				<RichText
					tagName="h3"
					className="rt-pe-embedded-post__title"
					value={ post.title }
					onChange={ handleTitleChange }
					placeholder={ __( 'Post title…', 'rt-post-embedder' ) }
				/>

				{ post.showDate && (
					<div className="rt-pe-embedded-post__date-wrap">
						<Button variant="link" onClick={ openDatePicker }>
							{ post.date ||
								__( 'Set date', 'rt-post-embedder' ) }
						</Button>
						{ isDatePickerOpen && (
							<Popover onClose={ closeDatePicker }>
								<DateTimePicker
									currentDate={ post.date || null }
									onChange={ handleDateChange }
								/>
							</Popover>
						) }
					</div>
				) }

				{ post.showExcerpt && (
					<RichText
						tagName="p"
						className="rt-pe-embedded-post__excerpt"
						value={ post.excerpt }
						onChange={ handleExcerptChange }
						placeholder={ __(
							'Post excerpt…',
							'rt-post-embedder'
						) }
					/>
				) }

				<div className="rt-pe-embedded-post__actions">
					<ToggleControl
						label={ __(
							'Sync changes with original content?',
							'rt-post-embedder'
						) }
						checked={ !! post.syncChanges }
						onChange={ handleSyncToggle }
						help={
							post.syncChanges
								? __(
										'Changes sync to the source post on save.',
										'rt-post-embedder'
								  )
								: __(
										'Changes stay local to this embed.',
										'rt-post-embedder'
								  )
						}
					/>

					<Button
						isDestructive
						variant="link"
						onClick={ handleRemoveClick }
					>
						{ __( 'Remove embedded post', 'rt-post-embedder' ) }
					</Button>
				</div>
			</div>
		</div>
	);
}
