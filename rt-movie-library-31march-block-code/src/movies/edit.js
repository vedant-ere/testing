/**
 * Movies block editor component.
 *
 * The sidebar controls intentionally use small lookup payloads so editors can
 * iterate quickly on filters without loading full movie/person records.
 *
 * @package RT_Movie_Library
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
 * @returns {number} Parsed integer or 0.
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
 * @returns {Array<{label: string, value: number}>} Select options.
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
 * Edit component for Movies block.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Current attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @returns {JSX.Element} Editor UI.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { count, directorId, genreId, labelId, languageId } = attributes;
	const blockProps = useBlockProps();
	const [ directors, setDirectors ] = useState( [] );
	const [ isDirectorsLoading, setIsDirectorsLoading ] = useState( true );
	const [ directorsError, setDirectorsError ] = useState( '' );

	/**
	 * Load directors for inspector filter.
	 *
	 * This data is fetched from a dedicated endpoint so the dropdown can map to
	 * person IDs directly, which keeps filtering explicit and predictable.
	 *
	 * @returns {void}
	 */
	const fetchDirectors = useCallback( () => {
		setIsDirectorsLoading( true );

		/**
		 * Handle successful directors response.
		 *
		 * @param {Array|Object} response REST response payload.
		 * @returns {void}
		 */
		function handleResponse( response ) {
			setDirectors( Array.isArray( response ) ? response : [] );
			setDirectorsError( '' );
		}

		/**
		 * Handle directors request failure.
		 *
		 * @param {Error|Object} error Fetch error object.
		 * @returns {void}
		 */
		function handleError( error ) {
			// eslint-disable-next-line no-console
			console.error(
				'[RT Movie Library] Failed loading directors.',
				error
			);
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
		 * @returns {void}
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
	 * @returns {void}
	 */
	useEffect( fetchDirectors, [ fetchDirectors ] );

	/**
	 * Select movie genres.
	 *
	 * @param {Function} select Data store selector.
	 * @returns {Array} Genre term list.
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
	 * @returns {Array} Label term list.
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
	 * @returns {Array} Language term list.
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
	 * @returns {void}
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
	 * @returns {void}
	 */
	function handleDirectorChange( value ) {
		setAttributes( { directorId: parseSelectValue( value ) } );
	}

	/**
	 * Update genre filter.
	 *
	 * @param {string|number|null} value Selected value.
	 * @returns {void}
	 */
	function handleGenreChange( value ) {
		setAttributes( { genreId: parseSelectValue( value ) } );
	}

	/**
	 * Update label filter.
	 *
	 * @param {string|number|null} value Selected value.
	 * @returns {void}
	 */
	function handleLabelChange( value ) {
		setAttributes( { labelId: parseSelectValue( value ) } );
	}

	/**
	 * Update language filter.
	 *
	 * @param {string|number|null} value Selected value.
	 * @returns {void}
	 */
	function handleLanguageChange( value ) {
		setAttributes( { languageId: parseSelectValue( value ) } );
	}

	/**
	 * Build a compact editor summary for selected filter values.
	 *
	 * This keeps the editor fast by avoiding large server-render previews while
	 * still giving authors confidence about active block configuration.
	 *
	 * @returns {string} Human-readable filter summary.
	 */
	function getFilterSummary() {
		const selectedFilters = [];

		if ( directorId > 0 ) {
			selectedFilters.push(
				__( 'Director selected', 'rt-movie-library' )
			);
		}

		if ( genreId > 0 ) {
			selectedFilters.push( __( 'Genre selected', 'rt-movie-library' ) );
		}

		if ( labelId > 0 ) {
			selectedFilters.push( __( 'Label selected', 'rt-movie-library' ) );
		}

		if ( languageId > 0 ) {
			selectedFilters.push(
				__( 'Language selected', 'rt-movie-library' )
			);
		}

		if ( 0 === selectedFilters.length ) {
			return __( 'No filters selected', 'rt-movie-library' );
		}

		return selectedFilters.join( ', ' );
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

			<div { ...blockProps }>
				<Placeholder
					icon="video-alt2"
					label={ __( 'Movies', 'rt-movie-library' ) }
					instructions={ __(
						'Movie list preview is disabled in editor. View the published page to see rendered output.',
						'rt-movie-library'
					) }
				>
					<p>
						{ __( 'Count:', 'rt-movie-library' ) } { count }
					</p>
					<p>{ getFilterSummary() }</p>
				</Placeholder>
			</div>
		</>
	);
}
