import os
import zipfile
import html

def escape_xml(s):
    return html.escape(s, quote=True)

def generate_presentation(output_path="AIR_Presentation.pptx", group_code="AIR", section="BCS-7A"):
    slides_data = [
        {
            "title": "CampusConnect",
            "subtitle": f"University Microblogging & Announcement Hub\nAgile Software Project Management (ASPM) — Sprint 1–5 Final Review\nSection: {section} | Group Code: {group_code}\nTeam: Muddassir (Lead Backend) | Faiz Hussain (Backend & QA) | Qaim (Frontend & Docs)",
            "bullets": []
        },
        {
            "title": "Executive Summary & Problem Statement",
            "subtitle": "Why CampusConnect?",
            "bullets": [
                "The Academic Dilemma: University communication is fragmented across informal WhatsApp groups and cluttered emails.",
                "Lack of Dedicated Identity: No centralized space for verified student society announcements, departmental updates, and peer networking.",
                "Core Solution: A high-performance, privacy-respecting microblogging platform built with PHP 8 OOP and MySQL.",
                "Agile Delivery: Developed iteratively across 5 two-week sprints with strict Git feature branching and continuous verification."
            ]
        },
        {
            "title": "Agile Methodology & Scrum Process",
            "subtitle": "Iterative Development Lifecycle",
            "bullets": [
                "5 Sprints Delivered: Auth (S1) -> Post Engine (S2) -> Social Graph & Redesign (S3) -> Caching & Likes (S4) -> Hardening & Delivery (S5).",
                "Scrum Ceremonies: Sprint Planning, Mid-Sprint Syncs, Sprint Reviews with live user feedback, and honest Retrospectives.",
                "Git Discipline: Strict feature branch workflow (`feature/<sprint>-<feature-name>`); zero direct commits to main; verified PR merges.",
                "WBS Traceability: Exact 1-to-1 mapping maintained between academic WBS items and codebase artifacts (see `docs/wbs-mapping.md`)."
            ]
        },
        {
            "title": "Sprints 1 & 2: Architecture & Core Post Engine",
            "subtitle": "OOP Foundation and Baseline Features",
            "bullets": [
                "Singleton Database Pattern: Robust PDO connection with automatic, zero-config SQLite memory fallback for rapid testing.",
                "Secure Authentication (Sprint 1): Bcrypt password hashing (`PASSWORD_BCRYPT`), strong password policy (8+ chars, upper, lower, number, special char).",
                "Core Chirp Engine (Sprint 2): 280-character post validation (`mb_strlen`), live UI character countdown, and timeline feed pagination.",
                "Soft-Delete Architecture: Posts marked with `deleted_at IS NULL` rather than physical row destruction to preserve academic auditability."
            ]
        },
        {
            "title": "Sprint 3: Social Graph, Profiles & Design System",
            "subtitle": "Connecting the Campus Community",
            "bullets": [
                "Social Graph: Follow/Unfollow model, one-way relationships, personalized feeds combining own and followed posts.",
                "Discussion Threads & Bookmarks: Threaded replies beneath posts (oldest-first) and private one-click bookmarks.",
                "Database Session Handler: Replaced native PHP sessions with a secure `sessions` table (`DbSessionHandler`) preventing session fixation.",
                "Product-Scale Redesign: Hairline divider feed, Interlocked CC brand mark, and 6 curated duotone cover styles (zero upload attack surface)."
            ]
        },
        {
            "title": "Sprint 4: Engagement, Hashtags & Redis Caching",
            "subtitle": "High Performance & Discovery Layer",
            "bullets": [
                "Likes & Heart Reactions: Idempotent like toggle, AJAX endpoint, real-time DOM counter updates, and profile Likes archive tab.",
                "Hashtags & Discovery: Regex parser extracting `#tags`, clickable link rendering, tag-filtered exploration, and Trending Topics sidebar widget.",
                "Multi-Tier Caching Architecture: Redis caching with a pure-PHP socket client (`TinyRedisClient.php`) and seamless runtime memory fallback.",
                "Intelligent Invalidation: Post mutations and engagement toggles automatically purge the `feed_` cache prefix to maintain consistency."
            ]
        },
        {
            "title": "Sprint 5: In-App Notifications, RBAC & Mobile Polish",
            "subtitle": "Hardening, Governance & Viewport Polish",
            "bullets": [
                "In-App Notifications (WBS 5.1): Instant event notifications (new follower, reply to post, like on post) with dynamic unread header badge.",
                "Role-Based Access Control (WBS 5.2): Three-tier role hierarchy (`student`, `society_admin`, `system_admin`) — core built; admin panel and society announcements in progress (due 15 Nov).",
                "Security Hardening (WBS 5.3): 30-minute session idle timeout built; anti-CSRF tokens and login rate limiting in progress (due 15 Nov).",
                "Responsive Polish (WBS 5.4): Complete layout reflow down to 360px mobile viewports, tablet optimization, tactile button press feedback (`transform: scale(0.97)`)."
            ]
        },
        {
            "title": "Database Architecture & Schema Evolution",
            "subtitle": "Relational Data Modeling (9 Tables)",
            "bullets": [
                "Core Entities: `users` (profiles, roles, cover styles) and `posts` (280-char content, soft-delete timestamp).",
                "Social Relations: `follows` (composite PK, self-follow check), `replies` (threaded comments), and `bookmarks` (saved posts).",
                "Security & Sessions: `sessions` table for database-backed session persistence (`DbSessionHandler`) with session-ID regeneration on login.",
                "Engagement & Activity: `likes` (composite PK), `hashtags`, `post_hashtags`, and `notifications` (foreign keys with cascade rules)."
            ]
        },
        {
            "title": "Performance Optimization & Benchmarks",
            "subtitle": "Sub-Millisecond Response Latencies",
            "bullets": [
                "Latency Acceleration: Measured 532x speedup for cached reads vs. direct database queries (Sprint 4 verification, benchmark.php).",
                "N+1 Elimination: Audited query profiles in `docs/n-plus-1-audit-sprint4.md`; eager joins eliminate loop queries across all feed views.",
                "Resilient Socket Client: `TinyRedisClient.php` communicates directly via RESP protocol over TCP, eliminating compiled PECL extension dependencies.",
                "Graceful Zero-Downtime Fallback: If Redis is unreachable, the system silently continues serving requests via PHP memory caching without errors."
            ]
        },
        {
            "title": "Live Demonstration Flow",
            "subtitle": "End-to-End User Scenario",
            "bullets": [
                "1. User Registration: Real-time password criteria validation -> Instant login -> Personalized onboarding.",
                "2. Feed Exploration: Discovering peers via 'Who to Follow' -> Following classmates -> Timeline updates dynamically.",
                "3. Publishing & Tagging: Composing chirp with `#CampusConnect` -> Auto-indexing tag -> Trending Topics update.",
                "4. Social Engagement: Real-time heart like animation -> Threaded reply -> Saving post to private Bookmarks.",
                "5. Notifications: Unread bell badge increments on follow/like/reply -> notification center -> mark as read."
            ]
        },
        {
            "title": "Team Contribution & Verifiable Git Audit",
            "subtitle": "Documented Division of Labor",
            "bullets": [
                "Muddassir (27 Commits on main): Architecture lead, Sprint 1 Auth, Sprint 2 Post Engine, Sprint 3 Social/Design, Sprint 4 Redis fixes, Sprint 5 RBAC & Release.",
                "Faiz Hussain (7 Commits on main): Sprint 4 core likes, hashtags, caching layer foundation (`3ac38d4`); Sprint 5 notifications backend & seed data fixtures (3 commits, PR #9).",
                "Qaim (4 Sprint 5 Commits): Sprint 5 frontend responsive mobile polish (360px/tablet), micro-interactions, complete documentation suite, Agile presentation & report.",
                "Full Auditability: Complete commit log, author emails, dates, and diff statistics recorded in `docs/CONTRIBUTION.md`."
            ]
        },
        {
            "title": "Sprint Retrospective & Project Conclusions",
            "subtitle": "Agile Maturity & Lessons Learned",
            "bullets": [
                "What Went Well: Zero regressions across 181 unit tests; high code reusability via OOP; clean responsive UI without heavyweight frameworks.",
                "Lessons Learned: Early sprints suffered from commit centralization; Sprint 5 restructuring ensured distributed academic ownership and fair credit.",
                "Production Readiness: Complete documentation suite (`USER_MANUAL.md`, `ADMIN_MANUAL.md`, `DEPLOYMENT.md`), seed SQL, and packaged deliverables.",
                "Thank You! Q&A Session."
            ]
        }
    ]

    # Build PPTX XML files
    content_types = ['<?xml version="1.0" encoding="UTF-8" standalone="yes"?>',
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">',
        '  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>',
        '  <Default Extension="xml" ContentType="application/xml"/>',
        '  <Override PartName="/ppt/presentation.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/>',
        '  <Override PartName="/ppt/slideMasters/slideMaster1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideMaster+xml"/>',
        '  <Override PartName="/ppt/slideLayouts/slideLayout1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml"/>',
        '  <Override PartName="/ppt/slideLayouts/slideLayout2.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml"/>',
        '  <Override PartName="/ppt/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>'
    ]
    for i in range(1, len(slides_data) + 1):
        content_types.append(f'  <Override PartName="/ppt/slides/slide{i}.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>')
    content_types.append('</Types>')
    content_types_xml = '\n'.join(content_types)

    rels_root = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="ppt/presentation.xml"/>
