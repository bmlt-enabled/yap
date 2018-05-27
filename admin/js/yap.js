$(function() {
    $("#add-volunteer").click(function() {
        var volunteerCardTemplate = $("#volunteerCardTemplate").clone();
        volunteerCardTemplate.attr("id", "volunteerCard");
        volunteerCardTemplate.show();
        volunteerCardTemplate.find("#volunteer_name").val($("#new_volunteer_name").val());
        volunteerCardTemplate.appendTo("#volunteerCards");
        $("#new_volunteer_name").val("");
    });

    $("#save-volunteers").click(function() {
        $.ajax({
            type: "POST",
            url: "/admin/save_volunteers.php",
            data: JSON.stringify({"data" : $("#volunteersForm").serializeArray()}),
            dataType: "json",
            contentType: "application/json"
        });
    })
});