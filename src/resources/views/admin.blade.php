<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Yap</title>
    <link rel="icon" type="image/x-icon" href="{{ asset("public/favicon.ico") }}">
</head>
<body>
<div id="root"></div>
<script>const rootUrl = "{{ $rootUrl }}";const baseUrl = "{{ $baseUrl }}";</script>
<script src="{{ asset("public" . mix('js/index.js'), request()->secure()) }}"></script>
</body>
</html>
