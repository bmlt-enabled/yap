<!DOCTYPE html>
<html lang="en">
<head>
    <title>Meetings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href='https://fonts.googleapis.com/css?family=Droid+Sans' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" type="text/css" href="{{ url("/public/dist/croutonjs/yap-meeting-results.min.css") }}" />
    <style type="text/css">
        #bmlt-map { display: none; }
    </style>
    <script src="{{ url("/public/dist/croutonjs/yap-meeting-results.min.js") }}"></script>
    <script type="text/javascript">
        var crouton = new Crouton({
            root_server: "{{ $rootServerUrl }}",
            theme: "sezf",
            template_path: "{{ url("/public/dist/croutonjs/templates") }}",
            has_languages: "1",
            time_format: "H:mm (h:mma) z",
            filter_tabs: 0,
            map_search: {
                latitude: {{ $latitude }},
                longitude: {{ $longitude }},
                coordinates_search: true,
                width: -100
            }
        });

        crouton.render();
    </script>
</head>
<body>
<div id="bmlt-tabs"></div>
</body>
</html>
