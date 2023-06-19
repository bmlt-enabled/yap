@include('admin.partials.nav')
<?php $_REQUEST['include_warnings'] = "1"; ?>
<div class="container">
    <div class="row">
        <div class="col-md">
            <div class="jumbotron">
                <div class="home-title">{{ $settings->word('welcome') }}, {{ $username }}...</div>
                @include('admin.partials.statusControl', ['status' => $status])
                <hr class="my-4">
                <div class="btn-group-lg">
                    <a target="_blank" class="btn btn-primary btn-md" href="https://yap.bmlt.app" role="button">{{ $settings->word('documentation') }}</a>
                    <a target="_blank" class="btn btn-primary btn-md" href="https://github.com/bmlt-enabled/yap/issues" role="button">{{ $settings->word('bugs_requests') }}</a>
                    <a target="_blank" class="btn btn-primary btn-md" href="https://github.com/bmlt-enabled/yap/blob/main/RELEASENOTES.md" role="button">{{ $settings->word('release_notes') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@include('admin.partials.footer')
<script type="text/javascript">
    $(function() {
        $("#upgrade-advisor-details").tooltip();
    })
</script>
