/**
 * Person block editor component.
 *
 * Fetches the selected person via the custom REST API and renders an inline
 * card preview that mirrors the front-end markup.
 *
 * @package
 */

import apiFetch from '@wordpress/api-fetch';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	ComboboxControl,
	PanelBody,
	Placeholder,
	Spinner,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';

/**
 * Parse combobox value to safe integer.
 *
 * @param {string|number|null} value Raw value.
 * @return {number} Parsed value.
 */
function parseComboboxValue( value ) {
	const parsed = Number.parseInt( String( value ?? 0 ), 10 );
	return Number.isNaN( parsed ) ? 0 : parsed;
}

/**
 * Resolve career term IDs into names.
 *
 * @param {Array} termIds Career term IDs.
 * @return {Promise<string[]>} Career names.
 */
async function resolveCareerNames( termIds ) {
	if ( ! Array.isArray( termIds ) || 0 === termIds.length ) {
		return [];
	}

	try {
		const terms = await apiFetch( {
			path: `/wp/v2/rt-person-career?include=${ termIds.join( ',' ) }&_fields=id,name`,
		} );

		if ( ! Array.isArray( terms ) ) {
			return [];
		}

		return terms.map( ( t ) => t.name ).filter( Boolean );
	} catch {
		return [];
	}
}

/**
 * Strip HTML tags from a string.
 *
 * @param {string} html HTML string.
 * @return {string} Plain text.
 */
function stripTags( html ) {
	if ( 'string' !== typeof html ) {
		return '';
	}

	const div = document.createElement( 'div' );
	div.innerHTML = html;
	return div.textContent || div.innerText || '';
}

/**
 * Resolve a featured_media attachment ID into a source URL.
 *
 * @param {number} mediaId Attachment post ID.
 * @return {Promise<string>} Image URL or empty string.
 */
async function resolveMediaUrl( mediaId ) {
	if ( ! mediaId || mediaId <= 0 ) {
		return '';
	}

	try {
		const media = await apiFetch( {
			path: `/wp/v2/media/${ mediaId }?_fields=source_url`,
		} );

		return media?.source_url || '';
	} catch {
		return '';
	}
}

/**
 * Render a single person card matching the front-end markup.
 *
 * @param {Object}   props          Component props.
 * @param {Object}   props.person   REST API person object.
 * @param {string[]} props.careers  Resolved career names.
 * @param {string}   props.imageUrl Resolved featured image URL.
 * @return {JSX.Element} Person card element.
 */
function PersonCard( { person, careers, imageUrl } ) {
	const birthDate =
		person.meta?.[ 'rt-person-meta-basic-birth-date' ] || '';

	const rawExcerpt = person.excerpt || person.content || '';
	const plainExcerpt = stripTags( rawExcerpt ).trim();
	const bio =
		plainExcerpt.length > 120
			? plainExcerpt.substring( 0, 120 ) + '…'
			: plainExcerpt;
	const careerLabel = careers.length > 0 ? careers[ 0 ] : '';

	return (
		<article className="person-card">
			{ imageUrl ? (
				<img
					className="person-card__image"
					src={ imageUrl }
					alt={ person.title || '' }
				/>
			) : (
				<div
					className="person-card__image"
					style={ { background: '#444' } }
				/>
			) }
			<div className="person-card__content">
				<h3 className="person-card__name">
					{ person.title }
					{ careerLabel && (
						<span className="person-card__role">
							{ ' ' }({ careerLabel })
						</span>
					) }
				</h3>
				{ birthDate && (
					<p className="person-card__dob">
						{ __( 'Born -', 'rt-movie-library' ) } { birthDate }
					</p>
				) }
				{ bio && <p className="person-card__excerpt">{ bio }</p> }
				<span className="person-card__link">
					{ __( 'Learn more →', 'rt-movie-library' ) }
				</span>
			</div>
		</article>
	);
}

