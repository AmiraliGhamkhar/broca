/**
 * SmoothCursor — physics-based cursor, ported from Magic UI's SmoothCursor
 * (credit @Code_Parth) to vanilla JS for this Blade + Alpine stack.
 *
 * Behaviour:
 *  - Spring-follows the pointer (damping 45, stiffness 400) and rotates
 *    toward the direction of travel, squashing slightly while moving.
 *  - Only runs on hover-capable, fine-pointer (mouse/trackpad) devices;
 *    touch devices never see it, exactly like the original.
 *  - Respects prefers-reduced-motion: disabled entirely.
 *  - RAF-throttled, transform/opacity only, so it stays off the layout path.
 */
const SPRING_CONFIG = { damping: 45, stiffness: 400, mass: 1, restDelta: 0.001 };
const DESKTOP_POINTER_QUERY = "(any-hover: hover) and (any-pointer: fine)";
const REDUCED_MOTION_QUERY = "(prefers-reduced-motion: reduce)";

/* Minimal critically-damped spring integrator (per-frame, RAF-driven).
   Equivalent in feel to motion's useSpring with the same constants. */
function createSpring(target, { damping, stiffness, mass, restDelta }) {
    let value = target;
    let velocity = 0;
    let goal = target;

    return {
        get value() {
            return value;
        },
        set(next) {
            goal = next;
        },
        /* Advance one ~16.7ms frame; returns true while still in motion. */
        step() {
            const dt = 1 / 60;
            const force = -stiffness * (value - goal);
            const damper = -damping * velocity;
            const acceleration = (force + damper) / mass;
            velocity += acceleration * dt;
            value += velocity * dt;

            if (Math.abs(velocity) < restDelta && Math.abs(value - goal) < restDelta) {
                value = goal;
                velocity = 0;
                return false;
            }
            return true;
        },
    };
}

