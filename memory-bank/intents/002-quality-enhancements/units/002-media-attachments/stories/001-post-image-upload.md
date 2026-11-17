---
story: 001-post-image-upload
unit: 002-media-attachments
intent: 002-quality-enhancements
status: ready
created: 2026-11-17T11:30:00Z
updated: 2026-11-17T11:30:00Z
---

# Story: Post Image Attachment & Rendering

As a university student,
I want to attach a photo or flyer to my post,
So that I can visually announce campus events, hackathons, and project showcases.

## Acceptance Criteria
- [ ] Compose box provides an image upload button with file name preview.
- [ ] Backend validates MIME type using PHP `finfo` (not just file extension).
- [ ] Uploaded image renders smoothly inside the post card on both desktop and mobile viewports.
- [ ] When a post is soft-deleted, associated media is hidden from public view.
