---
unit: 002-media-attachments
intent: 002-quality-enhancements
phase: inception
status: ready
created: 2026-11-17T11:30:00Z
updated: 2026-11-17T11:30:00Z
---

# Unit Brief: Post Media & Image Attachments

## Assigned Owner
**Faiz Hussain** (23K-2061) · Branch: `feature/unit-002-media-attachments`

## Purpose
Allow students and society administrators to attach images to posts, providing richer campus visual communication.

## Scope
### In Scope
- DB Table `post_media` (`id`, `post_id`, `file_name`, `file_path`, `file_size`, `mime_type`, `created_at`).
- Server-side validation (max 5MB, allowed MIME: `image/jpeg`, `image/png`, `image/webp`).
- Class `classes/Media.php` to handle upload, sanitization, and unlink on post deletion.
- UI: File input pill on compose box and responsive image display on feed cards.

### Out of Scope
- Video attachments or streaming.

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-2 | Single/multiple image attachment on post creation with secure mime validation | Must |
