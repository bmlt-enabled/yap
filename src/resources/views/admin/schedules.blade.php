@include('admin.partials.nav')
<div class="container">
    <div class="row">
        <div class="col-md">
            <label for="service_body_id">Service Body</label>
            <select class="form-control form-control-sm" id="service_body_id">
                <option>-= Select A Service Body =-</option>
                @foreach ($serviceBodiesEnabledForRouting as $item)
                <option value="{{ $item->service_body_id }}">{{ $item->service_body_name }} ({{ $item->service_body_id }}) / {{ $item->service_body_parent_name }} ({{ $item->service_body_parent_id }})</option>
                @endforeach
            </select>
            <div id='calendar'></div>
        </div>
    </div>
</div>
@include('admin.partials.footer')
<link rel='stylesheet' href='<?php echo url("dist/css/yap-schedule.min.css")?>' />
<script src='<?php echo url("dist/js/yap-schedule.min.js")?>'></script>
<script type="text/javascript">$(function(){schedulePage()})</script>
