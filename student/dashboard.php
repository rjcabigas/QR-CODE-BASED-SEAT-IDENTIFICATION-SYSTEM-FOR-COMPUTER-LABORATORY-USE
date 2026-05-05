<?php 
include "dashboard_data.php"; 

$student_id = $student_id ?? '';
if(empty($student_id)){
    header("Location: login.php");
    exit;
}

$student_name = htmlspecialchars($student_name ?? "Student");
$section_raw = $section ?? "";
$section = htmlspecialchars($section_raw);

$profile_pic = (!empty($profile_pic) && file_exists(__DIR__ . "/../uploads/" . $profile_pic)) 
    ? htmlspecialchars($profile_pic) 
    : "profile.png";
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Student Dashboard</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../mobile_css/student_dashboard_mobile_view.css">
<link rel="stylesheet" href="../mobile_css/student_dashboard_modal.css">

<script src="https://unpkg.com/html5-qrcode"></script>

</head>
<body 
data-last-pc="<?php echo htmlspecialchars($last_pc ?? ''); ?>" 
data-last-comlab="<?php echo htmlspecialchars($last_comlab ?? ''); ?>"
>

<div class="container">

<div class="header">

<img src="../img/student_img/bpc_logo.png" alt="School Logo">

<div class="header-text">
<h3>Hi, <?php echo $student_name; ?></h3>
<small><?php echo $section; ?></small>
</div>

<div class="header-icons">

<a href="notification.php" class="notif-wrapper">
<img src="../img/student_img/notification.png" alt="Notification">

<?php if(!empty($unreadCount) && $unreadCount > 0): ?>
<span class="notif-badge">
<?php echo ($unreadCount > 99) ? "99+" : $unreadCount; ?>
</span>
<?php endif; ?>

</a>

<img src="../img/student_img/scanner.png" id="openScanner" alt="Scanner">
<img src="../img/student_img/setting.png" id="settingBtn" alt="Settings">

<div id="settingMenu" style="display:none;">

<p class="menu-title">Other Menu:</p>

<a href="javascript:void(0)" 
   id="updatePCBtn"
   onclick="openPCModal()"
   style="pointer-events: none; opacity: 0.5;">

<img src="../img/student_img/update_pc.png" alt="Update PC"> Update PC
</a>

<a href="comment.php">
<img src="../img/student_img/evaluation.png" alt="Comment"> Comment
</a>

<a href="javascript:void(0)" id="openSubjectBtn">
<img src="../img/student_img/subject.png" alt="Subjects"> My Subjects
</a>

<p class="menu-title">Others:</p>

<a href="trash.php">
<img src="../img/student_img/trash.png" alt="Trash"> Trash Files
</a>

<a href="javascript:void(0)" onclick="openLogoutModal()">
<img src="../img/student_img/log-out.png" alt="Logout"> Logout
</a>

</div>

</div>
</div>

<div class="panel top-panel">

<div class="welcome-container">

<div class="welcome-text">
<h2>Welcome!!</h2>
<p>What would you like to accomplish today?</p>
</div>

<div class="welcome-icon">
<img src="../img/student_img/welcome.png" alt="Welcome Icon">
</div>

</div>
</div>

<div class="mini-wrapper">

<a href="profile_mobile.php" class="mini-panel">
<img src="../uploads/<?php echo $profile_pic; ?>" alt="Profile" onerror="this.src='../img/student_img/default.png'">
<p>My Profile</p>
</a>

<a href="submit_file_mobile.php" class="mini-panel">
<img src="../img/student_img/folder.png" alt="Submission">
<p>Submission</p>
</a>

<a href="attendance_mobile.php" class="mini-panel">
<img src="../img/student_img/attendance_history.png" alt="Attendance">
<p>Attendance</p>
</a>

</div>

<div class="panel five-panel">

<div class="recent-header">
    <p class="file-title">Recent Uploads</p>
    <span class="view-all-btn">View All</span>
</div>

<div class="file-body">

<?php if(!empty($recentFiles)): ?>

<?php foreach($recentFiles as $file): ?>

<div class="recent-item">

<div class="recent-left">

<img src="<?php echo $file['icon']; ?>" alt="File Icon">

<div class="recent-info">
<strong><?php echo $file['name']; ?></strong>
<small><?php echo $file['date']; ?></small>
</div>

</div>

<div class="recent-size">
<?php echo $file['size']; ?>
</div>

</div>

<?php endforeach; ?>

<?php else: ?>

<div class="empty-state">
<img src="../img/student_img/no_file.png" alt="No Files">
<p class="empty-text">Don't have any files right now.</p>
</div>

<?php endif; ?>

</div>
</div>

<div class="panel six-panel">

<p class="summary-title">Summary of all my attendance.</p>

<div class="summary-wrapper">

<div class="summary-box present">
<img src="../img/student_img/present.png" alt="Present">
<p><?php echo intval($present_count ?? 0); ?></p>
<small>PRESENT</small>
</div>

<div class="summary-box late">
<img src="../img/student_img/late.png" alt="Late">
<p><?php echo intval($late_count ?? 0); ?></p>
<small>LATE</small>
</div>

<div class="summary-box absent">
<img src="../img/student_img/absent.png" alt="Absent">
<p><?php echo intval($absent_count ?? 0); ?></p>
<small>ABSENT</small>
</div>

