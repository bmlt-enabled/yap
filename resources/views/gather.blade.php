{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<Response>
    <Gather input="{{ $inputType }}" numDigits="{{ $numDigits }}" timeout="10" speechTimeout="auto" action="{{ $action }}" method="GET">
        <Say voice="{{ $voice }}" language="{{ $language }}">
            {{ $sayText }}
        </Say>
    </Gather>
</Response>
