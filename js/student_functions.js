function parseQR(qr){

    if(qr.includes("|")){
        let parts = qr.split("|");
        currentPC = parts[0] ?? "";
        currentComlab = parts[1] ?? "";
    }
    else{
        let match = qr.match(/(COMLAB-\d+)-(PC-\d+)/i);

        if(match){
            currentComlab = match[1];
            currentPC = match[2];
        }
        else{
            currentPC = qr;
            currentComlab = "";
        }
    }
}

function sendQR(qr){

    fetch("scan_qr.php",{
        method:"POST",
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:"qr=" + encodeURIComponent(qr)
    })
    .then(r => r.text())
    .then(d => {

if(d === "ready"){

    const scannerModal = document.getElementById("scannerModal");
    const pcModal = document.getElementById("pcModal");

    if(scannerModal) scannerModal.style.display = "none";
    if(pcModal) pcModal.style.display = "flex";

    const updateBtn = document.getElementById("updatePCBtn");
    if(updateBtn){
        updateBtn.style.pointerEvents = "auto";
        updateBtn.style.opacity = "1";
    }

    const pcInput = document.getElementById("pc_no");
    const comlabInput = document.getElementById("comlab");

    if(pcInput) pcInput.value = currentPC;
    if(comlabInput) comlabInput.value = currentComlab;

    const statusDropdown = document.getElementById("statusDropdown");
    const issueDropdown = document.getElementById("issueDropdown");
    const description = document.getElementById("description");
    const statusInput = document.getElementById("statusSelect");

    if(currentPC === "PC-0" || currentPC === "PC 0"){

        if(statusDropdown) statusDropdown.style.display = "none";
        if(issueDropdown) issueDropdown.style.display = "none";
        if(description) description.style.display = "none";

        if(statusInput) statusInput.value = "No PC";

    } else {

        if(statusDropdown) statusDropdown.style.display = "block";
    }

}
        else if(d === "time_out"){

            showToast("Time out recorded","error");

            setTimeout(()=>{
                location.href="attendance_mobile.php";
            },1500);

        }
        else if(d === "occupied"){
            showToast("PC is already occupied","error");
        }
        else if(d === "already"){
            showToast("Already logged out today","error");
        }
        else if(d === "no_active_subject"){
            showToast("No active class right now","error");
        }
        else if(d === "invalid_qr"){
            showToast("Invalid QR code","error");
        }
        else if(d === "not_logged"){
            showToast("Session expired. Login again.","error");
        }
        else{
            showToast("Server error","error");
        }

    })
    .catch(()=>{
        showToast("Server error","error");
    });
}

function openPCModal(){
    const menu = document.getElementById("settingMenu");
    const modal = document.getElementById("pcModal");

    if(menu) menu.style.display = "none";
    if(modal) modal.style.display = "flex";

    const pcInput = document.getElementById("pc_no");
    const comlabInput = document.getElementById("comlab");

    if(pcInput) pcInput.value = currentPC;
    if(comlabInput) comlabInput.value = currentComlab;
}

function closePCModal(){
    const modal = document.getElementById("pcModal");
    if(modal) modal.style.display = "none";
}

function handleStatusChange(){

    let status = document.getElementById("statusSelect").value;

    let issueType = document.getElementById("issueType");
    let description = document.getElementById("description");

    // reset
    issueType.value = "";
    description.value = "";

    if(status === "Defective"){

        // ✅ show dropdown ONLY
        document.getElementById("issueDropdown").style.display = "block";

        // ❗ textbox hidden muna
        description.style.display = "none";

        issueType.required = true;
        description.required = false;

    }
    else{

        // ❌ hide everything
        document.getElementById("issueDropdown").style.display = "none";
        description.style.display = "none";

        issueType.required = false;
        description.required = false;
    }
}

function showToast(message,type="success"){

    const toast = document.getElementById("toast");
    if(!toast) return;

    toast.textContent = message;
    toast.className = "toast show " + type;

    setTimeout(()=>{
        toast.className = "toast";
    },3000);
}

