function showMessage(msg){
    alert(msg);
}

function courseMessage(){
    alert("Course details will be available soon.");
}

function materialMessage(){
    alert("This material is not available yet.");
}

function validateForm(){
    var name = document.querySelector('input[placeholder="Enter your full name"]').value;
    var phone = document.querySelector('input[placeholder="Enter your 10 digit number"]').value;
    var email = document.querySelector('input[placeholder="Enter your email"]').value;
    var password = document.querySelector('input[placeholder="Enter your password"]').value;
    var confirm = document.querySelector('input[placeholder="Confirm your password"]').value;

    if(name == "" || phone == "" || email == "" || password == "" || confirm == ""){
        alert("Please fill all the required fields.");
        return false;
    }

    if(phone.length != 10 || isNaN(phone)){
        alert("Please enter a valid 10 digit phone number.");
        return false;
    }

    if(password != confirm){
        alert("Passwords do not match.");
        return false;
    }

    alert("Account created successfully.");
    window.location.href = "dashboard.html";
    return false;
}
function changeMode(){
document.body.classList.toggle("dark");
var button = document.getElementById("modeBtn");
if(document.body.classList.contains("dark")){
    button.innerHTML = "Light Mode";
}else{
    button.innerHTML = "Dark Mode";
}
}
function signupForm(){
    var email = document.getElementById("signupEmail").value;
    var password = document.getElementById("signupPassword").value;

    if(email == "" || password == ""){
        alert("Please fill all the fields.");
        return false;
    }
    window.location.href = "dashboard.html";
    return false;
}