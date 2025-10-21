// Theme toggle with persistence
(function(){
	const root = document.documentElement;
	const storageKey = 'sa-theme';
	const btn = document.getElementById('themeToggle');

	function applyTheme(mode){
		if (mode === 'dark') {
			root.setAttribute('data-theme','dark');
				if (btn) {
					btn.setAttribute('data-mode','dark');
					btn.textContent = '☀️';
					btn.setAttribute('aria-label','Passa al tema chiaro');
					btn.title = 'Tema: scuro (clicca per chiaro)';
				}
		} else {
			root.removeAttribute('data-theme');
				if (btn) {
					btn.removeAttribute('data-mode');
					btn.textContent = '🌙';
					btn.setAttribute('aria-label','Passa al tema scuro');
					btn.title = 'Tema: chiaro (clicca per scuro)';
				}
		}
	}

	// Determine initial theme: saved -> system -> light
	let mode = localStorage.getItem(storageKey);
	if (!mode) {
		const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
		mode = prefersDark ? 'dark' : 'light';
	}
	applyTheme(mode);

	// Toggle on click
	if (btn) {
		btn.addEventListener('click', () => {
			const isDark = root.getAttribute('data-theme') === 'dark';
			const next = isDark ? 'light' : 'dark';
			applyTheme(next);
			localStorage.setItem(storageKey, next);
		});
	}

	// Respond to system theme changes if user hasn't chosen
	if (!localStorage.getItem(storageKey) && window.matchMedia) {
		const mq = window.matchMedia('(prefers-color-scheme: dark)');
		mq.addEventListener('change', (e)=>{
			applyTheme(e.matches ? 'dark' : 'light');
		});
	}
})();

// Reveal animations on scroll (progressive enhancement)
(function(){
	const els = document.querySelectorAll('.reveal, .card, .product, .table tbody tr');
	if (!('IntersectionObserver' in window)) {
		els.forEach(el=>el.classList.add('show'));
		return;
	}
	const io = new IntersectionObserver((entries)=>{
		entries.forEach(e=>{
			if (e.isIntersecting) {
				e.target.classList.add('show');
				io.unobserve(e.target);
			}
		});
	}, {threshold: 0.08});
	els.forEach(el=>io.observe(el));
})();