</Relationships>'''

    pres_rels = [
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>',
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">',
        '  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="slideMasters/slideMaster1.xml"/>',
        '  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml"/>'
    ]
    for i in range(1, len(slides_data) + 1):
        pres_rels.append(f'  <Relationship Id="rId{i+2}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide{i}.xml"/>')
    pres_rels.append('</Relationships>')
    pres_rels_xml = '\n'.join(pres_rels)

    pres_xml = [
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>',
        '<p:presentation xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">',
        '  <p:sldMasterIdLst>',
        '    <p:sldMasterId id="2147483648" r:id="rId1"/>',
        '  </p:sldMasterIdLst>',
        '  <p:sldIdLst>'
    ]
    for i in range(1, len(slides_data) + 1):
        pres_xml.append(f'    <p:sldId id="{255 + i}" r:id="rId{i+2}"/>')
    pres_xml.extend([
        '  </p:sldIdLst>',
        '  <p:sldSz cx="12192000" cy="6858000" type="screen16x9"/>',
        '  <p:notesSz cx="6858000" cy="9144000"/>',
        '  <p:defaultTextStyle/>',
        '</p:presentation>'
    ])
    presentation_xml = '\n'.join(pres_xml)

    theme_xml = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="CampusConnect Theme">
  <a:themeElements>
    <a:clrScheme name="CampusConnect">
      <a:dk1><a:srgbClr val="0F1419"/></a:dk1>
      <a:lt1><a:srgbClr val="FFFFFF"/></a:lt1>
      <a:dk2><a:srgbClr val="1A2358"/></a:dk2>
      <a:lt2><a:srgbClr val="F7F9FA"/></a:lt2>
      <a:accent1><a:srgbClr val="2B3FE0"/></a:accent1>
      <a:accent2><a:srgbClr val="0A5C50"/></a:accent2>
      <a:accent3><a:srgbClr val="D97E4A"/></a:accent3>
      <a:accent4><a:srgbClr val="7B4BC4"/></a:accent4>
      <a:accent5><a:srgbClr val="177245"/></a:accent5>
      <a:accent6><a:srgbClr val="D92D20"/></a:accent6>
      <a:hlink><a:srgbClr val="2B3FE0"/></a:hlink>
      <a:folHlink><a:srgbClr val="1A2358"/></a:folHlink>
    </a:clrScheme>
    <a:fontScheme name="Inter Grotesque">
      <a:majorFont><a:latin typeface="Segoe UI"/><a:ea typeface=""/><a:cs typeface=""/></a:majorFont>
      <a:minorFont><a:latin typeface="Segoe UI"/><a:ea typeface=""/><a:cs typeface=""/></a:minorFont>
    </a:fontScheme>
    <a:fmtScheme name="Office">
      <a:fillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:fillStyleLst>
      <a:lnStyleLst><a:ln w="9525"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:ln><a:ln w="9525"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:ln><a:ln w="9525"><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:ln></a:lnStyleLst>
      <a:effectStyleLst><a:effectStyle><a:effectLst/></a:effectStyle><a:effectStyle><a:effectLst/></a:effectStyle><a:effectStyle><a:effectLst/></a:effectStyle></a:effectStyleLst>
      <a:bgFillStyleLst><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:solidFill><a:schemeClr val="phClr"/></a:solidFill><a:solidFill><a:schemeClr val="phClr"/></a:solidFill></a:bgFillStyleLst>
    </a:fmtScheme>
  </a:themeElements>
</a:theme>'''

    slide_master_rels = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout2.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="../theme/theme1.xml"/>
