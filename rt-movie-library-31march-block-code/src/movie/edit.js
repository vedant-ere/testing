/**
 * Single Movie block editor component.
 *
 * Using a combobox keeps selection scalable when movie volume grows, while the
 * endpoint intentionally returns concise records to keep typing responsive.
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
 * Parse combobox value into a safe integer attribute value.
 *
 * @param {string|number|null} value Combobox value.
 * @returns {number} Parsed integer or 0.
 */
function parseComboboxValue( value ) {
	const parsed = Number.parseInt( String( value ?? 0 ), 10 );
	return Number.isNaN( parsed ) ? 0 : parsed;
}

/**
 * Edit component for Movie block.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Current attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @returns {JSX.Element} Editor UI.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { movieId } = attributes;
	const blockProps = useBlockProps();
	const [ movieOptions, setMovieOptions ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( false );
	const debounceTimeoutRef = useRef( null );

	/**
	 * Clear pending debounced search timer.
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
	 * Query movie options for a search term.
	 *
	 * @param {string} searchTerm Typed search term.
	 * @returns {void}
	 */
	const fetchMovieOptions = useCallback( ( searchTerm ) => {
		setIsLoading( true );

		/**
		 * Map REST movie payload to combobox option shape.
		 *
		 * @param {Object} movie Movie payload entry.
		 * @returns {{value: string, label: string}} Combobox option.
		 */
		function mapMovieToOption( movie ) {
			let label = '';

			if ( 'string' === typeof movie.title && '' !== movie.title ) {
				label = movie.title;
			} else if ( 'string' === typeof movie.name ) {
				label = movie.name;
			}

			return {
				value: String( movie.id ),
				label,
			};
		}

		/**
		 * Handle successful movie search response.
		 *
		 * @param {Array|Object} response REST response payload.
		 * @returns {void}
		 */
		function handleResponse( response ) {
			if ( ! Array.isArray( response ) ) {
				setMovieOptions( [] );
				return;
			}

			setMovieOptions(
				response
					.map( mapMovieToOption )
					.filter( ( option ) => '' !== option.label )
			);
		}

		/**
		 * Handle movie search failures.
		 *
		 * @param {Error|Object} error Error object.
		 * @returns {void}
		 */
		function handleError( error ) {
			// eslint-disable-next-line no-console
			console.error( '[RT Movie Library] Movie search failed.', error );
			setMovieOptions( [] );
		}

		/**
		 * Finalize movie search loading state.
		 *
		 * @returns {void}
		 */
		function handleFinally() {
			setIsLoading( false );
		}

		apiFetch( {
			path: `/rt-movie-library/v1/block-data/movies?search=${ encodeURIComponent(
				searchTerm
			) }`,
		} )
			.then( handleResponse )
			.catch( handleError )
			.finally( handleFinally );
	}, [] );

	/**
	 * Handle combobox search input.
	 *
	 * Debouncing reduces REST chatter while users type quickly in the sidebar.
	 *
	 * @param {string} inputValue Current search value.
	 * @returns {void}
	 */
	const handleFilterValueChange = useCallback(
		( inputValue ) => {
			/**
			 * Execute search after debounce interval.
			 *
			 * @returns {void}
			 */
			function runDelayedSearch() {
				fetchMovieOptions( inputValue );
			}

			clearPendingTimer();
			debounceTimeoutRef.current = setTimeout( runDelayedSearch, 300 );
		},
		[ clearPendingTimer, fetchMovieOptions ]
	);

	/**
	 * Preload options once so users can pick from recent movies even before
	 * typing, reducing "no items found" states on first open.
	 *
	 * @returns {void}
	 */
	useEffect( () => {
		fetchMovieOptions( '' );
	}, [ fetchMovieOptions ] );

	/**
	 * Handle movie selection change.
	 *
	 * @param {string|number|null} value Selected movie value.
	 * @returns {void}
	 */
	const handleMovieSelectionChange = useCallback(
		( value ) => {
			setAttributes( { movieId: parseComboboxValue( value ) } );
		},
		[ setAttributes ]
	);

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Movie Selection', 'rt-movie-library' ) }
					initialOpen={ true }
				>
					<ComboboxControl
						label={ __( 'Select Movie', 'rt-movie-library' ) }
						value={ movieId > 0 ? String( movieId ) : '' }
						options={ movieOptions }
						onChange={ handleMovieSelectionChange }
						onFilterValueChange={ handleFilterValueChange }
						help={ __(
							'Type to search by movie title.',
							'rt-movie-library'
						) }
					/>
					{ isLoading && <Spinner /> }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<Placeholder
					icon="video-alt2"
					label={ __( 'Movie', 'rt-movie-library' ) }
					instructions={ __(
						'Preview is disabled in editor. View the published page to see rendered output.',
						'rt-movie-library'
					) }
				>
					<p>
						{ movieId > 0
							? __( 'Movie selected.', 'rt-movie-library' )
							: __(
									'No movie selected yet.',
									'rt-movie-library'
							  ) }
					</p>
				</Placeholder>
			</div>
		</>
	);
}
