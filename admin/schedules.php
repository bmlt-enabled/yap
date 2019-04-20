<?php require_once 'nav.php';?>
<div class="container">
    <div class="row">
        <div class="col-md">
            <label for="service_body_id">Service Body</label>
            <select class="form-control form-control-sm" id="service_body_id">
                <option>-= Select A Service Body =-</option>
                <?php
                $helplineConfiguration = getVolunteerRoutingEnabledServiceBodies();
                sort_on_field($helplineConfiguration, 'service_body_name');
                foreach ($helplineConfiguration as $item) {?>
                    <option value="<?php echo $item->service_body_id ?>"><?php echo $item->service_body_name ?></option>
                    <?php
                }?>
            </select>
            <div id='calendar'></div>
        </div>
    </div>
</div>
<?php require_once 'footer.php';?>
<link rel='stylesheet' href='vendor/@fullcalendar/core/main.min.css' />
<link rel='stylesheet' href='vendor/@fullcalendar/daygrid/main.min.css' />
<link rel='stylesheet' href='vendor/@fullcalendar/timegrid/main.min.css' />
<link rel='stylesheet' href='vendor/@fullcalendar/list/main.min.css' />
<script src='vendor/moment/min/moment.min.js'></script>
<script src='vendor/@fullcalendar/core/main.min.js'></script>
<script src='vendor/@fullcalendar/daygrid/main.min.js'></script>
<script src='vendor/@fullcalendar/timegrid/main.min.js'></script>
<script src='vendor/@fullcalendar/list/main.min.js'></script>
<script type="text/javascript">$(function(){schedulePage()})</script>
