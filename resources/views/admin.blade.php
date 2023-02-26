<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ env('APP_NAME') }}</title>
    <link href="{{ url('public' . mix('css/app.css')) }}" rel="stylesheet">
</head>
<body>
<div id="app"></div>
<script src="{{ url('public' . mix('js/app.js')) }}"></script>
</body>
</html>