</Relationships>'''

    slide_master_xml = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sldMaster xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:cSld>
    <p:spTree>
      <p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>
      <p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>
    </p:spTree>
  </p:cSld>
  <p:clrMap bg1="lt1" tx1="dk1" bg2="lt2" tx2="dk2" accent1="accent1" accent2="accent2" accent3="accent3" accent4="accent4" accent5="accent5" accent6="accent6" hlink="hlink" folHlink="folHlink"/>
  <p:sldLayoutIdLst>
    <p:sldLayoutId id="2147483649" r:id="rId1"/>
    <p:sldLayoutId id="2147483650" r:id="rId2"/>
  </p:sldLayoutIdLst>
</p:sldMaster>'''

    layout_title_xml = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sldLayout xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" type="title" preserve="1">
  <p:cSld name="Title Slide">
    <p:spTree>
      <p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>
      <p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>
    </p:spTree>
  </p:cSld>
  <p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr>
</p:sldLayout>'''

    layout_content_xml = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sldLayout xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main" type="obj" preserve="1">
  <p:cSld name="Title and Content">
    <p:spTree>
      <p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>
      <p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>
    </p:spTree>
  </p:cSld>
  <p:clrMapOvr><a:masterClrMapping/></p:clrMapOvr>
</p:sldLayout>'''

    layout_rels = '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="../slideMasters/slideMaster1.xml"/>
