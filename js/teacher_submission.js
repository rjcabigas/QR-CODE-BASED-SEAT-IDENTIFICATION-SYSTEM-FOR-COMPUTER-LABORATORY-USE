const confirmModal = document.getElementById("confirmModal");
const confirmYes = document.getElementById("confirmYes");
const confirmCancel = document.getElementById("confirmCancel");
const confirmTitle = document.getElementById("confirmTitle");

const createBtn = document.getElementById("createBtn");
const dotToggle = document.getElementById("dotToggle");
const backBtn = document.getElementById("backBtn");
const dotMenu = document.getElementById("dotMenu");
const downloadBtn = document.getElementById("downloadBtn");
const renameBtn = document.getElementById("renameBtn");
const deleteBtn = document.getElementById("deleteBtn");
const deadlineBtn = document.getElementById("deadlineBtn");

const textarea = document.getElementById("instructionContent");
const saveBtn = document.getElementById("saveInstruction");
const instructionPanel = document.getElementById("instructionPanel");

const deadlineModal = document.getElementById("deadlineModal");
const cancelDeadline = document.getElementById("cancelDeadline");

let deleteTargetId = null;
let selected = null;
let selectedFile = null;
let editing = null;
let currentInstructionFolder = null;
let currentFolderId = new URLSearchParams(window.location.search).get("folder_id") || null;
let originalInstruction = "";

function updateBackState(){
    if(!backBtn) return;
    const disabled = !currentFolderId;
    backBtn.style.pointerEvents = disabled ? "none" : "auto";
    backBtn.style.opacity = disabled ? 0.4 : 1;
}

function updateMenuState(){
if(currentFolderId){
    createBtn.style.pointerEvents="none";
    createBtn.style.opacity=0.5;

    downloadBtn.style.pointerEvents=(selected || selectedFile)?"auto":"none";
    downloadBtn.style.opacity=(selected || selectedFile)?1:0.5;

    renameBtn.style.pointerEvents="none";
    renameBtn.style.opacity=0.5;

    deleteBtn.style.pointerEvents="none";
    deleteBtn.style.opacity=0.5;

    if(deadlineBtn){
        deadlineBtn.style.pointerEvents="none";
        deadlineBtn.style.opacity=0.5;
    }

    if(sectionBtn){
        sectionBtn.style.pointerEvents="none";
        sectionBtn.style.opacity=0.5;
    }

    return;
}

    const disabled = !selected;

    [downloadBtn,renameBtn,deleteBtn].forEach(btn=>{
        if(!btn) return;
        btn.style.pointerEvents = disabled ? "none" : "auto";
        btn.style.opacity = disabled ? 0.5 : 1;
    });

    if(deadlineBtn){
        deadlineBtn.style.pointerEvents = disabled ? "none" : "auto";
        deadlineBtn.style.opacity = disabled ? 0.5 : 1;
    }

    const noSection = !activeSection || activeSection.trim() === "";

    createBtn.style.pointerEvents = noSection ? "none" : "auto";
    createBtn.style.opacity = noSection ? 0.5 : 1;

    if(noSection){
        createBtn.title = "Select a section first";
    }else{
        createBtn.title = "Create Folder";
    }
}

function closeDotMenu(){
    dotMenu.style.display = "none";
}

dotToggle.onclick = e=>{
    e.stopPropagation();
    dotMenu.style.display = dotMenu.style.display==="block"?"none":"block";
};

document.addEventListener("click",e=>{
    if(editing && document.body.contains(editing) && editing.contains(e.target)) return;

    closeDotMenu();

    if(instructionPanel && !instructionPanel.contains(e.target)){
        instructionPanel.style.display="none";
    }

    if(editing) saveEdit();

    deselectAll();
});

function initFolders(){
    document.querySelectorAll(".folder").forEach(f=>attachFolderEvents(f));
}

function attachFolderEvents(folder){
    let clickTimer = null;

    folder.onclick = e => {
        e.stopPropagation();
        if(editing) return;

        clearTimeout(clickTimer);

        clickTimer = setTimeout(() => {
            deselectAll();
            folder.classList.add("active");
            selected = folder;

            if(currentFolderId){
                selectedFile = folder;
            }

            updateMenuState();
        }, 0);
    };

    folder.ondblclick = e => {
        e.stopPropagation();
        clearTimeout(clickTimer);
        openFolder(folder);
    };
}

function openFolder(folder){
    window.location="submission.php?folder_id="+folder.dataset.id;
}

function goBack(){
    sessionStorage.setItem("clearSelection", "true");
    history.back();
}

function startEdit(folder){
    editing=folder;
    const s=folder.querySelector("span");
    s.contentEditable=true;
    s.spellcheck=false;
    s.focus();

    const range=document.createRange();
    range.selectNodeContents(s);

    const sel=window.getSelection();
    sel.removeAllRanges();
    sel.addRange(range);

    s.onblur=()=>saveEdit();

    s.onkeydown=e=>{
        if(e.key==="Enter"){
            e.preventDefault();
            saveEdit();
        }
    };
}

function deselectAll(){
    document.querySelectorAll(".folder").forEach(x=>x.classList.remove("active"));
    document.querySelectorAll(".file-card").forEach(x=>x.classList.remove("selected"));
    selected=null;
    selectedFile=null;
    updateMenuState();
}

function ajax(data,cb){
    fetch("",{
        method:"POST",
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:new URLSearchParams(data)
    }).then(r=>r.text()).then(res=>{ if(cb) cb(res); });
}

