<?php include_once 'nav.php';?>
<div class="container">
    <div class="row">
        <div class="col-md">
            <label for="service_body_id">Service Body</label>
            <select class="form-control form-control-sm" id="service_body_id">
                <option>-= Select A Service Body =-</option>
                <?php
                $helplineConfiguration = getVolunteerRoutingEnabledServiceBodies();
                foreach ($helplineConfiguration as $item) {?>
                    <option value="<?php echo $item['service_body_id']?>"><?php echo $item['service_body_name']?></option>
                    <?php
                }?>
            </select>
            <div id='calendar'></div>
        </div>
    </div>
</div>
<?php include_once 'footer.php';?>
<link rel='stylesheet' href='css/fullcalendar-3.9.0.min.css' />
<script src='js/moment-2.11.1.min.js'></script>
<script src='js/fullcalendar-3.9.0.min.js'></script>
<script type="text/javascript">
    $(function() {
        $('#calendar').fullCalendar({
            allDaySlot: false,
            defaultView: 'agendaWeek',
            nowIndicator: true,
            firstDay: (new Date()).getDay(),
            themeSystem: 'bootstrap4',
            header: {
                left: null,
                center: null,
                right: null
            },
            height: 'auto'
        });

        $('select#service_body_id').change(function() {
            if (parseInt($('select#service_body_id').val()) > 0) {
                $('#calendar').fullCalendar('removeEventSources');
                $("#calendar").fullCalendar('removeEvents');
                $('#calendar').fullCalendar('addEventSource', '../helpline-schedule.php?service_body_id=' + $('select#service_body_id').val());
            }
        })
    })
</script>