</Relationships>'''

    with zipfile.ZipFile(output_path, 'w', compression=zipfile.ZIP_DEFLATED) as z:
        z.writestr('[Content_Types].xml', content_types_xml)
        z.writestr('_rels/.rels', rels_root)
        z.writestr('ppt/presentation.xml', presentation_xml)
        z.writestr('ppt/_rels/presentation.xml.rels', pres_rels_xml)
        z.writestr('ppt/theme/theme1.xml', theme_xml)
        z.writestr('ppt/slideMasters/slideMaster1.xml', slide_master_xml)
        z.writestr('ppt/slideMasters/_rels/slideMaster1.xml.rels', slide_master_rels)
        z.writestr('ppt/slideLayouts/slideLayout1.xml', layout_title_xml)
        z.writestr('ppt/slideLayouts/_rels/slideLayout1.xml.rels', layout_rels)
        z.writestr('ppt/slideLayouts/slideLayout2.xml', layout_content_xml)
        z.writestr('ppt/slideLayouts/_rels/slideLayout2.xml.rels', layout_rels)

        for i, s in enumerate(slides_data, start=1):
            is_title_slide = (i == 1)
            layout_target = "../slideLayouts/slideLayout1.xml" if is_title_slide else "../slideLayouts/slideLayout2.xml"
            slide_rel = f'''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="{layout_target}"/>
</Relationships>'''
            z.writestr(f'ppt/slides/_rels/slide{i}.xml.rels', slide_rel)

            title_escaped = escape_xml(s["title"])
            subtitle_escaped = escape_xml(s["subtitle"])

            if is_title_slide:
                slide_content = f'''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:cSld>
    <p:bg>
      <p:bgRef idx="1"><a:schemeClr val="accent1"/></p:bgRef>
    </p:bg>
    <p:spTree>
      <p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>
      <p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="2" name="Title"/><p:cNvSpPr><a:spLocks noGrp="1"/></p:cNvSpPr><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="1000000" y="1600000"/><a:ext cx="10192000" cy="1800000"/></a:xfrm></p:spPr>
        <p:txBody>
          <a:bodyPr anchor="ctr"/>
          <a:p><a:pPr algn="ctr"/><a:r><a:rPr sz="5200" b="1"><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill></a:rPr><a:t>{title_escaped}</a:t></a:r></a:p>
          <a:p><a:pPr algn="ctr"/><a:r><a:rPr sz="2200" b="0"><a:solidFill><a:srgbClr val="ECEEFE"/></a:solidFill></a:rPr><a:t>University Microblogging &amp; Announcement Hub</a:t></a:r></a:p>
        </p:txBody>
      </p:sp>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="3" name="Subtitle"/><p:cNvSpPr><a:spLocks noGrp="1"/></p:cNvSpPr><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="1000000" y="3800000"/><a:ext cx="10192000" cy="2200000"/></a:xfrm></p:spPr>
        <p:txBody>
          <a:bodyPr anchor="top"/>
          <a:p><a:pPr algn="ctr"/><a:r><a:rPr sz="2000" b="1"><a:solidFill><a:srgbClr val="FFFFFF"/></a:solidFill></a:rPr><a:t>Agile Software Project Management (ASPM) — Final Project Review (Sprints 1–5)</a:t></a:r></a:p>
          <a:p><a:pPr algn="ctr"/><a:r><a:rPr sz="1900" b="1"><a:solidFill><a:srgbClr val="ECEEFE"/></a:solidFill></a:rPr><a:t>Section: BCS-7A | Group Code: AIR</a:t></a:r></a:p>
          <a:p><a:pPr algn="ctr"/><a:r><a:rPr sz="1700"><a:solidFill><a:srgbClr val="ECEEFE"/></a:solidFill></a:rPr><a:t>Team: Muddassir (Lead Backend) | Faiz Hussain (Backend &amp; QA) | Qaim (Frontend &amp; Docs)</a:t></a:r></a:p>
          <a:p><a:pPr algn="ctr"/><a:r><a:rPr sz="1500"><a:solidFill><a:srgbClr val="A5B4FF"/></a:solidFill></a:rPr><a:t>November 2026</a:t></a:r></a:p>
        </p:txBody>
      </p:sp>
    </p:spTree>
  </p:cSld>
