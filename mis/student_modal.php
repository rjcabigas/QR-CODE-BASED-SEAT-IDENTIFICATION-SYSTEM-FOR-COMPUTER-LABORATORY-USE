<div class="modal-overlay" id="deleteModal">

<div class="student-modal delete-box">

<form method="POST">

<input type="hidden" name="delete_id" id="delete_id">

<h3 class="delete-title" id="deleteTitle"></h3>

<p class="delete-sub">
Are you sure you want to move this student to archive?
</p>

<div class="delete-divider"></div>

<div class="delete-actions">
<button type="button" class="cancel-btn" onclick="closeDelete()">Cancel</button>
<button type="submit" class="yes-btn" name="confirm_delete">Yes</button>
</div>

</form>

</div>

</div>

<div class="modal-overlay <?= $edit ? 'show-modal' : '' ?>" id="studentModal">

<div class="student-modal">

<div class="modal-note">
<img src="../img/mis_img/info.png">
<span>Please enter the student information here.</span>
</div>

<form class="modal-form grid-form" method="POST" enctype="multipart/form-data">

<input type="hidden" name="edit_id" value="<?= $edit['id'] ?? '' ?>">

<div class="input-group full">
<input type="text" name="student_name"
value="<?= $edit['student_name'] ?? '' ?>"
placeholder="Student name:" required>
<small class="error-msg"></small>
</div>

<div class="input-group full">
<input type="text" name="student_id"
value="<?= $edit['student_id'] ?? '' ?>"
placeholder="Student ID:" required>
<small class="error-msg"></small>
</div>

<div class="input-group full">
<input type="email" name="email" required
value="<?= $edit['email'] ?? '' ?>"
placeholder="Email Address:">
<small class="error-msg"></small>
</div>

<input type="text" name="course"
value="<?= $edit['course'] ?? '' ?>"
placeholder="Course:" required>

<div class="select-box">
<select name="year" required>
<option <?= ($edit && $edit['year']=="1ST YEAR")?"selected":"" ?>>1ST YEAR</option>
<option <?= ($edit && $edit['year']=="2ND YEAR")?"selected":"" ?>>2ND YEAR</option>
<option <?= ($edit && $edit['year']=="3RD YEAR")?"selected":"" ?>>3RD YEAR</option>
<option <?= ($edit && $edit['year']=="4TH YEAR")?"selected":"" ?>>4TH YEAR</option>
</select>
<img src="../img/mis_img/drop_down.png">
</div>

<input type="text" name="section"
value="<?= $edit['section'] ?? '' ?>"
placeholder="Section:" required>

<div class="select-box">
<select name="gender" required>
<option <?= ($edit && $edit['gender']=="MALE")?"selected":"" ?>>MALE</option>
<option <?= ($edit && $edit['gender']=="FEMALE")?"selected":"" ?>>FEMALE</option>
</select>
<img src="../img/mis_img/drop_down.png">
</div>

<div class="select-box full">
<select name="semester" required>
<option <?= ($edit && $edit['semester']=="1ST SEMESTER")?"selected":"" ?>>1ST SEMESTER</option>
<option <?= ($edit && $edit['semester']=="2ND SEMESTER")?"selected":"" ?>>2ND SEMESTER</option>
</select>
<img src="../img/mis_img/drop_down.png">
</div>

<div class="modal-footer full">

  <div class="footer-left">
    <?php if(!$edit): ?>
    <div class="file-upload">
        <input type="file" name="excel_file" id="excelFile" accept=".xls,.xlsx" hidden>

        <div class="file-box" onclick="document.getElementById('excelFile').click()">
            <span class="fake-btn">Choose file:</span>
            <span id="fileName">no file chosen.</span>
        </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="footer-right">
    <?php if($edit): ?>
    <button type="submit" class="save-btn" name="update_student" disabled>
        UPDATE
    </button>
    <?php else: ?>
    <button type="submit" id="saveBtn" class="save-btn" name="save_student">
        <span class="btn-text">Add</span>
        <span class="btn-loading">Adding</span>
    </button>
    <?php endif; ?>
  </div>

</div>

</form>

</div>
</div>

<script>
document.getElementById("excelFile")?.addEventListener("change", function() {
    const fileName = this.files[0]?.name || "no file chosen.";
    document.getElementById("fileName").textContent = fileName;
});
</script>