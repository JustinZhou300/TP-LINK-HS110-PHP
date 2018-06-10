
const customInputRow =  document.getElementById("customInputRow");
const lightSliderRow = document.getElementById("lightSliderRow");
const rangeSlider = document.getElementById("brightnessRange");
const labelBrightness  = document.getElementById("labelBrightness") ;
const form = document.getElementById("clientForm");
const table = document.getElementById("table");
const commands = document.getElementById("commands") ;
const device = document.getElementById("device") ;

customInputRow.style.display = "none";
lightSliderRow.style.display = "none";
changeCommands();
checkcustomInput();

function checkcustomInput(){ 
    if (commands.value === "custom"){
        customInputRow.style.display = "block";
    } else if (commands.value === "transition_light"){
        customInputRow.style.display = "none";
        lightSliderRow.style.display = "block";
    }
    else {
        lightSliderRow.style.display = "none";
        customInputRow.style.display = "none";
    }
}

function changeCommands(){
    if (device.value === "1"){
        commands.options[4].style.display = "block";
        commands.options[5].style.display = "block";
    } else{
        commands.options[4].style.display = "none";
        commands.options[5].style.display = "none";
    }
}

function showBrightnessValue(){
    setTimeout(function() { AJAXsubmit(); }, 1000);
    labelBrightness.innerHTML = `Brightness: ${rangeSlider.value}` ;   
}

form.addEventListener('submit',function(event){
    event.preventDefault();
    AJAXsubmit();
})

function updateTable(responses){
for(let [index,response] of responses.entries() ){
    const row = table.insertRow(1);
    const cell1 = row.insertCell(0);
    const cell2 = row.insertCell(1);
    cell1.innerHTML = response[0];
    cell2.innerHTML = response[1];
}
}

function AJAXsubmit(){
    let str = "";
    for (var i = 0;i<form.length ;i++) {
        if (i != 0) str += '&';
        str += `${form[i].name}=${form[i].value}`;
    }
    const url = './TPLINKResponse.php' ;
    const headers = {
    "Content-type": "application/x-www-form-urlencoded; charset=UTF-8"
    };
    const options = {
        method: 'post',
        headers: headers,
        body: str
    }
    fetch(url,options)
    .then(res => res.json())
    .catch(error => console.log('Request failed', error)) 
    .then(res => updateTable(res));
}

function resetTable(){
    table.innerHTML = "";
    const header = table.createTHead();
    const row = header.insertRow(0);
    const x = document.createElement("TH");
    const x2 = document.createElement("TH");
    const cell1 = row.appendChild(x);
    const cell2 = row.appendChild(x2);
    cell1.style.width = "120px";
    cell1.innerHTML = "IP";
    cell2.innerHTML = "Message" ;
}