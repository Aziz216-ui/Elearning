document.addEventListener('DOMContentLoaded', function() {
    const quizTable = document.querySelector('.quiz-table');
    const searchInput = document.querySelector('input[name="search"]');
    const visibilitySelect = document.querySelector('select[name="isVisible"]');
    const publishedSelect = document.querySelector('select[name="isPublished"]');
    const courseSelect = document.querySelector('select[name="cours"]');
    const loadingIndicator = document.createElement('div');
    
    // Set up loading indicator
    loadingIndicator.className = 'loading-indicator';
    loadingIndicator.style.display = 'none';
    loadingIndicator.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
    quizTable.parentNode.insertBefore(loadingIndicator, quizTable.nextSibling);

    // Function to fetch quizzes via AJAX
    async function fetchQuizzes() {
        const search = searchInput ? searchInput.value : '';
        const isVisible = visibilitySelect ? visibilitySelect.value : '';
        const isPublished = publishedSelect ? publishedSelect.value : '';
        const cours = courseSelect ? courseSelect.value : '';

        // Show loading indicator
        loadingIndicator.style.display = 'block';
        quizTable.style.opacity = '0.5';

        try {
            const response = await fetch('/admin/quiz/ajax', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    search: search,
                    isVisible: isVisible,
                    isPublished: isPublished,
                    cours: cours
                })
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            updateQuizTable(data.quizzes);
        } catch (error) {
            console.error('Error fetching quizzes:', error);
            showError('Une erreur est survenue lors du chargement des quiz. Veuillez réessayer.');
        } finally {
            loadingIndicator.style.display = 'none';
            quizTable.style.opacity = '1';
        }
    }

    // Function to update the quiz table with new data
    function updateQuizTable(quizzes) {
        const tbody = quizTable.querySelector('tbody');
        
        if (!quizzes || quizzes.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center">Aucun quiz trouvé.</td></tr>';
            return;
        }

        tbody.innerHTML = quizzes.map(quiz => `
            <tr>
                <td>${quiz.id}</td>
                <td>${quiz.title}</td>
                <td>${quiz.cours ? quiz.cours.title : 'Aucun cours associé'}</td>
                <td>${quiz.questions ? quiz.questions.length : 0}</td>
                <td>${quiz.totalPoints || 0}</td>
                <td>
                    <span class="badge ${quiz.isVisible ? 'bg-success' : 'bg-secondary'}">
                        ${quiz.isVisible ? 'Visible' : 'Masqué'}
                    </span>
                </td>
                <td class="text-end">
                    <a href="/admin/quiz/${quiz.id}/edit" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <form action="/admin/quiz/${quiz.id}/toggle-visibility" method="post" class="d-inline">
                        <input type="hidden" name="_token" value="${quiz.csrfToken}">
                        <button type="submit" class="btn btn-sm ${quiz.isVisible ? 'btn-outline-warning' : 'btn-outline-success'}">
                            <i class="fas ${quiz.isVisible ? 'fa-eye-slash' : 'fa-eye'}"></i>
                            ${quiz.isVisible ? 'Masquer' : 'Afficher'}
                        </button>
                    </form>
                    <form action="/admin/quiz/${quiz.id}" method="post" class="d-inline" 
                          onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce quiz ?');">
                        <input type="hidden" name="_token" value="${quiz.csrfToken}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-trash"></i> Supprimer
                        </button>
                    </form>
                </td>
            </tr>
        `).join('');
    }

    // Function to show error message
    function showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger';
        errorDiv.textContent = message;
        
        // Insert before the table
        quizTable.parentNode.insertBefore(errorDiv, quizTable);
        
        // Remove error message after 5 seconds
        setTimeout(() => {
            errorDiv.remove();
        }, 5000);
    }

    // Debounce function to limit API calls
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    // Event listeners
    if (searchInput) {
        searchInput.addEventListener('input', debounce(fetchQuizzes, 300));
    }
    
    if (visibilitySelect) {
        visibilitySelect.addEventListener('change', fetchQuizzes);
    }
    
    if (publishedSelect) {
        publishedSelect.addEventListener('change', fetchQuizzes);
    }
    
    if (courseSelect) {
        courseSelect.addEventListener('change', fetchQuizzes);
    }

    // Initial load
    fetchQuizzes();
});
