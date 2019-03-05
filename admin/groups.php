<?php require_once 'nav.php';
$data_type = DataType::YAP_GROUP_VOLUNTEERS_V2;
?>
<div class="container">
    <input type="hidden" name="service_body_id" id="service_body_id" value="<?php echo $_REQUEST['service_body_id']?>" />
    <div class="alert alert-success" role="alert" style="display:none;" id="volunteer_saved_alert">
        Saved.
    </div>
    <label for="group_id"><?php echo word('groups')?></label>
    <form id="groupsForm">
        <select class="form-control form-control-sm dropdown_next_to_another_field" id="group_id">
            <option value="0">-= Select A Group =-</option>
            <?php
            $groups = getGroups($_REQUEST['service_body_id']);
            sort_on_field($groups, 'name');
            foreach ($groups as $item) {?>
                <option value="<?php echo $item->id ?>"><?php echo $item->name ?></option>
                <?php
            }?>
        </select>
    </form>
    <script type="text/javascript">groups=<?php echo json_encode($groups); ?></script>
    <button class="btn btn-sm btn-primary volunteer-manage-buttons" id="addGroupButton" onclick="addGroup();">Add</button>
    <button class="btn btn-sm btn-secondary volunteer-manage-buttons" id="editGroupButton" onclick="editGroup();" style="display:none;">Edit</button>
    <button class="btn btm-sm btn-warning volunteer-manage-buttons" id="deleteGroupButton" onclick="deleteGroup();" style="display:none;">Delete</button>
    <?php require_once '_includes/volunteers_control.php';?>
</div>
<div class="modal fade" id="addGroupDialog" tabindex="-1" role="dialog" aria-labelledby="addGroupDialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header" id="groupEditorHeader"></div>
            <div class="modal-body">
                <div class="form-group form-row">
                    <form id="groupEditor">
                        Name (required):
                        <input class="form-control form-control-sm" type="text" id="group_name" name="group_name">

                        <?php
                            $shareable_service_bodies = getServiceBodies();
                            sort_on_field($shareable_service_bodies, 'name')
                        ?>
                        Shared With Service Bodies (optional): <select size="10" multiple class="form-control form-control-sm" name="group_shared_service_bodies" id="group_shared_service_bodies">
                            <?php
                            foreach ($shareable_service_bodies as $service_body) { ?>
                                <option value="<?php echo $service_body->id; ?>"><?php echo $service_body->name; ?></option>
                            <?php } ?>
                        </select>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <div id="group_dialog_message"></div>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmGroup(this)">OK</button>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">$(function(){groupsPage()})</script>
