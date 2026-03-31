/**
 * Person block entry point.
 *
 * @package RT_Movie_Library
 */

import { registerBlockType } from '@wordpress/blocks';

import Edit from './edit';
import metadata from './block.json';
import save from './save';

registerBlockType( metadata.name, {
	edit: Edit,
	save,
} );
