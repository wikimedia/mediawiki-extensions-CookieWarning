/*!
 * Grunt file
 *
 * @package CookieWarning
 */

/* eslint-env node */
module.exports = function ( grunt ) {
	var conf = grunt.file.readJSON( 'extension.json' );

	grunt.loadNpmTasks( 'grunt-banana-checker' );
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-stylelint' );

	grunt.initConfig( {
		banana: conf.MessagesDirs,
		eslint: {
			options: {
				cache: true
			},
			all: [
				'**/*.{js,json}',
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

	grunt.registerTask( 'lint', [ 'eslint', 'stylelint', 'banana' ] );
	grunt.registerTask( 'test', [ 'lint', 'banana' ] );
	grunt.registerTask( 'default', 'test' );
};
