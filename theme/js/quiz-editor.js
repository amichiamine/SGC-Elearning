document.addEventListener('DOMContentLoaded', function() {
    const addChoiceBtn = document.getElementById('add-choice-btn');
    const choicesContainer = document.getElementById('choices-container');
    let choiceCounter = 0;

    if (!addChoiceBtn || !choicesContainer) {
        return; // Exit if the required elements are not on the page
    }

    function addChoiceInput() {
        const choiceWrapper = document.createElement('div');
        choiceWrapper.className = 'form-group d-flex align-items-center mb-small';

        const radioInput = document.createElement('input');
        radioInput.type = 'radio';
        radioInput.name = 'is_correct';
        radioInput.value = choiceCounter;
        radioInput.className = 'mr-small';
        radioInput.required = true;
        if (choiceCounter === 0) {
            radioInput.checked = true; // Default check the first one
        }

        const textInput = document.createElement('input');
        textInput.type = 'text';
        textInput.name = `choices[${choiceCounter}]`;
        textInput.className = 'form-control';
        textInput.placeholder = `Choix ${choiceCounter + 1}`;
        textInput.required = true;

        choiceWrapper.appendChild(radioInput);
        choiceWrapper.appendChild(textInput);
        choicesContainer.appendChild(choiceWrapper);

        choiceCounter++;
    }

    addChoiceBtn.addEventListener('click', addChoiceInput);

    // Add a few choices by default for a new question
    addChoiceInput();
    addChoiceInput();
});