function openLogoutModal(){
    const modal = document.getElementById("logoutModal");
    if(modal) modal.style.display = "flex";
}

function closeLogoutModal(){
    const modal = document.getElementById("logoutModal");
    if(modal) modal.style.display = "none";
}

const dropdown = document.getElementById("statusDropdown");

if (dropdown) {

    const selected = dropdown.querySelector(".selected");
    const options = dropdown.querySelectorAll(".options div");
    const hiddenInput = document.getElementById("statusSelect");

    selected.addEventListener("click", () => {
        dropdown.classList.toggle("active");
    });

options.forEach(opt => {
    opt.addEventListener("click", () => {

        options.forEach(o => o.classList.remove("active"));

        opt.classList.add("active");

        selected.textContent = opt.textContent;
        hiddenInput.value = opt.dataset.value;

        selected.classList.remove("status-working","status-defective","status-notused");
        
        if(opt.dataset.value === "Working"){
        selected.classList.add("status-working");
         }
        else if(opt.dataset.value === "Not Used"){
        selected.classList.add("status-notused"); // ✅ ito yung bago
        }
        else if(opt.dataset.value === "Defective"){
        selected.classList.add("status-defective");
        }

        dropdown.classList.remove("active");

        handleStatusChange();
    });
});

    document.addEventListener("click", (e) => {
        if (!dropdown.contains(e.target)) {
            dropdown.classList.remove("active");
        }
    });
}

const issueDropdown = document.getElementById("issueDropdown");

if (issueDropdown) {

    const selected = issueDropdown.querySelector(".selected");
    const hiddenInput = document.getElementById("issueType");

    // toggle dropdown
    selected.addEventListener("click", () => {
        issueDropdown.classList.toggle("active");
    });

// accordion with toggle + arrow rotate
document.querySelectorAll(".issue-group").forEach(group => {
    group.addEventListener("click", () => {

        const type = group.dataset.type;
        const target = document.querySelector("." + type);
        const arrow = group.querySelector("img");

        const isOpen = target.style.display === "block";

        // close all items
        document.querySelectorAll(".issue-items").forEach(el => {
            el.style.display = "none";
        });

        // reset arrows
        document.querySelectorAll(".issue-group img").forEach(img => {
            img.style.transform = "rotate(0deg)";
        });

        // remove active sa lahat
        document.querySelectorAll(".issue-group").forEach(g => {
            g.classList.remove("active");
        });

        // toggle open
        if (!isOpen) {
            target.style.display = "block";
            if (arrow) arrow.style.transform = "rotate(90deg)";

            // add active sa current
            group.classList.add("active");
        }

    });
});

// select issue
const options = issueDropdown.querySelectorAll(".issue-items div");

options.forEach(opt => {
    opt.addEventListener("click", () => {

        options.forEach(o => o.classList.remove("active"));
        opt.classList.add("active");

        selected.textContent = opt.textContent;

        // 🔥 GET CATEGORY (hardware / software / network)
        let group = opt.closest(".issue-group");
        let category = group ? group.dataset.type : "";

        const description = document.getElementById("description");

        // 🔥 USE CSS
        issueDropdown.classList.add("has-value");
        selected.classList.remove("status-others");

        if(opt.dataset.value === "Others"){

            // ✅ issue_type = Others
            hiddenInput.value = "Others";

            selected.classList.add("status-others");

            description.style.display = "block";
            description.required = true;
            description.value = "";
            description.focus();

            description.style.color = "#999";

            description.addEventListener("input", function(){
                if(this.value.trim() !== ""){
                    this.style.color = "#000";
                } else {
                    this.style.color = "#999";
                }
            });

        }
        else{

            // ✅ issue_type = category (hardware/software/network)
            hiddenInput.value = category;

            description.style.display = "none";
            description.required = false;

            // ✅ description = actual issue
            description.value = opt.dataset.value;
        }

        issueDropdown.classList.remove("active");
    });
});

    document.addEventListener("click", (e) => {
        if (!issueDropdown.contains(e.target)) {
            issueDropdown.classList.remove("active");
        }
    });
}