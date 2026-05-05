<?php
?>

<div class="time-overlay" id="timeOverlay">

    <div class="time-panel">

        <div class="time-header">
            <h4>ENTER TIME</h4>

            <div class="time-range" id="timeRangeDisplay">
                <?= $displayRange ?>
            </div>

            <img src="../img/teacher_img/change_time.png" class="change-time-icon">

            <div class="time-overlay" id="rangeModal">
                <div class="time-panel">

                    <div class="range-group-wrapper">

                        <div class="range-group">
                            <label>Start with</label>
                            <input type="time" id="startTime" value="<?= $subjectStart ?>">
                        </div>

                        <div class="range-group">
                            <label>End with</label>
                            <input type="time" id="endTime" value="<?= $subjectEnd ?>">
                        </div>

                    </div>

                    <button id="saveRangeBtn" class="save-range-btn">
                        Save
                    </button>

                </div>
            </div>
        </div>

        <div class="modern-time-wrapper">

            <div class="modern-row">

                <div class="modern-field">
                    <label>
                        Hour <span>(Optional)</span>
                    </label>

                    <input
                        type="number"
                        id="hourSelect"
                        min="0"
                        max="12"
                        placeholder="0">
                </div>

                <div class="modern-field">
                    <label>
                        Minutes <span>(Optional)</span>
                    </label>

                    <input
                        type="number"
                        id="minuteSelect"
                        min="0"
                        max="59"
                        placeholder="00">
                </div>

            </div>

            <div class="quick-title">
                Quick Duration for late
            </div>

            <div class="quick-buttons">

                <button
                    type="button"
                    class="quick-btn"
                    data-minutes="20">
                    20 mins
                </button>

                <button
                    type="button"
                    class="quick-btn"
                    data-minutes="40">
                    40 mins
                </button>

                <button
                    type="button"
                    class="quick-btn"
                    data-minutes="60">
                    1 hour
                </button>

                <button
                    type="button"
                    class="quick-btn"
                    data-minutes="120">
                    2 hours
                </button>

            </div>

            <div class="modern-actions">

                <div class="selected-preview">
                    Selected time:
                    <span id="selectedPreview">None</span>
                </div>

                <div class="action-buttons">
                    <span id="cancelTime">Cancel</span>

                    <button
                        type="button"
                        id="applyTimeBtn">
                        Apply
                    </button>
                </div>

            </div>

        </div>

    </div>

</div>

<div id="toast" class="toast"></div>

<script>

function showToast(message, type = "default") {
    const toast = document.getElementById("toast");

    toast.className = "toast";

    if (type === "success") {
        toast.classList.add("toast-success");
    } else if (type === "late") {
        toast.classList.add("toast-late");
    }

    toast.textContent = message;

    requestAnimationFrame(() => {
        toast.classList.add("show");
    });

    setTimeout(() => {
        toast.classList.remove("show");
    }, 3000);
}

const setTimeBtn = document.getElementById("setTimeBtn");
const timeOverlay = document.getElementById("timeOverlay");
const cancelTime = document.getElementById("cancelTime");

setTimeBtn.onclick = () => {
    timeOverlay.style.display = "flex";
    dotDropdown.style.display = "none";
};

cancelTime.onclick = () => {
    timeOverlay.style.display = "none";
};

const changeTimeIcon = document.querySelector(".change-time-icon");
const rangeModal = document.getElementById("rangeModal");
const saveRangeBtn = document.getElementById("saveRangeBtn");
const startTimeInput = document.getElementById("startTime");
const endTimeInput = document.getElementById("endTime");

let originalStartTime = startTimeInput.value;
let originalEndTime = endTimeInput.value;

saveRangeBtn.disabled = true;

changeTimeIcon.onclick = () => {
    rangeModal.style.display = "flex";

    originalStartTime = startTimeInput.value;
    originalEndTime = endTimeInput.value;

    saveRangeBtn.disabled = true;
};

function checkRangeChanges() {
    if (
        startTimeInput.value !== originalStartTime ||
        endTimeInput.value !== originalEndTime
    ) {
        saveRangeBtn.disabled = false;
    } else {
        saveRangeBtn.disabled = true;
    }
}

startTimeInput.addEventListener("input", checkRangeChanges);
endTimeInput.addEventListener("input", checkRangeChanges);

