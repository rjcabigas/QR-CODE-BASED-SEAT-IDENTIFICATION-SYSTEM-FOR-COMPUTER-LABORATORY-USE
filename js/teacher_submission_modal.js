function showToast(msg){
    const toast = document.getElementById("toast");
    if(!toast) return;
    toast.textContent = msg;
    toast.classList.add("show");
    setTimeout(()=> toast.classList.remove("show"),2500);
}

const qs = sel => document.querySelector(sel);
const qsa = sel => document.querySelectorAll(sel);

function updateApplyState(){
    const start = qs("#startTime").value;
    const end = qs("#endTime").value;
    const active = qsa(".quick-buttons button.active").length;

    const hasTime = (start && start !== "--:--") || (end && end !== "--:--");
    qs("#applyDeadline").disabled = !(hasTime || active);
}

function formatFromDB(time){
    if(!time || time === "00:00:00") return "--:--";
    const [h,m] = time.split(":");
    let hour = parseInt(h);
    const ampm = hour >= 12 ? "PM" : "AM";
    hour = hour % 12 || 12;
    return `${hour.toString().padStart(2,"0")}:${m} ${ampm}`;
}

function to24Hour(time){
    if(!time || time === "--:--") return "";
    let [t,mod] = time.split(" ");
    let [h,m] = t.split(":");
    h = parseInt(h);
    if(mod === "PM" && h !== 12) h += 12;
    if(mod === "AM" && h === 12) h = 0;
    return `${h.toString().padStart(2,"0")}:${m}:00`;
}

function formatTime(value){
    if(!value) return "--:--";
    const [h,m] = value.split(":");
    let hour = parseInt(h);
    const ampm = hour >= 12 ? "PM" : "AM";
    hour = hour % 12 || 12;
    return `${hour.toString().padStart(2,"0")}:${m} ${ampm}`;
}

function convertToMinutes(text){
    const num = parseInt(text.match(/\d+/)?.[0] || 0);
    text = text.toLowerCase();
    if(text.includes("month")) return num * 43200;
    if(text.includes("week")) return num * 10080;
    if(text.includes("day")) return num * 1440;
    return null;
}

function durationToText(minutes){
    if(minutes % 43200 === 0) return `${minutes/43200} month`;
    if(minutes % 10080 === 0) return `${minutes/10080} week`;
    if(minutes % 1440 === 0) return `${minutes/1440} day`;
    return "";
}

function syncQuickPanelDisabled(){
    const activeBtn = qs(".quick-buttons button.active");
    const panelBtns = qsa("#quickPanel button");

    panelBtns.forEach(btn=>{
        btn.disabled = false;
        btn.style.opacity = "1";
        btn.style.cursor = "pointer";
    });

    if(!activeBtn) return;

    const activeText = activeBtn.textContent.toLowerCase();

    panelBtns.forEach(btn=>{
        if(btn.textContent.toLowerCase() === activeText){
            btn.disabled = true;
            btn.style.opacity = "0.5";
            btn.style.cursor = "not-allowed";
        }
    });
}

function attachQuickBtnEvent(btn){
    btn.onclick = e => {
        e.stopPropagation();
        e.preventDefault();

        const isAlreadyActive = btn.classList.contains("active");

        qsa(".quick-buttons button").forEach(b => {
            b.classList.remove("active");
        });

        if(isAlreadyActive){
            qs("#selectedDurationText span").textContent = "None";
        } else {
            btn.classList.add("active");
            qs("#selectedDurationText span").textContent = btn.textContent;
        }

        syncQuickPanelDisabled();
        updateApplyState();
    };
}

function syncQuickButtons(container, durations){
    qsa(".quick-buttons button").forEach(b => b.classList.remove("active"));

    const first = durations[0];

    const btn = [...container.children].find(b => b.dataset.duration == first);

    if(btn){
        btn.classList.add("active");
        qs("#selectedDurationText span").textContent = btn.textContent;
    }else{
        qs("#selectedDurationText span").textContent = "None";
    }

    syncQuickPanelDisabled();
}

function normalizeDurations(raw){
    let durations;

    try{
        durations = JSON.parse(raw);
    }catch{
        durations = raw;
    }

    if(!Array.isArray(durations)){
        durations = durations ? [durations] : [];
    }

    durations = durations
        .map(d => parseInt(d))
        .filter(d => !isNaN(d));

    return [...new Set(durations)];
}

