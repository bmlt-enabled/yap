<div class="row">
    <div id="newVolunteerDialog" class="col-sm" style="display:none;">
        <div class="form-group">
            <button id="add-volunteer" class="btn btn-sm btn-primary volunteer-manage-buttons" type="button" onclick="includeVolunteers();">{{ $settings->word('add_volunteer') }}</button>
            <button id="save-volunteers" class="btn btn-sm btn-success volunteer-manage-buttons" type="button" onclick="saveVolunteers('<?php echo $dataType?>');">{{ $settings->word('save_volunteers') }}</button>
            <button id="include-group" class="btn btn-sm btn-warning volunteer-manage-buttons" type="button" onclick="showGroupsModal();" style="display: none;">{{ $settings->word('include_group') }}</button>
            <button id="volunteers-download-list-csv" class="btn btn-sm btn-secondary volunteer-manage-buttons" type="button" style="display: none;">Volunteer List (CSV)</button>
            <button id="volunteers-download-list-json" class="btn btn-sm btn-secondary volunteer-manage-buttons" type="button" style="display: none;">Volunteer List (JSON)</button>
        </div>
    </div>
</div>
<div class="modal fade" id="selectTimeZoneDialog" tabindex="-1" role="dialog" aria-labelledby="selectTimeZoneDialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                Select Time Zone For 24/7 Shifts
            </div>
            <div class="modal-body">
                <div class="form-group form-row">
                    Time Zone:
                    <select class="form-control form-control-sm time_zone_selector" id="time_zone">
                        @foreach ($settings->getTimezoneList() as $tzItem)
                            <option value="{{ $tzItem }}">{{ $tzItem }}</option>
                        @endforeach
                    </select>

                    Type:
                    <select class="form-control form-control-sm type_selector" id="shift_type">
                        <option value="PHONE" selected>Phone</option>
                        <option value="SMS">SMS</option>
                        <option value="PHONE,SMS">Phone & SMS</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal" onclick="$('#selectTimeZoneDialog').modal('toggle');">Close</button>
                <button type="button" class="btn btn-primary" onclick="selectTimeZoneFor247Shifts(this)">Select</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="selectRepeatShiftDialog" tabindex="-1" role="dialog" aria-labelledby="selectRepeatShiftDialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Shift: <span id="shiftVolunteerName"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="selectRepeatShiftDialogValidation"></div>
                <div class="form-group form-row form-inline">
                    Time Zone:
                    <select class="form-control form-control-sm time_zone_selector" id="time_zone">
                        @foreach ($settings->getTimezoneList() as $tzItem)
                        <option value="{{ $tzItem }}">{{ $tzItem }}</option>
                        @endforeach
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
                <div class="form-group form-row form-inline">
                    Type:
                    <select class="form-control form-control-sm type_selector" id="shift_type">
                        <option value="PHONE" selected>Phone</option>
                        <option value="SMS">SMS</option>
                        <option value="PHONE,SMS">Phone + SMS</option>
                    </select>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal" onclick="$('#selectRepeatShiftDialog').modal('toggle');">Close</button>
                <button type="button" class="btn btn-warning" onclick="save7DayShifts(this)">Save changes</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="selectShiftDialog" tabindex="-1" role="dialog" aria-labelledby="selectShiftDialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Shift: <span id="shiftVolunteerName"></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="selectShiftDialogValidation"></div>
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
                        @foreach ($settings->getTimezoneList() as $tzItem)
                            <option value="{{ $tzItem }}">{{ $tzItem }}</option>
                        @endforeach
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
                <div class="form-group form-row form-inline">
                    Type:
                    <select class="form-control form-control-sm type_selector" id="shift_type">
                        <option value="PHONE" selected>Phone</option>
                        <option value="SMS">SMS</option>
                        <option value="PHONE,SMS">Phone + SMS</option>
                    </select>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal"  onclick="$('#selectShiftDialog').modal('toggle');">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveShift(this)">Save changes</button>
            </div>
        </div>
    </div>
