var del = require('del');
var elixir = require('laravel-elixir');
var gulp = require("gulp");
var concat = require("gulp-concat");
var coffee = require("gulp-coffee");
var cjsx = require('gulp-cjsx');
var Notification = require('laravel-elixir/ingredients/commands/Notification');
var es = require('event-stream');
var uglify = require('gulp-uglify');

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


var onError = function(e) {
    new Notification().error(e, 'CoffeeScript Compilation Failed!');

    this.emit('end');
};


// combine admin
elixir.extend("combineAdmin", function() {
    gulp.task('combineAdmin', function() {
        // es.merge(
        //     gulp.src('resources/assets/coffee/admin/*.coffee'),
        //     gulp.src('resources/assets/coffee/shared/*.coffee')
        // )
        gulp.src([
            'resources/assets/coffee/admin/*.coffee',
            'resources/assets/coffee/shared/*.coffee'
        ])
      // gulp.src('resources/assets/coffee/admin/*.coffee')
        .pipe(concat('admin-combined.coffee'))
        .pipe(coffee({}).on('error', onError))
        .pipe(gulp.dest('public/js/admin'))
    });

    this.registerWatcher("combineAdmin", [
        "resources/assets/coffee/shared/*.coffee",
        "resources/assets/coffee/admin/**/*.coffee"
    ]);

    return this.queueTask("combineAdmin");
});


// // combine popup
// elixir.extend("combinePopup", function() {
//     gulp.task('combinePopup', function() {
//         es.merge(
//             gulp.src('resources/assets/coffee/popup/*.cjsx'),
//             gulp.src('resources/assets/coffee/popup/*.coffee'),
//             gulp.src('resources/assets/coffee/shared/*.coffee')
//         )
//         .pipe(concat('popup-combined.cjsx'))
//         .pipe(cjsx().on('error', onError))
//         .pipe(gulp.dest('public/js/popup'))
//     });


//     this.registerWatcher("combinePopup", [
//         "resources/assets/coffee/shared/*.coffee",
//         "resources/assets/coffee/popup/**/*.coffee",
//         "resources/assets/coffee/popup/**/*.cjsx",
//     ]);

//     return this.queueTask("combinePopup");
// });


// combine bot
elixir.extend("combinePublicBotApp", function() {
    gulp.task('combinePublicBotApp', function() {
        // es.merge(
        //     gulp.src('resources/assets/coffee/bot/*.cjsx'),
        //     gulp.src('resources/assets/coffee/bot/*.coffee'),
        //     gulp.src('resources/assets/coffee/shared/*.coffee'),
        //     gulp.src('resources/assets/coffee/services/*.coffee')
        // )
        gulp.src([
            'resources/assets/coffee/shared/*.coffee',
            'resources/assets/coffee/services/*.coffee',
            'resources/assets/coffee/bot/*.cjsx',
            'resources/assets/coffee/bot/*.coffee'
        ])
        .pipe(concat('bot-combined.cjsx'))
        .pipe(cjsx().on('error', onError))
        .pipe(gulp.dest('public/js/bot'))
    });


    this.registerWatcher("combinePublicBotApp", [
        "resources/assets/coffee/shared/*.coffee",
        "resources/assets/coffee/services/*.coffee",
        "resources/assets/coffee/bot/**/*.coffee",
        "resources/assets/coffee/bot/**/*.cjsx",
    ]);

    return this.queueTask("combinePublicBotApp");
});


// 
elixir(function(mix) {
    // del('/tmp/elixir-admin-build/*', {force: true});

    // less
    mix
        // less files
        .less(['admin.less', 'main.less'])

        // admin
        .combineAdmin()

        // // popup
        // .combinePopup()

        // bot
        .combinePublicBotApp()

        // public
        .coffee('public/asyncLoad.coffee', 'public/js/public')
        ;
});
