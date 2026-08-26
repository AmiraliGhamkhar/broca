# Broca Platform — Phase 0 Design Specification

**Date:** 2026-08-26  
**Scope:** Phase 0 (Scaffold, Foundation, RTL, Vazirmatn, CI/Testing Base, Landing Skeleton)  

---

## 1. Objectives

1. Verify Laravel 13 environment, MySQL configuration, and test runner.
2. Establish RTL-first layout baseline with Vazirmatn font locally hosted.
3. Integrate Tailwind CSS v4 and Alpine.js.
4. Set up base health-check route, home landing page skeleton with responsive 3D-heart video/image placeholder boundary.
5. Setup CI/linting commands (Pint, PHPUnit).

---

## 2. Components & Structure

### 2.1 Font & Styling
- Self-hosted Vazirmatn font files placed in `public/fonts/vazirmatn/` or loaded via Tailwind.
- Root HTML with `dir="rtl"` and `lang="fa"`.
- Tailwind v4 theme configured for Broca brand colors (cream background `#FDFBF7`, primary slate/charcoal, accent colors).

### 2.2 Landing Hero Skeleton
- Full-viewport responsive section.
- Video/animation placeholder boundary (`video` tag with MP4/WebM sources + poster image fallback).
- Smooth scroll integration (Lenis placeholder or CSS scroll-behavior).
- Clear disclaimer that video asset is a placeholder.

### 2.3 Health & Testing
- `/` returns landing view.
- `/health` returns JSON status (DB connectivity, app environment).
- Pest/PHPUnit base feature test verifying home page renders and returns 200 OK with RTL and Vazirmatn references.

---

## 3. Data Flow & Configuration
- Read `.env` values for DB, App name, timezones (`Asia/Tehran`).
- Mail and payment configurations are loaded from `.env` and kept stubbed/sandbox-ready.

---

## 4. Error Handling & Validation
- Standard Laravel exception handler with Persian localized error pages (`404.blade.php`, `500.blade.php`) supporting RTL.

---

## 5. Testing Plan
- Feature test: `tests/Feature/LandingPageTest.php` checking:
  - Homepage returns 200.
  - HTML contains `dir="rtl"`.
  - Vazirmatn font reference exists.
  - Landing hero placeholder container exists.
- Pint check for code formatting.
