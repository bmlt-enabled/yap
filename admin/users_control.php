<div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserDialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo 'Add User' ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body serviceBodyCallHandlingItems">
                <form id="addUserForm" class="addUserForm">
                    <div id="serviceBodyCallHandlingValidation"></div>
                    <div class="users_username control-group">
                        <label for="username">Username:</label>
                        <input class="form-control form-control-sm" type="text" name="username" id="username">
                    </div>

                    <div class="users_name control-group">
                        <label for="name">Display Name:</label>
                        <input class="form-control form-control-sm" type="text" name="name" id="name">
                    </div>

                    <div class="users_password control-group">
                        <label for="password">Password:</label>
                        <input class="form-control form-control-sm" type="password" name="password" id="password">
                    </div>

                    <input class="form-control form-control-sm" type="hidden" name="id" id="id">

                    <div class="users_service_bodies control-group">
                        <?php
                        $shareable_service_bodies = getServiceBodies();
                        sort_on_field($shareable_service_bodies, 'name')
                        ?>
                        Service Bodies Access: <select size="10" multiple class="form-control form-control-sm" name="service_bodies" id="service_bodies">
                            <?php
                            foreach ($shareable_service_bodies as $service_body) { ?>
                                <option value="<?php echo $service_body->id; ?>"><?php echo $service_body->name; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                <button id="usersSaveButton" class="btn btn-sm btn-primary">Save</button>
            </div>
        </div>
    </div>
</div>
