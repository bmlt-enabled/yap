<?php require_once 'nav.php';?>
<div class="container">
    <div class="row">
        <button class="btn btn-sm btn-primary" onclick="showAddUsersModal();">Add User</button>
    </div>
    <div class="row">
        <div class="col-md">
            <table class="table table-striped table-borderless table-responsive">
                <thead>
                <tr>
                    <th scope="col"></th>
                    <th scope="col">Name</th>
                    <th scope="col">Username</th>
                    <th scope="col">Service Bodies</th>
                    <th scope="col">Date Created</th>
                </tr>
                </thead>
                <tbody>
    <?php
    $users = getUsers();
    foreach ($users as $user) {?>
        <tr>
            <td>
                <button class="btn btn-sm btn-warning">Edit</button>
                <button class="btn btn-sm btn-danger" onclick="deleteUserHandling(<?php echo $user['id']?>)">Delete</button>
            </td>
            <td><?php echo $user['name']?></td>
            <td><?php echo $user['username']?></td>
            <td><?php echo $user['service_bodies']?></td>
            <td><?php echo $user['created_on']?></td>
        </tr>
    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
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
                        <div class="users_username">
                            <label for="username">Username:</label>
                            <input class="form-control form-control-sm" type="text" name="username" id="username">
                        </div>

                        <div class="users_name">
                            <label for="name">Name:</label>
                            <input class="form-control form-control-sm" type="text" name="name" id="name">
                        </div>

                        <div class="users_password">
                            <label for="password">Password:</label>
                            <input class="form-control form-control-sm" type="password" name="password" id="password">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-sm btn-secondary" data-dismiss="modal">Cancel</button>
                    <button class="btn btn-sm btn-primary" onclick="addUserHandling()">Add User</button>
                </div>
            </div>
        </div>
    </div>
<?php require_once 'footer.php';
