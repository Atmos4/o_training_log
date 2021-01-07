

function openModal(id){
    document.getElementById(id).style.display = "block";
}

function closeModal(id){
    document.getElementById(id).style.display = "none";
}

function dismissModal(event){
    if (event.target.className == "modal") {
        event.target.style.display = "none";
      }
}