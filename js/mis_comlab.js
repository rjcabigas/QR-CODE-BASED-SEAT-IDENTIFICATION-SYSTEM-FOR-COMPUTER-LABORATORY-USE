const btn = document.getElementById("comlabBtn");
const list = document.getElementById("comlabList");
const icon = document.getElementById("dropIcon");
const addBtn = document.getElementById("addComlabBtn");
const input = document.getElementById("comlabInput");

let open = false;
let saving = false;

window.selectedComlab = null;

let selectedLabToDelete = null;

const deleteModal = document.getElementById("comlabDeleteModal");
const deleteTitle = document.getElementById("deleteTitle");
const deleteConfirm = document.getElementById("deleteConfirm");
const deleteCancel = document.getElementById("deleteCancel");

restoreSelectedLab();

btn.onclick = (e)=>{
    e.stopPropagation();
    open = !open;

    if(open){
        list.style.display = "block";
        icon.style.transform = "rotate(90deg)";
    }else{
        closeAll();
    }
};

addBtn.onclick = (e)=>{
    e.stopPropagation();
    input.style.display = "block";
    input.focus();
};

input.addEventListener("keydown",(e)=>{
    if(e.key === "Enter"){
        e.preventDefault();
        saveComlab();
    }
});

list.addEventListener("click",(e)=>{

    const item = e.target.closest(".comlabItem");
    if(!item) return;

    const labId = item.dataset.id;
    const labName = item.querySelector("span").innerText;

    if(e.target.classList.contains("removeComlab")){

        e.stopPropagation();

        selectedLabToDelete = labName;

        deleteTitle.innerText = `Delete ${labName}?`;

        deleteModal.style.display = "flex";

        return;
    }

    selectLab(item);
});

function selectLab(item){

    const labId = item.dataset.id;
    const labName = item.querySelector("span").innerText;

    window.selectedComlab = labId;
    localStorage.setItem("selectedComlab",labId);

    document.getElementById("comlabText").innerText = labName;

    loadPCs();
    closeAll();
}

function restoreSelectedLab(){

    const labs = document.querySelectorAll(".comlabItem");

    if(!labs.length) return;

    const saved = localStorage.getItem("selectedComlab");

    if(saved){

        const found = document.querySelector(`.comlabItem[data-id="${saved}"]`);

        if(found){
            selectLab(found);
            return;
        }

    }

    selectLab(labs[0]);
}

function closeAll(){
    open = false;
    list.style.display = "none";
    icon.style.transform = "rotate(0deg)";
    input.style.display = "none";
}

function saveComlab(){

    if(saving) return;

    const name = input.value.trim();
    if(!name) return;

    saving = true;

    fetch("",{
        method:"POST",
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:"lab="+encodeURIComponent(name)
    })
    .then(()=>location.reload())
    .finally(()=>saving=false);
}

document.addEventListener("click",(e)=>{
    if(!btn.contains(e.target) && !list.contains(e.target)){
        closeAll();
    }
});

deleteConfirm.addEventListener("click", ()=>{

    if(!selectedLabToDelete) return;

    fetch("monitor.php",{
        method:"POST",
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:"delete_lab="+encodeURIComponent(selectedLabToDelete)
    })
    .then(()=> location.reload());

});

deleteCancel.addEventListener("click", ()=>{
    deleteModal.style.display = "none";
    selectedLabToDelete = null;
});

deleteModal.addEventListener("click",(e)=>{
    if(e.target === deleteModal){
        deleteModal.style.display = "none";
        selectedLabToDelete = null;
    }
});