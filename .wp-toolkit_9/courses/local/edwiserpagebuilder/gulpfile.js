// Gulp.
var gulp = require('gulp');

// Sass/CSS stuff.
var sass = require('gulp-sass');
var concat = require('gulp-concat');
var prefix = require('gulp-autoprefixer');
var minifycss = require('gulp-minify-css');
var exec  = require('gulp-exec');
var notify = require("gulp-notify");
var rename = require('gulp-rename');
const babel = require('gulp-babel');
const sourcemaps = require('gulp-sourcemaps');
const gulpStylelint = require('gulp-stylelint');
const gulpEslint = require('gulp-eslint');
const mediaGroup = require('gulp-group-css-media-queries');
const mediaMerge = require('gulp-merge-media-queries');
const cleanCSS = require('gulp-clean-css');
const del = require('del');
const replaceAll = require("replaceall");

// JS stuff.
const minify = require('gulp-minify');

var jssrc = 'amd/src/**/*.js';
var jsdest = 'amd/build';

// add your ingonrable files here.
// Like below.
    //  var jsIgnoreLint = [
    // '!amd/src/alert.js',
    // '!amd/src/aria.js',
    // '!amd/src/aspieprogress.js',
    // '!amd/src/babel-external-helpers.js',
    // '!amd/src/bootstrap-select.js',
    // '!amd/src/breakpoints.js',
    // '!amd/src/button.js',
    // '!amd/src/carousel.js',
    // '!amd/src/collapse.js',
    // '!amd/src/color-picker.js',
    // '!amd/src/dropdown.js',
    // '!amd/src/jquery-asPieProgress.js',
    // '!amd/src/jquery-floatingscroll.js',
    // '!amd/src/jquery-toolbar.js',
    // '!amd/src/modal.js',
    // '!amd/src/Plugin.js',
    // '!amd/src/popover.js',
    // '!amd/src/scrollspy.js',
    // '!amd/src/slick.js',
    // '!amd/src/tab.js',
    // '!amd/src/tether.js',
    // '!amd/src/TimeCircles.js',
    // '!amd/src/tooltip.js',
    // '!amd/src/util.js',
    // '!amd/src/feedback.js'
    // ];
var jsIgnoreLint = [];

// Check production mode.
// const PRODUCTION = process.argv.includes('-production');
const PRODUCTION = true;

function copyFiles(src, dest) {
    return gulp.src(src)
    .pipe(gulp.dest(dest))
}

gulp.task('lint-styles', function lintStyles() {
    return gulp.src('scss/**/*.scss')
    .pipe(gulpStylelint({
        reporters: [
          {formatter: 'string', console: true}
        ]
    }));
});

gulp.task('fix-styles', function fixCssTask() {
    return gulp
    .src('scss/**/*.scss')
    .pipe(gulpStylelint({
        fix: true
    }))
    .pipe(gulp.dest('scss'));
});

gulp.task('styles', function() {
    return gulp.src('scss/main.scss')
    .pipe(sass({
        outputStyle: PRODUCTION ? 'compressed' : false
    }))
    .pipe(mediaMerge())
    .pipe(mediaGroup())
    .pipe(cleanCSS({compatibility: 'ie8'}))
    .pipe(rename('styles.css'))
    .pipe(gulp.dest('./'));
});

gulp.task('lint-js', function() {
    return gulp.src([jssrc].concat(jsIgnoreLint))
        // Note: eslint() attaches the lint output to the "eslint" property.
        // Of the file object so it can be used by other modules.
        .pipe(gulpEslint())
        // Note: eslint.format() outputs the lint results to the console.
        // Alternatively use eslint.formatEach() (see Docs).
        .pipe(gulpEslint.format());
        // To have the process exit with an error code (1) on.
        // lint error, return the stream and pipe to failAfterError last.
        // .pipe(gulpEslint.failAfterError());
});

gulp.task('clean', function(done) {
    del('amd/build');
    done();
});

gulp.task('compress', function() {    
    var task = gulp.src(jssrc)
    .pipe(sourcemaps.init());
    if (PRODUCTION) {
        task = task.pipe(babel({ presets: [["@babel/preset-env"]] }))
        .pipe(minify({
            ext:{
                min: '.min.js'
            },
            noSource: true,
            ignoreFiles: []
        }));
    }
    return task.pipe(sourcemaps.write('.'))
    .pipe(gulp.dest(jsdest));
});
gulp.task('purge', gulp.series(function() {
    return gulp.src('.')
    .pipe(exec('php /var/www/remui/html/v39/admin/cli/purge_caches.php'))
    .pipe(notify('Purged All'))
}));

gulp.task('purgejs', gulp.series(function() {
    return gulp.src('.')
    .pipe(exec('php /var/www/remui/html/v39/admin/cli/purge_caches.php --js=true'))
    .pipe(notify('Purged JS'))
}));

gulp.task('purgelang', gulp.series(function() {
    return gulp.src('.')
    .pipe(exec('php /var/www/remui/html/v39/admin/cli/purge_caches.php --lang=true'))
    .pipe(notify('Purged Language Packs'))
}));


// gulp.task('dist-remuicss', gulp.series('styles', 'purge'));
gulp.task('dist-remuijs', gulp.series('compress', 'purge'));

gulp.task('watch', function(done) {
    var watcher = gulp.watch('./amd/src/**/*.js', gulp.series('compress', 'purgejs'));
    watcher.on('change', function(obj) {
        jssrc = obj;
        jsdest = obj.match(/(.*)[\/\\]/)[1]||'';
        jsdest = replaceAll('src', 'build', jsdest);
        return gulp.series('compress');
    });
    gulp.watch([
        './scss/**/*.scss',
        './scss/**/*.css'
    ], gulp.series('styles', 'purge'));
    gulp.watch([
        './lang/**/*.php',
        './templates/**/*'
    ], gulp.series('purge'));
    done();
});

gulp.task('watchlintjs', function(done) {
    gulp.watch('./amd/src/*.js', gulp.series('lint-js'));
    done();
});

gulp.task('watchlintstyles', function(done) {
    gulp.watch('scss/**/*.scss', gulp.series('lint-styles'));
    done();
});

gulp.task('default', gulp.series('clean', 'styles', 'compress', 'purge', 'watch'));
// gulp.task('default', gulp.series('clean', 'compress', 'purge', 'watch'));

gulp.task('lintjs', gulp.series('watchlintjs', 'lint-js'));

gulp.task('lintstyles', gulp.series('watchlintstyles', 'lint-styles'));
