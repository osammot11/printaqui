(function () {
    function visibleElements(selector) {
        return Array.from(document.querySelectorAll(selector)).filter(function (element) {
            return element.offsetParent !== null && !element.closest('.mobile-menu');
        });
    }

    function initStorefrontMotion() {
        if (!window.gsap) return;

        const gsap = window.gsap;
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const isMobile = window.matchMedia('(max-width: 767px)').matches;
        const canHover = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

        if (reduceMotion) return;

        if (window.ScrollTrigger) {
            gsap.registerPlugin(window.ScrollTrigger);
        }

        const ease = 'power3.out';
        const revealDistance = isMobile ? 16 : 28;
        const revealDuration = isMobile ? 0.46 : 0.68;

        gsap.from('.site-header .navbar', {
            autoAlpha: 0,
            y: isMobile ? -6 : -12,
            duration: 0.55,
            ease: ease,
            clearProps: 'all',
        });

        gsap.from('.desktop-nav a, .nav-actions > *', {
            autoAlpha: 0,
            y: -8,
            duration: 0.42,
            delay: 0.12,
            stagger: 0.035,
            ease: ease,
            clearProps: 'all',
        });

        const heroItems = visibleElements('.hero .stack-mid > *');
        if (heroItems.length) {
            gsap.from(heroItems, {
                autoAlpha: 0,
                y: isMobile ? 14 : 24,
                duration: isMobile ? 0.52 : 0.74,
                delay: 0.08,
                stagger: isMobile ? 0.055 : 0.085,
                ease: ease,
                clearProps: 'all',
            });
        }

        const heroImages = visibleElements('.hero img');
        if (heroImages.length) {
            gsap.from(heroImages, {
                autoAlpha: 0,
                scale: isMobile ? 1.01 : 1.035,
                y: isMobile ? 10 : 18,
                duration: isMobile ? 0.58 : 0.82,
                delay: 0.16,
                ease: ease,
                clearProps: 'opacity,visibility,transform',
            });
        }

        const revealSelector = [
            '.section-head > *',
            '.panel',
            '.card',
            '.collection-card',
            '.product-gallery-button',
            '.order-status-grid > div',
            '.checkout-data-grid > div',
            '.grid-2 > .card',
            '.footer-lead',
            '.footer-column',
            '.footer-meta',
        ].join(',');

        const revealElements = visibleElements(revealSelector);

        if (revealElements.length && window.ScrollTrigger) {
            gsap.set(revealElements, {
                autoAlpha: 0,
                y: revealDistance,
            });

            window.ScrollTrigger.batch(revealElements, {
                start: isMobile ? 'top 94%' : 'top 90%',
                once: true,
                batchMax: isMobile ? 4 : 8,
                onEnter: function (batch) {
                    gsap.to(batch, {
                        autoAlpha: 1,
                        y: 0,
                        duration: revealDuration,
                        stagger: isMobile ? 0.04 : 0.065,
                        ease: ease,
                        clearProps: 'opacity,visibility,transform',
                    });
                },
            });
        } else if (revealElements.length) {
            gsap.from(revealElements, {
                autoAlpha: 0,
                y: revealDistance,
                duration: revealDuration,
                stagger: isMobile ? 0.025 : 0.045,
                ease: ease,
                clearProps: 'all',
            });
        }

        if (!isMobile && window.ScrollTrigger) {
            visibleElements('.hero img, [data-main-product-image]').forEach(function (image) {
                gsap.to(image, {
                    yPercent: -4,
                    ease: 'none',
                    scrollTrigger: {
                        trigger: image,
                        start: 'top bottom',
                        end: 'bottom top',
                        scrub: 0.6,
                    },
                });
            });
        }



        const mainProductImage = document.querySelector('[data-main-product-image]');
        visibleElements('[data-gallery-image]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!mainProductImage) return;

                gsap.fromTo(mainProductImage, {
                    autoAlpha: 0.72,
                    scale: 0.992,
                }, {
                    autoAlpha: 1,
                    scale: 1,
                    duration: 0.28,
                    ease: 'power2.out',
                    clearProps: 'opacity,visibility,transform',
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initStorefrontMotion);
    } else {
        initStorefrontMotion();
    }
})();
