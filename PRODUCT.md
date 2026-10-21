# Product

<!-- impeccable:product-schema 1 -->

> Source: owner's master plan (`plan.md`) and standing rules. The owner said "proceed" instead of an interview, so facts marked _(inferred)_ come from plan.md, not a confirmed answer.

## Platform

web

## Users

- **Clinic owners evaluating the product (primary for Phase 1).** Owners and managers of Philippine aesthetic clinics watching a sales demo. Their job: judge in a few minutes whether this looks like an established premium clinic platform and whether a patient could book through it.
- **Prospective patients (the audience the demo site plays to).** Adults in Metro Manila and Cebu considering facial, injectable, body, hair or wellness treatments. They browse on a phone, compare treatments and prices, and want to book a consultation without calling.
- **Clinic staff (admin side).** Receptionists, practitioners and managers who see the leads and appointments the site creates.

## Product Purpose

Gluta RepubliQ Aesthetics is a real clinic platform: marketing site, booking, CRM, then clinical records, POS, inventory, accounting and payroll. Phase 1 success: a clinic owner sees the site, books an appointment, and watches it appear in the admin as a lead and an appointment.

## Positioning

One system from the first website visit to the treatment, payment and follow-up (plan.md §54), built for Philippine clinics: peso pricing, GCash/Maya, Data Privacy Act-aware handling of health data. _(inferred)_

## Operating Context

- The demo is shown live on a laptop and on phones, on localhost now and later on cPanel hosting.
- Patients book by treatment, branch (Cebu, San Pedro, Sta. Rosa, Bacoor), practitioner, date and time, as a guest if they prefer.
- Every website conversion creates a CRM lead with UTM and source data.

## Capabilities and Constraints

- Stack: Laravel 13, Inertia 3, React 19, TypeScript, Tailwind 4, MySQL.
- Content (treatments, specialists, testimonials, FAQs, branches) is database-driven, never hard-coded.
- Brand values live in `config/clinic.php` so the clinic can be renamed.
- No medical claims: no guaranteed results, "100% safe", "permanent" or superiority claims (plan.md §83).
- Not to be advertised as BIR compliant.

## Brand Commitments

- Name: Gluta RepubliQ Aesthetics. Tagline: "Hindi mahal ang magpaganda. Mag-Gluta RepubliQ ka na."
- Palette (owner, 2026-10-21): Powder Blue #9AC0D7, Tropical Teal #6AB5B1, Air Force Blue #5A95B8, Blue Slate #406480, White. Roles and contrast notes live in the `@theme` block of `resources/css/app.css` (Blue Slate for text and actions; Air Force Blue only for large text, borders and focus; #3D7599 for small accent text; Teal fills always carry dark ink text).
- Owner rules: Noto Serif Display (light) headings with Geist body, self-hosted; sentence-case nav; square buttons and corners; light theme for the public site; motion under 300 ms ease-out and reduced-motion safe (the hero lens drift is the one ambient loop); no em-dashes in UI copy.
- Media: real royalty-free photos stored in the repo and credited. Never placeholder art.

## Evidence on Hand

- No real patients, reviews, results, press or certifications exist. Testimonials and practitioner profiles are fictional demo content; before/after imagery must be labelled illustrative.
- Demo practitioners: Dr. Camille Rivera, Dr. Adrian Santos, Dr. Rhea Kapoor.

## Product Principles

1. Book first: every page leads to a consultation booking in as few steps as possible, no forced sign-up.
2. Trust through restraint: calm, clinical precision over hype; claims stay modest and truthful.
3. Privacy is visible: consent and data handling are named plainly, never buried.
4. The demo is real: every screen runs on seeded data through the actual backend.

## Accessibility & Inclusion

WCAG-conscious contrast, keyboard navigation, visible focus, labelled forms, reduced-motion support (plan.md §86). English copy for a Philippine audience.
