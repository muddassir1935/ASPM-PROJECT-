# CampusConnect — End-User Manual

Welcome to **CampusConnect**, the dedicated microblogging, social networking, and announcement platform designed specifically for university students, faculty, and student societies.

This user manual walks you through all features available to standard student users, from initial account creation to exploring campus trends and managing your profile.

---

## Table of Contents
1. [Getting Started](#1-getting-started)
   - [Account Registration](#11-account-registration)
   - [Logging In and Session Security](#12-logging-in-and-session-security)
   - [Logging Out](#13-logging-out)
2. [Navigating CampusConnect](#2-navigating-campusconnect)
   - [Desktop Layout Overview](#21-desktop-layout-overview)
   - [Mobile and Tablet Experience](#22-mobile-and-tablet-experience)
3. [Composing and Managing Posts](#3-composing-and-managing-posts)
   - [Publishing a New Post](#31-publishing-a-new-post)
   - [Using Hashtags](#32-using-hashtags)
   - [Editing an Existing Post](#33-editing-an-existing-post)
   - [Deleting a Post](#34-deleting-a-post)
4. [Engaging with the Campus Community](#4-engaging-with-the-campus-community)
   - [Personalized Feed vs. Explore Feed](#41-personalized-feed-vs-explore-feed)
   - [Following and Unfollowing Peers](#42-following-and-unfollowing-peers)
   - [Liking Posts](#43-liking-posts)
   - [Replying to Posts](#44-replying-to-posts)
   - [Bookmarking Posts for Later](#45-bookmarking-posts-for-later)
5. [Notifications](#5-notifications)
   - [Unread Notification Badge](#51-unread-notification-badge)
   - [Viewing and Clearing Notifications](#52-viewing-and-clearing-notifications)
6. [Profile Customization](#6-profile-customization)
   - [Updating Your Name and Bio](#61-updating-your-name-and-bio)
   - [Selecting a Profile Cover Style](#62-selecting-a-profile-cover-style)
   - [Reviewing Your Activity and Likes](#63-reviewing-your-activity-and-likes)
7. [Troubleshooting & FAQ](#7-troubleshooting--faq)

---

## 1. Getting Started

### 1.1 Account Registration
1. Navigate to the CampusConnect homepage in your web browser.
2. Click **Create Account** in the top navigation bar or **Create Your Account** on the welcome landing page.
3. Fill out the registration form:
   - **Full Name**: Enter your official student name.
   - **Campus Email**: Enter your university email address (e.g., `student@campus.edu.pk`). Email addresses must be unique.
   - **Password**: To safeguard campus accounts, CampusConnect enforces a strong password policy:
     - Minimum **8 characters** in length
     - At least one **uppercase letter** (`A–Z`)
     - At least one **lowercase letter** (`a–z`)
     - At least one **number** (`0–9`)
     - At least one **special symbol** (e.g., `@`, `#`, `$`, `%`, `!`, `&`, `*`)
4. Real-time checklist indicators will turn green as you fulfill each password criterion.
5. Click **Create Account**. Upon successful registration, you will be redirected to the login screen with a confirmation alert.

### 1.2 Logging In and Session Security
1. Go to `login.php` or click **Log In** from the top header.
2. Provide your registered email address and password.
3. Click **Sign In**.
4. Sessions are stored server-side in the database (`DbSessionHandler`) and invalidated on logout. Session IDs are regenerated on login to prevent session fixation.

### 1.3 Logging Out
To protect your account, especially when using shared campus lab computers or public Wi-Fi:
1. Click the **Logout** button located in the top-right corner of the navigation bar.
2. Your session will be immediately invalidated and purged from the server.

### 1.4 Changing Your Password
1. Open your own profile and click **Edit Profile**.
2. In the **Change password** section, enter your current password, then the new password twice (same policy as signup: min 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character).
3. Click **Change Password**. Your other sessions (other browsers/devices) are signed out; the current session stays logged in.

If you forget your password entirely, ask a system admin to reset it — they will give you a temporary password, which you should change immediately after logging in (see above). The app sends no email, so there is no "forgot password" link.

---

## 2. Navigating CampusConnect

### 2.1 Desktop Layout Overview
On desktop viewports, CampusConnect presents an intuitive 3-column architecture:
- **Left Column (Navigation Rail)**: Fast one-click access to **Home**, **Explore**, **Bookmarks**, **Notifications**, and your **Profile**.
- **Center Column (Main Stream)**: Dynamic timeline feed, active compose box, detailed post views, and reply threads.
- **Right Column (Context Rail)**: Quick search bar, "Who to follow" peer suggestions, and "Trending Topics" hashtag list.

### 2.2 Mobile and Tablet Experience
- **Tablet (721px – 1080px)**: The navigation collapses into a compact icon rail, while the context rail tucks away cleanly to give maximum focus to the reading timeline.
- **Mobile (360px – 720px)**: The navigation transforms into a sticky, touch-friendly navigation bar. All avatars, buttons, and text wrap without horizontal scroll bars down to narrow 360px smartphone screens.

---

## 3. Composing and Managing Posts

### 3.1 Publishing a New Post
1. On your **Home** page, locate the compose card at the top of the feed.
2. Type your message in the text box. Posts can be up to **280 characters** long.
3. A live character counter (`0 / 280`) displays your remaining space. The "Post" button activates as soon as valid text is entered.
4. Click **Post**. The post is immediately published, indexed for search, and added to your followers' feeds.

### 3.2 Using Hashtags
- Include hashtags anywhere in your post using `#keyword` format (e.g., `#Hackathon2026`, `#CampusLife`, `#AI_Lab`).
- Hashtags are automatically detected, converted into interactive clickable blue links, and aggregated into the **Trending Topics** sidebar.
- Clicking any hashtag opens the Explore page filtered to posts with that tag.

### 3.3 Editing an Existing Post
1. Open the post you authored (either by viewing it on your feed or clicking into its detail page).
2. Click **Edit**. An inline text area opens pre-populated with your current content.
3. Update your text (subject to the 280-character limit) and click **Save Changes**.
4. Posts that have been modified show a subtle `edited` label in their timestamp.

### 3.4 Deleting a Post
1. On posts you have authored, click the **Delete** button.
2. Confirm the browser prompt ("Delete this post? This cannot be undone.").
3. The post is soft-deleted: it immediately disappears from all user timelines and search indexes while preserving platform database integrity.

---

## 4. Engaging with the Campus Community

### 4.1 Personalized Feed vs. Explore Feed
- **Home Feed**: Displays an algorithmically pure, chronological stream containing your own posts and updates published by campus members you follow.
- **Explore Feed (`explore.php`)**: Displays all public university updates, community announcements, and allows keyword search across student profiles and post contents.

### 4.2 Following and Unfollowing Peers
1. Locate another user through the Explore page, search, or by clicking their avatar on any post.
2. Click the **Follow** button on their profile or from the "Who to follow" suggestion widget.
3. The button instantly toggles to **Following** via AJAX without refreshing the page.
4. To unfollow, click the button again. Their future posts will no longer appear in your Home feed.

### 4.3 Liking Posts
- Click the heart icon on any post card.
- The heart lights up in vibrant crimson with an interactive pop animation, and the like count increments in real time.
- Clicking again removes your like.
- All posts you like are collected in the **Likes** tab on your profile page.

### 4.4 Replying to Posts
1. Click the reply bubble icon or the post text to open the dedicated post view (`post.php`).
2. Scroll to the "Post a reply" box beneath the main chirp.
3. Enter your response (up to 280 characters) and click **Reply**.
4. Replies appear threaded sequentially (oldest to newest) directly under the original post.

### 4.5 Bookmarking Posts for Later
- Click the **Save** button (ribbon icon) on any post card to bookmark it for later review.
- Access all your saved posts at any time by selecting **Bookmarks** from the left navigation rail.
- Bookmarks are strictly private; other users cannot see what you have saved.

---

## 5. Notifications

### 5.1 Unread Notification Badge
- The bell icon in the navigation bar highlights an unread notification count badge whenever relevant activity occurs:
  - When another student begins **following** you.
  - When someone **likes** one of your posts.
  - When someone **replies** to your post.

### 5.2 Viewing and Clearing Notifications
1. Click **Notifications** in the navigation rail.
2. Browse through your recent activity feed. Each notification provides a direct link to the user or post involved.
3. Click **Mark all as read** at the top-right of the notification center to clear the badge.

---

## 6. Profile Customization

### 6.1 Updating Your Name and Bio
1. Click **Profile** in the navigation menu.
2. Click the **Edit Profile** button.
3. In the modal dialog:
   - Edit your display name.
   - Write or update a university bio (up to 500 characters) describing your department, batch, interests, or society affiliations.
4. Click **Save Changes**.

### 6.2 Selecting a Profile Cover Style
CampusConnect provides 6 curated, accessible duotone gradient cover styles that echo the modern campus branding without requiring slow image uploads:
- **Signature**: Ink-to-cobalt signature duotone.
- **Cobalt**: Deep university navy blue.
- **Teal**: Clean emerald and seafoam gradient.
- **Sunset**: Warm berry and amber gradient.
- **Violet**: Rich amethyst and royal purple.
- **Graphite**: Minimalist monochrome slate.

Select your preferred swatch in the Edit Profile modal and hit **Save Changes**. Your profile banner updates immediately.

### 6.3 Reviewing Your Activity and Likes
On your profile, two dedicated tabs help you navigate your activity:
- **Posts (N)**: Displays your published updates and announcements.
- **Likes (N)**: Displays the archive of posts you have liked across the platform.
- Click on the **followers** or **following** counters to inspect interactive modal lists of peers.

---

## 7. Troubleshooting & FAQ

**Q: Can I change my registered email address?**  
A: Student email addresses serve as the unique academic identifier. To modify an email address, contact your system administrator.

**Q: Why don't I see any posts on my Home feed?**  
A: Your Home feed is personalized: it shows posts from yourself and users you follow. If you are a new user, head to the **Explore** page or check the "Who to follow" sidebar to follow classmates and societies!

**Q: Are deleted posts recoverable?**  
A: When you delete a post, it is permanently hidden from all public feeds and cannot be restored.

**Q: What browser should I use?**  
A: CampusConnect is built on standard responsive web technologies and runs smoothly on all modern browsers (Chrome, Firefox, Safari, Edge) across Windows, macOS, Linux, Android, and iOS.
