---
unit: 008-realtime-notifications
intent: 002-quality-enhancements
phase: inception
status: ready
created: 2026-11-17T11:30:00Z
updated: 2026-11-17T11:30:00Z
---

# Unit Brief: Real-Time Notification Polling & Heartbeat

## Assigned Owner
**Saad** (22K-5039) · Branch: `feature/unit-008-realtime-notifications`

## Purpose
Introduce a periodic background polling heartbeat so students are alerted to incoming likes, replies, and follow requests in real-time without needing to manually refresh the page.

## Scope
### In Scope
- Endpoint `api/unread_counts.php`: Returns JSON `{ unread_notifications: N, unread_messages: M }` authenticated via session.
- Client-side polling script in `assets/js/main.js` (every 30 seconds with exponential backoff on inactive tabs).
- Live counter badge updates in header navigation.
- Non-blocking browser toast alert popup when a new notification arrives while browsing.

### Out of Scope
- Full WebSocket server daemon (lightweight HTTP long/short poll designed for standard XAMPP Apache environments).

## Assigned Requirements
| FR | Description | Priority |
|----|-------------|----------|
| FR-8 | 30s background unread count polling with live badge and toast popups | Should |
