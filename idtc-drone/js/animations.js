/**
 * ==============================================================================
 * IDTC DRONE - RESTRAINED MOTION ENGINE
 * ==============================================================================
 * Single, unified IntersectionObserver for natural, Apple-style entrance reveals.
 */

document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const revealTargets = document.querySelectorAll('.fade-in-up, [data-scroll-reveal]');

    if (!revealTargets.length) {
        return;
    }

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        revealTargets.forEach(el => el.classList.add('visible'));
        return;
    }

    const observerOptions = {
        root: null,
        rootMargin: '0px 0px -10% 0px',
        threshold: 0.12
    };

    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    revealTargets.forEach(el => revealObserver.observe(el));
});
