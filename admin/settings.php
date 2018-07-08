<?php include_once 'nav.php'?>
    <div class="container">
        <div class="row">
            <div class="col-md">
                <table class="table table-striped table-bordered">
                    <thead>
                    <tr>
                        <th scope="col">Setting</th>
                        <th scope="col">Value</th>
                        <th scope="col">Current Source</th>
                        <th scope="col">Default</th>
                        <th scope="col">Description</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $settings_array = $GLOBALS['settings_whitelist'];
                    ksort($settings_array);
                    foreach ($settings_array as $key => $value) { ?>
                        <tr>
                            <td><?php echo $key ?></td>
                            <td><?php
                                if (is_bool(setting($key))) {
                                    echo setting($key) ? "true" : "false";
                                } else {
                                    echo setting($key);
                                }?>
                            </td>
                            <td><?php echo setting_source($key)?></td>
                            <td><?php
                                if (is_bool($value['default'])) {
                                    echo $value['default'] ? "true" : "false";
                                } else {
                                    echo $value['default'];
                                }?>
                            </td>
                            <td><?php echo $value['description'] ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php include_once 'footer.php';
