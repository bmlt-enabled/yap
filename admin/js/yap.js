$(function() {
    $("#add-volunteer").click(function() {
        addVolunteer({
            "volunteer_name": $("#new_volunteer_name").val()
        });

        $("#new_volunteer_name").val("");
    });

    $("#save-volunteers").click(function() {
        var volunteerCards = $("#volunteerCards").children();
        var data = [];
        for (var volunteerCard of volunteerCards) {
            var formData = $(volunteerCard).find("#volunteersForm").serializeArray();
            var dataObj = {};
            for (var formItem of formData) {
                dataObj[formItem["name"]] = formItem["value"]
            }

            data.push(dataObj);
        }

        console.log(data);

        $.ajax({
            async: true,
            type: "POST",
            url: "/admin/api.php?action=save&helpline_data_id=" + $("#helpline_data_id").val()
            + "&service_body_id=" + $("#service_body_id").val(),
            data: JSON.stringify({"data": data}),
            dataType: "json",
            contentType: "application/json",
            success: function () {

            }
        });

        $("#volunteer_saved_alert").show();
        $("#volunteer_saved_alert").fadeOut(3000);
    });

    $("#service_body_id").on("change", function() {
        loadVolunteers($(this).val())
    })
});

function loadVolunteers(serviceBodyId) {
    $.getJSON("/admin/api.php?service_body_id=" + serviceBodyId, function(data) {
        $("#helpline_data_id").val(data["id"]);
        for (item of data["data"]) {
            addVolunteer(item)
        }
    })
}

function addVolunteer(volunteerData) {
    var getLastVolunteerCard = parseInt($("#volunteerCards").children().length);
    var volunteerCardTemplate = $("#volunteerCardTemplate").clone();
    volunteerCardTemplate.attr("id", "volunteerCard_" + (++getLastVolunteerCard));
    volunteerCardTemplate.show();
    for (var key in volunteerData) {
        volunteerCardTemplate.find("#" + key).val(volunteerData[key]);
    }
    volunteerCardTemplate.appendTo("#volunteerCards");
}

function removeVolunteer(e) {
    $(e).closest(".volunteerCard").remove();
}