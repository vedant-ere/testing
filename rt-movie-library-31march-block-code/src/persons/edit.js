/**
 * Persons block editor component.
 *
 * @package RT_Movie_Library
 */

import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	Placeholder,
	RangeControl,
	SelectControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

/**
 * Parse SelectControl value into a safe integer.
 *
 * @param {string|number|null} value Raw value.
 * @returns {number} Parsed integer value.
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
 * @returns {Array<{label: string, value: number}>} Select options.
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
 * Edit component for Persons block.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attributes setter.
 * @returns {JSX.Element} Editor UI.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { count, careerId } = attributes;
	const blockProps = useBlockProps();

	/**
	 * Select career terms for inspector dropdown.
	 *
	 * @param {Function} select Data selector.
	 * @returns {Array} Career terms.
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
	 * Update count attribute.
	 *
	 * @param {number|undefined} value New value.
	 * @returns {void}
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
	 * @returns {void}
	 */
	function handleCareerChange( value ) {
		setAttributes( { careerId: parseSelectValue( value ) } );
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

			<div { ...blockProps }>
				<Placeholder
					icon="groups"
					label={ __( 'Persons', 'rt-movie-library' ) }
					instructions={ __(
						'Person list preview is disabled in editor. View the published page to see rendered output.',
						'rt-movie-library'
					) }
				>
					<p>
						{ __( 'Count:', 'rt-movie-library' ) } { count }
					</p>
					<p>
						{ careerId > 0
							? __( 'Career selected', 'rt-movie-library' )
							: __(
									'No career filter selected',
									'rt-movie-library'
							  ) }
					</p>
				</Placeholder>
			</div>
		</>
	);
}
