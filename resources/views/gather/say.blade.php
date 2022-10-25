{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<Response>
    <Gather language="{{ $gatherLanguage }}" input="{{ $inputType }}" @if(isset($hints))hints="{{ $hints }}"@endif @if(isset($numDigits))numDigits="{{ $numDigits }}"@endif timeout="10" speechTimeout="auto" action="{{ $action }}" method="GET">
        <Say voice="{{ $voice }}" language="{{ $language }}">
            {{ $sayText }}
        </Say>
    </Gather>
</Response>
