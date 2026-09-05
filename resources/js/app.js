import Alpine from "alpinejs";

window.Alpine = Alpine;

/**
 * Motion policy: every JS-driven effect below bails out when the user asks
 * for reduced motion (the CSS global rule handles the Alpine x-transition
 * side of things). Nothing here is decorative — each effect either reports
 * state (submitting, error) or helps the eye track content (reveal).
 */
const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

/* ------------------------------------------------------------------ *
 * Flash toast: fades in, auto-dismisses after 7s, fades out on ✕.
 * ------------------------------------------------------------------ */
Alpine.data("flashToast", () => ({
    show: true,
    timer: null,
    init() {
        this.timer = setTimeout(() => this.dismiss(), 7000);
    },
    dismiss() {
        if (!this.show) return;
        clearTimeout(this.timer);
        this.show = false;
    },
}));

Alpine.start();

/* ------------------------------------------------------------------ *
 * Submit feedback: the primary button shows "در حال پردازش…" while the
 * form posts. For classic (non-AJAX) forms the page either navigates or
 * re-renders fresh on validation error — nothing to restore.
 * ------------------------------------------------------------------ */
document.addEventListener("submit", (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;

    form.querySelectorAll('button[type="submit"]').forEach((button) => {
        if (button.disabled) return;
        button.dataset.label = button.innerHTML;
        button.disabled = true;
        button.classList.add("is-loading");
        button.innerHTML =
            '<span class="inline-flex items-center gap-2"><span class="spinner-dot" aria-hidden="true"></span>در حال پردازش…</span>';
    });

    form.querySelectorAll('input[type="submit"]').forEach((input) => {
        if (input.disabled) return;
        input.dataset.label = input.value;
        input.disabled = true;
        input.value = "در حال پردازش…";
    });
});

/* ------------------------------------------------------------------ *
 * Scroll reveal: elements tagged [data-reveal] fade up once when they
 * enter the viewport. Progressive enhancement: the hidden initial state
 * only exists after JS opts the page in (html.reveal-ready), so a JS
 * failure or a reduced-motion user always sees plain content.
 * ------------------------------------------------------------------ */
function initReveal() {
    if (prefersReducedMotion) return;

    const targets = document.querySelectorAll("[data-reveal]");
    if (targets.length === 0) return;

    if (!("IntersectionObserver" in window)) return;

    document.documentElement.classList.add("reveal-ready");

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                observer.unobserve(el);

                // Stagger cards that sit side by side (cap at 5 steps).
                const siblings = Array.from(el.parentElement.querySelectorAll("[data-reveal]"));
                const step = Math.min(Math.max(siblings.indexOf(el), 0), 5) * 60;

                el.style.transitionDelay = step + "ms";
                el.classList.add("is-revealed");
                window.setTimeout(() => {
                    el.style.transitionDelay = "";
                }, step + 600);
            });
        },
        { threshold: 0.15, rootMargin: "0px 0px -40px 0px" }
    );

    targets.forEach((el) => observer.observe(el));
}

/* ------------------------------------------------------------------ *
 * One gentle shake on server-rendered validation alerts, so a returned
 * form draws the eye to what went wrong.
 * ------------------------------------------------------------------ */
function initErrorShake() {
    if (prefersReducedMotion) return;

    document.querySelectorAll('[role="alert"]').forEach((el) => {
        el.classList.add("shake-once");
    });
}

document.addEventListener("DOMContentLoaded", () => {
    initReveal();
    initErrorShake();
});
