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
        <div class="modal fade" id="selectShiftDialog" tabindex="-1" role="dialog" aria-labelledby="selectShiftDialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Select Shift for <span id="shiftDayTitle"></span></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group form-row form-inline">
                            Start Time:
                            <select class="form-control form-control-sm hours_field" id="start_time_hour"></select> :
                            <select class="form-control form-control-sm minutes_field" id="start_time_minute"></select>
                            <select class="form-control form-control-sm division_field" id="start_time_division">
                                <option value="AM">AM</option>
                                <option value="PM">PM</option>
                            </select>
                        </div>
                        <div class="form-group form-row form-inline">
                            End Time:
                            <select class="form-control form-control-sm hours_field" id="end_time_hour"></select> :
                            <select class="form-control form-control-sm minutes_field" id="end_time_minute"></select>
                            <select class="form-control form-control-sm division_field" id="end_time_division">
                                <option value="AM">AM</option>
                                <option value="PM">PM</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" onclick="saveShift(this)">Save changes</button>
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
            <table id="volunteer_schedule" class="table table-striped table-bordered">
            <?php
            $x = 0;
            foreach($GLOBALS['days_of_the_week'] as $day) {
                $x++; ?>
                <tr>
                    <th scope="col"><?php echo $day ?></th>
                    <td scope="col">
                        <button class="btn btn-sm btn-info" data-shiftid="day_<?php echo $x?>" onclick="selectShift(this, '<?php echo $day ?>');return false;"><?php echo $GLOBALS['add_shift']?></button>
                        <input class="day_of_the_week_field" type="text" name="day_<?php echo $x?>" id="day_<?php echo $x?>"/>
                    </td>
                </tr>
            <?php } ?>
            </table>
        </div>
        <div class="card-footer bg-transparent">
            <div id="volunteerCardFooter" class="float-right">
                <div class="form-check form-check-inline">
                    <input type="checkbox" class="form-check-input" name="volunteer_enabled" id="volunteer_enabled" value="false" onclick="volunteerStatusToggle(this)">
                    <label class="form-check-label" for="volunteer_enabled">Enabled</label>
                </div>
                <button class="btn btn-sm btn-danger" type="button" onclick="removeVolunteer(this);return false;"><?php echo $GLOBALS['remove']?></button>
            </div>
        </div>
    </form>
</div>
