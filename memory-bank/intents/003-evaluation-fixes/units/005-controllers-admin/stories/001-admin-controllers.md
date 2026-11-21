---
story: 001-admin-controllers
unit: 005-controllers-admin
intent: 003-evaluation-fixes
status: completed
created: 2026-11-18T08:00:00Z
updated: 2026-11-22T00:45:00Z
---

# Story: Admin and Moderation Actions Are Handled by a Controller

As a developer,
I want admin and report-review actions handled by a controller,
So that moderation logic is testable and separated from `admin.php` / `report_review.php`.

## Acceptance Criteria
- [x] `AdminController` owns role changes, deactivation, report review and password resets; pages delegate.
- [x] RBAC checks stay enforced (covered by RbacTest); suite green.

## Estimate
3 story points · Priority: Must

## Verification
- Full suite green (RbacTest + ReportTest passing); admin flows smoke-tested live (role change, report review, reset).
