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
                    <button id="add-volunteer" class="btn btn-sm btn-primary" type="button" onclick="addVolunteers();""><?php echo $GLOBALS['add_volunteer']?></button>
                    <button id="save-volunteers" class="btn btn-sm btn-success" type="button" onclick="saveVolunteers();"><?php echo $GLOBALS['save_volunteers']?></button>
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
                        <h5 class="modal-title">Add Shift <span id="shiftVolunteerName"></span></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group form-row form-inline">
                            Day:
                            <select class="form-control form-control-sm day_of_the_week" id="day_of_the_week">
                                <option value="1">Sunday</option>
                                <option value="2">Monday</option>
                                <option value="3">Tuesday</option>
                                <option value="4">Wednesday</option>
                                <option value="5">Thursday</option>
                                <option value="6">Friday</option>
                                <option value="7">Saturday</option>
                            </select>
                        </div>
                        <div class="form-group form-row form-inline">
                            Time Zone:
                            <select class="form-control form-control-sm time_zone_selector" id="time_zone">
                                <?php
                                foreach (getTimezoneList() as $tzItem) { ?>
                                    <option value="<?php echo $tzItem?>"><?php echo $tzItem; ?></option>
                                <?php } ?>
                            </select>
                        </div>
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
            <button class="btn btn-sm btn-info volunteerCardBodyToggleButton" type="button" onclick="toggleCardDetails(this);return false;">+</button>
            <span id="volunteerSequence" class="float-right"></span>
        </div>
        <div class="card-body volunteerCardBody collapse">
            Phone Number: <input type="text" id="volunteer_phone_number" name="volunteer_phone_number">
            <table id="volunteer_schedule" class="table table-striped table-bordered">
                <tr><th>Shifts <button class="btn btn-sm btn-info" onclick="addShift(this);return false;"><?php echo $GLOBALS['add_shift']?></button></th></tr>
                <tr>
                    <td>
                        <div class="card-deck" id="shiftsCards"></div>
                    </td>
                </tr>
            </table>
            <input class="day_of_the_week_field" type="text" name="volunteer_shift_schedule" id="volunteer_shift_schedule" size="1"/>
        </div>
        <div class="card-footer bg-transparent">
            <div id="volunteerCardFooter" class="float-right">
                <div class="form-check form-check-inline">
                    <input type="checkbox" class="form-check-input" name="volunteer_enabled" id="volunteer_enabled" value="false" onclick="checkboxStatusToggle(this)">
                    <label class="form-check-label" for="volunteer_enabled">Enabled</label>
                </div>
                <button class="btn btn-sm btn-danger" type="button" onclick="removeVolunteer(this);return false;"><?php echo $GLOBALS['remove']?></button>
            </div>
        </div>
    </form>
</div>
<div class="card text-white bg-secondary mb-3 shiftCard" id="shiftCardTemplate" style="max-width: 15rem; display:none;">
    <div class="card-header">
        <div id="shiftDay"></div>
    </div>
    <div class="card-body">
        <div class="card-text-sm" id="shiftInfo"></div>
    </div>
    <div class="card-footer">
        <div id="shiftRemove" class="float-right">
            <button class="btn btn-sm btn-danger" type="button" onclick="removeShift(this);return false;"><?php echo $GLOBALS['remove']?></button>
        </div>
    </div>
</div>
<script type="text/javascript">$(function(){volunteerPage()})</script>
