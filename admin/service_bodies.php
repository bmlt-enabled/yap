<?php include_once 'nav.php';
$service_bodies = getServiceBodyDetailForUser();
sort_on_field($service_bodies, 'name');
?>
<div class="container">
    <div class="alert alert-success" role="alert" style="display:none;" id="service_body_saved_alert">
        Saved.
    </div>
    <div class="modal fade" id="spinnerDialog" tabindex="-1" role="dialog" aria-labelledby="spinnerDialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div>
                        Retrieving data...
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md">
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Service Body</th>
                        <th scole="col">Helpline</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($service_bodies as $service_body) { ?>
                    <tr>
                        <td><?php echo $service_body->id ?></td>
                        <td><?php echo $service_body->name ?></td>
                        <td><?php echo isset($service_body->helpline) ? $service_body->helpline : "" ?></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="serviceBodyConfigure(<?php echo $service_body->id ?>);">Configure</button>
                            <div class="modal fade" id="serviceBodyConfiguration_<?php echo $service_body->id ?>" tabindex="-1" role="dialog" aria-labelledby="configureShiftDialog" aria-hidden="true">
                                <input type="hidden" name="helpline_data_id" class="helpline_data_id" value="0" />
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Configure <?php echo $service_body->name ?></h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body serviceBodyConfigurationItems">
                                            <form id="serviceBodyConfigurationForm">
                                                <div class="form-check form-check-inline">
                                                    <input type="checkbox" class="form-check-input" name="volunteer_routing_enabled" id="volunteer_routing_enabled" value="false" onclick="checkboxStatusToggle(this)">
                                                    <label class="form-check-label" for="volunteer_routing_enabled">Volunteer Routing</label>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                            <button class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
                                            <button class="btn btn-sm btn-primary" onclick="saveServiceBodyConfig(<?php echo $service_body->id ?>)">Save Changes</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include_once 'footer.php';?>
