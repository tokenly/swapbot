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

/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Less
 | file for our application, as well as publishing vendor resources.
 |
 */

// combine admin
gulp.task('combineAdmin', function() {
    gulp.src([
        'resources/assets/coffee/admin/*.coffee',
        'resources/assets/coffee/shared/*.coffee'
    ])

    .pipe(order([
        'resources/assets/coffee/admin/*.coffee',
        'resources/assets/coffee/shared/*.coffee'
    ]))

    .pipe(concat('admin-combined.coffee'))

    .pipe(
        coffee({})
        .on('error', notify.onError({message: 'Error: <%= error.message %>'}))
        .on("error", function (err) { console.log("Error:", err);})
    )
    .pipe(rev())
    .pipe(gulp.dest('public/js/admin'))

    .pipe(rev.manifest({path: 'public/manifest/rev-manifest.json', base: 'public', merge: true}))
    .pipe(gulp.dest('public'))
});




// combine bot
gulp.task('combinePublicBotApp', function() {
    gulp.src([
        'resources/assets/coffee/shared/*.coffee',
        'resources/assets/coffee/services/*.coffee',
        'resources/assets/coffee/bot/**/*.cjsx',
        'resources/assets/coffee/bot/**/*.coffee'
    ])

    .pipe(concat('bot-combined.cjsx'))

    .pipe(cjsx()
        .on('error', notify.onError({message: 'Error: <%= error.message %>'}))
    )
    .pipe(rev())
    .pipe(gulp.dest('public/js/bot'))

    .pipe(rev.manifest({path: 'public/manifest/rev-manifest.json', base: 'public', merge: true}))
    .pipe(gulp.dest('public'))
});


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



gulp.task('watch', function() {
    gulp.watch([
        "resources/assets/coffee/shared/*.coffee",
        "resources/assets/coffee/admin/**/*.coffee"
    ], ['combineAdmin']);

    gulp.watch([
        "resources/assets/coffee/shared/*.coffee",
        "resources/assets/coffee/services/*.coffee",
        "resources/assets/coffee/bot/**/*.coffee",
        "resources/assets/coffee/bot/**/*.cjsx",
    ], ['combinePublicBotApp']);

    gulp.watch([
        "resources/assets/public/*.coffee",
    ], ['public']);

    gulp.watch([
        "resources/assets/less/**/*.less",
    ], ['less']);
});

// // 
// elixir(function(mix) {
//     // del('/tmp/elixir-admin-build/*', {force: true});

//     // less
//     mix
//         // less files
//         .less(['admin.less', 'main.less', 'details.less', 'utility.less'])

//         // admin
//         .combineAdmin()

//         // // popup
//         // .combinePopup()

//         // bot
//         .combinePublicBotApp()

//         // public
//         .coffee('public/asyncLoad.coffee', 'public/js/public')
//         .coffee('public/changebot.coffee', 'public/js/public')
//         ;
// });
