/**
 * DiaTextReveal — horizontal gradient band sweeps across text with a shine,
 * then settles on the heading's original color. Ported from Magic UI's
 * DiaTextReveal (credit @chishiyac) to vanilla JS for this Blade + Alpine
 * stack, applied automatically to every <h1> on the site.
 *
 * Behaviour:
 *  - Each <h1> gets a per-frame linear-gradient (background-clip: text) whose
 *    colored band sweeps from -17% to 117%, with the same cubic ease and
 *    1.5s duration as the original.
 *  - Starts once the heading enters the viewport (IntersectionObserver,
 *    amount 0.1, once) — same as the original's startOnView/once defaults.
 *  - Mirrors the sweep direction for RTL headings so Persian text reveals
 *    right-to-left, in reading order.
 *  - Respects prefers-reduced-motion: headings are left untouched (which is
 *    exactly the original's reduced-motion end state: solid theme color).
 *  - Opt out per heading with data-no-dia-reveal on the <h1> or any ancestor.
 *  - Progressive enhancement: without JS nothing changes; styles are only
 *    applied from here.
 */
const DEFAULT_COLORS = ["#c679c4", "#fa3d1d", "#ffb005", "#e1e1fe", "#0358f7"];
const BAND_HALF = 17;
const SWEEP_START = -BAND_HALF;
const SWEEP_END = 100 + BAND_HALF;
const SWEEP_DURATION = 1.5; // seconds, one sweep

/* Same ease as the original component (cubic in-out). */
const sweepEase = (t) => (t < 0.5 ? 4 * t ** 3 : 1 - (-2 * t + 2) ** 3 / 2);

/**
 * Build the sweeping gradient at position `pos` (0..100 = element width).
 * `mirrored` flips the x axis for RTL headings.
 */
function buildGradient(pos, colors, textColor, mirrored) {
    const map = (x) => (mirrored ? 100 - x : x);
    const bandStart = pos - BAND_HALF;
    const bandEnd = pos + BAND_HALF;

    if (bandStart >= 100) {
        return `linear-gradient(90deg, ${textColor}, ${textColor})`;
    }

    const n = colors.length;
    const parts = [];

    if (bandStart > 0) {
        parts.push(`${textColor} ${map(0).toFixed(2)}%`, `${textColor} ${map(bandStart).toFixed(2)}%`);
    }

    colors.forEach((c, i) => {
        const pct = n === 1 ? pos : bandStart + (i / (n - 1)) * BAND_HALF * 2;
        parts.push(`${c} ${map(pct).toFixed(2)}%`);
    });

    if (bandEnd < 100) {
        parts.push(`transparent ${map(bandEnd).toFixed(2)}%`, "transparent 100%");
    }

    return `linear-gradient(90deg, ${parts.join(", ")})`;
}

function playSweep(el, textColor, mirrored) {
    el.style.color = "transparent";
    el.style.backgroundClip = "text";
    el.style.webkitBackgroundClip = "text";
    el.style.backgroundSize = "100% 100%";
    el.style.backgroundRepeat = "no-repeat";

    let start = null;

    function frame(now) {
        if (start === null) start = now;
        const t = Math.min((now - start) / (SWEEP_DURATION * 1000), 1);
        const pos = SWEEP_START + sweepEase(t) * (SWEEP_END - SWEEP_START);

        el.style.backgroundImage = buildGradient(pos, DEFAULT_COLORS, textColor, mirrored);

        if (t < 1) {
            requestAnimationFrame(frame);
        } else {
            // Sweep complete: settle on the heading's original color and
            // clean up the paint-only styles.
            el.style.color = "";
            el.style.backgroundImage = "";
            el.style.backgroundClip = "";
            el.style.webkitBackgroundClip = "";
            el.style.backgroundSize = "";
            el.style.backgroundRepeat = "";
        }
    }

    requestAnimationFrame(frame);
}

function setupHeading(el) {
    if (el.dataset.diaReveal) return;
    el.dataset.diaReveal = "1";

    // Capture the heading's own color before we make the text transparent,
    // so the sweep settles back on exactly the theme color it had.
    const textColor = getComputedStyle(el).color;
    const mirrored = getComputedStyle(el).direction === "rtl";

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                observer.unobserve(el);
                playSweep(el, textColor, mirrored);
            });
        },
        { threshold: 0.1 }
    );
    observer.observe(el);
}

function initDiaTextReveal() {
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) return;
    if (!("IntersectionObserver" in window)) return;

    document
        .querySelectorAll('h1:not([data-no-dia-reveal]):not([data-dia-reveal])')
        .forEach((el) => {
            if (el.closest("[data-no-dia-reveal]")) return;
            setupHeading(el);
        });
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initDiaTextReveal);
} else {
    initDiaTextReveal();
}
