document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileNav = document.getElementById('mobile-nav');
    const mobileLinks = document.querySelectorAll('.mobile-link');
    const body = document.body;

    function toggleMenu() {
        mobileMenuBtn.classList.toggle('active');
        mobileNav.classList.toggle('active');
        // Prevent scrolling when menu is open
        if (mobileNav.classList.contains('active')) {
            body.style.overflow = 'hidden';
        } else {
            body.style.overflow = '';
        }
    }

    mobileMenuBtn.addEventListener('click', toggleMenu);

    // Close menu when clicking a link
    mobileLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (mobileNav.classList.contains('active')) {
                toggleMenu();
            }
        });
    });

    // 2. Navbar Scroll Effect
    const navbar = document.getElementById('navbar');

    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // 3. Scroll Reveal Animations (Intersection Observer)
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.15
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target); // Trigger once
            }
        });
    }, observerOptions);

    // Initial trigger for elements already in viewport (like hero section)
    setTimeout(() => {
        document.querySelectorAll('.fade-in-up').forEach(el => {
            // Check if element is in initial viewport viewport to reveal immediately
            const rect = el.getBoundingClientRect();
            if (rect.top < window.innerHeight) {
                el.classList.add('visible');
            } else {
                observer.observe(el);
            }
        });
    }, 100); // slight delay to ensure DOM paint

    // 4. Animated Counters for Trust Banner
    const counters = document.querySelectorAll('.stat-number');
    let hasAnimated = false;

    const counterObserver = new IntersectionObserver((entries) => {
        const entry = entries[0];
        if (entry.isIntersecting && !hasAnimated) {
            hasAnimated = true;
            counters.forEach(counter => {
                const target = +counter.getAttribute('data-target');
                const duration = 2000; // 2 seconds
                const increment = target / (duration / 16); // 60fps

                let current = 0;
                const updateCounter = () => {
                    current += increment;
                    if (current < target) {
                        counter.innerText = Math.ceil(current);
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.innerText = target;
                    }
                };
                updateCounter();
            });
        }
    }, { threshold: 0.5 });

    const trustBanner = document.querySelector('.trust-banner');
    if (trustBanner) {
        counterObserver.observe(trustBanner);
    }

    // 5. Mouse tracking glow effect on Service Cards
    const cards = document.querySelectorAll('.service-card');
    cards.forEach(card => {
        card.addEventListener('mousemove', e => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            card.style.setProperty('--mouse-x', `${x}px`);
            card.style.setProperty('--mouse-y', `${y}px`);
        });
    });
});
