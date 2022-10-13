<?php
require_once '_includes/functions.php';
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";

$gender = getIvrResponse("gender-routing.php", null, [VolunteerGender::MALE, VolunteerGender::FEMALE, VolunteerGender::NO_PREFERENCE]);
if ($gender == null) {
    return false;
}
$_SESSION['Gender'] = $gender;
?>
<Response>
    <Redirect>helpline-search.php?SearchType=<?php echo $_REQUEST['SearchType'] ?></Redirect>
</Response>
