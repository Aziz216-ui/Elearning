document.addEventListener('DOMContentLoaded', function() {
    // Gestion de l'ajout de questions
    const addQuestionButton = document.getElementById('add-question');
    const questionsContainer = document.getElementById('questions-container');
    let questionIndex = document.querySelectorAll('.question-container').length;

    if (addQuestionButton && questionsContainer) {
        addQuestionButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Créer un nouveau conteneur de question
            const questionTemplate = `
                <div class="question-container card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Nouvelle question</h5>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-question">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label>Texte de la question</label>
                            <input type="text" name="quiz[questions][${questionIndex}][text]" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Points</label>
                            <input type="number" name="quiz[questions][${questionIndex}][points]" class="form-control" min="1" value="1" required>
                        </div>
                        <div class="answers-container" data-prototype="">
                            <h6>Réponses</h6>
                            <div class="answers-list">
                                <!-- Les réponses seront ajoutées ici -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary add-answer">
                                <i class="fas fa-plus"></i> Ajouter une réponse
                            </button>
                        </div>
                    </div>
                </div>
            `;

            const questionElement = document.createElement('div');
            questionElement.innerHTML = questionTemplate;
            questionsContainer.appendChild(questionElement);

            // Ajouter deux réponses par défaut
            const answersList = questionElement.querySelector('.answers-list');
            addAnswer(answersList, questionIndex, 0);
            addAnswer(answersList, questionIndex, 1);

            questionIndex++;
        });
    }

    // Fonction pour ajouter une réponse
    function addAnswer(container, questionIndex, answerIndex) {
        const answerTemplate = `
            <div class="answer-item d-flex align-items-center mb-2">
                <div class="form-check me-3">
                    <input type="radio" 
                           name="quiz[questions][${questionIndex}][correct_answer]" 
                           value="${answerIndex}" 
                           class="form-check-input" 
                           ${answerIndex === 0 ? 'checked' : ''}>
                    <label class="form-check-label">Correcte</label>
                </div>
                <input type="text" 
                       name="quiz[questions][${questionIndex}][answers][${answerIndex}][text]" 
                       class="form-control" 
                       placeholder="Texte de la réponse" 
                       required>
                <button type="button" class="btn btn-sm btn-outline-danger ms-2 remove-answer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;

        const answerElement = document.createElement('div');
        answerElement.innerHTML = answerTemplate;
        container.appendChild(answerElement);
    }
    // Gestion de l'ajout de réponses
    const addAnswerButton = document.querySelector('.add-answer');
    if (addAnswerButton) {
        addAnswerButton.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Trouver le conteneur de réponses le plus proche
            const answerContainer = this.closest('.question-container').querySelector('.answers-container');
            const answerCount = answerContainer.querySelectorAll('.answer-item').length;
            
            // Vérifier qu'on ne dépasse pas 3 réponses
            if (answerCount >= 3) {
                alert('Vous ne pouvez pas ajouter plus de 3 réponses par question.');
                return;
            }
            
            // Récupérer le prototype du formulaire
            const answerPrototype = answerContainer.dataset.prototype;
            const newIndex = answerCount;
            const newAnswer = answerPrototype.replace(/__name__/g, newIndex);
            
            // Créer un nouvel élément de réponse
            const answerElement = document.createElement('div');
            answerElement.className = 'answer-item mb-2';
            answerElement.innerHTML = newAnswer;
            
            // Ajouter la réponse au conteneur
            answerContainer.appendChild(answerElement);
            
            // Mettre à jour les numéros des réponses
            updateAnswerNumbers(answerContainer);
        });
    }
    
    // Gestion de la suppression de réponses
    document.addEventListener('click', function(e) {
        if (e.target && e.target.classList.contains('remove-answer')) {
            e.preventDefault();
            
            const answerItem = e.target.closest('.answer-item');
            const answersContainer = answerItem.parentElement;
            
            // Vérifier qu'il reste au moins 2 réponses
            if (answersContainer.querySelectorAll('.answer-item').length <= 2) {
                alert('Chaque question doit avoir au moins 2 réponses.');
                return;
            }
            
            answerItem.remove();
            updateAnswerNumbers(answersContainer);
        }
    });
    
    // Mettre à jour les numéros des réponses
    function updateAnswerNumbers(container) {
        const answerItems = container.querySelectorAll('.answer-item');
        answerItems.forEach((item, index) => {
            const label = item.querySelector('label');
            if (label) {
                label.textContent = `Réponse ${index + 1}:`;
            }
        });
    }
    
    // Initialisation des numéros de réponses au chargement de la page
    document.querySelectorAll('.answers-container').forEach(container => {
        updateAnswerNumbers(container);
    });
});
