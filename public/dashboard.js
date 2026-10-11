document.addEventListener("DOMContentLoaded", () => {
    // Basic logout logic
    const logoutBtn = document.getElementById("logoutBtn");
    if (logoutBtn) {
        logoutBtn.addEventListener("click", (e) => {
            e.preventDefault();
            alert("Anda telah keluar.");
            window.location.href = "index.html";
        });
    }

    // Add slight entrance animation for cards
    const cards = document.querySelectorAll('.stat-card, .facility-card, .reservation-item');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'all 0.5s ease-out';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 * index);
    });

    // Notification Dropdown (opens on hover via CSS; click toggle for touch & keyboard)
    const notificationToggle = document.getElementById('notificationToggle');
    const notificationWrapper = notificationToggle?.closest('.notification-wrapper');

    if (notificationToggle && notificationWrapper) {
        const isNotificationOpen = () => notificationWrapper.classList.contains('is-open');
        const setNotificationOpen = (open) => {
            notificationWrapper.classList.toggle('is-open', open);
            notificationToggle.setAttribute('aria-expanded', String(open));
        };

        notificationWrapper.addEventListener('mouseenter', () => notificationToggle.setAttribute('aria-expanded', 'true'));
        notificationWrapper.addEventListener('mouseleave', () => notificationToggle.setAttribute('aria-expanded', String(isNotificationOpen())));

        notificationToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            setNotificationOpen(!isNotificationOpen());
        });

        document.addEventListener('click', (e) => {
            if (isNotificationOpen() && !notificationWrapper.contains(e.target)) {
                setNotificationOpen(false);
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && isNotificationOpen()) {
                setNotificationOpen(false);
                notificationToggle.focus();
            }
        });
    }

    // Theme Toggle Logic
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const themeIcon = document.getElementById('themeIcon');
    
    // Cek preferensi user di localStorage
    const currentTheme = localStorage.getItem('theme');
    if (currentTheme === 'light') {
        document.body.classList.add('light-mode');
        if (themeIcon) {
            themeIcon.classList.remove('fa-sun');
            themeIcon.classList.add('fa-moon');
        }
    }
    
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            document.body.classList.toggle('light-mode');
            let theme = 'dark';
            if (document.body.classList.contains('light-mode')) {
                theme = 'light';
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            } else {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            }
            localStorage.setItem('theme', theme);
        });
    }
});
