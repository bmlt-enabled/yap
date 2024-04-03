<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserDialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><span class="users_modal_title"><?php echo 'Add User' ?></span></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body serviceBodyCallHandlingItems">
                <form id="addUserForm" class="addUserForm">
                    <div id="serviceBodyCallHandlingValidation"></div>
                    <div class="users_username control-group">
                        <label for="username">Username:</label>
                        <input class="form-control form-control-sm" type="text" name="username" id="username" autocomplete="username">
                    </div>

                    <div class="users_name control-group">
                        <label for="name">Display Name:</label>
                        <input class="form-control form-control-sm" type="text" name="name" id="name">
                    </div>

                    <div class="users_password control-group">
                        <label for="password">Password:</label>
                        <input class="form-control form-control-sm" type="password" name="password" id="password" autocomplete="new-password">
                    </div>

                    <input class="form-control form-control-sm" type="hidden" name="id" id="id">

                    <div class="users_permissions control-group">
                        Permissions: (press and hold Command/Control (macOS/Windows) and click to deselect an option) <select size="5" multiple class="form-control form-control-sm" name="permissions" id="permissions">
                            <option value="1">Manage Users</option>
                        </select>
                    </div>

                    <div class="users_service_bodies control-group">
                       @if (count($serviceBodiesForUser) > 0)
                        Service Bodies Access: (press and hold Command/Control (macOS/Windows) and click to deselect an option) <select size="10" multiple class="form-control form-control-sm" name="service_bodies" id="service_bodies">
                            @foreach ($serviceBodiesForUser as $serviceBody) { ?>
                                <option value="{{ $serviceBody->id }}">{{ $serviceBody->name }} ({{ $serviceBody->id }}) / {{ $serviceBody->parent_name }} ({{ $serviceBody->parent_id }})</option>
                            @endforeach
                        </select>
                     @endif
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-danger" data-dismiss="modal">Cancel</button>
                <button id="usersSaveButton" class="btn btn-sm btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>
