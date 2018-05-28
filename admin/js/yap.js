$(function() {
    $("#add-volunteer").click(function() {
        addVolunteer({
            "name": "volunteerName",
            "value": $("#new_volunteer_name").val()
        });

        $("#new_volunteer_name").val("");
    });

    $("#save-volunteers").click(function() {
        var data = $("#volunteersForm").serializeArray();

        $.ajax({
            type: "POST",
            url: "/admin/api.php?action=save&helpline_data_id=" + $("#helpline_data_id").val()
                + "&service_body_id=" + $("#service_body_id").val(),
            data: JSON.stringify({"data" : data}),
            dataType: "json",
            contentType: "application/json"
        });
    });

    $("#service_body_id").on("change", function() {
        $.getJSON("/admin/api.php?service_body_id=" + $(this).val(), function(data) {
            $("#helpline_data_id").val(data["id"]);
            for (item of data["data"]) {
                addVolunteer(item)
            }
        })
    })
});

function addVolunteer(volunteerData) {
    var volunteerCardTemplate = $("#volunteerCardTemplate").clone();
    volunteerCardTemplate.attr("id", "volunteerCard");
    volunteerCardTemplate.show();
    volunteerCardTemplate.find("#volunteer_name").val(volunteerData["value"]);
    volunteerCardTemplate.appendTo("#volunteerCards");
}

function removeVolunteer(e) {
    $(e).closest("#volunteerCard").remove();
}