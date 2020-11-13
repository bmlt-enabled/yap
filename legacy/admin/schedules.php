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
                    <option value="<?php echo $item->service_body_id ?>"><?php echo $item->service_body_name ?> (<?php echo $item->service_body_id ?>) / <?php echo $item->service_body_parent_name ?> (<?php echo $item->service_body_parent_id ?>)</option>
                    <?php
                }?>
            </select>
            <div id='calendar'></div>
        </div>
    </div>
</div>
<?php require_once 'footer.php';?>
<link rel='stylesheet' href='dist/css/yap-schedule.min.css' />
<script src='dist/js/yap-schedule.min.js'></script>
<script type="text/javascript">$(function(){schedulePage()})</script>
