# UX Guide — CampusConnect

## Design direction

X-style microblogging shell: 3-column layout (left nav, centre feed, right
sidebar with search / trending / who-to-follow). Restrained, product-like —
not a generic template. Brand mark: **Interlocked CC** logo (user's choice).

## Type & colour

- Fonts (Google Fonts, need internet): Inter (UI), Bricolage Grotesque
  (display/headings), Public Sans (body).
- Colour: dark sidebar/nav accents, light content surfaces, blue primary
  actions. Contrast passes WCAG AA on the audited pages.

## Components

- Composer: 280-character counter, live remaining count, disabled submit at 0.
- Post card: avatar, name + @handle, relative timestamp ("2h"), like/reply/
  bookmark/repost actions with filled state for the viewer's own likes.
- Owner actions: single consistent treatment (delete via modal with confirm).
- Profile: cover picker (6 styles), avatar, bio, follower/following counts,
  Posts / Replies / Likes tabs.
- Empty states: friendly message + a next action (e.g. "No bookmarks yet").
- Modals close on Escape and on backdrop click.

## Responsive

- Breakpoints: desktop 3-column → tablet 2-column → mobile single column
  (bottom nav). No horizontal scroll at 390px (fixed in the Sprint 5 polish;
  the Explore page was the worst offender — "xplore" clipping).
- Touch targets and Explore pagination are known remaining gaps (audit items
  M-12, M-13 — cut for time in the final polish, see `decision-index.md`).

## States to design for

Every list/page needs an empty state, a loading state, and an error state.
Timestamps are relative everywhere except the admin queue (absolute, for audit).
Pluralization is correct ("1 post", "2 posts") — the old "1 posts" bug is fixed;
do not reintroduce it.
