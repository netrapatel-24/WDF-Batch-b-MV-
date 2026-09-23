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
var notices = [];
var currentPage = 1;
var perPage = 3;

function loadNotices(){
    fetch("data.json")
        .then(function(response){
            return response.json();
        })
        .then(function(data){
            notices = data.notices;
            showNotices();
        });
}

function showNotices(){
    var search = document.getElementById("searchNotice").value.toLowerCase();
    var filter = document.getElementById("noticeFilter").value;
    var sort = document.getElementById("noticeSort").value;

    var result = notices.filter(function(notice){
        var matchSearch = notice.title.toLowerCase().includes(search) ||
                          notice.description.toLowerCase().includes(search);

        var matchFilter = filter == "all" || notice.category == filter;

        return matchSearch && matchFilter;
    });

    if(sort == "new"){
        result.sort(function(a,b){
            return new Date(b.date) - new Date(a.date);
        });
    }

    if(sort == "old"){
        result.sort(function(a,b){
            return new Date(a.date) - new Date(b.date);
        });
    }

    if(sort == "az"){
        result.sort(function(a,b){
            return a.title.localeCompare(b.title);
        });
    }

    var start = (currentPage - 1) * perPage;
    var end = start + perPage;
    var pageData = result.slice(start,end);

    var html = "";

    pageData.forEach(function(notice){
        html += `
            <div class="notice">
                <h3>${notice.title}</h3>
                <p>${notice.description}</p>
                <p>Date: ${notice.date}</p>
                <p>Category: ${notice.category}</p>
            </div>
        `;
    });

    document.getElementById("noticeList").innerHTML = html;

    showPagination(result.length);
}

function showPagination(total){
    var pages = Math.ceil(total / perPage);
    var html = "";

    for(var i = 1; i <= pages; i++){
        html += `<button onclick="changePage(${i})">${i}</button>`;
    }

    document.getElementById("pagination").innerHTML = html;
}

function changePage(page){
    currentPage = page;
    showNotices();
}

var exams = [];
var examPage = 1;
var examPerPage = 5;

function loadExams(){
    fetch("data.json")
        .then(function(response){
            return response.json();
        })
        .then(function(data){
            exams = data.exams;
            showExams();
        });
}

function showExams(){
    var search = document.getElementById("searchExam").value.toLowerCase();
    var filter = document.getElementById("examFilter").value;
    var sort = document.getElementById("examSort").value;

    var result = exams.filter(function(exam){
        var matchSearch = exam.subject.toLowerCase().includes(search) ||
                          exam.room.toLowerCase().includes(search);

        var matchFilter = filter == "all" || exam.status == filter;

        return matchSearch && matchFilter;
    });

    if(sort == "new"){
        result.sort(function(a,b){
            return new Date(b.date) - new Date(a.date);
        });
    }

    if(sort == "old"){
        result.sort(function(a,b){
            return new Date(a.date) - new Date(b.date);
        });
    }

    if(sort == "az"){
        result.sort(function(a,b){
            return a.subject.localeCompare(b.subject);
        });
    }

    var start = (examPage - 1) * examPerPage;
    var end = start + examPerPage;
    var pageData = result.slice(start,end);

    var html = "";

    pageData.forEach(function(exam){
        html += `
            <tr>
                <td><b>${exam.date}</b></td>
                <td>${exam.subject}</td>
                <td>${exam.time}</td>
                <td>${exam.room}</td>
                <td><span class="up">${exam.status}</span></td>
            </tr>
        `;
    });

    document.getElementById("examList").innerHTML = html;

    showExamPagination(result.length);
}

function showExamPagination(total){
    var pages = Math.ceil(total / examPerPage);
    var html = "";

    for(var i = 1; i <= pages; i++){
        html += `<button onclick="changeExamPage(${i})">${i}</button>`;
    }

    document.getElementById("examPagination").innerHTML = html;
}

function changeExamPage(page){
    examPage = page;
    showExams();
}
document.addEventListener("DOMContentLoaded", function(){

    if(document.getElementById("noticeList")){
        loadNotices();

        document.getElementById("searchNotice").addEventListener("input", function(){
            currentPage = 1;
            showNotices();
        });

        document.getElementById("noticeFilter").addEventListener("change", function(){
            currentPage = 1;
            showNotices();
        });

        document.getElementById("noticeSort").addEventListener("change", function(){
            currentPage = 1;
            showNotices();
        });
    }

    if(document.getElementById("examList")){
        loadExams();

        document.getElementById("searchExam").addEventListener("input", function(){
            examPage = 1;
            showExams();
        });

        document.getElementById("examFilter").addEventListener("change", function(){
            examPage = 1;
            showExams();
        });

        document.getElementById("examSort").addEventListener("change", function(){
            examPage = 1;
            showExams();
        });
    }

});