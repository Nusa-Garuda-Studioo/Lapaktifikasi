<style id="critical-above-the-fold">
/* ========================================================
   CRITICAL ABOVE-THE-FOLD CSS (Inlined for 0 Render Blocking)
   Contains: Variables, Resets, Navbar, Hero, Stats, Blobs
   ======================================================== */

/* 1. Font Fallbacks with size-adjust to eliminate CLS */
@font-face {
    font-family: 'Plus Jakarta Sans Fallback';
    src: local('Arial'), local('Helvetica');
    ascent-override: 95%;
    descent-override: 25%;
    line-gap-override: 0%;
    size-adjust: 102%;
}
@font-face {
    font-family: 'Space Grotesk Fallback';
    src: local('Arial Black'), local('Trebuchet MS');
    ascent-override: 90%;
    descent-override: 22%;
    line-gap-override: 0%;
    size-adjust: 100%;
}

/* 2. Theme Tokens */
:root {
    --primary: #000000;
    --primary-2: #1a1a1a;
    --accent: #5417D7;
    --accent-light: #7033ff;
    --accent-2: #333333;
    --dark-bg: #ffffff;
    --dark-2: #fafafa;
    --glass-bg: rgba(255, 255, 255, 0.82);
    --glass-border: rgba(0, 0, 0, 0.08);
    --glass-hover: rgba(0, 0, 0, 0.04);
    --text-main: #111111;
    --text-muted: #555555;
    --text-dim: #888888;
    --white: #ffffff;
    --radius-sm: 8px;
    --radius-md: 14px;
    --radius-lg: 24px;
    --radius-xl: 32px;
    --whatsapp-color: #25D366;
    --gmail-color: #EA4335;
    --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
    --shadow-md: 0 10px 30px rgba(0, 0, 0, 0.06);
    --shadow-lg: 0 20px 50px rgba(0, 0, 0, 0.1);
    --shadow-float: 0 20px 45px rgba(0, 0, 0, 0.07);
}

/* 3. Global Reset */
*, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
html {
    scroll-behavior: smooth;
    -webkit-text-size-adjust: 100%;
}
body {
    font-family: 'Plus Jakarta Sans', 'Plus Jakarta Sans Fallback', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background: #f8fafc;
    color: var(--text-main);
    overflow-x: hidden;
    position: relative;
    min-height: 100vh;
}
img {
    max-width: 100%;
    height: auto;
    display: block;
}
a {
    color: inherit;
    text-decoration: none;
}

/* 4. Ambient Background Blobs & Grid Pattern */
body::before {
    content: '';
    position: fixed;
    inset: 0;
    z-index: -10;
    background-color: #f4f6f9;
    background-image:
        radial-gradient(at 40% 20%, hsla(220, 100%, 80%, 0.2) 0px, transparent 50%),
        radial-gradient(at 80% 0%, hsla(280, 100%, 80%, 0.2) 0px, transparent 50%),
        radial-gradient(at 0% 50%, hsla(180, 100%, 80%, 0.2) 0px, transparent 50%),
        radial-gradient(at 80% 50%, hsla(320, 100%, 80%, 0.2) 0px, transparent 50%),
        radial-gradient(at 0% 100%, hsla(40, 100%, 80%, 0.2) 0px, transparent 50%),
        radial-gradient(at 80% 100%, hsla(120, 100%, 80%, 0.2) 0px, transparent 50%);
    transform: translateZ(0);
    will-change: transform;
    pointer-events: none;
}
.bg-blobs {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: -1;
    overflow: hidden;
}
.blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.35;
    pointer-events: none;
}
.blob-1 {
    width: 500px;
    height: 500px;
    background: radial-gradient(circle, #5417D7, #ff6b6b);
    top: -150px;
    left: -150px;
}
.blob-2 {
    width: 450px;
    height: 450px;
    background: radial-gradient(circle, #4facfe, #00f2fe);
    top: 30%;
    right: -100px;
}
.blob-3 {
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, #5417D7, #38ef7d);
    bottom: 10%;
    left: 20%;
}
.bg-grid {
    position: fixed;
    inset: 0;
    background-image:
        linear-gradient(rgba(0, 0, 0, 0.02) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0, 0, 0, 0.02) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
    z-index: -1;
}

/* 5. Desktop & Mobile Navbar */
#navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    margin: 0 auto;
    width: 100%;
    max-width: 100%;
    z-index: 1000;
    padding: 16px 4%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    transform: translateZ(0);
    will-change: transform, width, top;
    box-sizing: border-box;
}
#navbar.scrolled {
    top: 14px;
    width: 95%;
    max-width: 1280px;
    background: rgba(255, 255, 255, 0.88);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 9999px;
    padding: 9px 24px;
    box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.07), 0 1px 2px rgba(0, 0, 0, 0.02);
}
.nav-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none !important;
    flex-shrink: 0;
}
.nav-logo img {
    max-height: 42px !important;
    width: auto;
    object-fit: contain;
}
.nav-logo-icon {
    width: 40px;
    height: 40px;
    background: #000000;
    color: #ffffff;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1.2rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
}
.nav-logo-text {
    font-family: 'Space Grotesk', 'Space Grotesk Fallback', sans-serif;
    font-size: 1.4rem;
    font-weight: 800;
    color: #000000;
    letter-spacing: -0.5px;
}
.nav-links {
    display: flex;
    align-items: center;
    list-style: none;
    gap: 28px;
    margin: 0;
    padding: 0;
}
.nav-links a {
    color: var(--text-muted);
    font-size: 0.92rem;
    font-weight: 600;
    transition: color 0.2s ease;
    padding: 6px 0;
    position: relative;
}
.nav-links a:hover, .nav-links a.active {
    color: #000000;
}
.nav-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}
.btn-nav-login {
    padding: 8px 18px;
    border-radius: 50px;
    font-size: 0.88rem;
    font-weight: 600;
    color: #000000;
    background: transparent;
    border: 1px solid rgba(0, 0, 0, 0.12);
    transition: all 0.2s ease;
}
.btn-nav-signup {
    padding: 8px 20px;
    border-radius: 50px;
    font-size: 0.88rem;
    font-weight: 700;
    color: #ffffff;
    background: #000000;
    border: 1px solid #000000;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
.nav-toggle {
    display: none;
    background: transparent;
    border: none;
    font-size: 1.6rem;
    color: #000000;
    cursor: pointer;
    padding: 4px;
}

/* 6. Buttons & Shared Utilities */
.btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 28px;
    border-radius: 50px;
    font-size: 0.95rem;
    font-weight: 700;
    color: #ffffff !important;
    background: #000000;
    border: 1.5px solid #000000;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 14px 28px;
    border-radius: 50px;
    font-size: 0.95rem;
    font-weight: 700;
    color: #000000 !important;
    background: #ffffff;
    border: 1.5px solid rgba(0, 0, 0, 0.15);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    cursor: pointer;
    transition: transform 0.2s ease, border-color 0.2s ease;
}

