/**
 * WebFalx Animation & Scroll-Reveal Orchestrator
 * Integrates GSAP / ScrollTrigger. Fallback: Vanilla IntersectionObserver.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Determine whether GSAP is available in the global scope
    if (typeof gsap !== 'undefined') {
        initGsapAnimations();
    } else {
        console.warn('GSAP not loaded. Falling back to native IntersectionObserver.');
        initObserverFallback();
    }

    // Launch stats counters regardless of library state
    initStatsCounters();
});

/**
 * 1. Premium GSAP & ScrollTrigger Animations
 */
function initGsapAnimations() {
    // Register scroll trigger if present
    if (typeof ScrollTrigger !== 'undefined') {
        gsap.registerPlugin(ScrollTrigger);
    }

    // Header intro
    gsap.from('.navbar-header', {
        y: -100,
        opacity: 0,
        duration: 1.2,
        ease: 'power4.out',
        delay: 0.5
    });

    // Hero content reveal animations
    gsap.from('.hero-reveal', {
        y: 60,
        opacity: 0,
        duration: 1.4,
        stagger: 0.2,
        ease: 'power4.out',
        delay: 0.8
    });

    // Pages use the native reveal classes for their content.  Previously these
    // stayed hidden whenever GSAP was available because the observer fallback
    // (which adds `.revealed`) is intentionally skipped in that case.
    const nativeRevealTargets = document.querySelectorAll('.reveal, .reveal-fade, .reveal-left, .reveal-right');
    nativeRevealTargets.forEach((element) => {
        // This class is also the CSS fallback used by the non-GSAP path.
        // Add it immediately so no page section can be left invisible if a
        // ScrollTrigger event is delayed or unavailable.
        element.classList.add('revealed');
    });

    // General scroll reveals using ScrollTrigger
    const scrollReveals = document.querySelectorAll('.gsap-reveal');
    scrollReveals.forEach(element => {
        gsap.from(element, {
            scrollTrigger: {
                trigger: element,
                start: 'top 85%',
                toggleActions: 'play none none none'
            },
            y: 50,
            opacity: 0,
            duration: 1,
            ease: 'power3.out'
        });
    });

    // Parallax floating background shape micro-movement
    document.addEventListener('mousemove', (e) => {
        const depth = 0.015;
        const x = (window.innerWidth / 2 - e.clientX) * depth;
        const y = (window.innerHeight / 2 - e.clientY) * depth;
        
        gsap.to('.gradient-blob', {
            x: x * 2,
            y: y * 2,
            duration: 1,
            ease: 'power2.out'
        });
    });
}

/**
 * 2. Native IntersectionObserver Fallback
 */
function initObserverFallback() {
    const options = {
        root: null,
        rootMargin: '0px',
        threshold: 0.15
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                observer.unobserve(entry.target); // Trigger only once
            }
        });
    }, options);

    // Bind viewport targets
    const revealTargets = document.querySelectorAll('.reveal, .reveal-fade, .reveal-left, .reveal-right, .gsap-reveal');
    revealTargets.forEach(target => {
        observer.observe(target);
    });
}

/**
 * 3. Animated Statistics Counters
 */
function initStatsCounters() {
    const stats = document.querySelectorAll('.counter-value');
    if (stats.length === 0) return;

    const options = {
        threshold: 0.8
    };

    const statsObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const target = entry.target;
                const endVal = parseInt(target.getAttribute('data-target') ?? '0', 10);
                animateCounter(target, endVal);
                observer.unobserve(target);
            }
        });
    }, options);

    stats.forEach(stat => statsObserver.observe(stat));
}

function animateCounter(element, endValue) {
    let start = 0;
    const duration = 2000; // 2 seconds total animation
    const startTime = performance.now();

    function updateCounter(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        
        // Ease out quadratic
        const easeProgress = progress * (2 - progress);
        const currentValue = Math.floor(easeProgress * endValue);
        
        element.textContent = currentValue.toLocaleString();

        if (progress < 1) {
            requestAnimationFrame(updateCounter);
        } else {
            element.textContent = endValue.toLocaleString();
        }
    }

    requestAnimationFrame(updateCounter);
}
