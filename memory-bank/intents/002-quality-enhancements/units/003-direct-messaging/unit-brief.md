---
unit: 003-direct-messaging
intent: 002-quality-enhancements
phase: inception
status: ready
created: 2026-11-17T11:30:00Z
updated: 2026-11-17T11:30:00Z
---

# Unit Brief: Peer-to-Peer Direct Messaging (DMs)

## Assigned Owner
**Qaim Mehdi** (23K-2075) · Branch: `feature/unit-003-direct-messaging`

## Purpose
Introduce a 1-on-1 private messaging channel between verified FAST-NUCES students and faculty for academic coordination and FYP teaming.

## Scope
### In Scope
- DB Table `messages` (`id`, `sender_id`, `receiver_id`, `message`, `is_read`, `created_at`, `deleted_at`).
- Model `classes/DirectMessage.php` with thread retrieval, message sending, and unread count methods.
- UI: `messages.php` (conversation sidebar + chat pane) and `message_action.php`.
- Profile integration: "Message" button on user profile pages.

### Out of Scope
- Group chats or media files inside DMs (text-only for Sprint 6).

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-3 | 1-on-1 private direct messaging between campus accounts | Must |
