/**
 * Single Movie block editor component.
 *
 * Fetches the selected movie via the custom REST API and renders an inline
 * card preview that mirrors the front-end markup so the editor stylesheet
 * can style both identically.
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
 * Parse combobox value into a safe integer attribute value.
 *
 * @param {string|number|null} value Combobox value.
 * @return {number} Parsed integer or 0.
 */
function parseComboboxValue( value ) {
	const parsed = Number.parseInt( String( value ?? 0 ), 10 );
	return Number.isNaN( parsed ) ? 0 : parsed;
}

/**
 * Format a runtime in minutes to a human-readable string.
 *
 * @param {number|string} minutes Raw runtime value in minutes.
 * @return {string} Formatted runtime, e.g. "1 hr 14 min".
 */
function formatRuntime( minutes ) {
	const total = Number.parseInt( String( minutes ?? 0 ), 10 );

	if ( Number.isNaN( total ) || total <= 0 ) {
		return __( 'N/A', 'rt-movie-library' );
	}

	const hrs = Math.floor( total / 60 );
	const mins = total % 60;

	if ( hrs > 0 && mins > 0 ) {
		return `${ hrs } hr ${ mins } min`;
	}

	if ( hrs > 0 ) {
		return `${ hrs } hr`;
	}

	return `${ mins } min`;
}

/**
 * Render a single movie card matching the front-end markup.
 *
 * @param {Object} props       Component props.
 * @param {Object} props.movie REST API movie object.
 * @return {JSX.Element} Movie card element.
 */
function MovieCard( { movie } ) {
	const runtime = formatRuntime(
		movie.meta?.[ 'rt-movie-meta-basic-runtime' ]
	);

	const releaseDateFormatted = movie.formatted_release_date || '';

	const posterUrl = movie.featured_image_url || '';

	return (
		<article className="movie-card">
			<div className="movie-card__poster">
				{ posterUrl ? (
					<img src={ posterUrl } alt={ movie.title || '' } />
				) : (
					<div
						style={ {
							width: '100%',
							height: '100%',
							background: '#444',
						} }
					/>
				) }
			</div>
			<div className="movie-card__content">
				<div className="movie-card__row">
					<h3 className="movie-card__title">{ movie.title }</h3>
					<p className="movie-card__runtime">{ runtime }</p>
				</div>
				{ releaseDateFormatted && (
					<p className="movie-card__release-date">
						{ releaseDateFormatted }
					</p>
				) }
			</div>
		</article>
	);
}

/**
 * Edit component for Movie block.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Current attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {JSX.Element} Editor UI.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { movieId } = attributes;
	const blockProps = useBlockProps();
	const [ movieOptions, setMovieOptions ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ movieData, setMovieData ] = useState( null );
	const [ isMovieLoading, setIsMovieLoading ] = useState( false );
	const debounceTimeoutRef = useRef( null );

	/**
	 * Clear pending debounced search timer.
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
	 * Query movie options for a search term.
	 *
	 * @param {string} searchTerm Typed search term.
	 * @return {void}
	 */
	const fetchMovieOptions = useCallback( ( searchTerm ) => {
		setIsLoading( true );

		/**
		 * Map REST movie payload to combobox option shape.
		 *
		 * @param {Object} movie Movie payload entry.
		 * @return {{value: string, label: string}} Combobox option.
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
		 * @return {void}
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
		 * @return {void}
		 */
		function handleError() {
			setMovieOptions( [] );
		}

		/**
		 * Finalize movie search loading state.
		 *
		 * @return {void}
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
	 * @param {string} inputValue Current search value.
	 * @return {void}
	 */
	const handleFilterValueChange = useCallback(
		( inputValue ) => {
			/**
			 * Execute search after debounce interval.
			 *
			 * @return {void}
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
	 * Preload options on mount.
	 *
	 * @return {void}
	 */
	useEffect( () => {
		fetchMovieOptions( '' );
	}, [ fetchMovieOptions ] );

	/**
	 * Fetch full movie data whenever movieId changes.
	 */
	useEffect( () => {
		if ( movieId <= 0 ) {
			setMovieData( null );
			return;
		}

		setIsMovieLoading( true );

		apiFetch( {
			path: `/rt-movie-library/v1/movies/${ movieId }`,
		} )
			.then( ( response ) => {
				setMovieData( response );
			} )
			.catch( () => {
				setMovieData( null );
			} )
			.finally( () => {
				setIsMovieLoading( false );
			} );
	}, [ movieId ] );

	/**
	 * Handle movie selection change.
	 *
	 * @param {string|number|null} value Selected movie value.
	 * @return {void}
	 */
	const handleMovieSelectionChange = useCallback(
		( value ) => {
			setAttributes( { movieId: parseComboboxValue( value ) } );
		},
		[ setAttributes ]
	);

	/**
	 * Determine the main block content based on current state.
	 *
	 * @return {JSX.Element} Block content.
	 */
	function renderContent() {
		if ( isMovieLoading ) {
			return (
				<Placeholder
					icon="video-alt2"
					label={ __( 'Movie', 'rt-movie-library' ) }
				>
					<Spinner />
				</Placeholder>
			);
		}

		if ( movieId > 0 && movieData ) {
			return (
				<div className="page-archive-movie rt-block-archive-bridge">
					<section className="movie-section">
						<div className="container">
							<div className="movie-grid">
								<MovieCard movie={ movieData } />
							</div>
						</div>
					</section>
				</div>
			);
		}

		return (
			<Placeholder
				icon="video-alt2"
				label={ __( 'Movie', 'rt-movie-library' ) }
				instructions={ __(
					'Select a movie from the sidebar to preview.',
					'rt-movie-library'
				) }
			/>
		);
	}

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

			<div { ...blockProps }>{ renderContent() }</div>
		</>
	);
}
