<div id="dotMenu"> 

<div class="menu-label">Other Menu:</div>

<div id="createBtn" onclick="createFolder(event)">
<img src="../img/teacher_img/folder.png">
<span>Create Folder</span>
</div>

<div id="sectionBtn">
<img src="../img/teacher_img/sections.png">
<span>Section</span>
<img src="../img/teacher_img/right_arrow.png" class="arrow">
</div>

<div id="deadlineBtn" class="disabled">
<img src="../img/teacher_img/deadline.png">
<span>Deadline</span>
</div>

<div id="downloadBtn" onclick="downloadFolder()">
<img src="../img/teacher_img/download.png">
<span>Download</span>
</div>

<div id="renameBtn" onclick="renameFolder(event)">
<img src="../img/teacher_img/rename.png">
<span>Rename</span>
</div>

<div class="menu-label other">Other:</div>

<div id="deleteBtn" onclick="deleteFolder()">
<img src="../img/teacher_img/delete.png">
<span>Delete</span>
</div>

</div>

<div id="confirmModal" class="modal">
<div class="modal-box">
<h3 id="confirmTitle">Delete this Assignment?</h3>
<p id="confirmMessage">
Are you sure you want to delete this folder?<br>
This action cannot be undone.
</p>
<div class="modal-actions">
<button id="confirmCancel">Cancel</button>
<button id="confirmYes">Confirm</button>
</div>
</div>
</div>

<div id="deadlineModal" class="modal">
<div class="modal-box deadline-box-modal">

<div class="deadline-header">
<div class="header-left">
<img src="../img/teacher_img/timer.png" class="header-icon">
<div class="header-text">
<span class="title">Select Duration</span>
<p class="deadline-sub">
Select start and end time or choose preset durations
</p>
</div>
</div>
</div>

<div class="divider"></div>

<div class="deadline-row">

<div class="time-group">
<label>Start time <span>(Optional)</span></label>
<div class="time-input">
<input type="text" id="startTime" value="--:--" readonly>
<img src="../img/teacher_img/time.png" class="time-icon" id="startIcon">
</div>
</div>

<div class="time-group">
<label>End time <span>(Optional)</span></label>
<div class="time-input">
<input type="text" id="endTime" value="--:--" readonly>
<img src="../img/teacher_img/time.png" class="time-icon" id="endIcon">
</div>
</div>

</div>

<input type="time" id="startPicker" style="position:fixed; opacity:0;">
<input type="time" id="endPicker" style="position:fixed; opacity:0;">

<div class="divider"></div>

<div class="quick-duration">
<div class="quick-header">
    <span>Quick Duration</span>
    <img src="../img/teacher_img/add.png" class="add-icon" onclick="openQuickPanel()">
</div>

<div class="quick-buttons">
<button data-duration="1440">1 day</button>
<button data-duration="2880">2 days</button>
<button data-duration="4320">3 days</button>
<button data-duration="5760">4 days</button>
<button data-duration="7200">5 days</button>
<button data-duration="10080">7 days</button>
</div>
</div>

<div class="divider"></div>

<div class="deadline-footer">
<div class="footer-left">
<img src="../img/teacher_img/detail.png" class="footer-icon">
<span id="selectedDurationText">Selected duration: <span>None</span></span>
</div>

<div class="footer-actions">
<button id="cancelDeadline" class="cancel-btn">Cancel</button>
<button id="applyDeadline" disabled>Apply</button>
</div>
</div>

</div>

<div id="quickPanel" class="quick-panel">
<div class="quick-panel-box">
<span class="quick-title">Choose quick duration</span>

<div class="quick-grid">
<button>8 days</button>
<button>9 days</button>
<button>10 days</button>

<button>1 week</button>
<button>2 weeks</button>
<button>3 weeks</button>

<button>4 weeks</button>
<button>1 month</button>
</div>

</div>
</div>

</div>