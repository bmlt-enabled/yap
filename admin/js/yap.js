$(function() {
    $("#add-volunteer").click(function() {
        var volunteerCardTemplate = $(".volunteerCardTemplate");
        volunteerCardTemplate.show();
        volunteerCardTemplate.find(".card-title").html($("#volunteerName").val());
        volunteerCardTemplate.appendTo("#volunteerCards");
        $("#volunteerName").val("");
    });
});