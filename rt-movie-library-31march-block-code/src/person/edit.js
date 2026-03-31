/**
 * Person block editor component.
 *
 * @package RT_Movie_Library
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
 * @returns {number} Parsed value.
 */
function parseComboboxValue( value ) {
	const parsed = Number.parseInt( String( value ?? 0 ), 10 );
	return Number.isNaN( parsed ) ? 0 : parsed;
}

/**
 * Edit component for Person block.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attributes setter.
 * @returns {JSX.Element} Editor UI.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { personId } = attributes;
	const blockProps = useBlockProps();
	const [ personOptions, setPersonOptions ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( false );
	const debounceTimeoutRef = useRef( null );

	/**
	 * Clear pending search timer.
	 *
	 * @returns {void}
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
	 * @returns {void}
	 */
	const fetchPersonOptions = useCallback( ( searchTerm ) => {
		setIsLoading( true );

		/**
		 * Map person payload into combobox option.
		 *
		 * @param {Object} person Person payload.
		 * @returns {{value:string,label:string}} Combobox option.
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
		 * @returns {void}
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
		 * @param {Error|Object} error Error object.
		 * @returns {void}
		 */
		function handleError( error ) {
			// eslint-disable-next-line no-console
			console.error( '[RT Movie Library] Person search failed.', error );
			setPersonOptions( [] );
		}

		/**
		 * End loading state.
		 *
		 * @returns {void}
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
	 * @returns {void}
	 */
	const handleFilterValueChange = useCallback(
		( inputValue ) => {
			/**
			 * Trigger delayed search.
			 *
			 * @returns {void}
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
	 * Preload options once so selection works without requiring exact typing.
	 *
	 * @returns {void}
	 */
	useEffect( () => {
		fetchPersonOptions( '' );
	}, [ fetchPersonOptions ] );

	/**
	 * Handle person selection change.
	 *
	 * @param {string|number|null} value Selected option.
	 * @returns {void}
	 */
	const handlePersonSelectionChange = useCallback(
		( value ) => {
			setAttributes( { personId: parseComboboxValue( value ) } );
		},
		[ setAttributes ]
	);

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

			<div { ...blockProps }>
				<Placeholder
					icon="id-alt"
					label={ __( 'Person', 'rt-movie-library' ) }
					instructions={ __(
						'Preview is disabled in editor. View the published page to see rendered output.',
						'rt-movie-library'
					) }
				>
					<p>
						{ personId > 0
							? __( 'Person selected.', 'rt-movie-library' )
							: __(
									'No person selected yet.',
									'rt-movie-library'
							  ) }
					</p>
				</Placeholder>
			</div>
		</>
	);
}
