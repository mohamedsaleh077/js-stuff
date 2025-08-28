const DaysCount = document.getElementById('days');
const SinceDay = document.getElementById('since');
const SinceHour = document.getElementById('hours');
const DateInput = document.getElementById('date');
const TimeInput = document.getElementById('time');
const DateSubmit = document.getElementById('submit');
const startButton = document.getElementById('start-button');
const TitleInput = document.getElementById('title');
const LogsView = document.getElementById('logs');
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


function saveLog() {
    let storageLogs;

    let raw = localStorage.getItem('logs');

    if (raw == null) {
        storageLogs = {
            relapsLogs: [

            ]
        }
        console.log("EMPTY");
    } else {
        storageLogs = JSON.parse(localStorage.getItem('logs'));
        console.log(storageLogs);
    }

    let startDate = loadDate();
    let endDate = getCurrentDate();
    let Days = calcDiff(loadDate());

    let LogObj = {
        s: startDate,
        e: endDate,
        d: Days
    };

    storageLogs.relapsLogs.push(LogObj);

    console.log(storageLogs);
    localStorage.setItem('logs', JSON.stringify(storageLogs));
}

function loadLogs() {
    let raw = localStorage.getItem('logs');
    let logsHTML = '';
    if (raw == null) {
        LogsView.innerText = `No Logs yet`;
        return;
    } else {
        let storageLogs = JSON.parse(localStorage.getItem('logs'));
        storageLogs.relapsLogs.forEach((value, index) => {
            logsHTML += `\n${index + 1}, from: ${value.s.d}-${value.s.m}-${value.s.y} to: ${value.e.D}-${value.e.M}-${value.e.Y} as ${value.d} Days Streak`
        });
    }
    LogsView.innerText = logsHTML;
    return;
}


function saveDate() {
    let isStarted = localStorage.getItem('startStatue');

    if (isStarted == null) {
        localStorage.setItem('startStatue', '1')
        localStorage.setItem('date', JSON.stringify(getDate()));

        displayData();
        alert("Date Saved!");
        startButton.innerText = 'Relapse'
    } else {
        startButton.innerText = 'Relapse'
        saveLog();
        loadLogs();
        localStorage.setItem('date', JSON.stringify(getDate()));
        displayData();
        alert('Relapse Recorded')
    }
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
        M: CurrentTime.getMinutes(),
        S: CurrentTime.getSeconds()
    }
}

function getCurrentDate() {
    let currentDate = new Date();

    return {
        D: currentDate.getDate(),
        M: currentDate.getMonth() + 1,
        Y: currentDate.getFullYear()
    }
}

function displayData() {
    if (loadDate() == null) {
        DaysCount.innerText = '00';
        SinceDay.innerText = `press Start to`;
        SinceHour.innerText = `no data have been added`;
        startButton.innerText = 'Start'

    } else {
        let Days = calcDiff(loadDate());
        let Date = loadDate();
        let hours = getCurrentTime();
        startButton.innerText = 'Relapse'
        DaysCount.innerText = Days;
        SinceDay.innerText = `Since ${Date.d}-${Date.m}-${Date.y}`;
        SinceHour.innerText = `and ${hours.H} hours, ${hours.M} minute and ${hours.S} Secound`;
    }
}

function clearStorage() {
    localStorage.clear();
    localStorage.setItem('title', 'click me to set a title')
    TitleInput.value = localStorage.getItem('title');
    alert('Storage have been cleared');
    displayData();
    loadLogs();
}

function setTitle() {
    localStorage.setItem('title', TitleInput.value);
    TitleInput.value = localStorage.getItem('title');
    alert('the new title ' + TitleInput.value + ' is saved!');
    displayData();
}

TitleInput.value = localStorage.getItem('title');
displayData();
loadLogs();
setInterval(() => { displayData() }, 1000);