</div>

</div>

</div>

<div id="pcModal" class="pc-modal" style="display:none">

<form id="pcForm" class="pc-box">

<h4>Maintenance Report for PC</h4>

<input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">

<input type="text" id="pc_no" name="pc_no" placeholder="PC NO:" readonly required>
<input type="text" id="comlab" name="comlab" placeholder="COMLAB:" readonly required>

<div class="custom-select" id="statusDropdown">
    <div class="selected">SELECT PC STATUS:</div>
    <div class="options">
        <div data-value="Working">Working</div>
        <div data-value="Not Used">Not Used</div>
        <div data-value="Defective">Defective</div>
    </div>
</div>

<input type="hidden" id="statusSelect" name="status" required>

<div class="custom-select" id="issueDropdown" style="display:none;">
    <div class="selected">SELECT TYPE OF ISSUE:</div>

    <div class="options">

        <div class="issue-group" data-type="hardware">
            Hardware <img src="../img/student_img/right_arrow.png" alt=">">

            <div class="issue-items hardware" style="display:none;">
                <div data-value="No Display">No Display</div>
                <div data-value="Loose Connection">Loose Connection</div>
                <div data-value="No Power">No Power</div>
                <div data-value="Broken Mouse">Broken Mouse</div>
                <div data-value="Faulty Keyboard">Faulty Keyboard</div>
                <div data-value="Others">Others</div>
            </div>
        </div>

        <div class="issue-group" data-type="software">
            Software <img src="../img/student_img/right_arrow.png" alt=">">

            <div class="issue-items software" style="display:none;">
                <div data-value="Slow Performance">Slow Performance</div>
                <div data-value="Access Denied">Access Denied</div>
                <div data-value="Missing Software">Missing Software</div>
                <div data-value="Not Corresponding">Not Corresponding</div>
                <div data-value="System Freeze">System Freeze</div>
                <div data-value="Others">Others</div>
            </div>
        </div>

        <div class="issue-group" data-type="network">
            Network <img src="../img/student_img/right_arrow.png" alt=">">

            <div class="issue-items network" style="display:none;">
                <div data-value="No Internet Connection">No Internet Connection</div>
                <div data-value="Slow Internet">Slow Internet</div>
                <div data-value="Limited Access">Limited Access</div>
                <div data-value="Others">Others</div>
            </div>
        </div>

    </div>
</div>

<input type="hidden" id="issueType" name="issue_type">

<textarea id="description" name="description" placeholder="Provide short description of issue:" style="display:none;"></textarea>

<div class="pc-actions">
<span onclick="closePCModal()">Cancel</span>
<button type="submit">Submit</button>
</div>

</form>

</div>

<div id="scannerModal" class="scanner-modal" style="display:none;">

<div class="scanner-box">

<h3>Scan PC QR</h3>

<div id="reader"></div>

<button id="closeScanner">Close</button>

</div>

</div>

<div id="logoutModal" class="logout-modal">
    
<div class="logout-box">

<h3>Logout?</h3>
<p>Are you sure you want to logout?</p>

<div class="logout-actions">

<button class="cancel-btn" onclick="closeLogoutModal()">Cancel</button>

<a href="../auth/logout_student.php" class="yes-btn">Yes</a>

</div>

</div>
</div>

<div id="subjectModal" class="subject-modal" style="display:none;">

<div class="subject-box">

<h3>My Subject</h3>

<div class="subject-dropdown" id="subjectDropdown">
<span>Select Subject here:</span>
<img src="../img/student_img/drop_down.png" id="dropdownIcon" alt="Dropdown">

<div class="dropdown-list" id="dropdownList" style="display:none;">
    
<div class="dropdown-item no-current" data-subject="No current subject" style="display:none;">
    No current subject
</div>

<?php if(!empty($availableSubjects)): ?>
    
<?php foreach($availableSubjects as $sub): ?>
<div class="dropdown-item" data-subject="<?php echo htmlspecialchars($sub); ?>">
<?php echo htmlspecialchars($sub); ?>
</div>
<?php endforeach; ?>

<?php else: ?>
<div class="dropdown-item">No subjects available</div>
<?php endif; ?>

</div>
</div>

<div class="tag-container">
<?php foreach($savedSubjects as $sub): ?>
    <div class="subject-tag" data-subject="<?php echo htmlspecialchars($sub); ?>">
        <?php echo htmlspecialchars($sub); ?>
    </div>
<?php endforeach; ?>
</div>

<div class="no-subject" style="<?php echo count($savedSubjects) > 0 ? 'display:none;' : ''; ?>">
<img src="../img/student_img/no_subject.png" alt="No Subject">
<p>No subject has been selected yet.</p>
</div>

<button 
    id="saveSubjectsBtn" 
    class="save-btn"
    <?php echo count($savedSubjects) > 0 ? 'disabled' : ''; ?>
>
    Save
</button>

</div>

</div>
</div>

</div>

<div id="toast" class="toast"></div>

<script>
if(typeof Html5Qrcode === "undefined"){
    alert("Scanner failed to load. Please refresh.");
}
</script>

<script src="../js/student_main.js"></script>
<script src="../js/student_functions.js"></script>

</body>
</html>