// var elixir = require('laravel-elixir');
var gulp = require("gulp");
var concat = require("gulp-concat");
var order = require("gulp-order");
var coffee = require("gulp-coffee");
var cjsx = require('gulp-cjsx');
var notify = require("gulp-notify");
var uglify = require('gulp-uglify');
var less = require('gulp-less');
var rev = require('gulp-rev');

var coffee_reactify = require('coffee-reactify');

var browserify = require('browserify');
var watchify = require('watchify');

var source = require('vinyl-source-stream');
var buffer = require('vinyl-buffer');
var gutil = require('gulp-util');
var sourcemaps = require('gulp-sourcemaps');
var assign = require('lodash.assign');



// ---------------------------------------------------------------
// browserify

// add custom browserify options here
var customOpts = {
  entries: ['./resources/assets/coffee/bot/app.coffee'],
  debug: true,
  extensions: ['.coffee','.cjsx']
};
var opts = assign({}, watchify.args, customOpts);

function browserifyBundle(b, name, destLocation) {
    return b.bundle()
        // log errors if they happen
        .on('error', notify.onError({message: 'Browserify Error: <%= error.message %>'}))
        .on("error", function (err) { console.log("Browserify Error:", err);})

        .pipe(source(name))
        .pipe(buffer())
        .pipe(sourcemaps.init({loadMaps: true})) // loads map from browserify file

        // Add transformation tasks to the pipeline here.
        .pipe(sourcemaps.write('./')) // writes .map file

        .pipe(rev())
        .pipe(gulp.dest(destLocation))

        .pipe(rev.manifest({path: 'public/manifest/rev-manifest.json', base: 'public', merge: true}))
        .pipe(gulp.dest('public'))
        ;
}


// ---------------------------------------------------------------

// combine bot (browserify)
gulp.task('combinePublicBotApp', function() {
    // run the watchify bundler
    var b = browserify(assign({}, opts, {entries: ['./resources/assets/coffee/bot/app.coffee']})); 

    // add transformations
    b.transform(coffee_reactify);

    // browserify
    browserifyBundle(b, 'bot-combined.js', 'public/js/bot');
});

gulp.task('watchPublicBotApp', function() {
    gulp.watch([
        "resources/assets/coffee/bot/**/*.coffee",
        "resources/assets/coffee/bot/**/*.cjsx",
        "resources/assets/coffee/shared/**/*.coffee",
    ], ['combinePublicBotApp']);
});

// ---------------------------------------------------------------

// combine admin
gulp.task('combineAdmin', function() {
    // run the watchify bundler
    var b = browserify(assign({}, opts, {entries: ['./resources/assets/coffee/admin/admin.coffee']})); 

    // add transformations
    b.transform(coffee_reactify);

    // browserify
    browserifyBundle(b, 'admin-combined.js', 'public/js/admin');
});

gulp.task('watchAdmin', function() {
    gulp.watch([
        "resources/assets/coffee/admin/**/*.coffee",
        "resources/assets/coffee/shared/**/*.coffee",
    ], ['combineAdmin']);
});

// ---------------------------------------------------------------

gulp.task('public', function() {
    gulp.src([
        'resources/assets/coffee/public/*.coffee',
    ])

    .pipe(
        coffee({})
        .on('error', notify.onError({message: 'Error: <%= error.message %>'}))
        .on("error", function (err) { console.log("Error:", err);})
    )

    .pipe(rev())
    .pipe(gulp.dest('public/js/public'))

    .pipe(rev.manifest({path: 'public/manifest/rev-manifest.json', base: 'public', merge: true}))
    .pipe(gulp.dest('public'))
});

gulp.task('watchPublic', function() {
    gulp.watch([
        "resources/assets/coffee/public/**/*.coffee",
    ], ['public']);
});

// ---------------------------------------------------------------

gulp.task('less', function() {
    gulp.src([
        'resources/assets/less/admin.less',
        'resources/assets/less/main.less',
        'resources/assets/less/details.less',
        'resources/assets/less/utility.less'
    ])

    .pipe(
        less({})
        .on('error', notify.onError({message: 'Error: <%= error.message %>'}))
    )

    .pipe(rev())
    .pipe(gulp.dest('public/css'))

    .pipe(rev.manifest({path: 'public/manifest/rev-manifest.json', base: 'public', merge: true}))
    .pipe(gulp.dest('public'))
});


gulp.task('default', ['combineAdmin', 'combinePublicBotApp', 'public', 'less']);


// ---------------------------------------------------------------


// gulp.task('watch', function() {
//     // decorate browserify with watchify
//     var b = watchify(browserify(opts)); 

//     // add transformations
//     b.transform(coffee_reactify);

//     b.on('update', function() { browserifyBundle(b); }); // on any dep update, runs the bundler
//     b.on('log', gutil.log); // output build logs to terminal

//     browserifyBundle(b);
// });


gulp.task('watch', function() {
    // compile all files once to start
    gulp.start('combinePublicBotApp');
    gulp.start('combineAdmin');
    gulp.start('public');
    gulp.start('less');

    // watch js files
    gulp.start('watchPublicBotApp');
    gulp.start('watchAdmin');
    gulp.start('watchPublic');

    // Watch .less files
    gulp.watch([
        "resources/assets/less/**/*.less",
    ], ['less']);
});

