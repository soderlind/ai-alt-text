const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		'image-toolbar': path.resolve( __dirname, 'src/block/image-toolbar/index.js' ),
	},
	output: {
		path: path.resolve( __dirname, 'build/image-toolbar' ),
		filename: 'index.js',
	},
};
