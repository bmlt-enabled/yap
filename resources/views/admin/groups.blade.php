@include('admin.partials.nav')
<div class="container">
    <div class="alert alert-success" role="alert" style="display:none;" id="volunteer_saved_alert">
        Saved.
    </div>
    <form id="serviceBodyForm" method="POST" action="groups">
        <label for="service_body_id">{{ $settings->word('service_bodies') }}</label>
        <select class="form-control form-control-sm" id="service_body_id" name="service_body_id" onchange="onGroupServiceBodyChange()">
            <option value="0">-= Select A Service Body =-</option>
            @foreach($serviceBodiesForUser as $item)
                <option value="{{ $item->id }}">{{ $item->name }}({{ $item->id }}) / {{ $item->parent_name }} ({{ $item->parent_id }})</option>
            @endforeach
        </select>
    </form>

    <form id="groupsForm" style="display: none">
        <label for="group_id">{{ $settings->word('groups') }}</label>
        <select class="form-control form-control-sm dropdown_next_to_another_field" id="group_id"></select>
        <button class="btn btn-sm btn-primary volunteer-manage-buttons" id="addGroupButton" onclick="return addGroup();">{{ $settings->word('create') }}</button>
        <button class="btn btn-sm btn-warning volunteer-manage-buttons" id="editGroupButton" onclick="return editGroup();"
                style="display:none;">{{ $settings->word('edit') }}
        </button>
        <button class="btn btm-sm btn-danger volunteer-manage-buttons" id="deleteGroupButton" onclick="return deleteGroup();"
                style="display:none;">{{ $settings->word('delete') }}
        </button>
    </form>
@include('admin.partials.volunteersControl', ["dataType" =>\App\Constants\DataType::YAP_GROUP_VOLUNTEERS_V2])
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
                        Shared With Service Bodies (optional): <select size="10" multiple class="form-control form-control-sm" name="group_shared_service_bodies" id="group_shared_service_bodies">
                            @foreach ($serviceBodies as $service_body) { ?>
                            <option value="<?php echo $service_body->id; ?>"><?php echo $service_body->name; ?></option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
            <div class="modal-footer">
                <div id="group_dialog_message"></div>
                <button type="button" class="btn btn-danger" data-dismiss="modal" onclick="$('#addGroupDialog').modal('toggle');">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="return confirmGroup(this)">OK</button>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">$(function(){groupsPage()})</script>
