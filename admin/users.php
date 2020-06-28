<?php require_once 'nav.php';
if (!boolval($_SESSION['auth_is_admin'])) {
    echo("Access Denied");
    exit();
}
?>
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
                    <th scope="col">Username</th>
                    <th scope="col">Name</th>
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
                <button class="btn btn-sm btn-warning" onclick="editUser(<?php echo $user['id']?>, '<?php echo $user['username']?>', '<?php echo $user['name']?>', '<?php echo $user['service_bodies']?>', 'edit')">Edit</button>
                <?php if ($user['id'] !== $_SESSION['auth_id']) { ?>
                <button class="btn btn-sm btn-danger" onclick="deleteUserHandling(<?php echo $user['id']?>)">Delete</button>
                <?php } ?>
            </td>
            <td><?php echo $user['username']?></td>
            <td><?php echo $user['name']?></td>
            <td><?php echo $user['service_bodies']?></td>
            <td><?php echo $user['created_on']?></td>
        </tr>
    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
require_once 'footer.php';