function createFolder(e){
    if(!activeSection || activeSection.trim() === ""){
        showToast("Please select a section first");
        return;
    }

    if(e) e.stopPropagation();
    closeDotMenu();

    const empty=document.getElementById("emptyState");
    if(empty) empty.remove();

    const div=document.createElement("div");
    div.className="folder active";
    div.innerHTML=`
    <img src="../img/teacher_img/folder.png">
    <span>New Folder</span>
    <img src="../img/teacher_img/detail.png" class="detail">
    `;

    document.getElementById("area").appendChild(div);
    attachFolderEvents(div);
    attachDetail(div);

    selected=div;
    updateMenuState();

    setTimeout(()=>startEdit(div),10);
}

function renameFolder(e){
    if(e) e.stopPropagation();
    closeDotMenu();

    if(!selected){
        showToast("Select folder first");
        return;
    }

    setTimeout(()=>startEdit(selected),10);
}

function saveEdit(){
    if(!editing) return;

    if(!document.body.contains(editing)){
        editing=null;
        return;
    }

    const span=editing.querySelector("span");
    if(!span){
        editing=null;
        return;
    }

    const name=span.innerText.trim();

    if(!name){
        editing.remove();
        editing=null;
        return;
    }

    if(editing.dataset.id){
        ajax({action:"rename",id:editing.dataset.id,name:name},()=>{
            span.contentEditable=false;
            editing=null;
        });
    }else{
        ajax({action:"create",name:name},id=>{
            editing.dataset.id=id;
            span.contentEditable=false;
            editing=null;
        });
    }
}

function deleteFolder(){
    if(!selected){
        showToast("Select folder");
        return;
    }

    closeDotMenu();

    deleteTargetId = selected.dataset.id;
    const folderName = selected.querySelector("span").innerText;

    confirmTitle.innerText = `Delete this ${folderName}?`;
    confirmModal.style.display = "flex";
}

function downloadFolder(){
    if(!selected && !selectedFile){
        showToast("Select file or folder");
        return;
    }

    closeDotMenu();

    if(selectedFile){
        let url = "download.php?folder_id=" + currentFolderId + "&file=" + encodeURIComponent(selectedFile);
        window.location = url;
        return;
    }

    let id = currentFolderId;

    if(!id && selected){
        id = selected.dataset.id;
    }

    if(!id){
        showToast("Folder not found");
        return;
    }

    window.location = "download.php?folder_id=" + id;
}

document.querySelectorAll(".file-card").forEach(card=>{
    card.addEventListener("click",e=>{
        e.stopPropagation();

        if(card.classList.contains("selected")){
            card.classList.remove("selected");
            selectedFile=null;
            selected=null;
        }else{
            deselectAll();
            card.classList.add("selected");
            selectedFile=card.dataset.file;
            selected=card;
        }

        updateMenuState();
    });
});

function attachDetail(scope){
    scope.querySelectorAll(".detail").forEach(icon=>{
        icon.addEventListener("click",function(e){
            e.stopPropagation();

            let folder=this.closest(".folder");
            currentInstructionFolder=folder.dataset.id;

            let rect=folder.getBoundingClientRect();

            instructionPanel.style.top=(rect.bottom+window.scrollY+8)+"px";
            instructionPanel.style.left=(rect.left+window.scrollX)+"px";
            instructionPanel.style.display="block";

            fetch("instruction.php?action=get&folder_id="+currentInstructionFolder)
            .then(res=>res.text())
            .then(data=>{
                textarea.innerText = data;
                originalInstruction = data.trim();

                saveBtn.disabled = true;
                saveBtn.style.opacity = .5;
            });
        });
    });
}

const sectionBtn = document.getElementById("sectionBtn");
const sectionPanel = document.getElementById("sectionPanel");

let hoverTimer = null;

sectionBtn.addEventListener("mouseenter", () => {
    hoverTimer = setTimeout(() => {
        sectionPanel.style.display = "block";
    }, 300);
});

sectionBtn.addEventListener("mouseleave", () => {
    if (hoverTimer) {
        clearTimeout(hoverTimer);
        hoverTimer = null;
    }

    setTimeout(() => {
        if (!sectionPanel.matches(':hover')) {
            sectionPanel.style.display = "none";
        }
    }, 150);
});

sectionPanel.addEventListener("mouseenter", () => {
    if (hoverTimer) {
        clearTimeout(hoverTimer);
        hoverTimer = null;
    }
});

sectionPanel.addEventListener("mouseleave", () => {
    sectionPanel.style.display = "none";
});

sectionBtn.addEventListener("click", e => {
    e.stopPropagation();
    sectionPanel.style.display =
        sectionPanel.style.display === "block" ? "none" : "block";
});

document.addEventListener("click", e => {
    if (!sectionPanel.contains(e.target) && !sectionBtn.contains(e.target)) {
        sectionPanel.style.display = "none";
    }
});

document.querySelectorAll(".section-item").forEach(item=>{
    item.addEventListener("click",function(e){
        e.stopPropagation();

        const section=this.innerText.trim();

        fetch("set_active_section.php",{
            method:"POST",
            headers:{"Content-Type":"application/x-www-form-urlencoded"},
            body:"section="+encodeURIComponent(section)
        })
        .then(res=>res.text())
        .then(data=>{
            if(data==="ok"){
                location.reload();
            }
        });
    });
});

textarea.addEventListener("input", () => {
    const currentText = textarea.innerText.trim();

    if (currentText !== originalInstruction) {
        saveBtn.disabled = false;
        saveBtn.style.opacity = 1;
    } else {
        saveBtn.disabled = true;
        saveBtn.style.opacity = .5;
    }
});

window.addEventListener("pageshow", () => {
    if(sessionStorage.getItem("clearSelection") === "true"){
        deselectAll();
        sessionStorage.removeItem("clearSelection");
    }
});

initFolders();
attachDetail(document);
updateMenuState();
updateBackState();