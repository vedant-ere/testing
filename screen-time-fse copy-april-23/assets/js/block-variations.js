/**
 * Block Variations for Screen Time FSE.
 *
 * Provides custom variations for core blocks, such as Genre Pills for post terms.
 *
 * @package
 */

const { __ } = wp.i18n;

/**
 * Register Genre Pills variation for core/post-terms.
 */
wp.blocks.registerBlockVariation(
	'core/post-terms',
	{
		name: 'genre-pills',
		title: __( 'Genre Pills', 'screen-time-fse' ),
		description: __( 'Display movie genres as pill buttons', 'screen-time-fse' ),

		attributes: {
			term: 'genre',
			className: 'genre-pills',
		},

		scope: [ 'inserter' ],
	},
);
