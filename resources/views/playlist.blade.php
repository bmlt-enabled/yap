{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<Response>
    @foreach (explode(",", $items) as $item)
        <Play>{{ $item }}</Play>
    @endforeach
    <Redirect>playlist.php?items={{ $items }}</Redirect>
</Response>
