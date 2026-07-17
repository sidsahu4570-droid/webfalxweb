/**
 * WebFalx Main JavaScript Controller
 * Core Page Loader, Mobile Nav, Scroll progress bars, Custom Cursor, Typing, Accordions, Filters, Lightbox, Contact Form tabs, and Table of Contents
 */

// Force browser to load at the top of the page on refresh (GSAP ScrollTrigger best practice)
if (history.scrollRestoration) {
    history.scrollRestoration = 'manual';
}

document.addEventListener('DOMContentLoaded', () => {
    initPageLoader();
    initMobileNav();
    initScrollEffects();
    initCustomCursor();
    initGlowCards();
    initTypingAnimation();
    initFaqAccordion();
    initServicesFilterSearch();
    initInquiryFormValidation();
    initPortfolioFilterSearch();
    initLightboxGallery();
    initScrollProgressBars();
    initCounterAnimations();
    initContactTabs();
    initTableOfContents();
});

/**
 * 1. Page Loader
 */
function initPageLoader() {
    const loader = document.querySelector('.page-loader');
    if (!loader) return;

    window.addEventListener('load', () => {
        setTimeout(() => {
            loader.classList.add('loaded');
        }, 300);
    });

    setTimeout(() => {
        if (!loader.classList.contains('loaded')) {
            loader.classList.add('loaded');
        }
    }, 4000);
}

/**
 * 2. Mobile Navigation Toggle
 */
function initMobileNav() {
    const toggleBtn = document.querySelector('.mobile-nav-toggle');
    const navMenu = document.querySelector('.nav-menu');
    
    if (!toggleBtn || !navMenu) return;

    toggleBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        navMenu.classList.toggle('active');
        toggleBtn.setAttribute('aria-expanded', String(navMenu.classList.contains('active')));
        
        const spans = toggleBtn.querySelectorAll('span');
        if (navMenu.classList.contains('active')) {
            spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
            spans[1].style.opacity = '0';
            spans[2].style.transform = 'rotate(-45deg) translate(5px, -5px)';
        } else {
            spans[0].style.transform = 'none';
            spans[1].style.opacity = '1';
            spans[2].style.transform = 'none';
        }
    });

    document.addEventListener('click', (e) => {
        if (navMenu.classList.contains('active') && !navMenu.contains(e.target) && e.target !== toggleBtn) {
            navMenu.classList.remove('active');
            toggleBtn.setAttribute('aria-expanded', 'false');
            const spans = toggleBtn.querySelectorAll('span');
            spans[0].style.transform = 'none';
            spans[1].style.opacity = '1';
            spans[2].style.transform = 'none';
        }
    });

    navMenu.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            navMenu.classList.remove('active');
            toggleBtn.setAttribute('aria-expanded', 'false');
        });
    });
}

/**
 * 3. Scrolled Header and Back-to-Top
 */
function initScrollEffects() {
    const header = document.querySelector('.navbar-header');
    const backToTopBtn = document.querySelector('.back-to-top');

    window.addEventListener('scroll', () => {
        const scrollPos = window.scrollY;

        if (header) {
            if (scrollPos > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }

        if (backToTopBtn) {
            if (scrollPos > 400) {
                backToTopBtn.classList.add('visible');
            } else {
                backToTopBtn.classList.remove('visible');
            }
        }
    });

    if (backToTopBtn) {
        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}

/**
 * 4. Dual Custom Cursor (Dot and Outer ring with inertia)
 */
function initCustomCursor() {
    const dot = document.querySelector('.custom-cursor-dot');
    const outline = document.querySelector('.custom-cursor-outline');
    
    if (!dot || !outline) return;

    if (window.matchMedia("(pointer: fine)").matches) {
        document.body.style.cursor = 'none';
        dot.style.opacity = '1';
        outline.style.opacity = '1';
    } else {
        dot.style.display = 'none';
        outline.style.display = 'none';
        return;
    }

    let mouseX = 0, mouseY = 0;
    let outlineX = 0, outlineY = 0;
    const speed = 0.15;

    document.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
        
        dot.style.left = mouseX + 'px';
        dot.style.top = mouseY + 'px';
    });

    function animateOutline() {
        outlineX += (mouseX - outlineX) * speed;
        outlineY += (mouseY - outlineY) * speed;

        outline.style.left = outlineX + 'px';
        outline.style.top = outlineY + 'px';

        requestAnimationFrame(animateOutline);
    }
    requestAnimationFrame(animateOutline);

    const updateHoverables = () => {
        const hoverables = document.querySelectorAll('a, button, .btn, .faq-header, .interactive-card, input, textarea, select, .lightbox-trigger, .form-tab-btn');
        hoverables.forEach(item => {
            item.addEventListener('mouseenter', () => {
                document.body.classList.add('cursor-hover');
            });
            item.addEventListener('mouseleave', () => {
                document.body.classList.remove('cursor-hover');
            });
        });
    };
    updateHoverables();
    setInterval(updateHoverables, 2000);
}

