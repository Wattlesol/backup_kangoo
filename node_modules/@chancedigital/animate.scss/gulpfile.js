// Utilities
var path = require('path');
var autoprefixer = require('autoprefixer');
var cssnano = require('cssnano');
var fs = require('fs');

// Gulp
var gulp = require('gulp');

// Gulp plugins
var concat = require('gulp-concat');
var gutil = require('gulp-util');
var header = require('gulp-header');
var sass = require('gulp-sass');
var postcss = require('gulp-postcss');
var rename = require('gulp-rename');
var runSequence = require('run-sequence');
var sourcemaps = require('gulp-sourcemaps');

// Misc/global vars
var pkg = JSON.parse(fs.readFileSync('package.json'));
var source = path.resolve(__dirname, './source');

// Task options
var opts = {
  destPath: './',
  concatName: 'animate.css',

  autoprefixer: {
    browsers: ['> 1%', 'last 2 versions', 'Firefox ESR'],
    cascade: false,
  },

  minRename: {
    suffix: '.min',
  },

  banner: [
    '@charset "UTF-8";\n',
    '/*!',
    ' * <%= name %> -<%= homepage %>',
    ' * Version - <%= version %>',
    ' * Licensed under the MIT license - http://opensource.org/licenses/MIT',
    ' *',
    ' * Copyright (c) <%= new Date().getFullYear() %> <%= author.name %>',
    ' */\n\n',
  ].join('\n'),
};

// ----------------------------
// Gulp task definitions
// ----------------------------

gulp.task('createCSS', function() {
  return gulp
    .src([source + '/_base.scss'])
    .pipe(concat(opts.concatName))
    .pipe(sourcemaps.init())
    .pipe(sass().on('error', sass.logError))
    .pipe(postcss([require('postcss-import'), autoprefixer(opts.autoprefixer)]))
    .pipe(gulp.dest(opts.destPath))
    .pipe(postcss([cssnano({reduceIdents: {keyframes: false}})]))
    .pipe(rename(opts.minRename))
    .pipe(sourcemaps.write())
    .pipe(gulp.dest(opts.destPath));
});

gulp.task('addHeader', function() {
  return gulp
    .src('*.css')
    .pipe(header(opts.banner, pkg))
    .pipe(gulp.dest(opts.destPath));
});

gulp.task('default', gulp.series('createCSS', 'addHeader'));
