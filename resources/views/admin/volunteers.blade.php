@include('admin.partials.nav')
<div class="container">
    <div class="alert alert-success" role="alert" style="display:none;" id="volunteer_saved_alert">
        Saved.
    </div>
    <label for="service_body_id">{{ $settings->word('service_body') }}</label>
    <select class="form-control form-control-sm" id="service_body_id">
        <option>-= Select A Service Body =-</option>;
        @foreach ($serviceBodiesEnabledForRouting as $item)
        <option value="<?php echo $item->service_body_id ?>"><?php echo $item->service_body_name ?> (<?php echo $item->service_body_id ?>) / <?php echo $item->service_body_parent_name ?> (<?php echo $item->service_body_parent_id ?>)</option>
        @endforeach
    </select>
@include('admin.partials.volunteersControl', ['dataType' => \App\Constants\DataType::YAP_VOLUNTEERS_V2])
</div>
<div class="modal fade" id="includeGroupDialog" tabindex="-1" role="dialog" aria-labelledby="includeGroupDialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                Include Group
            </div>
            <div class="modal-body">
                <div class="form-group form-row">
                    <select class="form-control form-control-sm dropdown_next_to_another_field" id="selected_group_id">
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <div id="group_dialog_message"></div>
                <button type="button" class="btn btn-danger" data-dismiss="modal" onclick="$('#includeGroupDialog').modal('toggle');">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmIncludeGroup(this)">OK</button>
            </div>
        </div>
    </div>
</div>
<div class="card groupCard border-dark" id="groupCardTemplate" style="display:none;">
    <form id="volunteersForm">
        <div class="card-header">
            <div class="volunteer-sort-icon"></div>
            <input type="hidden" id="group_id" name="group_id">
            Group: <span id="group_name"></span>
            <span id="volunteerSequence" class="float-right"></span>
        </div>
        <div class="card-footer bg-transparent">
            <div id="groupCardFooter" class="float-right">
                <div class="form-check form-check-inline">
                    <input type="checkbox" class="form-check-input" name="group_enabled" id="group_enabled" value="false" onclick="checkboxStatusToggle(this)">
                    <label class="form-check-label" for="group_enabled">Enabled</label>
                </div>
                <button class="btn btn-sm btn-danger" type="button" onclick="removeCard(this);return false;"><?php echo $settings->word('remove')?></button>
            </div>
        </div>
    </form>
</div>
<script type="text/javascript">$(function(){volunteerPage()})</script>
