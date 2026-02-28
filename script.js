document.addEventListener('DOMContentLoaded', () => {
    // ═════════════════════════════════════════════════════════
    // 1. Mobile Menu Toggle
    // ═════════════════════════════════════════════════════════
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileNav = document.getElementById('mobile-nav');
    const mobileLinks = document.querySelectorAll('.mobile-link');
    const body = document.body;

    function toggleMenu() {
        mobileMenuBtn.classList.toggle('active');
        mobileNav.classList.toggle('active');
        if (mobileNav.classList.contains('active')) {
            body.style.overflow = 'hidden';
        } else {
            body.style.overflow = '';
        }
    }

    mobileMenuBtn.addEventListener('click', toggleMenu);

    mobileLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (mobileNav.classList.contains('active')) {
                toggleMenu();
            }
        });
    });

    // ═════════════════════════════════════════════════════════
    // 2. Navbar Scroll Effect
    // ═════════════════════════════════════════════════════════
    const navbar = document.getElementById('navbar');

    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // ═════════════════════════════════════════════════════════
    // 3. Scroll Reveal Animations (Intersection Observer)
    // ═════════════════════════════════════════════════════════
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.15
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    setTimeout(() => {
        document.querySelectorAll('.fade-in-up').forEach(el => {
            const rect = el.getBoundingClientRect();
            if (rect.top < window.innerHeight) {
                el.classList.add('visible');
            } else {
                observer.observe(el);
            }
        });
    }, 100);

    // ═════════════════════════════════════════════════════════
    // 4. Animated Counters for Trust Banner
    // ═════════════════════════════════════════════════════════
    const counters = document.querySelectorAll('.stat-number');
    let hasAnimated = false;

    const counterObserver = new IntersectionObserver((entries) => {
        const entry = entries[0];
        if (entry.isIntersecting && !hasAnimated) {
            hasAnimated = true;
            counters.forEach(counter => {
                const target = +counter.getAttribute('data-target');
                const duration = 2000;
                const increment = target / (duration / 16);

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

    // ═════════════════════════════════════════════════════════
    // 5. Mouse tracking glow effect on Service Cards
    // ═════════════════════════════════════════════════════════
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

    // ═════════════════════════════════════════════════════════
    // 6. Toast Notification System
    // ═════════════════════════════════════════════════════════
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${type === 'success' ? '✓' : '✕'}</span>
            <span class="toast-message">${message}</span>
        `;
        container.appendChild(toast);

        // Trigger animation
        requestAnimationFrame(() => toast.classList.add('show'));

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // ═════════════════════════════════════════════════════════
    // 7. CSRF Token Fetching
    // ═════════════════════════════════════════════════════════
    async function fetchCsrfToken(targetInputId) {
        try {
            const res = await fetch('api/admin.php?action=csrf');
            const data = await res.json();
            if (data.success && data.token) {
                const input = document.getElementById(targetInputId);
                if (input) input.value = data.token;
                return data.token;
            }
        } catch (e) {
            // PHP backend not available (e.g., GitHub Pages) — skip silently
            console.log('CSRF fetch skipped — PHP backend not available');
        }
        return '';
    }

    // ═════════════════════════════════════════════════════════
    // 8. Dynamic Testimonial Loading
    // ═════════════════════════════════════════════════════════
    const testimonialsGrid = document.getElementById('testimonials-grid');

    function renderStars(rating) {
        return '⭐'.repeat(rating);
    }

    function createTestimonialCard(review, delay = 0) {
        const card = document.createElement('div');
        card.className = 'testimonial-card fade-in-up';
        if (delay > 0) card.style.transitionDelay = `${delay * 0.15}s`;
        card.innerHTML = `
            <div class="stars">${renderStars(review.rating)}</div>
            <p class="testimonial-quote">"${review.message}"</p>
            <div class="testimonial-author">
                <strong>${review.name}</strong>
                <span>${review.location}</span>
            </div>
        `;
        return card;
    }

    async function loadApprovedReviews() {
        try {
            const res = await fetch('api/reviews.php');
            const data = await res.json();

            if (data.success && data.reviews && data.reviews.length > 0) {
                // Remove fallback hardcoded cards
                const fallbacks = testimonialsGrid.querySelectorAll('[data-fallback]');
                fallbacks.forEach(el => el.remove());

                // Render dynamic reviews
                data.reviews.forEach((review, i) => {
                    const card = createTestimonialCard(review, i);
                    testimonialsGrid.appendChild(card);

                    // Trigger reveal
                    setTimeout(() => {
                        const rect = card.getBoundingClientRect();
                        if (rect.top < window.innerHeight) {
                            card.classList.add('visible');
                        } else {
                            observer.observe(card);
                        }
                    }, 150);
                });
            }
            // If fetch succeeds but no reviews, fallbacks stay visible
        } catch (e) {
            // PHP backend not available — fallback hardcoded cards remain
            console.log('Reviews API not available — using fallback testimonials');
        }
    }

    loadApprovedReviews();

    // ═════════════════════════════════════════════════════════
    // 9. Review Submission Modal
    // ═════════════════════════════════════════════════════════
    const reviewModal = document.getElementById('review-modal');
    const openModalBtn = document.getElementById('open-review-modal');
    const closeModalBtn = document.getElementById('close-review-modal');
    const reviewForm = document.getElementById('review-form');
    const reviewFeedback = document.getElementById('review-feedback');
    const reviewSubmitBtn = document.getElementById('review-submit-btn');
    const ratingInput = document.getElementById('rating-value');
    const starPicker = document.getElementById('star-picker');
    const pickStars = document.querySelectorAll('.pick-star');
    let selectedRating = 0;

    // Open modal
    openModalBtn.addEventListener('click', async () => {
        reviewModal.classList.add('active');
        body.style.overflow = 'hidden';
        reviewFeedback.innerHTML = '';
        reviewForm.reset();
        selectedRating = 0;
        updateStarDisplay(0);
        await fetchCsrfToken('review-csrf-token');
    });

    // Close modal
    function closeModal() {
        reviewModal.classList.remove('active');
        body.style.overflow = '';
    }

    closeModalBtn.addEventListener('click', closeModal);

    // Close on overlay click
    reviewModal.addEventListener('click', (e) => {
        if (e.target === reviewModal) closeModal();
    });

    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && reviewModal.classList.contains('active')) {
            closeModal();
        }
    });

    // Star rating picker
    function updateStarDisplay(rating) {
        pickStars.forEach(star => {
            const val = parseInt(star.dataset.value);
            star.classList.toggle('active', val <= rating);
        });
    }

    pickStars.forEach(star => {
        star.addEventListener('click', () => {
            selectedRating = parseInt(star.dataset.value);
            ratingInput.value = selectedRating;
            updateStarDisplay(selectedRating);
        });

        star.addEventListener('mouseenter', () => {
            updateStarDisplay(parseInt(star.dataset.value));
        });
    });

    starPicker.addEventListener('mouseleave', () => {
        updateStarDisplay(selectedRating);
    });

    // Submit review
    reviewForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        reviewFeedback.innerHTML = '';

        // Client-side validation
        const formData = new FormData(reviewForm);
        const name = formData.get('name')?.trim();
        const location = formData.get('location')?.trim();
        const message = formData.get('message')?.trim();
        const rating = parseInt(formData.get('rating'));

        if (!name || name.length < 2) {
            showFormError(reviewFeedback, 'Please enter your name (at least 2 characters).');
            return;
        }
        if (!location || location.length < 2) {
            showFormError(reviewFeedback, 'Please enter your city.');
            return;
        }
        if (!rating || rating < 1) {
            showFormError(reviewFeedback, 'Please select a star rating.');
            return;
        }
        if (!message || message.length < 10) {
            showFormError(reviewFeedback, 'Please write at least 10 characters about your experience.');
            return;
        }

        // Submit
        reviewSubmitBtn.disabled = true;
        reviewSubmitBtn.textContent = 'Submitting...';

        try {
            const payload = {
                name: name,
                location: location,
                rating: rating,
                message: message,
                website: formData.get('website') || '',
                csrf_token: formData.get('csrf_token') || ''
            };

            const res = await fetch('api/reviews.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                reviewFeedback.innerHTML = `<p class="feedback-success">${data.message}</p>`;
                reviewForm.reset();
                selectedRating = 0;
                updateStarDisplay(0);

                // Auto-close modal after delay
                setTimeout(() => {
                    closeModal();
                    showToast(data.message, 'success');
                }, 2000);
            } else {
                showFormError(reviewFeedback, data.error || 'Something went wrong. Please try again.');
            }
        } catch (e) {
            showFormError(reviewFeedback, 'Unable to submit your review. Please try again later or call us at (386) 675-8206.');
        }

        reviewSubmitBtn.disabled = false;
        reviewSubmitBtn.textContent = 'Submit Review';
    });

    // ═════════════════════════════════════════════════════════
    // 10. Contact Form AJAX Submission
    // ═════════════════════════════════════════════════════════
    const quoteForm = document.getElementById('quote-form');
    const contactFeedback = document.getElementById('contact-feedback');
    const contactSubmitBtn = document.getElementById('contact-submit-btn');

    // Fetch CSRF token for contact form on load
    fetchCsrfToken('contact-csrf-token');

    quoteForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        contactFeedback.innerHTML = '';

        const formData = new FormData(quoteForm);
        const name = formData.get('name')?.trim();
        const email = formData.get('email')?.trim();
        const phone = formData.get('phone')?.trim();
        const service = formData.get('service');
        const message = formData.get('message')?.trim() || '';

        // Client-side validation
        if (!name || name.length < 2) {
            showFormError(contactFeedback, 'Please enter your name.');
            return;
        }
        if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            showFormError(contactFeedback, 'Please enter a valid email address.');
            return;
        }
        if (!phone || phone.length < 7) {
            showFormError(contactFeedback, 'Please enter a valid phone number.');
            return;
        }
        if (!service) {
            showFormError(contactFeedback, 'Please select a service.');
            return;
        }

        contactSubmitBtn.disabled = true;
        contactSubmitBtn.textContent = 'Sending...';

        try {
            const payload = {
                name: name,
                email: email,
                phone: phone,
                service: service,
                message: message,
                website: formData.get('website') || '',
                csrf_token: formData.get('csrf_token') || ''
            };

            const res = await fetch('api/contact.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await res.json();

            if (data.success) {
                contactFeedback.innerHTML = `<p class="feedback-success">${data.message}</p>`;
                quoteForm.reset();
                showToast(data.message, 'success');
                // Refresh CSRF token
                fetchCsrfToken('contact-csrf-token');
            } else {
                showFormError(contactFeedback, data.error || 'Something went wrong. Please try again.');
            }
        } catch (e) {
            showFormError(contactFeedback, 'Unable to send your request. Please call us at (386) 675-8206.');
        }

        contactSubmitBtn.disabled = false;
        contactSubmitBtn.textContent = 'Request Quote';
    });

    // ═════════════════════════════════════════════════════════
    // Helper: Show form error
    // ═════════════════════════════════════════════════════════
    function showFormError(container, message) {
        container.innerHTML = `<p class="feedback-error">${message}</p>`;
    }
});
