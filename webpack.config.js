const path = require( 'path' );
const { CleanWebpackPlugin } = require( 'clean-webpack-plugin' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );

const isProd = 'production' === process.env.NODE_ENV;
const mode = isProd ? 'production' : 'development';

module.exports = {
	mode,
	devtool: ! isProd ? 'eval-source-map' : '',
	entry: {
		admin: [ './src/admin/main.js', './src/admin/main.scss' ],
		front: [ './src/front/main.js', './src/front/main.scss' ],
	},
	output: {
		filename: 'js/[name].min.js',
		path: path.resolve( __dirname, './assets' ),
	},
	module: {
		rules: [
			{
				test: /\.(css|scss)$/,
				use: [
					MiniCssExtractPlugin.loader,
					{
						loader: 'css-loader',
						options: {
							sourceMap: true,
						},
					},
					{
						loader: 'resolve-url-loader',
					},
					{
						loader: 'sass-loader',
						options: {
							sourceMap: true,
							sourceMapContents: false,
						},
					},
				],
			},
			{
				test: /\.js$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: [ '@babel/env', '@babel/react' ],
					},
				},
			},
		],
	},

	plugins: [
		new MiniCssExtractPlugin( {
			filename: 'css/[name].min.css',
		} ),
		new CleanWebpackPlugin( {
			cleanOnceBeforeBuildPatterns: [],
			cleanAfterEveryBuildPatterns: [
				'js/*',
				'css/*',
			],
		} ),
	],
};