/**
 * 5. Radial Gradient Light tracking for Glow Cards
 */
function initGlowCards() {
    const updateGlow = () => {
        const cards = document.querySelectorAll('.glow-card');
        cards.forEach(card => {
            card.addEventListener('mousemove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                card.style.setProperty('--mouse-x', `${x}px`);
                card.style.setProperty('--mouse-y', `${y}px`);
            });
        });
    };
    updateGlow();
    setInterval(updateGlow, 2000);
}

/**
 * 6. Dynamic Typing Animation in Hero Section
 */
function initTypingAnimation() {
    const typingSpan = document.getElementById('typing-text');
    if (!typingSpan) return;

    let words = [];
    try {
        words = JSON.parse(typingSpan.getAttribute('data-words') || '[]');
    } catch(e) {
        words = ["Local Businesses", "Startups", "Doctors & Clinics", "E-commerce Brands", "Manufacturers", "Corporate Companies"];
    }

    if (words.length === 0) {
        words = ["Local Businesses", "Startups", "Doctors & Clinics", "E-commerce Brands", "Manufacturers", "Corporate Companies"];
    }

    let wordIndex = 0;
    let charIndex = 0;
    let isDeleting = false;
    let typeSpeed = 100;

    function type() {
        const currentWord = words[wordIndex];
        
        if (isDeleting) {
            typingSpan.textContent = currentWord.substring(0, charIndex - 1);
            charIndex--;
            typeSpeed = 50;
        } else {
            typingSpan.textContent = currentWord.substring(0, charIndex + 1);
            charIndex++;
            typeSpeed = 150;
        }

        if (!isDeleting && charIndex === currentWord.length) {
            typeSpeed = 2000;
            isDeleting = true;
        } else if (isDeleting && charIndex === 0) {
            isDeleting = false;
            wordIndex = (wordIndex + 1) % words.length;
            typeSpeed = 500;
        }

        setTimeout(type, typeSpeed);
    }

    setTimeout(type, 1000);
}

/**
 * 7. FAQ Accordion Handler
 */
function initFaqAccordion() {
    const faqContainer = document.querySelector('.faq-accordion');
    if (!faqContainer) return;

    faqContainer.addEventListener('click', (e) => {
        const header = e.target.closest('.faq-header');
        if (!header) return;

        const card = header.closest('.faq-card');
        const body = card.querySelector('.faq-body');

        const isActive = card.classList.contains('active');
        
        const allCards = faqContainer.querySelectorAll('.faq-card');
        allCards.forEach(c => {
            c.classList.remove('active');
            c.querySelector('.faq-body').style.maxHeight = null;
        });

        if (!isActive) {
            card.classList.add('active');
            body.style.maxHeight = body.scrollHeight + 'px';
        }
    });
}

/**
 * 8. Dynamic Client-Side Search and Category Filtering for Services
 */
function initServicesFilterSearch() {
    const searchInput = document.getElementById('service-search');
    const sortSelect = document.getElementById('service-sort');
    const filterButtons = document.querySelectorAll('.filter-btn');
    const serviceCards = document.querySelectorAll('.service-grid-item');
    const gridContainer = document.querySelector('.services-list-grid');

    if (!serviceCards.length || !gridContainer) return;

    let activeCategory = 'all';
    let searchQuery = '';

    function applyFilterAndSearch() {
        serviceCards.forEach(card => {
            const category = card.getAttribute('data-category');
            const title = card.querySelector('.service-title').textContent.toLowerCase();
            const desc = card.querySelector('.service-desc').textContent.toLowerCase();
            
            const matchesCategory = (activeCategory === 'all' || category === activeCategory);
            const matchesSearch = (title.includes(searchQuery) || desc.includes(searchQuery));

            if (matchesCategory && matchesSearch) {
                card.style.display = 'flex';
                card.classList.add('reveal');
                card.classList.add('revealed');
            } else {
                card.style.display = 'none';
            }
        });
    }

    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            filterButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeCategory = btn.getAttribute('data-filter');
            applyFilterAndSearch();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.toLowerCase().trim();
            applyFilterAndSearch();
        });
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', (e) => {
            const sortMode = e.target.value;
            const cardArray = Array.from(serviceCards);

            cardArray.sort((a, b) => {
                const titleA = a.querySelector('.service-title').textContent.toLowerCase().trim();
                const titleB = b.querySelector('.service-title').textContent.toLowerCase().trim();

                if (sortMode === 'asc') {
                    return titleA.localeCompare(titleB);
                } else if (sortMode === 'desc') {
                    return titleB.localeCompare(titleA);
                }
                return 0;
            });

            cardArray.forEach(card => {
                gridContainer.appendChild(card);
            });
        });
    }
}