function initSmoothCursor() {
    // Skip on touch-first devices or when the user prefers reduced motion.
    if (!window.matchMedia(DESKTOP_POINTER_QUERY).matches) return;
    if (window.matchMedia(REDUCED_MOTION_QUERY).matches) return;
    if (document.getElementById("smooth-cursor")) return;

    const cursor = document.createElement("div");
    cursor.id = "smooth-cursor";
    cursor.setAttribute("aria-hidden", "true");
    cursor.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="50" height="54" viewBox="0 0 50 54" fill="none" style="scale: 0.5">
            <g filter="url(#smooth-cursor-shadow)">
                <path d="M42.6817 41.1495L27.5103 6.79925C26.7269 5.02557 24.2082 5.02558 23.3927 6.79925L7.59814 41.1495C6.75833 42.9759 8.52712 44.8902 10.4125 44.1954L24.3757 39.0496C24.8829 38.8627 25.4385 38.8627 25.9422 39.0496L39.8121 44.1954C41.6849 44.8902 43.4884 42.9759 42.6817 41.1495Z" fill="#222222"/>
                <path d="M43.7146 40.6933L28.5431 6.34306C27.3556 3.65428 23.5772 3.69516 22.3668 6.32755L6.57226 40.6778C5.3134 43.4156 7.97238 46.298 10.803 45.2549L24.7662 40.109C25.0221 40.0147 25.2999 40.0156 25.5494 40.1082L39.4193 45.254C42.2261 46.2953 44.9254 43.4347 43.7146 40.6933Z" stroke="#ffffff" stroke-width="2.25825"/>
            </g>
            <defs>
                <filter id="smooth-cursor-shadow" x="0.602397" y="0.952444" width="49.0584" height="52.428" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
                    <feFlood flood-opacity="0" result="BackgroundImageFix"/>
                    <feColorMatrix in="SourceAlpha" type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0" result="hardAlpha"/>
                    <feOffset dy="2.25825"/>
                    <feGaussianBlur stdDeviation="2.25825"/>
                    <feComposite in2="hardAlpha" operator="out"/>
                    <feColorMatrix type="matrix" values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.08 0"/>
                    <feBlend mode="normal" in2="BackgroundImageFix" result="effect1_dropShadow_91_7928"/>
                    <feBlend mode="normal" in="SourceGraphic" in2="effect1_dropShadow_91_7928" result="shape"/>
                </filter>
            </defs>
        </svg>`;
    document.body.appendChild(cursor);

    const cursorX = createSpring(0, SPRING_CONFIG);
    const cursorY = createSpring(0, SPRING_CONFIG);
    const rotation = createSpring(0, { ...SPRING_CONFIG, damping: 60, stiffness: 300 });
    const scale = createSpring(1, { ...SPRING_CONFIG, stiffness: 500, damping: 35 });

    const lastMousePos = { x: 0, y: 0 };
    const velocity = { x: 0, y: 0 };
    let lastUpdateTime = performance.now();
    let previousAngle = 0;
    let accumulatedRotation = 0;
    let scaleResetTimer = null;

    let isVisible = false;
    let running = false;

    function render() {
        cursor.style.opacity = isVisible ? "1" : "0";
        const x = cursorX.value;
        const y = cursorY.value;
        const r = rotation.value;
        const s = scale.value;
        cursor.style.transform = `translate(${x}px, ${y}px) translate(-50%, -50%) rotate(${r}deg) scale(${s})`;
    }

    function tick() {
        const activeX = cursorX.step();
        const activeY = cursorY.step();
        const activeR = rotation.step();
        const activeS = scale.step();

        render();

        if (activeX || activeY || activeR || activeS) {
            requestAnimationFrame(tick);
        } else {
            running = false;
        }
    }

    function wake() {
        if (!running) {
            running = true;
            requestAnimationFrame(tick);
        }
    }

    function handlePointerMove(event) {
        if (event.pointerType === "touch") return;

        const now = performance.now();
        const deltaTime = now - lastUpdateTime;
        if (deltaTime > 0) {
            velocity.x = (event.clientX - lastMousePos.x) / deltaTime;
            velocity.y = (event.clientY - lastMousePos.y) / deltaTime;
        }
        lastUpdateTime = now;
        lastMousePos.x = event.clientX;
        lastMousePos.y = event.clientY;

        const speed = Math.sqrt(velocity.x ** 2 + velocity.y ** 2);

        cursorX.set(event.clientX);
        cursorY.set(event.clientY);

        if (!isVisible) {
            isVisible = true;
        }

        if (speed > 0.1) {
            const currentAngle =
                (Math.atan2(velocity.y, velocity.x) * 180) / Math.PI + 90;

            let angleDiff = currentAngle - previousAngle;
            if (angleDiff > 180) angleDiff -= 360;
            if (angleDiff < -180) angleDiff += 360;
            accumulatedRotation += angleDiff;
            rotation.set(accumulatedRotation);
            previousAngle = currentAngle;

            scale.set(0.95);

            clearTimeout(scaleResetTimer);
            scaleResetTimer = setTimeout(() => scale.set(1), 150);
        }

        wake();
    }

    function handlePointerLeave() {
        isVisible = false;
        wake();
    }

    // Hide the OS cursor sitewide, but keep the I-beam for text inputs so
    // users can still see where they're typing (accessibility trade-off
    // recommended by the original component docs).
    const style = document.createElement("style");
    style.textContent = `
        html.smooth-cursor-active body,
        html.smooth-cursor-active body a,
        html.smooth-cursor-active body button,
        html.smooth-cursor-active body [role="button"] {
            cursor: none !important;
        }
        html.smooth-cursor-active input,
        html.smooth-cursor-active textarea,
        html.smooth-cursor-active select {
            cursor: text !important;
        }
        #smooth-cursor {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 9999;
            pointer-events: none;
            opacity: 0;
            will-change: transform, opacity;
        }`;
    document.head.appendChild(style);
    document.documentElement.classList.add("smooth-cursor-active");

    window.addEventListener("pointermove", handlePointerMove, { passive: true });
    document.documentElement.addEventListener("pointerleave", handlePointerLeave);
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initSmoothCursor);
} else {
    initSmoothCursor();
}
