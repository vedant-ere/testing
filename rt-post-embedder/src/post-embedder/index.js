/**
 * Post Embedder block registration entry.
 *
 * @package rt-post-embedder
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import Edit from './edit';
import save from './save';
import './style-index.css';

registerBlockType( metadata.name, {
	title: __( 'Post Embedder', 'rt-post-embedder' ),
	description: __(
		'Search and embed existing posts with editable fields.',
		'rt-post-embedder'
	),
	edit: Edit,
	save,
} );
