const { watch, task, src, dest, series } = require('gulp');
const concat = require('gulp-concat');
const minify = require('gulp-minify');
const cleanCSS = require('gulp-clean-css');
const rename = require('gulp-rename');
const wrap = require('gulp-wrap');
const declare = require('gulp-declare');
const notify = require('gulp-notify');

let jsCoreFiles = [
    'node_modules/jquery/dist/jquery.js',
    'node_modules/jquery-ui-dist/jquery-ui.js',
    'node_modules/popper.js/dist/umd/popper.js',
    'node_modules/bootstrap/dist/js/bootstrap.js',
    'node_modules/jquery-ui-touch-punch/jquery.ui.touch-punch.js',
    'admin/src/js/yap-core.js',
];

let jsScheduleFiles = [
    'node_modules/moment/moment.js',
    'node_modules/@fullcalendar/core/main.js',
    'node_modules/@fullcalendar/daygrid/main.js',
    'node_modules/@fullcalendar/timegrid/main.js',
    'node_modules/@fullcalendar/interaction/main.js',
    'node_modules/@fullcalendar/list/main.js',
];

let jsReportsFiles = [
    'node_modules/moment/moment.js',
    'node_modules/tabulator-tables/dist/js/tabulator.js',
    'node_modules/plotly.js-dist/plotly.js',
    'node_modules/xlsx/dist/xlsx.full.min.js',
    'node_modules/leaflet/dist/leaflet.js',
    'node_modules/leaflet-fullscreen/dist/Leaflet.fullscreen.js',
];

let cssScheduleFiles = [
    'node_modules/@fullcalendar/core/main.css',
    'node_modules/@fullcalendar/daygrid/main.css',
    'node_modules/@fullcalendar/timegrid/main.css',
    'node_modules/@fullcalendar/list/main.css',
];

let cssReportsFiles = [
    'node_modules/tabulator-tables/dist/css/tabulator.css',
    'node_modules/leaflet/dist/leaflet.css',
    'node_modules/leaflet-fullscreen/dist/leaflet.fullscreen.css',
];

let cssCoreFiles = [
    'admin/src/css/spacelab.bootstrap.css',
    'admin/src/css/yap-core.css',
];

let distJsDir = 'admin/dist/js';
let distCssDir = 'admin/dist/css';

task('jsCore', () => {
    return src(jsCoreFiles)
        .pipe(concat('yap.js'))
        .pipe(dest(distJsDir))
        .pipe(minify({
            ext: {
                min:'.min.js'
            },
        }))
        .pipe(dest(distJsDir))
        .pipe(notify("jsCore complete"));
});

task('jsSchedule', () => {
    return src(jsScheduleFiles)
        .pipe(concat('yap-schedule.js'))
        .pipe(dest(distJsDir))
        .pipe(minify({
            ext: {
                min:'.min.js'
            },
        }))
        .pipe(dest(distJsDir))
        .pipe(notify("jsSchedule complete"));
});

task('jsReports', () => {
    return src(jsReportsFiles)
        .pipe(concat('yap-reports.js'))
        .pipe(dest(distJsDir))
        .pipe(minify({
            ext: {
                min:'.min.js'
            },
        }))
        .pipe(dest(distJsDir))
        .pipe(notify("jsReport complete"));
});

task('cssCore', () => {
    return src(cssCoreFiles)
        .pipe(concat('yap.css'))
        .pipe(dest(distCssDir))
        .pipe(cleanCSS())
        .pipe(rename({
            suffix: '.min'
        }))
        .pipe(dest(distCssDir))
        .pipe(notify("cssCore complete"));
});

task('cssSchedule', () => {
    return src(cssScheduleFiles)
        .pipe(concat('yap-schedule.css'))
        .pipe(dest(distCssDir))
        .pipe(cleanCSS())
        .pipe(rename({
            suffix: '.min'
        }))
        .pipe(dest(distCssDir))
        .pipe(notify("cssSchedule complete"));
});

task('cssReports', () => {
    return src(cssReportsFiles)
        .pipe(concat('yap-reports.css'))
        .pipe(dest(distCssDir))
        .pipe(cleanCSS())
        .pipe(rename({
            suffix: '.min'
        }))
        .pipe(dest(distCssDir))
        .pipe(notify("cssReports complete"));
});

task('default', series('jsCore', 'jsSchedule', 'jsReports', 'cssCore', 'cssSchedule', 'cssReports'));

task('watch', () => {
    watch(jsCoreFiles, series('jsCore'));
    watch(jsScheduleFiles, series('jsSchedule'));
    watch(jsReportsFiles, series('jsReports'));
    watch(cssCoreFiles, series('cssCore'));
    watch(cssScheduleFiles, series('cssSchedule'));
    watch(cssReportsFiles, series('cssReports'));
});
