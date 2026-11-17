---
story: 001-unread-count-polling
unit: 008-realtime-notifications
intent: 002-quality-enhancements
status: ready
created: 2026-11-17T11:30:00Z
updated: 2026-11-17T11:30:00Z
---

# Story: Unread Count Heartbeat & Live Notification Badge

As an active campus user,
I want my notification and message badges to update automatically while I'm reading the feed,
So that I know when someone replies to my post without repeatedly reloading the website.

## Acceptance Criteria
- [ ] Browser polls `api/unread_counts.php` every 30 seconds when tab is active.
- [ ] If count increases, the navigation pill updates dynamically with red count indicator.
- [ ] A discreet toast appears on the bottom-right for 4 seconds stating "New reply received".
- [ ] When the tab is hidden / minimized, polling slows to 2 minutes to conserve server load.