/**
 * 9. Mobile-Friendly Form Validation
 */
function initInquiryFormValidation() {
    const forms = document.querySelectorAll('.inquiry-form');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            const phoneInput = form.querySelector('input[name="phone"]');
            const emailInput = form.querySelector('input[name="email"]');
            const nameInput = form.querySelector('input[name="full_name"]');
            
            let hasErrors = false;

            if (nameInput && nameInput.value.trim().length < 2) {
                alert('Please enter a valid full name (minimum 2 characters).');
                nameInput.focus();
                hasErrors = true;
            }

            const phonePattern = /^[\d\s\-+()]{7,20}$/;
            if (!hasErrors && phoneInput && !phonePattern.test(phoneInput.value.trim())) {
                alert('Please enter a valid phone number.');
                phoneInput.focus();
                hasErrors = true;
            }

            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!hasErrors && emailInput && !emailPattern.test(emailInput.value.trim())) {
                alert('Please enter a valid email address.');
                emailInput.focus();
                hasErrors = true;
            }

            if (hasErrors) {
                e.preventDefault();
            }
        });
    });
}

/**
 * 10. Dynamic Portfolio Filters & Search Algorithm
 */
function initPortfolioFilterSearch() {
    const searchInput = document.getElementById('project-search');
    const sortSelect = document.getElementById('project-sort');
    const filterButtons = document.querySelectorAll('.portfolio-filter-btn');
    const projectCards = document.querySelectorAll('.project-grid-item');
    const gridContainer = document.querySelector('.portfolio-list-grid');

    if (!projectCards.length || !gridContainer) return;

    let activeCategory = 'all';
    let searchQuery = '';

    function applyFilterAndSearch() {
        projectCards.forEach(card => {
            const category = card.getAttribute('data-category');
            const title = card.querySelector('.project-title').textContent.toLowerCase();
            const desc = card.querySelector('.project-desc').textContent.toLowerCase();
            const tech = card.querySelector('.project-tech-tags').textContent.toLowerCase();
            
            const matchesCategory = (activeCategory === 'all' || category === activeCategory);
            const matchesSearch = (title.includes(searchQuery) || desc.includes(searchQuery) || tech.includes(searchQuery));

            if (matchesCategory && matchesSearch) {
                card.style.display = 'flex';
                card.classList.add('reveal');
                card.classList.add('revealed');
            } else {
                card.style.display = 'none';
            }
        });
    }

    filterButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            filterButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            activeCategory = btn.getAttribute('data-filter');
            applyFilterAndSearch();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.toLowerCase().trim();
            applyFilterAndSearch();
        });
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', (e) => {
            const sortMode = e.target.value;
            const cardArray = Array.from(projectCards);

            cardArray.sort((a, b) => {
                const titleA = a.querySelector('.project-title').textContent.toLowerCase().trim();
                const titleB = b.querySelector('.project-title').textContent.toLowerCase().trim();
                const dateA = parseInt(a.getAttribute('data-date') || '0', 10);
                const dateB = parseInt(b.getAttribute('data-date') || '0', 10);

                if (sortMode === 'asc') {
                    return titleA.localeCompare(titleB);
                } else if (sortMode === 'desc') {
                    return titleB.localeCompare(titleA);
                } else if (sortMode === 'latest') {
                    return dateB - dateA;
                } else if (sortMode === 'oldest') {
                    return dateA - dateB;
                }
                return 0;
            });

            cardArray.forEach(card => {
                gridContainer.appendChild(card);
            });
        });
    }
}

/**
 * 11. Fullscreen Lightbox Image Gallery
 */
