/**
 * Core Interactive Scripts for Lapaktifikasi Landing / Public Pages
 * Optimized for PageSpeed Insights (0 Forced Reflow, Decoupled rAF Scroll, Deferred Initializations)
 */

(function () {
    'use strict';

    // MOBILE MENU DRAWER
    window.toggleMobileMenu = function () {
        const menu = document.getElementById('mobileMenu');
        const overlay = document.getElementById('mobileMenuOverlay');
        if (!menu || !overlay) return;
        const isOpen = menu.classList.contains('open');
        if (isOpen) {
            window.closeMobileMenu();
        } else {
            menu.classList.add('open');
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeMobileMenu = function () {
        const menu = document.getElementById('mobileMenu');
        const overlay = document.getElementById('mobileMenuOverlay');
        if (menu) menu.classList.remove('open');
        if (overlay) overlay.classList.remove('open');
        document.body.style.overflow = '';
    };

    // SINGLE rAF BATCHED SCROLL HANDLER (Eliminates Forced Reflow)
    let lastScrollY = 0;
    let ticking = false;
    let navbarEl = null;
    let backToTopEl = null;

    function updateScrollState() {
        if (navbarEl) {
            navbarEl.classList.toggle('scrolled', lastScrollY > 40);
        }
        if (backToTopEl) {
            backToTopEl.classList.toggle('visible', lastScrollY > 300);
        }
        ticking = false;
    }

    function onWindowScroll() {
        lastScrollY = window.scrollY || window.pageYOffset;
        if (!ticking) {
            window.requestAnimationFrame(updateScrollState);
            ticking = true;
        }
    }

    // AUTH LOGIN MODAL
    const tabLabels = {
        customer: 'Email Pelanggan',
        admin: 'Email Admin / Seller'
    };

    window.switchModalTab = function (btn, role) {
        document.querySelectorAll('.login-tab').forEach(function (t) {
            t.classList.remove('active');
        });
        if (btn) btn.classList.add('active');

        const label = document.getElementById('modal-username-label');
        const emailInput = document.getElementById('modal-email');
        if (label && tabLabels[role]) {
            label.textContent = tabLabels[role];
        }
        if (emailInput && tabLabels[role]) {
            emailInput.placeholder = 'Masukkan ' + tabLabels[role].toLowerCase();
        }
    };

    window.openModal = function (role) {
        const modal = document.getElementById('login-modal');
        if (!modal) return;
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';

        if (role) {
            const targetBtn = document.querySelector(`.login-tab[data-role="${role}"]`);
            if (targetBtn) {
                window.switchModalTab(targetBtn, role);
            }
        }

        setTimeout(function () {
            const emailInput = document.getElementById('modal-email');
            if (emailInput) emailInput.focus();
        }, 120);
    };

    window.closeModal = function () {
        const modal = document.getElementById('login-modal');
        if (modal) modal.classList.remove('open');
        document.body.style.overflow = '';
    };

    window.closeModalOverlay = function (e) {
        if (e && e.target && e.target.id === 'login-modal') {
            window.closeModal();
        }
    };

    window.togglePassword = function () {
        const pass = document.getElementById('modal-password');
        if (pass) {
            pass.type = pass.type === 'password' ? 'text' : 'password';
        }
    };

    // FAQ ACCORDION
    window.toggleFaq = function (btn) {
        if (!btn) return;
        const answer = btn.nextElementSibling;
        if (!answer) return;
        const isOpen = answer.classList.contains('open');

        // Close other FAQ items
        document.querySelectorAll('.faq-answer').forEach(function (a) {
            a.classList.remove('open');
        });
        document.querySelectorAll('.faq-question').forEach(function (q) {
            q.classList.remove('open');
        });

        if (!isOpen) {
            answer.classList.add('open');
            btn.classList.add('open');
        }
    };

    // SCROLL REVEAL OBSERVER (Using IntersectionObserver)
    const initScrollReveal = function () {
        const reveals = document.querySelectorAll('.reveal');
        if (!reveals.length || !('IntersectionObserver' in window)) {
            reveals.forEach(function (r) { r.classList.add('visible'); });
            return;
        }

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (e.isIntersecting) {
                    e.target.classList.add('visible');
                    observer.unobserve(e.target);
                }
            });
        }, { threshold: 0.1 });

        reveals.forEach(function (r) {
            observer.observe(r);
        });
    };

    // SMOOTH SCROLL FOR IN-PAGE HASH LINKS
    const initSmoothScroll = function () {
        document.querySelectorAll('a[href^="#"]').forEach(function (a) {
            a.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (!href || href === '#') return;
                const target = document.querySelector(href);
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    };

    // INITIALIZATION (Scheduled via requestIdleCallback for 0 TBT)
    function init() {
        navbarEl = document.getElementById('navbar');
        backToTopEl = document.querySelector('.back-to-top');

        if (backToTopEl) {
            backToTopEl.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }

        window.addEventListener('scroll', onWindowScroll, { passive: true });
        onWindowScroll();

        // Defer non-critical listeners to idle time
        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(function () {
                initScrollReveal();
                initSmoothScroll();
            });
        } else {
            setTimeout(function () {
                initScrollReveal();
                initSmoothScroll();
            }, 300);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