/**
 * Edit component for Person block.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attributes setter.
 * @return {JSX.Element} Editor UI.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { personId } = attributes;
	const blockProps = useBlockProps();
	const [ personOptions, setPersonOptions ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ personData, setPersonData ] = useState( null );
	const [ careerNames, setCareerNames ] = useState( [] );
	const [ personImageUrl, setPersonImageUrl ] = useState( '' );
	const [ isPersonLoading, setIsPersonLoading ] = useState( false );
	const debounceTimeoutRef = useRef( null );

	/**
	 * Clear pending search timer.
	 *
	 * @return {void}
	 */
	const clearPendingTimer = useCallback( () => {
		if ( null !== debounceTimeoutRef.current ) {
			clearTimeout( debounceTimeoutRef.current );
			debounceTimeoutRef.current = null;
		}
	}, [] );

	/**
	 * Fetch person options for search term.
	 *
	 * @param {string} searchTerm Search term.
	 * @return {void}
	 */
	const fetchPersonOptions = useCallback( ( searchTerm ) => {
		setIsLoading( true );

		/**
		 * Map person payload into combobox option.
		 *
		 * @param {Object} person Person payload.
		 * @return {{value:string,label:string}} Combobox option.
		 */
		function mapPersonToOption( person ) {
			let label = '';

			if ( 'string' === typeof person.name && '' !== person.name ) {
				label = person.name;
			} else if ( 'string' === typeof person.title ) {
				label = person.title;
			}

			return {
				value: String( person.id ),
				label,
			};
		}

		/**
		 * Handle successful persons response.
		 *
		 * @param {Array|Object} response API response.
		 * @return {void}
		 */
		function handleResponse( response ) {
			if ( ! Array.isArray( response ) ) {
				setPersonOptions( [] );
				return;
			}

			setPersonOptions(
				response
					.map( mapPersonToOption )
					.filter( ( option ) => '' !== option.label )
			);
		}

		/**
		 * Handle fetch failure.
		 *
		 * @return {void}
		 */
		function handleError() {
			setPersonOptions( [] );
		}

		/**
		 * End loading state.
		 *
		 * @return {void}
		 */
		function handleFinally() {
			setIsLoading( false );
		}

		apiFetch( {
			path: `/rt-movie-library/v1/block-data/persons?search=${ encodeURIComponent(
				searchTerm
			) }`,
		} )
			.then( handleResponse )
			.catch( handleError )
			.finally( handleFinally );
	}, [] );

	/**
	 * Debounced handler for combobox input.
	 *
	 * @param {string} inputValue Input value.
	 * @return {void}
	 */
	const handleFilterValueChange = useCallback(
		( inputValue ) => {
			/**
			 * Trigger delayed search.
			 *
			 * @return {void}
			 */
			function runDelayedSearch() {
				fetchPersonOptions( inputValue );
			}

			clearPendingTimer();
			debounceTimeoutRef.current = setTimeout( runDelayedSearch, 300 );
		},
		[ clearPendingTimer, fetchPersonOptions ]
	);

	/**
	 * Preload options on mount.
	 *
	 * @return {void}
	 */
	useEffect( () => {
		fetchPersonOptions( '' );
	}, [ fetchPersonOptions ] );

	/**
	 * Fetch full person data whenever personId changes.
	 */
	useEffect( () => {
		if ( personId <= 0 ) {
			setPersonData( null );
			setCareerNames( [] );
			setPersonImageUrl( '' );
			return;
		}

		setIsPersonLoading( true );

		apiFetch( {
			path: `/rt-movie-library/v1/persons/${ personId }`,
		} )
			.then( ( response ) => {
				setPersonData( response );

				const careerIds =
					response.taxonomies?.[ 'rt-person-career' ] || [];

				return Promise.all( [
					resolveCareerNames( careerIds ),
					resolveMediaUrl( response.featured_media ),
				] );
			} )
			.then( ( results ) => {
				setCareerNames( results[ 0 ] || [] );
				setPersonImageUrl( results[ 1 ] || '' );
			} )
			.catch( () => {
				setPersonData( null );
				setCareerNames( [] );
				setPersonImageUrl( '' );
			} )
			.finally( () => {
				setIsPersonLoading( false );
			} );
	}, [ personId ] );

	/**
	 * Handle person selection change.
	 *
	 * @param {string|number|null} value Selected option.
	 * @return {void}
	 */
	const handlePersonSelectionChange = useCallback(
		( value ) => {
			setAttributes( { personId: parseComboboxValue( value ) } );
		},
		[ setAttributes ]
	);

	/**
	 * Render the main block content.
	 *
	 * @return {JSX.Element} Block content.
	 */
	function renderContent() {
		if ( isPersonLoading ) {
			return (
				<Placeholder
					icon="id-alt"
					label={ __( 'Person', 'rt-movie-library' ) }
				>
					<Spinner />
				</Placeholder>
			);
		}

		if ( personId > 0 && personData ) {
			return (
				<div className="page-archive-person rt-block-archive-bridge">
					<section className="movie-section">
						<div className="container">
							<div className="person-list">
								<PersonCard
									person={ personData }
									careers={ careerNames }								imageUrl={ personImageUrl }								/>
							</div>
						</div>
					</section>
				</div>
			);
		}

		return (
			<Placeholder
				icon="id-alt"
				label={ __( 'Person', 'rt-movie-library' ) }
				instructions={ __(
					'Select a person from the sidebar to preview.',
					'rt-movie-library'
				) }
			/>
		);
	}

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Person Selection', 'rt-movie-library' ) }
					initialOpen={ true }
				>
					<ComboboxControl
						label={ __( 'Select Person', 'rt-movie-library' ) }
						value={ personId > 0 ? String( personId ) : '' }
						options={ personOptions }
						onChange={ handlePersonSelectionChange }
						onFilterValueChange={ handleFilterValueChange }
						help={ __(
							'Type to search by person name.',
							'rt-movie-library'
						) }
					/>
					{ isLoading && <Spinner /> }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>{ renderContent() }</div>
		</>
	);
}
