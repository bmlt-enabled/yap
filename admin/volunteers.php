<?php include_once 'nav.php' ?>
<div id="volunteers" class="container">
    <div class="row">
        <div class="col-sm">
            <form method="POST">
                <div class="form-group">
                    <label for="volunteerName"><?php echo $GLOBALS['add_add_new_volunteer']?></label>
                    <input type="text" name="volunteerName" class="form-control" id="volunteerName" aria-describedby="volunteerNameHelp" placeholder="<?php echo $GLOBALS['volunteer_name']?>">
                    <button id="add-volunteer" class="btn btn-lg btn-primary" type="button"><?php echo $GLOBALS['add_volunteer']?></button>
                    <button id="save-volunteers" class="btn btn-lg btn-primary" type="submit"><?php echo $GLOBALS['save_volunteers']?></button>
                </div>
            </form>
        </div>
    </div>
    <div id="volunteerCards" class="list-group-flush" class="row">

    </div>
</div>
<?php include_once 'footer.php';?>
<div class="card volunteerCardTemplate" style="width: 18rem;display:none;">
    <div class="card-body">
        <h5 class="card-title"></h5>
    </div>
</div>