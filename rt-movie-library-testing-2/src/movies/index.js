/**
 * Movies block entry point.
 *
 * Keeping registration metadata-driven avoids repeating schema details in JS
 * and keeps source of truth in block.json for PHP/JS consistency.
 *
 * @package
 */

import { registerBlockType } from '@wordpress/blocks';

import './index.css';
import Edit from './edit';
import metadata from './block.json';
import save from './save';

registerBlockType( metadata.name, {
	edit: Edit,
	save,
} );
