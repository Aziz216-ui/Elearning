document.addEventListener('DOMContentLoaded', function () {
	// écoute les clics sur tout le document (délégué)
	document.body.addEventListener('click', function (e) {
		const btn = e.target.closest('.js-like-btn');
		if (!btn) return;

		e.preventDefault();

		const url = btn.datasetLikeUrl;
		const postId = btn.datasetPostId;
		if (!url || !postId) return;

		// optionnel: récupérer un token CSRF si vous l'exposez via <meta name="csrf-token" content="...">
		const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

		fetch(url, {
			method: 'POST',
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
				'Content-Type': 'application/json',
				'X-CSRF-Token': csrf
			},
			credentials: 'same-origin',
			body: null
		})
		.then(function (res) { return res.json(); })
		.then(function (data) {
			if (data && data.success) {
				const counter = document.querySelector('#likes-count-' + postId);
				if (counter) counter.textContent = data.likes;
				// Optionnel: ajouter une classe visuelle pour indiquer que l'utilisateur a liké
				btn.classList.add('is-liked');
			} else {
				console.error('Like failed', data);
			}
		})
		.catch(function (err) {
			console.error('Erreur fetch like:', err);
		});
	});
});
