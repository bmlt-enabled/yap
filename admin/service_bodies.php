<?php require_once 'nav.php';
$service_bodies = getServiceBodyDetailForUser();
sort_on_field($service_bodies, 'name');
?>
<div class="container">
    <div class="alert" role="alert" style="display:none;" id="service_body_saved_alert">
        Saved.
    </div>
    <div class="row">
        <div class="col-md">
            <table id="service-bodies-table" class="table table-striped table-borderless">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col"><?php echo word("service_body")?></th>
                        <th scope="col"><?php echo word("helpline")?></th>
                        <th scope="col"><?php echo word("action")?></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($service_bodies as $service_body) { ?>
                    <tr>
                        <td><?php echo $service_body->id ?></td>
                        <td><?php echo $service_body->name ?></td>
                        <td><?php echo isset($service_body->helpline) ? $service_body->helpline : "" ?></td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="openServiceBodyCallHandling(<?php echo $service_body->id ?>);"><?php echo word('call_handling')?></button>
                            <button class="btn btn-sm btn-success" onclick="openServiceBodyConfigure(<?php echo $service_body->id ?>);"><?php echo word('configure')?></button>
                            <button class="btn btn-sm btn-warning" onclick="location.href='voicemail.php?service_body_id=<?php echo $service_body->id ?>';"><?php echo word('voicemail')?></button>
                            <div class="modal fade" id="serviceBodyCallHandling_<?php echo $service_body->id ?>" tabindex="-1" role="dialog" aria-labelledby="configureShiftDialog" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo sprintf("%s (%s)", word('call_handling'), $service_body->name); ?></h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body serviceBodyCallHandlingItems">
                                            <form id="serviceBodyCallHandlingForm" class="serviceBodyCallHandlingForm">
                                                <div id="serviceBodyCallHandlingValidation"></div>
                                                Helpline Routing:
                                                <select class="form-control form-control-sm" name="volunteer_routing" id="volunteer_routing">
                                                    <option value="helpline_field">Helpline Field Number</option>
                                                    <option value="volunteers">Volunteers</option>
                                                    <option value="volunteers_redirect">Volunteers Redirect</option>
                                                    <option value="volunteers_and_sms">Volunteers and SMS</option>
                                                </select>

                                                <div class="service_bodies_field_container">
                                                    <label for="volunteers_redirect_id">Volunteers Redirect Id:</label>
                                                    <input class="form-control form-control-sm" type="text" name="volunteers_redirect_id" id="volunteers_redirect_id"
                                                           data-volunteer_routing="volunteers_redirect">
                                                </div>

                                                <div class="service_bodies_field_container">
                                                    <label for="forced_caller_id">Forced Caller Id (Must Be A Verified Twilio Number):</label>
                                                    <input class="form-control form-control-sm" type="text" name="forced_caller_id" id="forced_caller_id"
                                                           data-volunteer_routing="helpline_field,volunteers,volunteers_and_sms">
                                                </div>

                                                <div class="service_bodies_field_container">
                                                    <label for="call_timeout">Call Timeout (default: 20 seconds):</label>
                                                    <input class="form-control form-control-sm" type="text" name="call_timeout" id="call_timeout"
                                                           data-volunteer_routing="volunteers,volunteers_and_sms">
                                                </div>

                                                <div class="service_bodies_field_container">
                                                    <label for="gender_routing">Gender Routing:</label>
                                                    <select class="form-control form-control-sm" name="gender_routing" id="gender_routing"
                                                            data-volunteer_routing="volunteers,volunteers_and_sms">
                                                        <option value="0">Disabled</option>
                                                        <option value="1">Enabled</option>
                                                    </select>
                                                </div>

                                                <div class="service_bodies_field_container">
                                                    <label for="call_strategy">Call Strategy:</label>
                                                    <select class="form-control form-control-sm" name="call_strategy" id="call_strategy"
                                                            data-volunteer_routing="volunteers,volunteers_and_sms">
                                                        <option value="0">Linear Loop Forever</option>
                                                        <option value="1">Linear Cycle Once, Then Voicemail</option>
                                                        <option value="2">Random Loop Forever</option>
                                                        <option value="3">Blasting, Then Voicemail</option>
                                                        <option value="4">Random Loop Once, Then Voicemail</option>
                                                    </select>
                                                </div>

                                                <div class="service_bodies_field_container">
                                                    <label for="volunteer_sms_notification">Inbound call SMS to Volunteer Options:</label>
                                                    <select class="form-control form-control-sm" name="volunteer_sms_notification" id="volunteer_sms_notification"
                                                            data-volunteer_routing="volunteers,volunteers_and_sms">
                                                        <option value="no_sms">No SMS</option>
                                                        <option value="send_sms">Send SMS to Volunteer</option>
                                                    </select>
                                                </div>

                                                <div class="service_bodies_field_container">
                                                    <label for="sms_strategy">SMS Strategy:</label>
                                                    <select class="form-control form-control-sm" name="sms_strategy" id="sms_strategy"
                                                            data-volunteer_routing="volunteers_and_sms">
                                                        <option value="2">Random</option>
                                                        <option value="3">Blast</option>
                                                    </select>
                                                </div>

                                                <div class="service_bodies_field_container">
                                                    <label for="primary_contact">Primary Contact Number (typically the Chair/Coordinator):</label>
                                                    <input class="form-control form-control-sm" type="text" name="primary_contact" id="primary_contact"
                                                           data-volunteer_routing="volunteers,volunteers_and_sms" data-call_strategy="1,3,4">
                                                </div>

                                                <div class="service_bodies_field_container">
                                                    <label for="primary_contact_email">Primary Contact Email (typically the Chair/Coordinator):</label>
                                                    <input class="form-control form-control-sm" type="text" name="primary_contact_email" id="primary_contact_email"
                                                           data-volunteer_routing="volunteers,volunteers_and_sms" data-call_strategy="1,3,4">
                                                </div>

                                                <div class="service_bodies_field_container">
                                                    <label for="moh">Music On Hold (<a target="_blank" href="https://github.com/bmlt-enabled/yap/wiki/Music-On-Hold">more</a>):</label>
                                                    <input class="form-control form-control-sm" type="text" name="moh" id="moh"
                                                           data-volunteer_routing="volunteers,volunteers_and_sms">
                                                </div>

                                                <div class="service_bodies_field_container">
                                                    <label for="override_en_US_greeting">Recorded Greeting (URL to any MP3): (<a href="javascript:openUrl(this, 'override_en_US_greeting');">Play</a>)</label>
                                                    <input class="form-control form-control-sm" type="text" name="override_en_US_greeting" id="override_en_US_greeting"
                                                           data-volunteer_routing="helpline_field,volunteers,volunteers_and_sms">
                                                </div>

                                                <div class="service_bodies_field_container">
                                                    <label for="override_en_US_voicemail_greeting">Voice Mail Greeting (URL to any MP3): (<a href="javascript:openUrl(this, 'override_en_US_voicemail_greeting');">Play</a>)</label>
                                                    <input class="form-control form-control-sm" type="text" name="override_en_US_voicemail_greeting" id="override_en_US_voicemail_greeting"
                                                           data-volunteer_routing="volunteers,volunteers_and_sms" data-call_strategy="1,3,4">
                                                </div>
                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                            <button class="btn btn-sm btn-secondary" data-dismiss="modal">Close</button>
                                            <button class="btn btn-sm btn-primary" onclick="saveServiceBodyCallHandling(<?php echo $service_body->id ?>)">Save Changes</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal fade" id="serviceBodyConfiguration_<?php echo $service_body->id ?>" tabindex="-1" role="dialog" aria-labelledby="configureShiftDialog" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title"><?php echo sprintf("%s (%s)", word('configure'), $service_body->name) ?></h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body serviceBodyConfigurationItems">
                                            <div>For more information on using this, see the documentation on <a href="https://github.com/bmlt-enabled/yap/wiki/Configuration-Precedence" target="_blank">Configuration Precedence</a>.</div>
                                            <form id="serviceBodyConfigurationForm" class="serviceBodyConfigurationForm">
                                                <select class="form-control form-control-sm" name="serviceBodyConfigurationFields" id="serviceBodyConfigurationFields">
                                                    <?php foreach ($settings_allowlist as $setting => $value) {
                                                        if (gettype($value["default"]) === "array") {?>
                                                            <option data-default="<?php echo str_replace("\"", "'", json_encode($value["default"])) ?>" value="<?php echo $setting?>"><?php echo $setting?></option>
                                                        <?php } else { ?>
                                                            <option data-default="<?php echo get_str_val($value["default"]) ?>" value="<?php echo $setting?>"><?php echo $setting?></option>
                                                        <?php } ?>
                                                    <?php } ?>
                                                </select>
                                                <button type="button" class="btn btn-sm btn-primary addFieldButton" onclick="addServiceBodyButtonClick(<?php echo $service_body->id ?>)">+</button>
                                                <div id="serviceBodyFieldsPlaceholder"></div>
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
<?php require_once 'footer.php';?>
