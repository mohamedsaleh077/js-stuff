const DaysCount = document.getElementById('days');
const SinceDay = document.getElementById('since');
const SinceHour = document.getElementById('hours');
const DateInput = document.getElementById('date');
const TimeInput = document.getElementById('time');
const DateSubmit = document.getElementById('submit');
const startButton = document.getElementById('start-button');
const TitleInput = document.getElementById('title');
const oneDay = 24 * 60 * 60 * 1000; // hours*minutes*seconds*milliseconds

function getDate() {
    let userDate = DateInput.value;
    let userDateAsArray = userDate.split("-");

    let userDateAsObject = {
        d: 0,
        m: 0,
        y: 0
    };

    userDateAsArray.forEach((value, index) => {
        if (index === 0) {
            userDateAsObject.d = value;
        }
        if (index === 1) {
            userDateAsObject.m = value;
        }
        if (index === 2) {
            userDateAsObject.y = value;
        }
    });

    return userDateAsObject;
}

function saveDate() {
    localStorage.setItem('date', JSON.stringify(getDate()));
    displayData();
    alert("Date Saved!");
}

function loadDate() {
    return JSON.parse(localStorage.getItem('date'));
}

function calcDiff(DateAsObj) { // calculate the number of days between two dates
    let userInput = DateAsObj;
    let firstDate = new Date(userInput.d, userInput.m - 1, userInput.y)//userInput.H, userInput.M);
    let secondDate = new Date();

    let diff = Math.abs(secondDate - firstDate);
    let diffDays = Math.round(diff / oneDay);

    return diffDays;
}

function getCurrentTime() {
    let CurrentTime = new Date();

    return {
        H: CurrentTime.getHours(),
        M: CurrentTime.getMinutes()
    }
}

function displayData() {
    if (loadDate() == null) {
        DaysCount.innerText = '00';
        SinceDay.innerText = `press Start to`;
        SinceHour.innerText = `no data have been added`;
    } else {
        let Days = calcDiff(loadDate());
        let Date = loadDate();
        let hours = getCurrentTime();
        startButton.innerText = 'Change Date'
        DaysCount.innerText = Days;
        SinceDay.innerText = `Since ${Date.d}-${Date.m}-${Date.y}`;
        SinceHour.innerText = `and ${hours.H} hours also ${hours.M} minute`;
    }
}

function clear(){
    localStorage.clear();
    alert('Storage have been cleared');
}

function setTitle(){
    localStorage.setItem('title', TitleInput.value);
    alert(`the new title '${TitleInput.value}' is saved!`)
}

displayData() 
setInterval(() => { displayData() }, 60000);