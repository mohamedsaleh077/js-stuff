const DaysCount = document.getElementById('days');
const SinceDay = document.getElementById('since');
const DateInput = document.getElementById('date');
const TimeInput = document.getElementById('time');
const DateSubmit = document.getElementById('submit');
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

function saveDate(){
    localStorage.setItem('date', JSON.stringify(getDate()));
    alert("Date Saved!");
}

function loadDate(){
    return JSON.parse(localStorage.getItem('date'));
}

function calcDiff(DateAsObj) { // calculate the number of days between two dates
    let userInput = DateAsObj;
    let firstDate = new Date(userInput.d, userInput.m - 1, userInput.y)//userInput.H, userInput.M);
    let secondDate = new Date();

    let diff = Math.abs(secondDate - firstDate);
    let diffDays = Math.round(diff / oneDay);

    return diffDays - 1;
}

function getCurrentTime() {
    let CurrentTime = new Date();

    return {
        H: CurrentTime.getHours(),
        M: CurrentTime.getMinutes()
    }
}

function displayData() {
    let Days = calcDiff(loadDate());
    let Date = loadDate();
    DaysCount.innerText = Days;
    SinceDay.innerText = `Since ${Date.d}-${Date.m}-${Date.y}`;
}

setInterval( () => {displayData()}, 1000);