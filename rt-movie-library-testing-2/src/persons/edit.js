/**
 * Persons block editor component.
 *
 * Fetches persons from the custom REST API and renders a card grid preview
 * that mirrors the front-end markup.
 *
 * @package
 */

import apiFetch from '@wordpress/api-fetch';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
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
 * Parse SelectControl value into a safe integer.
 *
 * @param {string|number|null} value Raw value.
 * @return {number} Parsed integer value.
 */
function parseSelectValue( value ) {
	const parsed = Number.parseInt( String( value ?? 0 ), 10 );
	return Number.isNaN( parsed ) ? 0 : parsed;
}

/**
 * Build options array for SelectControl.
 *
 * @param {Array}  terms    Terms list.
 * @param {string} allLabel Label for default option.
 * @return {Array<{label: string, value: number}>} Select options.
 */
function buildTermOptions( terms, allLabel ) {
	const options = [ { label: allLabel, value: 0 } ];
	const source = Array.isArray( terms ) ? terms : [];

	for ( let index = 0; index < source.length; index += 1 ) {
		const term = source[ index ];

		if ( 'string' !== typeof term?.name || '' === term.name ) {
			continue;
		}

		options.push( {
			label: term.name,
			value: parseSelectValue( term.id ),
		} );
	}

	return options;
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
 * Render a single person card matching the front-end markup.
 *
 * @param {Object} props           Component props.
 * @param {Object} props.person    REST API person object.
 * @param {Object} props.careerMap Map of term ID → term name.
 * @param {Object} props.mediaMap  Map of attachment ID → source URL.
 * @return {JSX.Element} Person card element.
 */
function PersonCard( { person, careerMap, mediaMap } ) {
	const birthDate =
		person.meta?.[ 'rt-person-meta-basic-birth-date' ] || '';

	const rawExcerpt = person.excerpt || person.content || '';
	const plainExcerpt = stripTags( rawExcerpt ).trim();
	const bio =
		plainExcerpt.length > 120
			? plainExcerpt.substring( 0, 120 ) + '…'
			: plainExcerpt;

	const imageUrl =
		person.featured_media && mediaMap[ person.featured_media ]
			? mediaMap[ person.featured_media ]
			: '';

	const careerIds = person.taxonomies?.[ 'rt-person-career' ] || [];
	const careerLabel =
		careerIds.length > 0 && careerMap[ careerIds[ 0 ] ]
			? careerMap[ careerIds[ 0 ] ]
			: '';

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
 * Edit component for Persons block.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attributes setter.
 * @return {JSX.Element} Editor UI.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { count, careerId } = attributes;
	const blockProps = useBlockProps();
	const [ persons, setPersons ] = useState( [] );
	const [ isPersonsLoading, setIsPersonsLoading ] = useState( true );
	const [ careerMap, setCareerMap ] = useState( {} );
	const [ mediaMap, setMediaMap ] = useState( {} );

	/**
	 * Select career terms for inspector dropdown.
	 *
	 * @param {Function} select Data selector.
	 * @return {Array} Career terms.
	 */
	function selectCareers( select ) {
		return (
			select( coreStore ).getEntityRecords(
				'taxonomy',
				'rt-person-career',
				{
					per_page: 100,
					orderby: 'name',
					order: 'asc',
					_fields: 'id,name',
				}
			) || []
		);
	}

	const careers = useSelect( selectCareers, [] );

	/**
	 * Build career ID → name map whenever careers load.
	 */
	useEffect( () => {
		if ( ! Array.isArray( careers ) ) {
			return;
		}

		const map = {};

		for ( let i = 0; i < careers.length; i += 1 ) {
			if ( careers[ i ]?.id && careers[ i ]?.name ) {
				map[ careers[ i ].id ] = careers[ i ].name;
			}
		}

		setCareerMap( map );
	}, [ careers ] );

	/**
	 * Fetch persons for the card preview whenever count or career filter changes.
	 */
	const fetchPersons = useCallback( () => {
		setIsPersonsLoading( true );

		const params = new URLSearchParams( {
			per_page: String( count ),
		} );

		if ( careerId > 0 ) {
			params.append( 'careerId', String( careerId ) );
		}

		apiFetch( {
			path: `/rt-movie-library/v1/persons?${ params.toString() }`,
		} )
			.then( ( response ) => {
				const items = Array.isArray( response ) ? response : [];
				setPersons( items );

				// Collect unique featured_media IDs for batch resolution.
				const mediaIds = items
					.map( ( p ) => p.featured_media )
					.filter( ( id ) => id && id > 0 );

				const uniqueIds = [ ...new Set( mediaIds ) ];

				if ( 0 === uniqueIds.length ) {
					setMediaMap( {} );
					return;
				}

				return apiFetch( {
					path: `/wp/v2/media?include=${ uniqueIds.join( ',' ) }&per_page=${ uniqueIds.length }&_fields=id,source_url`,
				} ).then( ( mediaItems ) => {
					const map = {};

					if ( Array.isArray( mediaItems ) ) {
						for ( let i = 0; i < mediaItems.length; i += 1 ) {
							map[ mediaItems[ i ].id ] = mediaItems[ i ].source_url;
						}
					}

					setMediaMap( map );
				} );
			} )
			.catch( () => {
				setPersons( [] );
				setMediaMap( {} );
			} )
			.finally( () => {
				setIsPersonsLoading( false );
			} );
	}, [ count, careerId ] );

	useEffect( fetchPersons, [ fetchPersons ] );

	/**
	 * Update count attribute.
	 *
	 * @param {number|undefined} value New value.
	 * @return {void}
	 */
	function handleCountChange( value ) {
		if ( 'number' !== typeof value ) {
			return;
		}

		setAttributes( { count: value } );
	}

	/**
	 * Update career filter attribute.
	 *
	 * @param {string|number|null} value Selected value.
	 * @return {void}
	 */
	function handleCareerChange( value ) {
		setAttributes( { careerId: parseSelectValue( value ) } );
	}

	/**
	 * Render the persons grid preview.
	 *
	 * @return {JSX.Element} Grid or placeholder content.
	 */
	function renderContent() {
		if ( isPersonsLoading ) {
			return (
				<Placeholder
					icon="groups"
					label={ __( 'Persons', 'rt-movie-library' ) }
				>
					<Spinner />
				</Placeholder>
			);
		}

		if ( 0 === persons.length ) {
			return (
				<Placeholder
					icon="groups"
					label={ __( 'Persons', 'rt-movie-library' ) }
					instructions={ __(
						'No persons found.',
						'rt-movie-library'
					) }
				/>
			);
		}

		return (
			<div className="page-archive-person rt-block-archive-bridge">
				<section className="movie-section">
					<div className="container">
						<div className="person-list">
							{ persons.map( ( person ) => (
								<PersonCard
									key={ person.id }
									person={ person }
									careerMap={ careerMap }								mediaMap={ mediaMap }								/>
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
					title={ __( 'Person Filters', 'rt-movie-library' ) }
					initialOpen={ true }
				>
					<RangeControl
						label={ __( 'Number of Persons', 'rt-movie-library' ) }
						value={ count }
						onChange={ handleCountChange }
						min={ 1 }
						max={ 24 }
						step={ 1 }
					/>

					<SelectControl
						label={ __( 'Filter by Career', 'rt-movie-library' ) }
						value={ careerId }
						options={ buildTermOptions(
							careers,
							__( 'All Careers', 'rt-movie-library' )
						) }
						onChange={ handleCareerChange }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>{ renderContent() }</div>
		</>
	);
}
