/**
 * Search component for finding embeddable posts.
 *
 * @package rt-post-embedder
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, Notice, Spinner, TextControl } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const SEARCH_DEBOUNCE_MS = 400;

/**
 * Build endpoint path for one search request.
 *
 * @param {string} searchTerm Search string.
 * @param {number} pageNumber Current page number.
 * @returns {string} REST path.
 */
function buildSearchPath( searchTerm, pageNumber ) {
	const encodedSearch = encodeURIComponent( searchTerm );
	return `/rt-post-embedder/v1/search?search=${ encodedSearch }&page=${ pageNumber }`;
}

/**
 * PostSearch component.
 *
 * @param {Object}   props            Component props.
 * @param {Function} props.onSelect   Selection callback.
 * @param {string}   props.placeholder Input placeholder.
 * @returns {JSX.Element} Component tree.
 */
export default function PostSearch( { onSelect, placeholder } ) {
	const [ query, setQuery ] = useState( '' );
	const [ results, setResults ] = useState( [] );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 0 );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ errorMessage, setErrorMessage ] = useState( '' );

	const debounceRef = useRef( null );
	const requestVersionRef = useRef( 0 );

	/**
	 * Fetch one search page.
	 *
	 * Request-version guards ignore stale responses, which prevents slow requests
	 * from overwriting newer result sets when users type quickly.
	 *
	 * @param {string} searchTerm Search input.
	 * @param {number} pageNumber Page number.
	 * @returns {void}
	 */
	function fetchResults( searchTerm, pageNumber ) {
		if ( '' === searchTerm.trim() ) {
			setResults( [] );
			setTotalPages( 0 );
			setIsLoading( false );
			setErrorMessage( '' );
			return;
		}

		setIsLoading( true );
		setErrorMessage( '' );

		const requestVersion = requestVersionRef.current + 1;
		requestVersionRef.current = requestVersion;

		apiFetch( { path: buildSearchPath( searchTerm, pageNumber ) } )
			.then( function onSuccess( response ) {
				if ( requestVersion !== requestVersionRef.current ) {
					return;
				}

				setResults(
					Array.isArray( response.posts ) ? response.posts : []
				);
				setTotalPages(
					Number.isFinite( response.total_pages )
						? response.total_pages
						: 0
				);
				setIsLoading( false );
			} )
			.catch( function onError( error ) {
				if ( requestVersion !== requestVersionRef.current ) {
					return;
				}

				// eslint-disable-next-line no-console
				console.error( '[RT Post Embedder] Search failed:', error );
				setErrorMessage(
					__( 'Search failed. Please try again.', 'rt-post-embedder' )
				);
				setIsLoading( false );
			} );
	}

	/**
	 * Handle search query changes with debounce.
	 *
	 * @param {string} value Current input value.
	 * @returns {void}
	 */
	function handleQueryChange( value ) {
		setQuery( value );
		setPage( 1 );

		if ( null !== debounceRef.current ) {
			clearTimeout( debounceRef.current );
		}

		debounceRef.current = setTimeout( function onDebouncedSearch() {
			fetchResults( value, 1 );
		}, SEARCH_DEBOUNCE_MS );
	}

	/**
	 * Navigate to previous page.
	 *
	 * @returns {void}
	 */
	function handlePreviousPage() {
		if ( page <= 1 ) {
			return;
		}

		const nextPage = page - 1;
		setPage( nextPage );
		fetchResults( query, nextPage );
	}

	/**
	 * Navigate to next page.
	 *
	 * @returns {void}
	 */
	function handleNextPage() {
		if ( page >= totalPages ) {
			return;
		}

		const nextPage = page + 1;
		setPage( nextPage );
		fetchResults( query, nextPage );
	}

	/**
	 * Build click handler for one result row.
	 *
	 * @param {Object} result Result object.
	 * @returns {Function} Event callback.
	 */
	function getResultClickHandler( result ) {
		return function onResultSelect() {
			onSelect( result );
		};
	}

	/**
	 * Cleanup pending timers on unmount.
	 *
	 * @returns {Function} Cleanup callback.
	 */
	useEffect( function setupCleanup() {
		return function cleanup() {
			if ( null !== debounceRef.current ) {
				clearTimeout( debounceRef.current );
			}
		};
	}, [] );

	return (
		<div className="rt-pe-post-search">
			<TextControl
				label={ __( 'Search for a post', 'rt-post-embedder' ) }
				value={ query }
				onChange={ handleQueryChange }
				placeholder={
					placeholder ||
					__(
						'Type to search all supported post types…',
						'rt-post-embedder'
					)
				}
			/>

			{ isLoading && <Spinner /> }

			{ '' !== errorMessage && (
				<Notice status="error" isDismissible={ false }>
					{ errorMessage }
				</Notice>
			) }

			{ ! isLoading && results.length > 0 && (
				<ul className="rt-pe-search-results">
					{ results.map( function renderResult( result ) {
						return (
							<li
								key={ result.id }
								className="rt-pe-search-results__item"
							>
								<button
									type="button"
									className="rt-pe-search-results__button"
									onClick={ getResultClickHandler( result ) }
								>
									<span className="rt-pe-search-results__type">
										{ result.type }
									</span>
									<span className="rt-pe-search-results__title">
										{ result.title }
									</span>
								</button>
							</li>
						);
					} ) }
				</ul>
			) }

			{ ! isLoading &&
				'' !== query.trim() &&
				results.length === 0 &&
				'' === errorMessage && (
					<p className="rt-pe-search-results__empty">
						{ __( 'No posts found.', 'rt-post-embedder' ) }
					</p>
				) }

			{ totalPages > 1 && (
				<div className="rt-pe-pagination">
					<Button
						variant="secondary"
						onClick={ handlePreviousPage }
						disabled={ page <= 1 }
					>
						{ __( 'Previous', 'rt-post-embedder' ) }
					</Button>
					<span className="rt-pe-pagination__label">
						{ sprintf(
							/* translators: 1: current page, 2: total pages. */
							__( 'Page %1$d of %2$d', 'rt-post-embedder' ),
							page,
							totalPages
						) }
					</span>
					<Button
						variant="secondary"
						onClick={ handleNextPage }
						disabled={ page >= totalPages }
					>
						{ __( 'Next', 'rt-post-embedder' ) }
					</Button>
				</div>
			) }
		</div>
	);
}