function initLightboxGallery() {
    const modal = document.querySelector('.lightbox-modal');
    if (!modal) return;

    const modalImg = modal.querySelector('.lightbox-content');
    const closeBtn = modal.querySelector('.lightbox-close');
    const prevBtn = modal.querySelector('.lightbox-prev');
    const nextBtn = modal.querySelector('.lightbox-next');
    const triggers = document.querySelectorAll('.lightbox-trigger');

    if (!triggers.length) return;

    let currentIndex = 0;
    const imagesList = Array.from(triggers).map(t => t.getAttribute('data-fullsrc') || t.getAttribute('src'));

    function showImage(index) {
        if (index < 0) index = imagesList.length - 1;
        if (index >= imagesList.length) index = 0;
        
        currentIndex = index;
        modalImg.setAttribute('src', imagesList[currentIndex]);
    }

    triggers.forEach((trigger, idx) => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            modal.style.display = 'flex';
            showImage(idx);
        });
    });

    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
    }

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });

    if (prevBtn) {
        prevBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            showImage(currentIndex - 1);
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            showImage(currentIndex + 1);
        });
    }

    document.addEventListener('keydown', (e) => {
        if (modal.style.display === 'flex') {
            if (e.key === 'ArrowLeft') {
                showImage(currentIndex - 1);
            } else if (e.key === 'ArrowRight') {
                showImage(currentIndex + 1);
            } else if (e.key === 'Escape') {
                modal.style.display = 'none';
            }
        }
    });

    let startX = 0;
    modal.addEventListener('touchstart', (e) => {
        startX = e.changedTouches[0].clientX;
    }, { passive: true });

    modal.addEventListener('touchend', (e) => {
        const endX = e.changedTouches[0].clientX;
        const diffX = endX - startX;

        if (Math.abs(diffX) > 50) {
            if (diffX > 0) {
                showImage(currentIndex - 1);
            } else {
                showImage(currentIndex + 1);
            }
        }
    }, { passive: true });
}

/**
 * 12. Skill Progress Bars scroll animation trigger
 */
function initScrollProgressBars() {
    const bars = document.querySelectorAll('.skill-bar-fill');
    if (!bars.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const bar = entry.target;
                const pct = bar.getAttribute('data-percentage') || '0';
                bar.style.width = pct + '%';
                observer.unobserve(bar);
            }
        });
    }, { threshold: 0.1 });

    bars.forEach(bar => observer.observe(bar));
}

/**
 * 13. Dynamic Statistics Counters incrementor
 */
function initCounterAnimations() {
    const counters = document.querySelectorAll('.counter-value');
    if (!counters.length) return;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = parseInt(counter.getAttribute('data-target') || '0', 10);
                let current = 0;
                const duration = 2000;
                const stepTime = Math.max(Math.floor(duration / target), 15);
                
                const timer = setInterval(() => {
                    current += Math.ceil(target / 40);
                    if (current >= target) {
                        counter.textContent = target;
                        clearInterval(timer);
                    } else {
                        counter.textContent = current;
                    }
                }, stepTime);
                
                observer.unobserve(counter);
            }
        });
    }, { threshold: 0.1 });

    counters.forEach(counter => observer.observe(counter));
}

/**
 * 14. Contact Form toggles (Inquiry vs Quotation Request forms switcher)
 */
function initContactTabs() {
    const tabBtns = document.querySelectorAll('.form-tab-btn');
    const panels = document.querySelectorAll('.form-content-panel');

    if (!tabBtns.length) return;

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => b.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));

            btn.classList.add('active');
            const targetId = btn.getAttribute('data-target');
            const activePanel = document.getElementById(targetId);
            if (activePanel) {
                activePanel.classList.add('active');
            }
        });
    });
}

/**
 * 15. Dynamic Table of Contents Generator (Prompt 7)
 * Scans rich post content headers (H2 and H3), assigns anchor IDs, and compiles sidebar navigation list items.
 */
function initTableOfContents() {
    const tocBox = document.querySelector('.toc-box-list');
    const richContent = document.querySelector('.post-content-rich');
    
    if (!tocBox || !richContent) return;

    const headings = richContent.querySelectorAll('h2, h3');
    if (headings.length === 0) {
        // Hide parent box if no headings
        const container = document.querySelector('.toc-box');
        if (container) container.style.display = 'none';
        return;
    }

    const fragment = document.createDocumentFragment();

    headings.forEach((heading, index) => {
        const text = heading.textContent;
        const anchorId = 'article-heading-' + index;
        
        // Assign ID to original heading
        heading.setAttribute('id', anchorId);

        // Create TOC list item
        const li = document.createElement('li');
        li.className = heading.tagName.toLowerCase() === 'h3' ? 'toc-h3' : 'toc-h2';

        const a = document.createElement('a');
        a.setAttribute('href', '#' + anchorId);
        a.textContent = text;
        
        li.appendChild(a);
        fragment.appendChild(li);
    });

    tocBox.appendChild(fragment);
}
