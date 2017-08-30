// Require all the things (that we need).
var gulp = require('gulp');
var phpcs = require('gulp-phpcs');
var sort = require('gulp-sort');
var watch = require('gulp-watch');
var wp_pot = require('gulp-wp-pot');

// Define the source paths for each file type.
var src = {
	php: ['**/*.php','!vendor/**','!node_modules/**']
};

// Sniff our code.
gulp.task('php',function () {
	return gulp.src(src.php)
		.pipe(phpcs({
			bin: './vendor/bin/phpcs',
			standard: 'WordPress-Core'
		}))
		// Log all problems that was found
		.pipe(phpcs.reporter('log'));
});

// Create the .pot translation file.
gulp.task('translate', function () {
    gulp.src('**/*.php')
        .pipe(sort())
        .pipe(wp_pot( {
            domain: 'wpcampus-plugin',
            destFile:'wpcampus-plugin.pot',
            package: 'wpcampus-plugin',
            bugReport: 'https://github.com/wpcampus/wpcampus-plugin/issues',
            lastTranslator: 'WPCampus <code@wpcampus.org>',
            team: 'WPCampus <code@wpcampus.org>',
            headers: false
        } ))
        .pipe(gulp.dest('languages'));
});

// Test all the things.
gulp.task('test',['php']);

// I've got my eyes on you(r file changes).
gulp.task('watch', function() {
	gulp.watch(src.php, ['translate','php']);
});

// Let's get this party started.
gulp.task('default', ['translate','test']);