const qrGenerate = document.querySelector(".qr-generate");
const qrPreview = document.querySelector(".qr-preview");
const qrModal = document.querySelector(".qr-modal");

const qrSelected = document.getElementById("qrSelected");
const qrMenu = document.getElementById("qrDropdownMenu");

let selectedPC = null;

const originalGenerateText = qrGenerate.innerText;

qrGenerate.disabled = true;

qrBtn.addEventListener("click",(e)=>{

e.stopPropagation();

if(!window.selectedComlab){
showToast("Create Comlab first");
return;
}

qrModal.style.display="flex";
qrPreview.innerHTML="";

qrGenerate.disabled = true;
selectedPC = null;
qrSelected.innerText = "Select PC";

loadQRPCs();

});

function loadQRPCs(){

const pcs=[...document.querySelectorAll(".pc-box")].map(box=>{
return parseInt(box.querySelector("span").innerText.replace("PC ",""));
});

qrMenu.innerHTML = "";
qrGenerate.disabled = true;
selectedPC = null;
qrSelected.innerText = "Select PC";

if(!pcs.length) return;

pcs.sort((a,b)=>a-b);

qrMenu.innerHTML += `<div class="qr-option" data-value="ALL">All PCs</div>`;
qrMenu.innerHTML += `<div class="qr-option" data-value="0">No PC</div>`;

pcs.forEach(n=>{
qrMenu.innerHTML += `
<div class="qr-option" data-value="${n}">
PC ${String(n).padStart(2,"0")}
</div>`;
});

}

qrSelected.addEventListener("click", (e)=>{
e.stopPropagation();
qrMenu.style.display = qrMenu.style.display === "block" ? "none" : "block";
});

document.addEventListener("click",(e)=>{

if(e.target.classList.contains("qr-modal")){
closeQRModal();
}

qrMenu.style.display = "none";

});

document.addEventListener("keydown",(e)=>{

if(e.key==="Escape"){
closeQRModal();
}

});

qrMenu.addEventListener("click",(e)=>{

if(e.target.classList.contains("qr-option")){

selectedPC = e.target.dataset.value;

qrSelected.innerText = e.target.innerText;

qrGenerate.disabled = false;

qrMenu.style.display = "none";

}

});

function closeQRModal(){

qrModal.style.display="none";
qrPreview.innerHTML="";
qrGenerate.disabled=true;

selectedPC = null;
qrSelected.innerText = "Select PC";

}

function startGenerating(duration=2000){

qrGenerate.disabled=true;

let dots=0;

qrGenerate.innerText="Generating";

const interval=setInterval(()=>{

dots=(dots+1)%4;

qrGenerate.innerText="Generating"+".".repeat(dots);

},400);

setTimeout(()=>{

clearInterval(interval);

qrGenerate.innerText=originalGenerateText;
qrGenerate.disabled=false;

},duration);

}

qrGenerate.addEventListener("click",()=>{

const val = selectedPC;

if(!val){
showToast("Please select a PC first.");
return;
}

if(val==="ALL"){

startGenerating(4000);

qrPreview.innerHTML=`
<div class="zip-loading">
<div class="spinner"></div>
<p>Preparing ZIP folder.</p>
</div>
`;

setTimeout(()=>{

window.location="?download_qr_all="+window.selectedComlab;

setTimeout(()=>{
qrPreview.innerHTML=`<img src="../img/mis_img/zip_folder.png">`;
},800);

},2500);

return;

}

startGenerating(2000);

const url=`https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=COMLAB-${window.selectedComlab}-PC-${val}`;

const filename=`COMLAB-${window.selectedComlab}-PC-${String(val).padStart(2,"0")}.png`;

qrPreview.innerHTML=`<img src="${url}">`;

fetch(url)
.then(res=>res.blob())
.then(blob=>{

const a=document.createElement("a");

a.href=URL.createObjectURL(blob);
a.download=filename;

document.body.appendChild(a);
a.click();
a.remove();

})
.catch(err=>console.error(err));

});

function showToast(message){

const toast=document.createElement("div");

toast.className="toast-message";
toast.innerText=message;

document.body.appendChild(toast);

setTimeout(()=>{
toast.classList.add("show");
},10);

setTimeout(()=>{
toast.classList.remove("show");

setTimeout(()=>{
toast.remove();
},300);

},3000);

}