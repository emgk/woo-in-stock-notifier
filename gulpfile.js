const gulp = require( 'gulp' );
const { watch, series } = gulp;

const wpPot = require( 'gulp-wp-pot' );
const zip = require( 'gulp-zip' );
const del = require( 'del' );
const fs = require( 'fs' );
const webpack = require( 'webpack' );
const webpackStream = require( 'webpack-stream' );

const
	sourceFiles = 'src/',
	sourceJsFiles = sourceFiles + '**/**/*.js',
	outputDir = 'assets';

function watchFiles() {
	const webpackConfig = require( './webpack.config.js' );

	return gulp.src( sourceJsFiles )
		.pipe( webpackStream( webpackConfig ), webpack )
		.pipe( gulp.dest( outputDir ) );
}

function watchAllFiles() {
	watch( sourceFiles, watchFiles );
}

gulp.task( 'plugin-pot', function() {
	return gulp.src( '**/*.php' )
		.pipe( wpPot( {
			domain: 'in-stock-notifier',
		} ) )
		.pipe( gulp.dest( 'languages/in-stock-notifier-default.pot' ) );
} );

gulp.task( 'plugin-zip-cleanup', function( done ) {
	del.sync( [ 'zip/woo-in-stock-notifier' ] );
	done();
} );

gulp.task( 'plugin-zip-copy', function() {
	const glob = [
		'**/*',
		'!node_modules/**',
		'!src/**',
		'!.git',
		'!.gitattributes',
		'!.gitignore',
		'!.gitmodules',
		'!package.json',
		'!package-lock.json',
		'!webpack.config.js',
		'!gulpfile.js',
		'!.eslintrc.js.js',
		'!README.md',
		'!zip/**',
	];

	return gulp.src( glob )
		.pipe( gulp.dest( 'zip/woo-in-stock-notifier/' ) );
} );

gulp.task( 'plugin-zip', gulp.series( 'plugin-zip-cleanup', 'plugin-zip-copy', function() {
	const pkgInfo = JSON.parse( fs.readFileSync( './package.json' ) );
	return gulp.src( [ 'zip/woo-in-stock-notifier/**' ], { base: 'zip/' } )
		.pipe( zip( 'woo-in-stock-notifier-' + pkgInfo.version + '.zip' ) )
		.pipe( gulp.dest( 'zip/' ) );
}, 'plugin-zip-cleanup' ) );

gulp.task( 'production-env', function( done ) {
	process.env.NODE_ENV = 'production';
	done();
} );

gulp.task( 'watch', series( watchFiles, gulp.parallel( watchAllFiles ) ) );
gulp.task( 'build', gulp.series( [ 'production-env' ], gulp.parallel( watchFiles ) ) );
gulp.task( 'default', gulp.series( [ 'build' ] ) );

gulp.task( 'publish', gulp.series(
	[ 'production-env' ],
	[ 'build' ],
	[ 'plugin-pot' ],
	[ 'plugin-zip' ],
) );
