const { __ } = wp.i18n;

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
