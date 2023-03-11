<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ env('APP_NAME') }}</title>
</head>
<body>
<div id="root"></div>
<script>const rootUrl = "{{ $rootUrl }}";const baseUrl = "{{ $baseUrl }}";</script>
<script src="{{ url('public' . mix('js/index.js')) }}"></script>
</body>
</html>
