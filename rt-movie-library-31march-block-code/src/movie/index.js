/**
 * Single Movie block entry point.
 *
 * Keeping registration metadata-driven avoids config drift between block.json
 * and registration arguments as the block evolves.
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
