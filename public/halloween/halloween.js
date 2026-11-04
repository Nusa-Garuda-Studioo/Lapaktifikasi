/**
 * Lapaktifikasi - Enhanced Halloween Theme Engine
 * Flag HALLOWEEN_ENABLED: Set to true to activate full Halloween theme & decorations,
 * or false to completely revert to original look.
 */

const HALLOWEEN_ENABLED = false;

(function () {
    'use strict';

    if (!HALLOWEEN_ENABLED) {
        if (document.body) {
            document.body.classList.remove('halloween-theme');
        }
        return;
    }

    // Determine asset base directory relative to this script
    let halloweenAssetBase = '/halloween/assets';
    if (document.currentScript && document.currentScript.src) {
        const halloweenScriptDir = document.currentScript.src.substring(0, document.currentScript.src.lastIndexOf('/'));
        halloweenAssetBase = halloweenScriptDir + '/assets';
    }

    const HalloweenDecorations = {
        initialized: false,
        elements: [],

        init: function () {
            if (this.initialized || !HALLOWEEN_ENABLED) {
                return;
            }

            // 1. Activate root theme class
            document.body.classList.add('halloween-theme');

            // 2. Global Viewport Overlays
            this.createVignette();
            this.createCornerWeb();
            this.createFogLayer();
            this.createBatSwarm();

            // 3. Section-by-Section Decorations
            this.createHeroMoonAndBat();
            this.createStatsCandle();
            this.createFiturGhost();
            this.createTestimoniSkull();
            this.createCtaPumpkin();
            this.createFooterPumpkin();
            this.attachCardSpiderwebs();

            this.initialized = true;
        },

        createVignette: function () {
            if (document.getElementById('halloween-vignette-overlay')) return;
            const vignette = document.createElement('div');
            vignette.id = 'halloween-vignette-overlay';
            vignette.className = 'halloween-decoration-layer halloween-vignette';
            vignette.setAttribute('aria-hidden', 'true');
            document.body.appendChild(vignette);
            this.elements.push(vignette);
        },

        createCornerWeb: function () {
            if (document.getElementById('halloween-corner-web')) return;
            const webImg = document.createElement('img');
            webImg.id = 'halloween-corner-web';
            webImg.className = 'halloween-decoration-layer halloween-corner-web';
            webImg.src = halloweenAssetBase + '/halloween-spiderweb-corner.webp';
            webImg.alt = '';
            webImg.setAttribute('aria-hidden', 'true');
            webImg.width = 160;
            webImg.height = 160;
            webImg.draggable = false;
            document.body.appendChild(webImg);
            this.elements.push(webImg);
        },

        createFogLayer: function () {
            if (document.getElementById('halloween-fog-layer')) return;
            const fog = document.createElement('div');
            fog.id = 'halloween-fog-layer';
            fog.className = 'halloween-decoration-layer halloween-fog-layer';
            fog.setAttribute('aria-hidden', 'true');
            document.body.appendChild(fog);
            this.elements.push(fog);
        },

        createBatSwarm: function () {
            if (document.getElementById('halloween-bat-swarm')) return;
            const swarmWrapper = document.createElement('div');
            swarmWrapper.id = 'halloween-bat-swarm';
            swarmWrapper.className = 'halloween-decoration-layer halloween-bat-swarm';
            swarmWrapper.setAttribute('aria-hidden', 'true');

            const bat1 = document.createElement('img');
            bat1.src = halloweenAssetBase + '/halloween-bat-silhouette.webp';
            bat1.alt = '';
            bat1.width = 48;
            bat1.height = 48;
            bat1.draggable = false;

            const bat2 = document.createElement('img');
            bat2.className = 'bat-secondary';
            bat2.src = halloweenAssetBase + '/halloween-bat-silhouette.webp';
            bat2.alt = '';
            bat2.width = 32;
            bat2.height = 32;
            bat2.draggable = false;

            swarmWrapper.appendChild(bat1);
            swarmWrapper.appendChild(bat2);
            document.body.appendChild(swarmWrapper);
            this.elements.push(swarmWrapper);
        },

        createHeroMoonAndBat: function () {
            const heroTarget = document.querySelector('#hero') || document.querySelector('header');
            if (!heroTarget) return;

            // Full Moon behind hero content
            if (!document.getElementById('halloween-hero-moon')) {
                const moonWrapper = document.createElement('div');
                moonWrapper.id = 'halloween-hero-moon';
                moonWrapper.className = 'halloween-decoration-layer halloween-moon-wrapper';

                const moonImg = document.createElement('img');
                moonImg.className = 'halloween-moon';
                moonImg.src = halloweenAssetBase + '/halloween-full-moon.png';
                moonImg.alt = '';
                moonImg.setAttribute('aria-hidden', 'true');
                moonImg.width = 340;
                moonImg.height = 340;
                moonImg.draggable = false;

                moonWrapper.appendChild(moonImg);
                heroTarget.insertBefore(moonWrapper, heroTarget.firstChild);
                this.elements.push(moonWrapper);
            }

            // Bat gliding in hero
            if (!document.getElementById('halloween-hero-bat-container')) {
                const batWrapper = document.createElement('div');
                batWrapper.id = 'halloween-hero-bat-container';
                batWrapper.className = 'halloween-decoration-layer halloween-hero-bat-wrapper';

                const batImg = document.createElement('img');
                batImg.className = 'halloween-hero-bat';
                batImg.src = halloweenAssetBase + '/halloween-bat-silhouette.webp';
                batImg.alt = '';
                batImg.setAttribute('aria-hidden', 'true');
                batImg.width = 75;
                batImg.height = 75;
                batImg.draggable = false;

                batWrapper.appendChild(batImg);
                heroTarget.appendChild(batWrapper);
                this.elements.push(batWrapper);
            }
        },

        createStatsCandle: function () {
            const target = document.querySelector('#stats') || document.querySelector('#mitra-industri');
            if (!target || document.getElementById('halloween-stats-candle')) return;

            if (window.getComputedStyle(target).position === 'static') {
                target.style.position = 'relative';
            }

            const candleWrapper = document.createElement('div');
            candleWrapper.id = 'halloween-stats-candle';
            candleWrapper.className = 'halloween-decoration-layer halloween-candle-wrapper';

            const candleImg = document.createElement('img');
            candleImg.className = 'halloween-candle';
            candleImg.src = halloweenAssetBase + '/halloween-candle.webp';
            candleImg.alt = '';
            candleImg.setAttribute('aria-hidden', 'true');
            candleImg.loading = 'lazy';
            candleImg.width = 60;
            candleImg.height = 60;
            candleImg.draggable = false;

            candleWrapper.appendChild(candleImg);
            target.appendChild(candleWrapper);
            this.elements.push(candleWrapper);
        },

        createFiturGhost: function () {
            const target = document.querySelector('#fitur') || document.querySelector('#visimisi');
            if (!target || document.getElementById('halloween-fitur-ghost')) return;

            if (window.getComputedStyle(target).position === 'static') {
                target.style.position = 'relative';
            }

            const ghostWrapper = document.createElement('div');
            ghostWrapper.id = 'halloween-fitur-ghost';
            ghostWrapper.className = 'halloween-decoration-layer halloween-ghost-wrapper';

            const ghostImg = document.createElement('img');
            ghostImg.className = 'halloween-ghost';
            ghostImg.src = halloweenAssetBase + '/halloween-ghost.webp';
            ghostImg.alt = '';
            ghostImg.setAttribute('aria-hidden', 'true');
            ghostImg.loading = 'lazy';
            ghostImg.width = 90;
            ghostImg.height = 90;
            ghostImg.draggable = false;

            ghostWrapper.appendChild(ghostImg);
            target.appendChild(ghostWrapper);
            this.elements.push(ghostWrapper);
        },

        createTestimoniSkull: function () {
            const target = document.querySelector('#testimoni') || document.querySelector('#cara-kerja');
            if (!target || document.getElementById('halloween-testi-skull')) return;

            if (window.getComputedStyle(target).position === 'static') {
                target.style.position = 'relative';
            }

            const skullWrapper = document.createElement('div');
            skullWrapper.id = 'halloween-testi-skull';
            skullWrapper.className = 'halloween-decoration-layer halloween-skull-wrapper';

            const skullImg = document.createElement('img');
            skullImg.className = 'halloween-skull';
            skullImg.src = halloweenAssetBase + '/halloween-skull.webp';
            skullImg.alt = '';
            skullImg.setAttribute('aria-hidden', 'true');
            skullImg.loading = 'lazy';
            skullImg.width = 70;
            skullImg.height = 70;
            skullImg.draggable = false;

            skullWrapper.appendChild(skullImg);
            target.appendChild(skullWrapper);
            this.elements.push(skullWrapper);
        },

        createCtaPumpkin: function () {
            const target = document.querySelector('#cta .cta-box') || document.querySelector('#cta');
            if (!target || document.getElementById('halloween-cta-pumpkin')) return;

            if (window.getComputedStyle(target).position === 'static') {
                target.style.position = 'relative';
            }

            const pumpkinWrapper = document.createElement('div');
            pumpkinWrapper.id = 'halloween-cta-pumpkin';
            pumpkinWrapper.className = 'halloween-decoration-layer halloween-cta-pumpkin-wrapper';

            const pumpkinImg = document.createElement('img');
            pumpkinImg.className = 'halloween-cta-pumpkin';
            pumpkinImg.src = halloweenAssetBase + '/halloween-pumpkin-corner.webp';
            pumpkinImg.alt = '';
            pumpkinImg.setAttribute('aria-hidden', 'true');
            pumpkinImg.loading = 'lazy';
            pumpkinImg.width = 95;
            pumpkinImg.height = 95;
            pumpkinImg.draggable = false;

            pumpkinWrapper.appendChild(pumpkinImg);
            target.appendChild(pumpkinWrapper);
            this.elements.push(pumpkinWrapper);
        },

        createFooterPumpkin: function () {
            const footerTarget = document.querySelector('footer.footer') || document.querySelector('footer');
            if (!footerTarget || document.getElementById('halloween-footer-pumpkin-container')) return;

            const pumpkinWrapper = document.createElement('div');
            pumpkinWrapper.id = 'halloween-footer-pumpkin-container';
            pumpkinWrapper.className = 'halloween-decoration-layer halloween-footer-pumpkin-wrapper';

            const pumpkinImg = document.createElement('img');
            pumpkinImg.className = 'halloween-footer-pumpkin';
            pumpkinImg.src = halloweenAssetBase + '/halloween-pumpkin-corner.webp';
            pumpkinImg.alt = '';
            pumpkinImg.setAttribute('aria-hidden', 'true');
            pumpkinImg.loading = 'lazy';
            pumpkinImg.width = 110;
            pumpkinImg.height = 110;
            pumpkinImg.draggable = false;

            pumpkinWrapper.appendChild(pumpkinImg);
            footerTarget.appendChild(pumpkinWrapper);
            this.elements.push(pumpkinWrapper);
        },

        attachCardSpiderwebs: function () {
            // Select prominent cards on landing page to place tasteful corner web overlays
            const cards = document.querySelectorAll('.hero-3card-featured, .fitur-card:first-child, .step-card:first-child');
            const self = this;
            cards.forEach(function (card, index) {
                if (card.querySelector('.halloween-card-web')) return;

                if (window.getComputedStyle(card).position === 'static') {
                    card.style.position = 'relative';
                }

                const webImg = document.createElement('img');
                webImg.className = 'halloween-decoration-layer halloween-card-web';
                webImg.src = halloweenAssetBase + '/halloween-spiderweb-corner.webp';
                webImg.alt = '';
                webImg.setAttribute('aria-hidden', 'true');
                webImg.loading = 'lazy';
                webImg.width = 65;
                webImg.height = 65;
                webImg.draggable = false;

                card.appendChild(webImg);
                self.elements.push(webImg);
            });
        },

        destroy: function () {
            if (document.body) {
                document.body.classList.remove('halloween-theme');
            }
            this.elements.forEach(function (el) {
                if (el && el.parentNode) {
                    el.parentNode.removeChild(el);
                }
            });
            this.elements = [];
            this.initialized = false;
        }
    };

    // Expose namespace for testing or manual control
    window.HalloweenDecorations = HalloweenDecorations;

    // Initialize safely when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            HalloweenDecorations.init();
        });
    } else {
        HalloweenDecorations.init();
    }
})();
