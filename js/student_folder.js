const backBtn = document.getElementById("backFolder");

if(backBtn){
backBtn.onclick = ()=>{

    let parent = PARENT_ID;

    if(parent > 0){
        location.href = "folder_view.php?folder_id=" + parent;
    }else{
        location.href = "submit_file_mobile.php";
    }

};
}

const dropZone = document.getElementById("dropZone");
const fileInput = document.getElementById("fileInput");
const noFile = document.getElementById("noFile");
const fileList = document.getElementById("fileList");

let files = [];

if(dropZone && fileInput){

dropZone.onclick = ()=> fileInput.click();

fileInput.onchange = ()=>{

    files = Array.from(fileInput.files);

    noFile.style.display = files.length ? "none" : "block";

    uploadNow.disabled = files.length === 0;

    renderFiles();
};

}

function renderFiles(){

    if(!fileList) return;

    fileList.innerHTML = "";

    files.forEach((file,i)=>{

        let ext = file.name.split('.').pop().toLowerCase();
        let icon = "../img/student_img/files.png";

        if(ext === "pdf") icon = "../img/student_img/pdf.png";
        else if(ext === "doc" || ext === "docx") icon = "../img/student_img/word.png";
        else if(ext === "xls" || ext === "xlsx") icon = "../img/student_img/excel.png";

        const sizeMB = (file.size / 1024 / 1024).toFixed(1);

        let row = document.createElement("div");
        row.className = "file-row";

        row.innerHTML = `
        <div class="file-info">
            <img src="${icon}">
            <div class="file-text">
                <div class="file-name"></div>
                <div class="file-size">${sizeMB} MB</div>
            </div>
        </div>
        <img src="../img/student_img/delete.png"
             class="file-delete">
        `;

        row.querySelector(".file-name").textContent = file.name;

        row.querySelector(".file-delete").onclick = ()=>{
            removeFile(i);
        };

        fileList.appendChild(row);
    });

}

function removeFile(i){

    files.splice(i,1);

    fileInput.value = "";

    noFile.style.display = files.length ? "none" : "block";

    uploadNow.disabled = files.length === 0;

    renderFiles();
}

const menuPanel = document.getElementById("menuPanel");
const menuBtn = document.getElementById("menuBtn");
const createBtn = document.getElementById("createBtn");
const uploadBtn = document.getElementById("uploadBtn");
const uploadPanel = document.getElementById("uploadPanel");
const deleteBtn = document.getElementById("deleteBtn");
const renameBtn = document.getElementById("renameBtn");

const modal = document.getElementById("modal");
const deleteModal = document.getElementById("deleteModal");

const deleteTitle = document.getElementById("deleteTitle");
const cancelDelete = document.getElementById("cancelDelete");
const confirmDelete = document.getElementById("confirmDelete");

if(menuBtn){

menuBtn.onclick = e=>{
    e.stopPropagation();
    menuPanel.classList.toggle("show");
};

}

if(uploadBtn){

uploadBtn.onclick = ()=>{

    if(PARENT_ID == 0){
        alert("Open your folder first before uploading.");
        menuPanel.classList.remove("show");
        return;
    }

    uploadPanel.classList.add("show");
    menuPanel.classList.remove("show");
};

}

if(uploadPanel){

uploadPanel.onclick = e=>{
    if(e.target === uploadPanel){
        uploadPanel.classList.remove("show");
    }
};

}

if(modal){
    modal.onclick = e=>{
        if(e.target === modal){
            modal.classList.remove("show");
        }
    };
}

let selected = null;
let selectedFile = null;

function updateMenuState(){

    const active = selected || selectedFile;

    renameBtn.style.pointerEvents = active ? "auto" : "none";
    deleteBtn.style.pointerEvents = active ? "auto" : "none";

    renameBtn.style.opacity = active ? "1" : ".4";
    deleteBtn.style.opacity = active ? "1" : ".4";
}

updateMenuState();

document.addEventListener("click", e=>{

    if(
        !e.target.closest(".folder") &&
        !e.target.closest(".file-card") &&
        !e.target.closest("#menuPanel") &&
        !e.target.closest("#deleteModal")
    ){

        menuPanel.classList.remove("show");

        document.querySelectorAll(".folder").forEach(x=>x.classList.remove("active"));
        document.querySelectorAll(".file-card").forEach(x=>x.classList.remove("active"));

        selected = null;
        selectedFile = null;

        updateMenuState();
    }

});

if(createBtn){

createBtn.onclick = ()=>{

    fetch(window.location.href,{
        method:"POST",
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:"create_folder=1&folder_name=" + encodeURIComponent(STUDENT_NAME) + "&parent_id=" + FOLDER_ID
    })
    .then(()=>location.reload());

};

}