</div>
<div id="volunteerCards" class="list-group-flush" class="row"></div>
@include('admin.partials.footer')
<div class="card volunteerCard border-dark" id="volunteerCardTemplate" style="display:none;">
    <form id="volunteersForm">
        <div class="card-header">
            <div class="form-group row">
                <div class="volunteer-sort-icon"></div>
                <div class="volunteer-name-label">
                    Volunteer Name:
                </div>
                <div class="col-xs-4 volunteer-name-text" style="float: left;">
                    <input type="text" class="form-control form-control-sm volunteerName" id="volunteer_name" name="volunteer_name">
                </div>
                <div class="col-xs-4 expand-button">
                    <button class="btn btn-sm btn-outline-info volunteerCardBodyToggleButton" type="button" onclick="toggleCardDetails(this);return false;">+</button>
                </div>
                <div class="col">
                    <div id="volunteerSequence" class="float-right"></div>
                </div>
            </div>
        </div>
        <div class="card-body volunteerCardBody collapse">
            <div class="form-group form-row form-inline">
                Phone Number:  <input type="text" class="form-control form-control-sm volunteerPhoneNumber" id="volunteer_phone_number" name="volunteer_phone_number">
            </div>
            <div class="form-group form-row form-inline">
                Gender: <select class="form-control form-control-sm" name="volunteer_gender" id="volunteer_gender">
                    <option value="0">Unassigned</option>
                    <option value="1">Male</option>
                    <option value="2">Female</option>
                </select>
                Responder: <select class="form-control form-control-sm" name="volunteer_responder" id="volunteer_responder">
                    <option value="0">Unassigned</option>
                    <option value="1">Enabled</option>
                </select>
                @if ($settings->has('language_selections'))
                Languages: <select multiple class="form-control form-control-sm" name="volunteer_language" id="volunteer_language">
                    @foreach ($settings->languageSelections() as $key => $available_language) {
                    <option value="<?php echo $available_language; ?>"><?php echo $available_language; ?></option>
                    @endforeach
                @endif
                </select>
            </div>
            <table id="volunteer_schedule" class="table table-striped table-borderless">
                <tr>
                    <th>
                        Shifts
                        <button class="btn btn-sm btn-primary" onclick="addShift(this);return false;">{{ $settings->word('add_shift') }}</button>
                        <button class="btn btn-sm btn-primary" onclick="add7DayShifts(this);return false;">{{ $settings->word('add_7_day_shifts') }}</button>
                        <button class="btn btn-sm btn-primary" onclick="add24by7Shifts(this);return false;">{{ $settings->word('add_24by7_shifts') }}</button>
                        <button class="btn btn-sm btn-danger" onclick="removeAllShifts(this);return false;">{{ $settings->word('remove_all_shifts') }}</button>
                    </th>
                </tr>
                <tr>
                    <td>
                        <div class="card-deck">
                            <div class="card-columns" id="shiftsCards"></div>
                        </div>
                    </td>
                </tr>
            </table>
            <input class="day_of_the_week_field" type="text" name="volunteer_shift_schedule" id="volunteer_shift_schedule" size="1"/>
            <p>Notes</p>
            <textarea name="volunteer_notes" id="volunteer_notes"></textarea>
        </div>
        <div class="card-footer bg-transparent">
            <div id="volunteerCardFooter" class="float-right">
                <div class="form-check form-check-inline">
                    <input type="checkbox" class="form-check-input" name="volunteer_enabled" id="volunteer_enabled" value="false" onclick="checkboxStatusToggle(this)">
                    <label class="form-check-label" for="volunteer_enabled">Enabled</label>
                </div>
                <button class="btn btn-sm btn-danger" type="button" onclick="removeCard(this);return false;">{{ $settings->word('remove') }}</button>
            </div>
        </div>
    </form>
</div>
<div class="card text-white bg-secondary mb-3 shiftCard" id="shiftCardTemplate">
    <div class="card-header">
        <div id="shiftDay"></div>
    </div>
    <div class="card-body">
        <div class="card-text-sm" id="shiftInfo"></div>
    </div>
    <div id="shiftCardFooter" class="card-footer">
        <div id="shiftRemove" class="float-right">
            <button class="btn btn-sm btn-primary" type="button" onclick="editShift(this);return false;">{{ $settings->word('edit') }}</button>
            <button class="btn btn-sm btn-danger" type="button" onclick="removeShift(this);return false;">{{ $settings->word('remove') }}</button>
        </div>
    </div>
</div>