saveRangeBtn.onclick = () => {
    const startTime = document.getElementById("startTime").value;
    const endTime = document.getElementById("endTime").value;
    const timeRangeDisplay = document.getElementById("timeRangeDisplay");

    if (!startTime || !endTime) return;

    fetch("update_subject_time.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body:
            "start_time=" + encodeURIComponent(startTime) +
            "&end_time=" + encodeURIComponent(endTime)
    })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === "ok") {

            const formatTime = (time) => {
                let [hour, minute] = time.split(":");
                hour = parseInt(hour);

                const ampm = hour >= 12 ? "PM" : "AM";
                hour = hour % 12 || 12;

                return `${String(hour).padStart(2, "0")}:${minute} ${ampm}`;
            };

            timeRangeDisplay.innerText =
                `${formatTime(startTime)} - ${formatTime(endTime)}`;

            showToast("Time changed successfully", "success");
            rangeModal.style.display = "none";
        }
    });
};

const quickBtns = document.querySelectorAll(".quick-btn");
const selectedPreview = document.getElementById("selectedPreview");
const applyTimeBtn = document.getElementById("applyTimeBtn");
const hourInput = document.getElementById("hourSelect");
const minuteInput = document.getElementById("minuteSelect");

let selectedMinutes = <?= (int)$lateMinutes ?>;

selectedPreview.innerText =
    selectedMinutes > 0 ? selectedMinutes + " mins" : "None";

applyTimeBtn.disabled = true;

quickBtns.forEach(btn => {
    btn.classList.remove("active");

    const btnValue = parseInt(btn.dataset.minutes);

    if (btnValue === selectedMinutes) {
        btn.classList.add("active");
    }

    btn.onclick = () => {
        if (selectedMinutes === btnValue) {
            btn.classList.remove("active");
            selectedMinutes = 0;
            selectedPreview.innerText = "None";
            checkApplyState();
            return;
        }

        quickBtns.forEach(b => {
            b.classList.remove("active");
        });

        btn.classList.add("active");
        selectedMinutes = btnValue;
        selectedPreview.innerText = btn.innerText;

        hourInput.value = "";
        minuteInput.value = "";

        checkApplyState();
    };
});

function checkApplyState() {
    const hour = parseInt(hourInput.value) || 0;
    const minute = parseInt(minuteInput.value) || 0;

    if (hour > 0 || minute > 0 || selectedMinutes > 0) {
        applyTimeBtn.disabled = false;
    } else {
        applyTimeBtn.disabled = true;
    }
}

hourInput.addEventListener("input", () => {
    if ((parseInt(hourInput.value) || 0) > 0 || (parseInt(minuteInput.value) || 0) > 0) {
        quickBtns.forEach(b => b.classList.remove("active"));
        selectedMinutes = 0;
        selectedPreview.innerText = "Manual";
    } else if (selectedMinutes === 0) {
        selectedPreview.innerText = "None";
    }

    checkApplyState();
});

minuteInput.addEventListener("input", () => {
    if ((parseInt(hourInput.value) || 0) > 0 || (parseInt(minuteInput.value) || 0) > 0) {
        quickBtns.forEach(b => b.classList.remove("active"));
        selectedMinutes = 0;
        selectedPreview.innerText = "Manual";
    } else if (selectedMinutes === 0) {
        selectedPreview.innerText = "None";
    }

    checkApplyState();
});

document.getElementById("applyTimeBtn").onclick = () => {
    const hour = parseInt(hourInput.value) || 0;
    const minute = parseInt(minuteInput.value) || 0;

    let totalMinutes = (hour * 60) + minute;

    if (totalMinutes === 0) {
        totalMinutes = selectedMinutes;
    }

    fetch("update_late_minutes.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded"
        },
        body: "minutes=" + totalMinutes
    })
    .then(res => res.text())
    .then(data => {
        if (data.trim() === "ok") {
            showToast("Set time late successfully", "late");
            timeOverlay.style.display = "none";
        }
    });
};


document.addEventListener("click", (e) => {
    if (
        rangeModal.style.display === "flex" &&
        !rangeModal.querySelector(".time-panel").contains(e.target) &&
        !changeTimeIcon.contains(e.target)
    ) {
        rangeModal.style.display = "none";
    }
});
</script>