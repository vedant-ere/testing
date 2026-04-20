/**
 * Person block entry point.
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