function loadDeadline(folderId){
    fetch(`?action=get_deadline&folder_id=${folderId}`)
    .then(r=>r.json())
    .then(data=>{
        qs("#startTime").value="--:--";
        qs("#endTime").value="--:--";
        qs("#selectedDurationText span").textContent="None";

        const container = qs(".quick-buttons");
        if(!data) return;

        if(data.start_time) qs("#startTime").value = formatFromDB(data.start_time);
        if(data.end_time) qs("#endTime").value = formatFromDB(data.end_time);

        if(data.duration){
            const durations = normalizeDurations(data.duration);

            durations.forEach(d=>{
                const minutes = parseInt(d);
                let text = durationToText(minutes);
                if(!text) return;

                text += text.startsWith("1 ") ? "" : "s";

                let btn = [...container.children].find(b=>b.dataset.duration==minutes);

                if(!btn){
                    btn = document.createElement("button");
                    btn.textContent = text;
                    btn.dataset.duration = minutes;
                    container.appendChild(btn);
                    attachQuickBtnEvent(btn);
                }
            });

            syncQuickButtons(container, durations);
        }

        updateApplyState();
    });
}

confirmCancel.onclick = ()=>{
    confirmModal.style.display="none";
    deleteTargetId=null;
};

confirmYes.onclick = ()=>{
    if(!deleteTargetId) return;

    ajax({action:"delete",id:deleteTargetId},()=> showToast("Folder deleted"));

    const el = document.querySelector(`.folder[data-id="${deleteTargetId}"]`);
    if(el) el.remove();

    selected=null;
    updateMenuState();
    confirmModal.style.display="none";

    if(!qsa(".folder").length){
        qs("#area").innerHTML=`
        <div id="emptyState">
        <img src="../img/teacher_img/no_folder.png">
        <h3>No folders created yet</h3>
        <p>Folders will appear here once created.</p>
        </div>`;
    }
};

deadlineBtn?.addEventListener("click",e=>{
    e.stopPropagation();
    if(deadlineBtn.style.pointerEvents==="none") return;
    closeDotMenu();
    deadlineModal.style.display="flex";
    loadDeadline(selected.dataset.id);
});

cancelDeadline.onclick = ()=> deadlineModal.style.display="none";

qs("#applyDeadline").onclick = ()=>{
    if(!selected) return showToast("Select folder first");

    const start = qs("#startTime").value;
    const end = qs("#endTime").value;

    const activeBtn = qs(".quick-buttons button.active");
    const duration = activeBtn ? parseInt(activeBtn.dataset.duration) : null;

    ajax({
        action:"set_deadline",
        id:selected.dataset.id,
        start:to24Hour(start),
        end:to24Hour(end),
        duration: duration
    },()=>{
        showToast("Deadline saved");
        deadlineModal.style.display="none";
    });
};

document.addEventListener("keydown",e=>{
    if(e.key==="Escape"){
        confirmModal.style.display="none";
        deadlineModal.style.display="none";
    }
});

function openPicker(picker,input){
    const rect = input.getBoundingClientRect();
    picker.style.top = rect.bottom+"px";
    picker.style.left = rect.left+"px";
    picker.value="";
    picker.focus();
    setTimeout(()=> picker.showPicker?.(),10);
}

qs("#startIcon").onclick = e=>{
    e.stopPropagation();
    openPicker(qs("#startPicker"), qs("#startTime"));
};

qs("#endIcon").onclick = e=>{
    e.stopPropagation();
    openPicker(qs("#endPicker"), qs("#endTime"));
};

qs("#startTime").onclick = e=>{
    e.stopPropagation();
    openPicker(qs("#startPicker"), qs("#startTime"));
};

qs("#endTime").onclick = e=>{
    e.stopPropagation();
    openPicker(qs("#endPicker"), qs("#endTime"));
};

qs("#startPicker").onchange = function(){
    qs("#startTime").value = formatTime(this.value);
    updateApplyState();
};

qs("#endPicker").onchange = function(){
    qs("#endTime").value = formatTime(this.value);
    updateApplyState();
};

qsa(".quick-buttons button").forEach(attachQuickBtnEvent);

qs("#deadlineModal .modal-box").onclick = e=> e.stopPropagation();

function openQuickPanel(){
    const panel = qs("#quickPanel");
    panel.style.display = panel.style.display === "block" ? "none" : "block";
}

document.addEventListener("click",e=>{
    const panel = qs("#quickPanel");
    if(!panel) return;
    if(!panel.contains(e.target) && !e.target.classList.contains("add-icon")){
        panel.style.display="none";
    }
});

qsa("#quickPanel button").forEach(btn=>{
    btn.onclick = e=>{
        e.stopPropagation();

        const text = btn.textContent;
        const container = qs(".quick-buttons");

        let existing = [...container.children]
            .find(b=>b.textContent.toLowerCase()===text.toLowerCase());

        if(!existing){
            existing = document.createElement("button");
            existing.textContent = text;
            existing.dataset.duration = convertToMinutes(text);
            container.appendChild(existing);
            attachQuickBtnEvent(existing);
        }

        qsa(".quick-buttons button").forEach(b => b.classList.remove("active"));
        existing.classList.add("active");

        qs("#selectedDurationText span").textContent = existing.textContent;

        syncQuickPanelDisabled();

        qs("#quickPanel").style.display="none";
        updateApplyState();
    };
});

updateApplyState();