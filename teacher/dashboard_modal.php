<div class="modal-overlay" id="classModal">

    <div class="modal-wrapper">

        <div class="subject-panel">
            <img src="../img/teacher_img/back.png" class="back-btn disabled">

            <p class="subject-title">
                Select your class section and add your Subject here!
            </p>

            <h3>ADD SUBJECT</h3>

<?php
$subs=[];
$stmt = $conn->prepare("
SELECT DISTINCT subject FROM teacher_subjects WHERE teacher_id=?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$r = $stmt->get_result();
while($x=$r->fetch_assoc()) $subs[]=$x['subject'];
?>

<div id="subjectContainer">

<?php foreach($subs as $s): ?>
<div class="subject-pill" data-value="<?= $s ?>">
<?= strtoupper($s) ?>
</div>
<?php endforeach; ?>

<div class="subject-input">
    <img src="../img/teacher_img/add_sub.png" class="add-icon" id="addSubjectBtn">

    <input type="text" name="subjects[]" class="subjectInput" placeholder="Add Subject:">

    <img src="../img/teacher_img/time.png" class="time-icon">
</div>

</div>
            <button class="next-btn">Next</button>
        </div>

<div class="section-panel" id="sectionPanel">

    <img src="../img/teacher_img/back.png" class="back-btn" id="backBtn">

    <p class="subject-title">
        Select your class section and add your Subject here!
    </p>

    <h3>CHOOSE COURSE, YEAR & SECTION</h3>

<div class="custom-select" id="customSelect">

    <div class="select-selected">
        Select here:
        <img src="../img/teacher_img/drop_down.png" class="dropdown-icon">
    </div>

    <div class="select-options">
        <?php foreach ($sections as $sec): ?>
            <div class="select-option" data-value="<?= $sec ?>">
                <?= strtoupper($sec) ?>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<input type="hidden" id="sectionSelect" name="section">

<div id="sectionTags">

<?php
$tagStmt = $conn->prepare("
    SELECT section 
    FROM teacher_sections 
    WHERE teacher_id=?
");
$tagStmt->bind_param("i", $_SESSION['user_id']);
$tagStmt->execute();
$r = $tagStmt->get_result();

while($t = $r->fetch_assoc()):
$active = ($_SESSION['teacher_section'] ?? '') === $t['section'];
?>

<div class="section-pill <?= $active?'active':'' ?>" 
     data-value="<?= $t['section'] ?>">
<?= strtoupper($t['section']) ?>
</div>

<?php endwhile; $tagStmt->close(); ?>

</div>

    <button class="save-btn">Save</button>

</div>
    </div>

</div>

<div class="time-overlay" id="timeOverlay">

    <div class="time-panel">

        <div class="time-row">
            <div class="time-box">
                <label>Start with</label>
                <input type="time" id="startTime">
            </div>

            <div class="time-box">
                <label>End with</label>
                <input type="time" id="endTime">
            </div>
        </div>

        <button class="time-save" id="saveTime">Save</button>

    </div>

</div>