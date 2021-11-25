<?php require_once 'nav.php';
$data_type = DataType::YAP_GROUP_VOLUNTEERS_V2;
?>
<div class="container">
    <div class="alert alert-success" role="alert" style="display:none;" id="volunteer_saved_alert">
        Saved.
    </div>
    <form id="serviceBodyForm" method="POST" action="groups.php">
        <label for="service_body_id"><?php echo word('service_bodies')?></label>
        <select class="form-control form-control-sm" id="service_body_id" name="service_body_id" onchange="onGroupServiceBodyChange(this)">
            <option value="0">-= Select A Service Body =-</option>
            <?php
            $serviceBodies = getServiceBodiesForUser();
            sort_on_field($serviceBodies, 'name');
            foreach ($serviceBodies as $item) {?>
                <option value="<?php echo $item->id ?>"><?php echo $item->name ?> (<?php echo $item->id ?>) / <?php echo $item->parent_name ?> (<?php echo $item->parent_id ?>)</option>
                <?php
            }?>
        </select>
    </form>

    <form id="groupsForm" style="display: none">
        <label for="group_id"><?php echo word('groups')?></label>
        <select class="form-control form-control-sm dropdown_next_to_another_field" id="group_id"></select>
        <button class="btn btn-sm btn-primary volunteer-manage-buttons" id="addGroupButton" onclick="return addGroup();"><?php echo word('create') ?></button>
        <button class="btn btn-sm btn-warning volunteer-manage-buttons" id="editGroupButton" onclick="return editGroup();"
                style="display:none;"><?php echo word('edit') ?>
        </button>
        <button class="btn btm-sm btn-danger volunteer-manage-buttons" id="deleteGroupButton" onclick="return deleteGroup();"
                style="display:none;"><?php echo word('delete') ?>
        </button>
    </form>
    <?php require_once 'volunteers_control.php';?>
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
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="return confirmGroup(this)">OK</button>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">$(function(){groupsPage()})</script>
