---
story: 001-theme-persistence-a11y
unit: 006-accessibility-theme
intent: 002-quality-enhancements
status: ready
created: 2026-11-17T11:30:00Z
updated: 2026-11-17T11:30:00Z
---

# Story: Theme Persistence & Accessible Modal Focus

As a student using keyboard navigation or assistive technology,
I want modals to trap my focus and my dark mode choice to persist when I log in on another device,
So that my experience is comfortable and accessible across the campus network.

## Acceptance Criteria
- [ ] Theme toggling sends a background request to persist preference to the `users` row.
- [ ] On login from a new device, the user's saved theme applies immediately before render.
- [ ] Opening any modal locks focus within the modal dialog.
- [ ] Pressing `Escape` closes open modals and returns focus to the triggering element.
