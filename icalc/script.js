const userInputField = document.getElementById('user_input');
userInputField.addEventListener('input', function (event) {
  if (event.target.value === '=') {
    console.log("calc will go here");
  }
  event.target.value = event.target.value.replace(/[^0-9+\-\*\/]/g, '');
});

function AddInput(num) {
  inputElement = document.getElementById('user_input')
  let textLength = inputElement.value.length;

  let lastValue = inputElement.value[inputElement.value.length - 1];
  let valueNow = inputElement.value;
  let valueNowNoEnd = valueNow.substring(0, valueNow.length - 1)

  if (lastValue === '-' && num === '-') {
    inputElement.value = valueNowNoEnd + '+';

  } else if (
    lastValue === '-' && num === '+' ||
    lastValue === '+' && num === '-'
  ) {
    inputElement.value = valueNowNoEnd + '-';

  } else if (isOp(lastValue) && isOp(num)) {
    console.log("2 op is not possible")

  } else {
    inputElement.value += num;
  }

  inputElement.setSelectionRange(textLength, textLength);

}

function isOp(num) {
  if (num === '+' ||
    num === '-' ||
    num === '÷' ||
    num === '×') {
    return true;
  }
}

function reset() {
  document.getElementById('user_input').value = '';
}

window.onload = reset();