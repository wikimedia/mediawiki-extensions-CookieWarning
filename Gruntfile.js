/*!
 * Grunt file
 *
 * @package CookieWarning
 */

/* eslint-env node */
module.exports = function ( grunt ) {
	var conf = grunt.file.readJSON( 'extension.json' );

	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-jsonlint' );
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-stylelint' );

	grunt.initConfig( {
		banana: conf.MessagesDirs,
		jsonlint: {
			all: [
				'**/*.json',
				'!node_modules/**',
				'!vendor/**'
			]
		},
		eslint: {
			all: [
				'**/*.js',
				'!node_modules/**',
				'!vendor/**'
			]
		},
		stylelint: {
			options: {
				syntax: 'less'
			},
			src: [ 'resources/**/*.{css,less}' ]
		}
	} );

	grunt.registerTask( 'lint', [ 'eslint', 'stylelint', 'jsonlint', 'banana' ] );
	grunt.registerTask( 'test', [ 'lint', 'banana' ] );
	grunt.registerTask( 'default', 'test' );
};
