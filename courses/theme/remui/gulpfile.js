// Gulp.
var gulp = require('gulp');

// Sass/CSS stuff.
var sass = require('gulp-sass');
var exec = require('gulp-exec');
var notify = require("gulp-notify");
var rename = require('gulp-rename');
var babel = require('gulp-babel');
var sourcemaps = require('gulp-sourcemaps');
var gulpStylelint = require('gulp-stylelint');
var gulpEslint = require('gulp-eslint');
var mediaGroup = require('gulp-group-css-media-queries');
var mediaMerge = require('gulp-merge-media-queries');
var cleanCSS = require('gulp-clean-css');
var del = require('del');
var replaceAll = require("replaceall");
var frep = require('gulp-frep');

// JS stuff.
var minify = require('gulp-minify');

var jssrc = 'amd/src/**/*.js';
var jsdest = 'amd/build';

var jsIgnoreLint = [
    '!amd/src/alert.js',
    '!amd/src/aria.js',
    '!amd/src/aspieprogress.js',
    '!amd/src/babel-external-helpers.js',
    '!amd/src/bootstrap-select.js',
    '!amd/src/breakpoints.js',
    '!amd/src/button.js',
    '!amd/src/carousel.js',
    '!amd/src/collapse.js',
    '!amd/src/color-picker.js',
    '!amd/src/dropdown.js',
    '!amd/src/jquery-asPieProgress.js',
    '!amd/src/jquery-floatingscroll.js',
    '!amd/src/jquery-toolbar.js',
    '!amd/src/modal.js',
    '!amd/src/Plugin.js',
    '!amd/src/popover.js',
    '!amd/src/scrollspy.js',
    '!amd/src/slick.js',
    '!amd/src/tab.js',
    '!amd/src/tether.js',
    '!amd/src/TimeCircles.js',
    '!amd/src/tooltip.js',
    '!amd/src/util.js',
    '!amd/src/feedback.js'
];

// Check production mode.
// eslint-disable-next-line no-undef
var PRODUCTION = process.argv.includes('-production');

// Pattern for newline replacement for windows development environment.
var pattern = [{
    pattern: /\\r\\n/g,
    replacement: '\\n'
}];

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
    var task = gulp.src('scss/preset/default.scss')
    .pipe(sass({
        outputStyle: PRODUCTION ? 'compressed' : false
    }));
    if (PRODUCTION) {
        task = task.pipe(mediaMerge())
        .pipe(mediaGroup());
    }

    task = task.pipe(cleanCSS({compatibility: 'ie8'}));

    if (PRODUCTION) {
        task = task.pipe(frep(pattern));
    }
    return task.pipe(rename('remui-min.css'))
    .pipe(gulp.dest('./style/'));
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
        task = task.pipe(babel({presets: [["@babel/preset-env"]]}))
        .pipe(minify({
            ext: {
                min: '.min.js'
            },
            noSource: true,
            ignoreFiles: []
        }))
        .pipe(sourcemaps.write('.'))
        .pipe(frep(pattern));
    }
    return task.pipe(gulp.dest(jsdest));
});

gulp.task('purge', gulp.series(function() {
    return gulp.src('.')
    .pipe(exec('php /var/www/remui/html/v310/admin/cli/purge_caches.php'))
    .pipe(notify('Purged All'));
}));

gulp.task('purgejs', gulp.series(function() {
    return gulp.src('.')
    .pipe(exec('php /var/www/remui/html/v310/admin/cli/purge_caches.php --js=true'))
    .pipe(notify('Purged JS'));
}));

gulp.task('purgelang', gulp.series(function() {
    return gulp.src('.')
    .pipe(exec('php /var/www/remui/html/v310/admin/cli/purge_caches.php --lang=true'))
    .pipe(notify('Purged Language Packs'));
}));


gulp.task('dist-remuicss', gulp.series('styles', 'purge'));

gulp.task('watch', function(done) {
    gulp.watch('./amd/src/**/*.js', gulp.series('compress', 'purgejs'));
    // .on('change', function(obj) {
    //     jssrc = obj;
    //     jsdest = obj.match(/(.*)[\/\\]/)[1]||'';
    //     jsdest = replaceAll('src', 'build', jsdest);
    //     return gulp.series('compress');
    // });
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

gulp.task('lintjs', gulp.series('watchlintjs', 'lint-js'));

gulp.task('lintstyles', gulp.series('watchlintstyles', 'lint-styles'));
