if(textarea){

saveBtn.disabled=true;
saveBtn.style.opacity=.5;

textarea.addEventListener("input",function(){

this.style.height="auto";
this.style.height=this.scrollHeight+"px";

const text=this.innerText.trim();

if(text===""){
saveBtn.disabled=true;
saveBtn.style.opacity=.5;
}else{
saveBtn.disabled=false;
saveBtn.style.opacity=1;
}

});
}

if(saveBtn){
saveBtn.addEventListener("click",function(){

const text=textarea.innerText.trim();

if(text===""){
showToast("Instruction cannot be empty");
return;
}

fetch("instruction.php",{
method:"POST",
headers:{
"Content-Type":"application/x-www-form-urlencoded"
},
body:"action=save&folder_id="+currentInstructionFolder+"&text="+encodeURIComponent(text)
})
.then(res=>res.text())
.then(()=>{

showToast("Instruction saved");

instructionPanel.style.display="none";

});

});
}

const deleteInstruction=document.getElementById("deleteInstruction");

if(deleteInstruction){
deleteInstruction.addEventListener("click",function(){

if(!textarea.innerText.trim()){
showToast("Nothing to delete");
return;
}

textarea.innerText="";

fetch("instruction.php",{
method:"POST",
headers:{
"Content-Type":"application/x-www-form-urlencoded"
},
body:"action=save&folder_id="+currentInstructionFolder+"&text="
});

saveBtn.disabled=true;
saveBtn.style.opacity=.5;

showToast("Instruction deleted");

});
}

const instructionBox=document.getElementById("instructionContent");

if(instructionBox){
instructionBox.addEventListener("focus",function(){
if(this.innerText.trim()===""){
this.innerHTML="";
}
});
}