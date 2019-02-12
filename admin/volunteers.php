<?php require_once 'nav.php';
$data_type = DataType::YAP_VOLUNTEERS_V2;
?>
<div class="container">
    <div class="alert alert-success" role="alert" style="display:none;" id="volunteer_saved_alert">
        Saved.
    </div>
    <label for="service_body_id"><?php echo word('service_body')?></label>
    <select class="form-control form-control-sm" id="service_body_id">
        <option>-= Select A Service Body =-</option>
        <?php
        $helplineConfiguration = getVolunteerRoutingEnabledServiceBodies();
        foreach ($helplineConfiguration as $item) {?>
            <option value="<?php echo $item->service_body_id ?>"><?php echo $item->service_body_name ?></option>
            <?php
        }?>
    </select>
    <?php require_once '_includes/volunteers_control.php';?>
</div>
<script type="text/javascript">$(function(){volunteerPage()})</script>
