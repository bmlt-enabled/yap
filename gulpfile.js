const { watch, task, src, dest, series } = require('gulp');
const concat = require('gulp-concat');
const minify = require('gulp-minify');
const cleanCSS = require('gulp-clean-css');
const rename = require('gulp-rename');
const notify = require('gulp-notify');
const less = require('gulp-less');
const merge = require('merge-stream');
const order = require('gulp-order');

let jsCoreFiles = [
    'node_modules/jquery/dist/jquery.js',
    'node_modules/jquery-ui-dist/jquery-ui.js',
    'node_modules/popper.js/dist/umd/popper.js',
    'node_modules/bootstrap/dist/js/bootstrap.js',
    'node_modules/jquery-ui-touch-punch/jquery.ui.touch-punch.js',
    'node_modules/dark-mode-switch/dark-mode-switch.min.js',
    'node_modules/jquery-validation/dist/jquery.validate.min.js',
    'node_modules/libphonenumber-js/bundle/libphonenumber-min.js',
    'public/src/js/yap-core.js',
];

let jsScheduleFiles = [
    'node_modules/moment/moment.js',
    'node_modules/@fullcalendar/core/main.js',
    'node_modules/@fullcalendar/bootstrap/main.js',
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

let jsMeetingResultsFiles = [
    'node_modules/@bmlt-enabled/croutonjs/crouton.js'
];

let cssScheduleFiles = [
    'node_modules/@fullcalendar/bootstrap/main.css',
    'node_modules/@fullcalendar/core/main.css',
    'node_modules/@fullcalendar/daygrid/main.css',
    'node_modules/@fullcalendar/timegrid/main.css',
    'node_modules/@fullcalendar/list/main.css',
];

let cssReportsFiles = [
    'node_modules/leaflet/dist/leaflet.css',
    'node_modules/leaflet-fullscreen/dist/leaflet.fullscreen.css',
];

let cssTabulatorLightFiles = [
    'node_modules/tabulator-tables/dist/css/tabulator.css',
];

let cssTabulatorDarkFiles = [
    'node_modules/tabulator-tables/dist/css/tabulator_midnight.css',
];

let lessCoreFiles = [
    'public/src/css/yap-core.less',
];

let cssCoreFiles = [
    'public/src/css/spacelab.bootstrap.css',
];

let cssMeetingResultsFiles = [
    'node_modules/@bmlt-enabled/croutonjs/crouton.css'
];

let templatesMeetingResultsFiles = [
    './node_modules/@bmlt-enabled/croutonjs/templates/**/*'
];

let fontsMeetingResultsFiles = [
    './node_modules/@bmlt-enabled/croutonjs/fonts/**/*'
];


let distJsDir = 'public/dist/js';
let distCssDir = 'public/dist/css';

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
        .pipe(notify({"message": "jsCore complete", "wait": true}));
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
        .pipe(notify({"message": "jsSchedule complete", "wait": true}));
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
        .pipe(notify({"message": "jsReport complete", "wait": true}));
});

task('jsMeetingResults', () => {
    return src(jsMeetingResultsFiles)
        .pipe(concat('yap-meeting-results.js'))
        .pipe(dest('public/dist/croutonjs'))
        .pipe(minify({
            ext: {
                min:'.min.js'
            },
        }))
        .pipe(dest('public/dist/croutonjs'))
        .pipe(notify({"message": "jsMeetingResults complete", "wait": true}));
});

task('cssCore', () => {
    let lessStream =  src(lessCoreFiles)
        .pipe(less())
        .pipe(concat('less-files.less'));

    let cssStream = src(cssCoreFiles)
        .pipe(concat('css-files.css'));

    return mergedStream = merge(cssStream, lessStream)
        .pipe(order([
            'css-files.css',
            'less-files.less',
        ]))
        .pipe(concat('yap.css'))
        .pipe(dest(distCssDir))
        .pipe(cleanCSS())
        .pipe(rename({
            suffix: '.min'
        }))
        .pipe(dest(distCssDir))
        .pipe(notify({"message": "cssCore complete", "wait": true}));
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
        .pipe(notify({"message": "cssSchedule complete", "wait": true}));
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
        .pipe(notify({"message": "cssReports complete", "wait": true}));
});

task('cssTabulatorLight', () => {
    return src(cssTabulatorLightFiles)
        .pipe(concat('yap-tabulator-light.css'))
        .pipe(dest(distCssDir))
        .pipe(cleanCSS())
        .pipe(rename({
            suffix: '.min'
        }))
        .pipe(dest(distCssDir))
        .pipe(notify({"message": "cssTabulatorLight complete", "wait": true}));
});

task('cssTabulatorDark', () => {
    return src(cssTabulatorDarkFiles)
        .pipe(concat('yap-tabulator-dark.css'))
        .pipe(dest(distCssDir))
        .pipe(cleanCSS())
        .pipe(rename({
            suffix: '.min'
        }))
        .pipe(dest(distCssDir))
        .pipe(notify({"message": "cssTabulatorDark complete", "wait": true}));
});

task('cssMeetingResults', () => {
    return src(cssMeetingResultsFiles)
        .pipe(concat('yap-meeting-results.css'))
        .pipe(dest('public/dist/croutonjs'))
        .pipe(cleanCSS())
        .pipe(rename({
            suffix: '.min'
        }))
        .pipe(dest('public/dist/croutonjs'))
        .pipe(notify({"message": "cssMeetingResults complete", "wait": true}));
});

task('templatesMeetingResults', function () {
    return src(templatesMeetingResultsFiles)
        .pipe(dest('public/dist/croutonjs/templates'));
});

task('fontsMeetingResults', function () {
    return src(fontsMeetingResultsFiles)
        .pipe(dest('public/dist/croutonjs/fonts'));
});

task('default', series(
    'jsCore',
    'jsSchedule',
    'jsReports',
    'jsMeetingResults',
    'cssCore',
    'cssSchedule',
    'cssReports',
    'cssTabulatorLight',
    'cssTabulatorDark',
    'cssMeetingResults',
    'templatesMeetingResults',
    'fontsMeetingResults',
));

task('watch', () => {
    watch(jsCoreFiles, series('jsCore'));
    watch(jsScheduleFiles, series('jsSchedule'));
    watch(jsReportsFiles, series('jsReports'));
    watch(jsMeetingResultsFiles, series('jsMeetingResults'));
    watch(lessCoreFiles, series('cssCore'));
    watch(cssCoreFiles, series('cssCore'));
    watch(cssScheduleFiles, series('cssSchedule'));
    watch(cssReportsFiles, series('cssReports'));
    watch(cssTabulatorLightFiles, series('cssTabulatorLight'));
    watch(cssTabulatorDarkFiles, series('cssTabulatorDark'));
    watch(cssMeetingResultsFiles, series('cssMeetingResults'));
    watch(templatesMeetingResultsFiles, series('templatesMeetingResults'));
    watch(fontsMeetingResultsFiles, series('fontsMeetingResults'));
});
