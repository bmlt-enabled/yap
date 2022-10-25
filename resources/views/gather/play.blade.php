{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<Response>
    <Gather language="{{ $gatherLanguage }}" input="{{ $inputType }}" finishOnKey="#" timeout="{{ $timeout }}" action="{{ $action }}" method="GET">
        <Play>
            {{ $playUrl }}
        </Play>
    </Gather>
</Response>
