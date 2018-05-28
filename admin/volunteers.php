<?php include_once 'nav.php'; ?>
    <div id="volunteers" class="container">
        <input type="hidden" name="helpline_data_id" id="helpline_data_id" value="0" />
        <div class="alert alert-success" role="alert" style="display:none;" id="volunteer_saved_alert">
            Saved.
        </div>
        <label for="service_body_id">Service Body</label>
        <select class="form-control form-control-sm" id="service_body_id">
            <option value="0">-= Select a Service Body=-</option>
            <option value="43">North Carolina Region</option>
        </select>
        <div class="row">
            <div id="newVolunteerDialog" class="col-sm" style="display:none;">
                <div class="form-group">
                    <input type="text" name="new_volunteer_name" class="form-control" id="new_volunteer_name" aria-describedby="volunteerNameHelp" placeholder="<?php echo $GLOBALS['add_add_new_volunteer']?>">
                    <button id="add-volunteer" class="btn btn-sm btn-primary" type="button"><?php echo $GLOBALS['add_volunteer']?></button>
                    <button id="save-volunteers" class="btn btn-sm btn-success" type="button"><?php echo $GLOBALS['save_volunteers']?></button>
                </div>
            </div>
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
        <div id="volunteerCards" class="list-group-flush" class="row"></div>
    </div>
<?php include_once 'footer.php';?>
<div class="card volunteerCard border-dark" id="volunteerCardTemplate" style="display:none;">
    <form id="volunteersForm">
        <div class="card-header">
            Volunteer Name: <input type="text" id="volunteer_name" name="volunteer_name">
            <span id="volunteerSequence" class="float-right"></span>
        </div>
        <div class="card-body">
            Phone Number: <input type="text" id="volunteer_phone_number" name="volunteer_phone_number">
            <table id="volunteer_schedule" class="table table-striped table-bordered"
                <thead>
                    <tr>
                        <th scope="col">Sun</th>
                        <th scope="col">Mon</th>
                        <th scope="col">Tue</th>
                        <th scope="col">Wed</th>
                        <th scope="col">Thu</th>
                        <th scope="col">Fri</th>
                        <th scope="col">Sat</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" id="day_1" name="day_1" /></td>
                        <td><input type="text" id="day_2" name="day_2" /></td>
                        <td><input type="text" id="day_3" name="day_3" /></td>
                        <td><input type="text" id="day_4" name="day_4" /></td>
                        <td><input type="text" id="day_5" name="day_5" /></td>
                        <td><input type="text" id="day_6" name="day_6" /></td>
                        <td><input type="text" id="day_7" name="day_7" /></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-transparent">
            <div id="volunteerCardFooter" class="float-right">
                <div class="form-check form-check-inline">
                    <input type="checkbox" class="form-check-input" name="volunteer_enabled" id="volunteer_enabled" value="false" onclick="volunteerStatusToggle(this)">
                    <label class="form-check-label" for="volunteer_enabled">Enabled</label>
                </div>
                <button class="btn btn-sm btn-danger" type="button" onclick="removeVolunteer(this)"><?php echo $GLOBALS['remove']?></button>
            </div>
        </div>
    </form>
</div>
