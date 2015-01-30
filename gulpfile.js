var del = require('del');
var elixir = require('laravel-elixir');
var gulp = require("gulp");
var concat = require("gulp-concat");
var coffee = require("gulp-coffee");
var Notification = require('laravel-elixir/ingredients/commands/Notification');

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


 elixir.extend("combineAdmin", function() {
    gulp.task('combineAdmin', function() {
      gulp.src('resources/assets/coffee/admin/*.coffee')
        .pipe(concat('admin-combined.coffee'))
        .pipe(coffee({}).on('error', onError))
        .pipe(gulp.dest('public/js/admin'))
    });

    this.registerWatcher("combineAdmin", "resources/assets/coffee/admin/**/*.coffee");

    return this.queueTask("combineAdmin");
 });

elixir(function(mix) {
    del('/tmp/elixir-admin-build/*', {force: true});

    // less
    mix
        .less('app.less')

    // development build
        // .coffee('*.coffee')
        // .coffee('resources/assets/coffee/admin/*.coffee', 'public/js/admin')

    // combined build
        .combineAdmin()
        // .coffee('/tmp/elixir-admin-build/admin.coffee', 'public/js/admin/admin-combined.js')


        ;
});
