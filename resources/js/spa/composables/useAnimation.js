import gsap from 'gsap';

export function useAnimation() {
    function fadeIn(el, options = {}) {
        if (!el) {
            return;
        }

        return gsap.fromTo(
            el,
            { opacity: 0, y: options.y ?? 24 },
            {
                opacity: 1,
                y: 0,
                duration: options.duration ?? 0.6,
                ease: options.ease ?? 'power3.out',
                delay: options.delay ?? 0,
            }
        );
    }

    function fadeOut(el, options = {}) {
        if (!el) {
            return;
        }

        return gsap.to(el, {
            opacity: 0,
            y: options.y ?? -12,
            duration: options.duration ?? 0.35,
            ease: 'power2.in',
        });
    }

    function staggerIn(els, options = {}) {
        if (!els?.length) {
            return;
        }

        return gsap.fromTo(
            els,
            { opacity: 0, y: options.y ?? 20, scale: options.scale ?? 0.96 },
            {
                opacity: 1,
                y: 0,
                scale: 1,
                duration: options.duration ?? 0.5,
                stagger: options.stagger ?? 0.08,
                ease: 'back.out(1.4)',
            }
        );
    }

    function pulse(el) {
        if (!el) {
            return;
        }

        return gsap.to(el, {
            scale: 1.05,
            duration: 0.25,
            yoyo: true,
            repeat: 1,
            ease: 'power1.inOut',
        });
    }

    function pageEnter() {
        const root = document.querySelector('#spa-root');
        if (root) {
            gsap.fromTo(root, { opacity: 0.92 }, { opacity: 1, duration: 0.35, ease: 'power2.out' });
        }
    }

    function float(el) {
        if (!el) {
            return;
        }

        return gsap.to(el, {
            y: -8,
            duration: 2,
            repeat: -1,
            yoyo: true,
            ease: 'sine.inOut',
        });
    }

    return {
        fadeIn,
        fadeOut,
        staggerIn,
        pulse,
        pageEnter,
        float,
    };
}
