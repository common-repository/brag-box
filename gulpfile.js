// Main gulp package
var gulp = require('gulp');
// Package for minifying sass
var sass = require('gulp-sass');
// Package for running tasks automatically
var watch = require('gulp-watch');
// Package for minifying JS
var uglify = require('gulp-uglify');
// Packacge for renaming files
var rename = require("gulp-rename");
// Package for autoprefixing sass
var autoprefixer = require('gulp-autoprefixer');
// Package for livereload server
var livereload = require('gulp-livereload');
// Package for displaying filesizes
var filesize = require('gulp-filesize');

// Function which handles sass pre-processing.
gulp.task('sass', function(){
	return gulp.src('sass/*.scss')
		.pipe(sass({
			 outputStyle: 'compressed',
			}).on('error', sass.logError))
		.pipe(autoprefixer({
			browsers: ['last 2 versions'],
			cascade: false
			}))
		.pipe(rename(function(path){
			path.extname = ".min.css"
			}))
		.pipe(gulp.dest('css'))
		.pipe(livereload());
});

// Function for handling javascript minification.
gulp.task('scripts', function(){
	return gulp.src('js/*.js')
		.pipe(filesize())
        .pipe(uglify())
        .on('error', swallowError)
        .pipe(filesize())
        .pipe(rename(function(path){
			path.extname = ".min.js"
			}))
        .pipe(gulp.dest('js/dist'))
        .pipe(livereload());
});

// Function for reloading the page via livereload when php files are changed
gulp.task('php', function() {
	return gulp.src('*.php')
	.pipe(livereload());
});

// Function for handling functions automatically
gulp.task('watch', function(){
	livereload.listen();
	// gulp.watch('js/*.js', ['scripts']);
	gulp.watch('sass/*.scss', ['sass']);
	// gulp.watch('*.php', ['php'])
});

// Output errors to console.
function swallowError (error) {
    console.log(error.toString());
    this.emit('end');
}

// Default gulp task
gulp.task('default', ['sass', 'watch']);