/* 7. Hero Section (Above The Fold) */
#hero {
    min-height: auto;
    display: flex;
    align-items: center;
    padding: 100px 5% 50px;
    position: relative;
    z-index: 1;
}
.hero-inner {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 36px;
    max-width: 1140px;
    margin: 0 auto;
    width: 100%;
}
.hero-content {
    flex: 1 1 540px;
    max-width: 580px;
}
.hero-title {
    font-family: 'Space Grotesk', 'Space Grotesk Fallback', sans-serif;
    font-size: clamp(1.7rem, 3.2vw, 2.4rem);
    font-weight: 700;
    line-height: 1.2;
    margin-bottom: 24px;
    color: #000000;
}
.grad-text {
    background: linear-gradient(135deg, #000000 0%, #5417D7 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.hero-desc {
    font-size: 1.05rem;
    color: var(--text-muted);
    line-height: 1.75;
    margin-bottom: 36px;
    max-width: 500px;
}
.hero-cta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
}
.hero-visual {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    width: 100%;
    max-width: 480px;
    margin: 0;
}
.hero-img-wrapper {
    position: relative;
    width: 100%;
    max-width: 460px;
    aspect-ratio: 4 / 5;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}
.hero-img-wrapper picture {
    display: contents;
}
.hero-img-glow {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 85%;
    height: 85%;
    background: radial-gradient(circle, rgba(220, 38, 38, 0.18) 0%, rgba(84, 23, 215, 0.08) 50%, transparent 70%);
    border-radius: 50%;
    filter: blur(45px);
    z-index: 0;
    pointer-events: none;
}
.hero-banner-img {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 460px;
    height: auto;
    aspect-ratio: 4 / 5;
    object-fit: contain;
    display: block;
    filter: drop-shadow(0 15px 30px rgba(0, 0, 0, 0.08));
    user-select: none;
    -webkit-user-drag: none;
    pointer-events: none;
}

/* 8. Stats Section (Above/Near The Fold) */
#stats {
    position: relative;
    z-index: 1;
    padding: 0 5% 60px;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    max-width: 1200px;
    margin: 0 auto;
}
.glass-card {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.stat-card {
    padding: 28px 24px;
    text-align: center;
}
.stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    margin: 0 auto 16px;
    background: #fafafa;
    border: 1px solid #e5e5e5;
}
.stat-num {
    font-family: 'Space Grotesk', 'Space Grotesk Fallback', sans-serif;
    font-size: 2.2rem;
    font-weight: 700;
    color: #000000;
    line-height: 1;
    margin-bottom: 6px;
}
.stat-label {
    font-size: 0.88rem;
    color: var(--text-muted);
    font-weight: 500;
}

/* 9. Mobile & Responsive Layout */
@media (max-width: 991px) {
    .nav-links, .nav-actions {
        display: none;
    }
    .nav-toggle {
        display: block;
    }
    .hero-inner {
        flex-direction: column-reverse;
        text-align: center;
        gap: 30px;
    }
    .hero-content {
        max-width: 100%;
    }
    .hero-desc {
        margin: 0 auto 32px;
    }
    .hero-cta {
        justify-content: center;
    }
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 575px) {
    #hero {
        padding: 85px 4% 35px;
    }
    .stats-grid {
        grid-template-columns: 1fr;
        gap: 14px;
    }
}

/* 10. Below-the-fold Content Visibility (Optimize DOM Size & Forced Reflow) */
#mitra-industri,
#fitur,
#visimisi,
#cara-kerja,
#produk,
#jasa-website,
#testimoni,
#join-partner-promo,
#faq,
#cta,
footer.footer {
    content-visibility: auto;
    contain-intrinsic-size: 1px 650px;
}
</style>
