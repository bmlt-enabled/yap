<?php include_once 'nav.php'; ?>
    <div id="volunteers" class="container">
        <div class="row">
            <div class="col-sm">
                <div class="alert alert-success" role="alert" style="display:none;" id="volunteer_saved_alert">
                    Saved.
                </div>
                <div class="form-group">
                    <label for="new_volunteer_name"><?php echo $GLOBALS['add_add_new_volunteer']?></label>
                    <input type="text" name="new_volunteer_name" class="form-control" id="new_volunteer_name" aria-describedby="volunteerNameHelp" placeholder="<?php echo $GLOBALS['volunteer_name']?>">
                    <button id="add-volunteer" class="btn btn-sm btn-primary" type="button"><?php echo $GLOBALS['add_volunteer']?></button>
                    <button id="save-volunteers" class="btn btn-sm btn-success" type="button"><?php echo $GLOBALS['save_volunteers']?></button>
                </div>
            </div>
        </div>
        <input type="hidden" name="helpline_data_id" id="helpline_data_id" value="0" />
        <label for="service_body_id">Service Body</label>
        <select class="form-control" id="service_body_id">
            <option value="0">-= Select a Service Body=-</option>
            <option value="43">North Carolina Region</option>
        </select>
        <form id="volunteersForm">
            <div id="volunteerCards" class="list-group-flush" class="row"></div>
        </form>
    </div>
<?php include_once 'footer.php';?>
<div class="card volunteerCard" id="volunteerCardTemplate" style="display:none;">
    <div class="card-header">Volunteer Name: <input type="text" id="volunteer_name" name="volunteer_name"></div>
    <div class="card-body">

    </div>
    <div class="card-footer bg-transparent">
        <button class="btn btn-sm btn-danger" type="button" onclick="removeVolunteer(this)"><?php echo $GLOBALS['remove']?></button>
    </div>
</div>
