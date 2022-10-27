{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<Response>
    @if(isset($sayText))
    <Say voice="{{ $voice }}" language="{{ $language }}">{{ $sayText }}</Say>
    @endif
    <Dial>
        <Number sendDigits="{{ $extension }}">{{ $phoneNumber }}</Number>
    </Dial>
</Response>
