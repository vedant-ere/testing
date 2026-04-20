/**
 * Movies block editor component.
 *
 * Fetches movies from the custom REST API and renders a card grid preview
 * that mirrors the front-end markup.  Sidebar filters remain available so
 * authors can configure the block; the actual filtered output is produced
 * by the PHP render callback on the front end.
 *
 * @package
 */

import apiFetch from '@wordpress/api-fetch';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	Notice,
	PanelBody,
	Placeholder,
	RangeControl,
	SelectControl,
	Spinner,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from '@wordpress/element';

/**
 * Parse SelectControl value into a safe integer attribute value.
 *
 * @param {string|number|null} value Control value.
 * @return {number} Parsed integer or 0.
 */
function parseSelectValue( value ) {
	const parsed = Number.parseInt( String( value ?? 0 ), 10 );
	return Number.isNaN( parsed ) ? 0 : parsed;
}

/**
 * Convert lookup records into SelectControl options.
 *
 * @param {Array}  items          Lookup records.
 * @param {string} allOptionLabel Label for "all" option.
 * @return {Array<{label: string, value: number}>} Select options.
 */
function buildSelectOptions( items, allOptionLabel ) {
	const source = Array.isArray( items ) ? items : [];
	const options = [
		{
			label: allOptionLabel,
			value: 0,
		},
	];

	for ( let index = 0; index < source.length; index += 1 ) {
		const item = source[ index ];
		let label = '';

		if ( 'string' === typeof item?.name ) {
			label = item.name;
		} else if ( 'string' === typeof item?.title ) {
			label = item.title;
		}

		if ( '' === label ) {
			continue;
		}

		options.push( {
			label,
			value: parseSelectValue( item?.id ),
		} );
	}

	return options;
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
 * Edit component for Movies block.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Current attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {JSX.Element} Editor UI.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { count, directorId, genreId, labelId, languageId } = attributes;
	const blockProps = useBlockProps();
	const [ directors, setDirectors ] = useState( [] );
	const [ isDirectorsLoading, setIsDirectorsLoading ] = useState( true );
	const [ directorsError, setDirectorsError ] = useState( '' );
	const [ movies, setMovies ] = useState( [] );
	const [ isMoviesLoading, setIsMoviesLoading ] = useState( true );

	/**
	 * Load directors for inspector filter.
	 *
	 * @return {void}
	 */
	const fetchDirectors = useCallback( () => {
		setIsDirectorsLoading( true );

		/**
		 * Handle successful directors response.
		 *
		 * @param {Array|Object} response REST response payload.
		 * @return {void}
		 */
		function handleResponse( response ) {
			setDirectors( Array.isArray( response ) ? response : [] );
			setDirectorsError( '' );
		}

		/**
		 * Handle directors request failure.
		 *
		 * @return {void}
		 */
		function handleError() {
			setDirectorsError(
				__(
					'Could not load directors. Please retry.',
					'rt-movie-library'
				)
			);
		}

		/**
		 * Finalize request loading state.
		 *
		 * @return {void}
		 */
		function handleFinally() {
			setIsDirectorsLoading( false );
		}

		apiFetch( {
			path: '/rt-movie-library/v1/block-data/directors',
		} )
			.then( handleResponse )
			.catch( handleError )
			.finally( handleFinally );
	}, [] );

	/**
	 * Effect callback for initial director hydration.
	 *
	 * @return {void}
	 */
	useEffect( fetchDirectors, [ fetchDirectors ] );

	/**
	 * Fetch movies for the card preview whenever filters change.
	 */
	useEffect( () => {
		setIsMoviesLoading( true );

		const params = new URLSearchParams();
		params.append( 'per_page', count );

		if ( directorId > 0 ) {
			params.append( 'directorId', directorId );
		}

		if ( genreId > 0 ) {
			params.append( 'genreId', genreId );
		}

		if ( labelId > 0 ) {
			params.append( 'labelId', labelId );
		}

		if ( languageId > 0 ) {
			params.append( 'languageId', languageId );
		}

		apiFetch( {
			path: `/rt-movie-library/v1/movies?${ params.toString() }`,
		} )
			.then( ( response ) => {
				setMovies( Array.isArray( response ) ? response : [] );
			} )
			.catch( () => {
				setMovies( [] );
			} )
			.finally( () => {
				setIsMoviesLoading( false );
			} );
	}, [ count, directorId, genreId, labelId, languageId ] );

	/**
	 * Select movie genres.
	 *
	 * @param {Function} select Data store selector.
	 * @return {Array} Genre term list.
	 */
	function selectGenres( select ) {
		return (
			select( coreStore ).getEntityRecords(
				'taxonomy',
				'rt-movie-genre',
				{
					per_page: 100,
					orderby: 'name',
					order: 'asc',
					_fields: 'id,name',
				}
			) || []
		);
	}

	/**
	 * Select movie labels.
	 *
	 * @param {Function} select Data store selector.
	 * @return {Array} Label term list.
	 */
	function selectLabels( select ) {
		return (
			select( coreStore ).getEntityRecords(
				'taxonomy',
				'rt-movie-label',
				{
					per_page: 100,
					orderby: 'name',
					order: 'asc',
					_fields: 'id,name',
				}
			) || []
		);
	}

	/**
	 * Select movie languages.
	 *
	 * @param {Function} select Data store selector.
	 * @return {Array} Language term list.
	 */
	function selectLanguages( select ) {
		return (
			select( coreStore ).getEntityRecords(
				'taxonomy',
				'rt-movie-language',
				{
					per_page: 100,
					orderby: 'name',
					order: 'asc',
					_fields: 'id,name',
				}
			) || []
		);
	}

	const genres = useSelect( selectGenres, [] );
	const labels = useSelect( selectLabels, [] );
	const languages = useSelect( selectLanguages, [] );

	/**
	 * Update movie count.
	 *
	 * @param {number|undefined} value New count.
	 * @return {void}
	 */
	function handleCountChange( value ) {
		if ( 'number' !== typeof value ) {
			return;
		}

		setAttributes( { count: value } );
	}

	/**
	 * Update director filter.
	 *
	 * @param {string|number|null} value Selected value.
	 * @return {void}
	 */
	function handleDirectorChange( value ) {
		setAttributes( { directorId: parseSelectValue( value ) } );
	}

	/**
	 * Update genre filter.
	 *
	 * @param {string|number|null} value Selected value.
	 * @return {void}
	 */
	function handleGenreChange( value ) {
		setAttributes( { genreId: parseSelectValue( value ) } );
	}

	/**
	 * Update label filter.
	 *
	 * @param {string|number|null} value Selected value.
	 * @return {void}
	 */
	function handleLabelChange( value ) {
		setAttributes( { labelId: parseSelectValue( value ) } );
	}

	/**
	 * Update language filter.
	 *
	 * @param {string|number|null} value Selected value.
	 * @return {void}
	 */
	function handleLanguageChange( value ) {
		setAttributes( { languageId: parseSelectValue( value ) } );
	}

	/**
	 * Render the movie grid preview.
	 *
	 * @return {JSX.Element} Grid or placeholder content.
	 */
	function renderContent() {
		if ( isMoviesLoading ) {
			return (
				<Placeholder
					icon="video-alt2"
					label={ __( 'Movies', 'rt-movie-library' ) }
				>
					<Spinner />
				</Placeholder>
			);
		}

		if ( 0 === movies.length ) {
			return (
				<Placeholder
					icon="video-alt2"
					label={ __( 'Movies', 'rt-movie-library' ) }
					instructions={ __(
						'No movies found.',
						'rt-movie-library'
					) }
				/>
			);
		}

		return (
			<div className="page-archive-movie rt-block-archive-bridge">
				<section className="movie-section">
					<div className="container">
						<div className="movie-grid">
							{ movies.map( ( movie ) => (
								<MovieCard
									key={ movie.id }
									movie={ movie }
								/>
							) ) }
						</div>
					</div>
				</section>
			</div>
		);
	}

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Movie Filters', 'rt-movie-library' ) }
					initialOpen={ true }
				>
					<RangeControl
						label={ __( 'Number of Movies', 'rt-movie-library' ) }
						value={ count }
						onChange={ handleCountChange }
						min={ 1 }
						max={ 24 }
						step={ 1 }
					/>

					{ isDirectorsLoading && <Spinner /> }

					{ directorsError && (
						<Notice status="warning" isDismissible={ false }>
							{ directorsError }
						</Notice>
					) }

					{ ! isDirectorsLoading && '' === directorsError && (
						<SelectControl
							label={ __(
								'Filter by Director',
								'rt-movie-library'
							) }
							value={ directorId }
							options={ buildSelectOptions(
								directors,
								__( 'All Directors', 'rt-movie-library' )
							) }
							onChange={ handleDirectorChange }
						/>
					) }

					<SelectControl
						label={ __( 'Filter by Genre', 'rt-movie-library' ) }
						value={ genreId }
						options={ buildSelectOptions(
							genres,
							__( 'All Genres', 'rt-movie-library' )
						) }
						onChange={ handleGenreChange }
					/>

					<SelectControl
						label={ __( 'Filter by Label', 'rt-movie-library' ) }
						value={ labelId }
						options={ buildSelectOptions(
							labels,
							__( 'All Labels', 'rt-movie-library' )
						) }
						onChange={ handleLabelChange }
					/>

					<SelectControl
						label={ __( 'Filter by Language', 'rt-movie-library' ) }
						value={ languageId }
						options={ buildSelectOptions(
							languages,
							__( 'All Languages', 'rt-movie-library' )
						) }
						onChange={ handleLanguageChange }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>{ renderContent() }</div>
		</>
	);
}
