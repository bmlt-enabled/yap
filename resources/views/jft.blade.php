{!! '<'.'?xml version="1.0" encoding="UTF-8" ?>' !!}
<Response>
    @foreach ($jft_array as $item)
        @if (trim($item) !== "")
            <Say voice="{{ $voice }}" language="{{ $language }}">{{ str_replace("&nbsp;", " ", $item) }}</Say>
        @endif
    @endforeach
    <Hangup/>
</Response>