if(typeof saveSub !== "undefined"){

saveSub.onclick = ()=>{

    if(subName.value.trim() === ""){
        alert("Folder name required");
        return;
    }

    fetch("create_subfolder.php",{
        method:"POST",
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:"folder_id="+FOLDER_ID+"&name="+encodeURIComponent(subName.value)
    })
    .then(()=>location.reload());

};

}

document.querySelectorAll(".folder").forEach(f=>{

    f.addEventListener("click", e=>{

        e.stopPropagation();

        document.querySelectorAll(".folder").forEach(x=>x.classList.remove("active"));
        document.querySelectorAll(".file-card").forEach(x=>x.classList.remove("active"));

        f.classList.add("active");

        selected = {
            id:f.dataset.id,
            name:f.dataset.name
        };

        selectedFile = null;

        updateMenuState();
    });

    f.addEventListener("dblclick", ()=>{

        location.href =
        "folder_view.php?folder_id=" + f.dataset.id +
        "&folder_name=" + encodeURIComponent(f.dataset.name);

    });

});

document.querySelectorAll(".file-card").forEach(card=>{

    card.onclick = e=>{

        e.stopPropagation();

        document.querySelectorAll(".file-card").forEach(x=>x.classList.remove("active"));
        document.querySelectorAll(".folder").forEach(x=>x.classList.remove("active"));

        card.classList.add("active");

        selectedFile = card.dataset.file;
        selected = null;

        updateMenuState();
    };

});

deleteBtn.onclick = ()=>{

if(selectedFile){

    document.querySelector(".delete-message").innerText = "Do you want to delete this file?";
    deleteTitle.innerText = selectedFile.replace(/^\d+_/, '');

    deleteModal.classList.add("show");
    menuPanel.classList.remove("show");

    deleteModal.dataset.type = "file";

    return;
}

    if(!selected){
        alert("Select folder first");
        return;
    }

    deleteTitle.innerText = selected.name;
    deleteModal.classList.add("show");
    menuPanel.classList.remove("show");
};

cancelDelete.onclick = ()=>{
    deleteModal.classList.remove("show");
};

confirmDelete.onclick = ()=>{

    if(deleteModal.dataset.type === "file"){

        fetch(window.location.href,{
            method:"POST",
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:"delete_file="+encodeURIComponent(selectedFile)+"&folder_id="+FOLDER_ID
        }).then(()=>location.reload());

    }else{

        fetch(window.location.href,{
            method:"POST",
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:"delete_id="+selected.id
        }).then(()=>location.reload());

    }

};

renameBtn.onclick = ()=>{

    menuPanel.classList.remove("show");

    if(!selected){
        alert("Select folder first");
        return;
    }

    const folderEl = document.querySelector(`.folder[data-id='${selected.id}']`);
    const span = folderEl.querySelector("span");

    const oldName = span.textContent;

    const input = document.createElement("input");
    input.type = "text";
    input.value = oldName;
    input.className = "rename-input";

    span.replaceWith(input);
    input.focus();
    input.select();

    input.onblur = save;
    input.onkeydown = (e)=>{
        if(e.key === "Enter") save();
        if(e.key === "Escape") cancel();
    };

    function save(){

        const newName = input.value.trim();

        if(newName === "" || newName === oldName){
            cancel();
            return;
        }

        fetch(window.location.href,{
            method:"POST",
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:
                "rename_id=" + selected.id +
                "&rename_name=" + encodeURIComponent(newName)
        })
        .then(()=>location.reload());
    }

    function cancel(){
        input.replaceWith(span);
    }

};

const uploadNow = document.getElementById("uploadNow");

if(uploadNow){
    uploadNow.disabled = true;
}

let uploading = false;

if(uploadNow){

uploadNow.onclick = ()=>{

    if(uploading) return;

    if(files.length === 0){
        alert("Select file first");
        return;
    }

    uploading = true;

    let fd = new FormData();

    files.forEach(f=>{
        fd.append("files[]",f);
    });

    fd.append("folder_id",FOLDER_ID);

    fetch(window.location.href,{
        method:"POST",
        body:fd
    })
    .then(()=>{

        uploading = false;
        files = [];
        fileInput.value = "";

        uploadPanel.classList.remove("show");

        location.reload();

    })
    .catch(()=>{
        uploading = false;
        alert("Upload failed");
    });

};

}

if(uploadBtn && PARENT_ID == 0){
    uploadBtn.style.pointerEvents = "none";
    uploadBtn.style.opacity = "0.4";
}