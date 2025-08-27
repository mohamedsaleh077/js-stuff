let isDateSet = false;
let DateWindow = document.getElementById('add-date-window')

function openWindow() {
    if (!isDateSet) {
        DateWindow.style.display = 'block'
        isDateSet = true
    }
}

function closeWindow() {
    if (isDateSet) {
        DateWindow.style.display = 'none'
        isDateSet = false
    }
}

