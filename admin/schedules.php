<?php include_once 'nav.php';?>
<div class="container">
    <div class="row">
        <div class="col-md">
            <select name="servicebodies" id="servicebodies"><option value="0">-= Select A Service Body =-</option></select>
            <div id='calendar'></div>
        </div>
    </div>
</div>
<?php include_once 'footer.php';?>
<link rel='stylesheet' href='css/fullcalendar-3.9.0.min.css' />
<script src='js/moment-2.11.1.min.js'></script>
<script src='js/fullcalendar-3.9.0.min.js'></script>
<script type="text/javascript">
    function getParameterByName(name, url) {
        if (!url) url = window.location.href;
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    }

    function getYapHelplines() {
        $.getJSON("../helpline-yap-based.php", function(data) {
            for (var x = 0; x < data.length; x++) {
                $("select#servicebodies").append("<option value=\"" + data[x].id + "\">" + data[x].name + "</option>")
            }
        });
    }

    $(function() {
        getYapHelplines();
        $('#calendar').fullCalendar({
            allDaySlot: false,
            defaultView: 'agendaWeek',
            nowIndicator: true,
            firstDay: (new Date()).getDay(),
            themeSystem: 'bootstrap4'
        });

        $('select#servicebodies').change(function() {
            if (parseInt($('select#servicebodies').val()) > 0) {
                $('#calendar').fullCalendar('removeEventSources');
                $("#calendar").fullCalendar('removeEvents');
                $('#calendar').fullCalendar('addEventSource', '../helpline-schedule.php?service_body_id=' + $('select#servicebodies').val());
            }
        })

        if (getParameterByName("service_body_id") != null) {
            $('#calendar').fullCalendar('removeEventSources');
            $("#calendar").fullCalendar('removeEvents');
            $('#calendar').fullCalendar('addEventSource', '../helpline-schedule.php?service_body_id=' + getParameterByName("service_body_id"));
        }
    })
</script>