</p:sld>'''
            else:
                bullets_xml = []
                for b in s["bullets"]:
                    b_esc = escape_xml(b)
                    parts = b_esc.split(":", 1)
                    if len(parts) == 2:
                        b_header, b_body = parts[0], parts[1]
                        bullet_p = f'''<a:p>
            <a:pPr marL="342900" indent="-342900"><a:buFont typeface="Arial"/><a:buChar char="•"/></a:pPr>
            <a:r><a:rPr sz="1900" b="1"><a:solidFill><a:srgbClr val="0F1419"/></a:solidFill></a:rPr><a:t>{b_header}:</a:t></a:r>
            <a:r><a:rPr sz="1800"><a:solidFill><a:srgbClr val="2E3A42"/></a:solidFill></a:rPr><a:t>{b_body}</a:t></a:r>
          </a:p>'''
                    else:
                        bullet_p = f'''<a:p>
            <a:pPr marL="342900" indent="-342900"><a:buFont typeface="Arial"/><a:buChar char="•"/></a:pPr>
            <a:r><a:rPr sz="1800"><a:solidFill><a:srgbClr val="2E3A42"/></a:solidFill></a:rPr><a:t>{b_esc}</a:t></a:r>
          </a:p>'''
                    bullets_xml.append(bullet_p)
                bullets_content = '\n'.join(bullets_xml)

                slide_content = f'''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:cSld>
    <p:spTree>
      <p:nvGrpSpPr><p:cNvPr id="1" name=""/><p:cNvGrpSpPr/><p:nvPr/></p:nvGrpSpPr>
      <p:grpSpPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="0" cy="0"/><a:chOff x="0" y="0"/><a:chExt cx="0" cy="0"/></a:xfrm></p:grpSpPr>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="2" name="Title"/><p:cNvSpPr><a:spLocks noGrp="1"/></p:cNvSpPr><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="800000" y="600000"/><a:ext cx="10600000" cy="1100000"/></a:xfrm></p:spPr>
        <p:txBody>
          <a:bodyPr anchor="top"/>
          <a:p><a:r><a:rPr sz="3200" b="1"><a:solidFill><a:srgbClr val="2B3FE0"/></a:solidFill></a:rPr><a:t>{title_escaped}</a:t></a:r></a:p>
          <a:p><a:r><a:rPr sz="1800" b="1"><a:solidFill><a:srgbClr val="536471"/></a:solidFill></a:rPr><a:t>{subtitle_escaped}</a:t></a:r></a:p>
        </p:txBody>
      </p:sp>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="3" name="Content"/><p:cNvSpPr><a:spLocks noGrp="1"/></p:cNvSpPr><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="800000" y="1900000"/><a:ext cx="10600000" cy="4400000"/></a:xfrm></p:spPr>
        <p:txBody>
          <a:bodyPr anchor="top"/>
          {bullets_content}
        </p:txBody>
      </p:sp>
      <p:sp>
        <p:nvSpPr><p:cNvPr id="4" name="Footer"/><p:cNvSpPr><a:spLocks noGrp="1"/></p:cNvSpPr><p:nvPr/></p:nvSpPr>
        <p:spPr><a:xfrm><a:off x="800000" y="6400000"/><a:ext cx="10600000" cy="300000"/></a:xfrm></p:spPr>
        <p:txBody>
          <a:bodyPr anchor="b"/>
          <a:p><a:r><a:rPr sz="1200"><a:solidFill><a:srgbClr val="8696A3"/></a:solidFill></a:rPr><a:t>CampusConnect | Agile Software Project Management | Slide {i} of {len(slides_data)}</a:t></a:r></a:p>
        </p:txBody>
      </p:sp>
    </p:spTree>
  </p:cSld>
</p:sld>'''
            z.writestr(f'ppt/slides/slide{i}.xml', slide_content)

    print(f"Presentation successfully created at: {output_path}")

if __name__ == "__main__":
    # One set only: the AIR_* files are the deliverables. Do NOT generate
    # CampusConnect_* or [GroupCode]_* duplicates (same content, ~3x the bytes).
    generate_presentation("AIR_Presentation.pptx", group_code="AIR", section="BCS-7A")
