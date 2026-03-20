(function ($) {
    "use strict";

    const isSmallScreen = window.matchMedia('(max-width: 991.98px)').matches;
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // Spinner with smooth fade-out
    const spinner = function () {
        if ($('#spinner').length > 0) {
            setTimeout(function () {
                $('#spinner').addClass('hide').removeClass('show');
                setTimeout(function () {
                    $('#spinner').remove();
                }, 700);
            }, 650);
        }
    };
    spinner();
    
    
    // Keep reveal animations on larger screens only for smoother mobile performance.
    if (!isSmallScreen && !prefersReducedMotion && typeof WOW !== 'undefined') {
        new WOW().init();
    }

    // Speed up first load by deferring image decode/loading for non-critical images.
    const pageImages = document.querySelectorAll('img');
    pageImages.forEach(function (img, index) {
        if (index > 2 && !img.hasAttribute('loading')) {
            img.setAttribute('loading', 'lazy');
        }
        if (!img.hasAttribute('decoding')) {
            img.setAttribute('decoding', 'async');
        }
    });


    // Sticky glass navbar shadow on scroll
    $(window).scroll(function () {
        if ($(this).scrollTop() > 10) {
            $('#mainNavbar').addClass('navbar-scrolled shadow-lg');
        } else {
            $('#mainNavbar').removeClass('navbar-scrolled shadow-lg');
        }
    });


    // Hero floating neon dots that follow cursor direction and fade on leave
    const initHeroTentacleEffect = function () {
        const heroSection = document.getElementById('home');
        const canvas = document.getElementById('hero-tentacle-canvas');
        if (!heroSection || !canvas) {
            return;
        }

        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reducedMotion || isSmallScreen) {
            return;
        }

        const ctx = canvas.getContext('2d');
        if (!ctx) {
            return;
        }

        const TAU = Math.PI * 2;
        const rand = function (max) {
            return Math.random() * max;
        };

        const clamp = function (value, min, max) {
            return Math.max(min, Math.min(max, value));
        };

        const lerp = function (a, b, t) {
            return a + (b - a) * t;
        };

        let width = 0;
        let height = 0;
        let dpr = 1;
        let isVisible = true;
        let animationId = 0;

        const target = { x: 0, y: 0 };
        const cursor = { x: 0, y: 0 };
        const velocity = { x: 0, y: 0 };
        let follow = false;
        let intensity = 0;

        const palette = [
            [87, 226, 255],
            [90, 170, 255],
            [165, 130, 255],
            [91, 255, 214],
            [255, 170, 92],
            [113, 255, 173],
            [125, 210, 255],
            [196, 140, 255],
        ];

        class TentacleDots {
            constructor(index, total) {
                const color = palette[index % palette.length];
                this.r = color[0];
                this.g = color[1];
                this.b = color[2];
                this.index = index;
                this.total = total;
                this.segmentCount = window.innerWidth < 768 ? 33 : 41;
                this.segmentGap = 600 / (this.segmentCount - 1);
                this.baseAngle = (index / total) * TAU + rand(0.8);
                this.baseRadius = 10 + rand(26);
                this.phase = rand(TAU);
                this.dotSize = 1.15 + rand(1.85);
                this.maxAlpha = 0.44 + rand(0.35);
                this.alpha = 0;
                this.points = Array.from({ length: this.segmentCount }, function () {
                    return { x: target.x, y: target.y };
                });
            }

            resetBase() {
                this.baseAngle = (this.index / this.total) * TAU + rand(0.8);
            }

            update(time) {
                const t = time || performance.now();
                const dirX = clamp(velocity.x * 0.45, -22, 22);
                const dirY = clamp(velocity.y * 0.45, -22, 22);
                const waveX = Math.cos(t * 0.001 + this.phase) * 13;
                const waveY = Math.sin(t * 0.0013 + this.phase) * 11;

                const headTargetX = target.x + Math.cos(this.baseAngle) * this.baseRadius + waveX + dirX * 2.1;
                const headTargetY = target.y + Math.sin(this.baseAngle) * this.baseRadius + waveY + dirY * 2.1;

                this.points[0].x = lerp(this.points[0].x, headTargetX, follow ? 0.28 : 0.09);
                this.points[0].y = lerp(this.points[0].y, headTargetY, follow ? 0.28 : 0.09);

                for (let i = 1; i < this.points.length; i += 1) {
                    const prev = this.points[i - 1];
                    const current = this.points[i];
                    const pull = 0.2 - (i / this.points.length) * 0.08;
                    const tailFlow = 1 - i / this.points.length;

                    current.x = lerp(current.x, prev.x - dirX * tailFlow * 0.9, pull);
                    current.y = lerp(current.y, prev.y - dirY * tailFlow * 0.9, pull);

                    const dx = current.x - prev.x;
                    const dy = current.y - prev.y;
                    const dist = Math.hypot(dx, dy) || 1;
                    current.x = prev.x + (dx / dist) * this.segmentGap;
                    current.y = prev.y + (dy / dist) * this.segmentGap;
                }

                if (follow) {
                    this.alpha = Math.min(this.maxAlpha, this.alpha + 0.055);
                } else {
                    this.alpha = Math.max(0, this.alpha - 0.06);
                }
            }

            draw() {
                for (let i = this.points.length - 1; i >= 0; i -= 1) {
                    const p = this.points[i];
                    const fade = 1 - i / this.points.length;
                    const a = this.alpha * fade;
                    if (a <= 0.004) {
                        continue;
                    }

                    ctx.beginPath();
                    ctx.fillStyle = 'rgba(' + this.r + ',' + this.g + ',' + this.b + ',' + a + ')';
                    ctx.arc(p.x, p.y, this.dotSize * fade + intensity * 0.5, 0, TAU);
                    ctx.fill();
                }
            }
        }

        const tentacleCount = window.innerWidth < 768 ? 12 : 17;
        const tentacles = Array.from({ length: tentacleCount }, function (_, i) {
            return new TentacleDots(i, tentacleCount);
        });

        const resize = function () {
            dpr = Math.min(window.devicePixelRatio || 1, 1.5);
            width = canvas.clientWidth;
            height = canvas.clientHeight;
            canvas.width = Math.floor(width * dpr);
            canvas.height = Math.floor(height * dpr);
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

            target.x = width * 0.72;
            target.y = height * 0.52;
            cursor.x = target.x;
            cursor.y = target.y;
        };

        const render = function (time) {
            animationId = requestAnimationFrame(render);
            if (!isVisible) {
                return;
            }

            target.x = lerp(target.x, cursor.x, follow ? 0.28 : 0.08);
            target.y = lerp(target.y, cursor.y, follow ? 0.28 : 0.08);
            intensity = lerp(intensity, follow ? 1 : 0, follow ? 0.2 : 0.08);

            ctx.clearRect(0, 0, width, height);
            ctx.globalCompositeOperation = 'lighter';

            for (const tentacle of tentacles) {
                tentacle.update(time);
                tentacle.draw();
            }
        };

        const setPointerTarget = function (clientX, clientY) {
            const rect = canvas.getBoundingClientRect();
            const nx = clientX - rect.left;
            const ny = clientY - rect.top;

            velocity.x = nx - cursor.x;
            velocity.y = ny - cursor.y;

            cursor.x = nx;
            cursor.y = ny;
            follow = true;
        };

        heroSection.addEventListener('mousemove', function (event) {
            setPointerTarget(event.clientX, event.clientY);
        }, { passive: true });

        heroSection.addEventListener('touchmove', function (event) {
            if (!event.touches.length) {
                return;
            }
            const touch = event.touches[0];
            setPointerTarget(touch.clientX, touch.clientY);
        }, { passive: true });

        heroSection.addEventListener('mouseleave', function () {
            follow = false;
            velocity.x = 0;
            velocity.y = 0;
            for (const tentacle of tentacles) {
                tentacle.resetBase();
            }
        });

        const visibilityObserver = new IntersectionObserver(function (entries) {
            isVisible = entries[0].isIntersecting;
        }, { threshold: 0.1 });
        visibilityObserver.observe(heroSection);

        window.addEventListener('resize', resize);
        window.addEventListener('beforeunload', function () {
            if (animationId) {
                cancelAnimationFrame(animationId);
            }
            visibilityObserver.disconnect();
        });

        resize();
        render();
    };
    initHeroTentacleEffect();
    
    
    // Back to top button (prevent animation queue stacking on fast scroll)
    $(window).on('scroll', function () {
        const backToTop = $('.back-to-top');
        if (window.pageYOffset > 300) {
            backToTop.stop(true, true).fadeIn(180);
        } else {
            backToTop.stop(true, true).fadeOut(180);
        }
    });

    $('.back-to-top').on('click', function (e) {
        e.preventDefault();
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });


    // Faster smooth in-page scrolling for navbar links without animation queue stacking
    $('.navbar .nav-link[href^="#"]').on('click', function (e) {
        const href = this.getAttribute('href');
        if (!href || href === '#') {
            return;
        }

        const target = document.querySelector(href);
        if (!target) {
            return;
        }

        e.preventDefault();
        const y = target.getBoundingClientRect().top + window.pageYOffset - 78;
        window.scrollTo({
            top: y,
            behavior: 'smooth'
        });
    });

    // Keep existing testimonial carousel support on other pages
    if ($('.testimonial-carousel').length) {
        $('.testimonial-carousel').owlCarousel({
            autoplay: true,
            smartSpeed: 1000,
            loop: true,
            nav: false,
            dots: true,
            items: 1,
            dotsData: true,
        });
    }

    
})(jQuery);

