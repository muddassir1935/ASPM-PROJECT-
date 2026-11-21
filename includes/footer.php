</main>

<footer class="site-footer">
    <strong>CampusConnect</strong> &copy; <?= date('Y') ?>. All rights reserved.
</footer>

<script>
    // Sprint 5 CSRF: every AJAX POST carries the session token from the
    // <meta name="csrf-token"> tag (emitted by header.php when logged in).
    var csrfToken = '';
    (function () {
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) { csrfToken = meta.getAttribute('content') || ''; }
    })();

    // Audit F11: AJAX failures (expired session, server error, refused
    // action) used to be ignored silently — the button just did nothing.
    // ccNotify shows a short, self-dismissing message instead.
    function ccNotify(message) {
        var box = document.createElement('div');
        box.className = 'alert alert-error cc-toast';
        box.setAttribute('role', 'alert');
        box.textContent = message;
        document.body.appendChild(box);
        setTimeout(function () { box.remove(); }, 4500);
    }
    function ccJson(response) {
        if (response.status === 401 || response.status === 403) {
            throw new Error('Your session has expired. Please refresh and log in again.');
        }
        return response.json().catch(function () {
            throw new Error('Something went wrong. Please refresh and try again.');
        });
    }
    function ccFail(err) {
        ccNotify((err && err.message) || 'Network error. Please check your connection and try again.');
    }

    // Global delegated click handler
    document.addEventListener('click', function (event) {
        // Theme Switcher Toggle (1-click Light / Dark)
        var themeBtn = event.target.closest('#theme-toggle-btn');
        if (themeBtn) {
            event.preventDefault();
            var cur = document.documentElement.getAttribute('data-theme') || 'dark';
            var next = cur === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            try { localStorage.setItem('cc_theme', next); } catch (e) {}
            return;
        }

        // Show/Hide Password Toggle
        var pwToggle = event.target.closest('[data-toggle-pw]');
        if (pwToggle) {
            event.preventDefault();
            var wrap = pwToggle.closest('.password-input-wrap');
            if (wrap) {
                var input = wrap.querySelector('input');
                if (input) {
                    var isText = input.type === 'text';
                    input.type = isText ? 'password' : 'text';
                    var eyeOpen = pwToggle.querySelector('.eye-open');
                    var eyeClosed = pwToggle.querySelector('.eye-closed');
                    if (eyeOpen) eyeOpen.style.display = isText ? 'block' : 'none';
                    if (eyeClosed) eyeClosed.style.display = isText ? 'none' : 'block';
                }
            }
            return;
        }

        // Post Card 3-Dots Dropdown Menu Toggle
        var menuBtn = event.target.closest('[data-post-menu-toggle]');
        if (menuBtn) {
            event.preventDefault();
            event.stopPropagation();
            var pid = menuBtn.getAttribute('data-post-menu-toggle');
            var pmenu = document.getElementById('post-menu-' + pid);
            var wasClosed = !pmenu || pmenu.style.display === 'none';
            closeAllPostMenus();
            if (pmenu && wasClosed) {
                pmenu.style.display = 'flex';
            }
            return;
        }
        if (!event.target.closest('.post-dropdown-menu')) {
            closeAllPostMenus();
        }

        // Nav User Dock Dropdown Menu Toggle
        var userDockBtn = event.target.closest('#nav-user-pill-btn');
        var userDropdown = document.getElementById('nav-user-dropdown');
        if (userDockBtn) {
            event.preventDefault();
            event.stopPropagation();
            if (userDropdown) {
                var isHidden = userDropdown.style.display === 'none' || userDropdown.style.display === '';
                userDropdown.style.display = isHidden ? 'flex' : 'none';
                userDockBtn.setAttribute('aria-expanded', isHidden ? 'true' : 'false');
            }
            return;
        }
        if (userDropdown && !event.target.closest('#nav-user-dropdown')) {
            userDropdown.style.display = 'none';
            var pillBtn = document.getElementById('nav-user-pill-btn');
            if (pillBtn) { pillBtn.setAttribute('aria-expanded', 'false'); }
        }

        var followBtn = event.target.closest('[data-follow-toggle]');
        if (followBtn) {
            event.preventDefault();
            if (followBtn.disabled) { return; }
            followBtn.disabled = true;
            fetch('follow_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'user_id=' + encodeURIComponent(followBtn.getAttribute('data-user-id'))
                    + '&csrf_token=' + encodeURIComponent(csrfToken)
            })
            .then(ccJson)
            .then(function (data) {
                if (!data.success) { ccNotify(data.message || 'That did not work. Please try again.'); return; }
                var userId = followBtn.getAttribute('data-user-id');
                // Update every follow button for this user on the page.
                document.querySelectorAll('[data-follow-toggle][data-user-id="' + userId + '"]').forEach(function (btn) {
                    btn.textContent = data.following ? 'Following' : 'Follow';
                    btn.classList.toggle('following', data.following);
                });
                // Update every follower count label for this user.
                document.querySelectorAll('[data-followers-count-for="' + userId + '"]').forEach(function (el) {
                    el.textContent = data.followers_count;
                });
                // Audit F7: keep the Follower/Followers label and the
                // viewer's own Following count in step too.
                document.querySelectorAll('[data-followers-label-for="' + userId + '"]').forEach(function (el) {
                    el.textContent = Number(data.followers_count) === 1 ? 'Follower' : 'Followers';
                });
                if (data.viewer_id) {
                    document.querySelectorAll('[data-following-count-for="' + data.viewer_id + '"]').forEach(function (el) {
                        el.textContent = data.viewer_following_count;
                    });
                }
            })
            .catch(ccFail)
            .finally(function () { followBtn.disabled = false; });
            return;
        }

        var bookmarkBtn = event.target.closest('[data-bookmark-toggle]');
        if (bookmarkBtn) {
            event.preventDefault();
            if (bookmarkBtn.disabled) { return; }
            bookmarkBtn.disabled = true;
            fetch('bookmark_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'post_id=' + encodeURIComponent(bookmarkBtn.getAttribute('data-post-id'))
                    + '&csrf_token=' + encodeURIComponent(csrfToken)
            })
            .then(ccJson)
            .then(function (data) {
                if (!data.success) { ccNotify(data.message || 'That did not work. Please try again.'); return; }
                if (!data.bookmarked && bookmarkBtn.getAttribute('data-remove-card') === '1') {
                    var card = bookmarkBtn.closest('[data-post-card]');
                    if (card) { card.remove(); }
                    // Audit F7: keep "Your Bookmarks (N)" accurate and show
                    // the empty state once the last saved post is removed.
                    document.querySelectorAll('[data-bookmarks-total]').forEach(function (el) {
                        el.textContent = Math.max(0, (parseInt(el.textContent, 10) || 1) - 1);
                    });
                    var list = document.querySelector('[data-bookmarks-list]');
                    if (list && !list.querySelector('[data-post-card]')) {
                        var empty = document.createElement('div');
                        empty.className = 'empty-state';
                        var title = document.createElement('h4');
                        title.className = 'empty-title';
                        title.textContent = 'No saved posts yet';
                        var text = document.createElement('p');
                        text.className = 'empty-text';
                        text.textContent = 'Tap Save on any post to keep it here for later.';
                        empty.appendChild(title);
                        empty.appendChild(text);
                        list.appendChild(empty);
                    }
                    return;
                }
                bookmarkBtn.textContent = data.bookmarked ? 'Saved' : 'Save';
                bookmarkBtn.classList.toggle('is-bookmarked', data.bookmarked);
                bookmarkBtn.setAttribute('data-bookmarked', data.bookmarked ? '1' : '0');
                bookmarkBtn.setAttribute('aria-pressed', data.bookmarked ? 'true' : 'false');
            })
            .catch(ccFail)
            .finally(function () { bookmarkBtn.disabled = false; });
            return;
        }

        var likeBtn = event.target.closest('[data-like-toggle]');
        if (likeBtn) {
            event.preventDefault();
            if (likeBtn.disabled) { return; }
            likeBtn.disabled = true;
            var postId = likeBtn.getAttribute('data-post-id');
            fetch('like_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'post_id=' + encodeURIComponent(postId)
                    + '&csrf_token=' + encodeURIComponent(csrfToken)
            })
            .then(ccJson)
            .then(function (data) {
                if (!data.success) { ccNotify(data.message || 'That did not work. Please try again.'); return; }
                document.querySelectorAll('[data-like-toggle][data-post-id="' + postId + '"]').forEach(function (btn) {
                    btn.classList.toggle('is-liked', data.liked);
                    btn.setAttribute('aria-pressed', data.liked ? 'true' : 'false');
                    var svg = btn.querySelector('svg');
                    if (svg) {
                        svg.setAttribute('fill', data.liked ? '#e0245e' : 'none');
                    }
                    var countSpan = btn.querySelector('.like-count');
                    if (countSpan) {
                        countSpan.textContent = data.like_count;
                    }
                });
            })
            .catch(ccFail)
            .finally(function () { likeBtn.disabled = false; });
            return;
        }
    });

    // Sprint 5 final fix (security M5): double-clicking Post/Reply submitted
    // the form twice and created duplicates (AJAX toggles disable
    // themselves; plain forms did not). Disable the submit button on first
    // submit — the page navigates away, so no re-enable path is needed.
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (form.id !== 'compose-form' && form.id !== 'reply-form') { return; }
        var btn = form.querySelector('button[type="submit"]');
        if (btn) { btn.disabled = true; }
    });

    // Sprint 5 UI polish (M-15): a "Following" button states a fact, so on
    // hover it flips to the action it performs — "Unfollow" — matching the
    // red hover tint from CSS. The click handler above still owns the label
    // after a toggle; hover only swaps it while the pointer is over the
    // button. relatedTarget guards against flicker on child boundaries.
    document.addEventListener('mouseover', function (event) {
        var btn = event.target.closest ? event.target.closest('.btn-follow.following') : null;
        if (btn && !btn.contains(event.relatedTarget)) { btn.textContent = 'Unfollow'; }
    });
    document.addEventListener('mouseout', function (event) {
        var btn = event.target.closest ? event.target.closest('.btn-follow.following') : null;
        if (btn && !btn.contains(event.relatedTarget)) { btn.textContent = 'Following'; }
    });

    // Post card 3-dots menu dismissal
    function closeAllPostMenus() {
        document.querySelectorAll('.post-dropdown-menu').forEach(function (m) {
            m.style.display = 'none';
        });
    }

    // Sprint 5 UI polish (M-7): one inline-edit toggle for every post
    // card (feed, profile, explore, bookmarks share it now).
    function togglePostEdit(postId) {
        var form = document.getElementById('post-edit-' + postId);
        if (form) {
            form.style.display = (form.style.display === 'none') ? 'block' : 'none';
        }
    }

    // Sprint 3: follower / following modal toggle (profile page).
    // Sprint 5 UI polish (M-8): Escape closes any open modal and
    // returns focus to the element that opened it (verified live twice
    // that the followers modal stayed open after Escape before this).
    var modalOpener = null;
    function toggleModal(modalId) {
        var modal = document.getElementById(modalId);
        if (!modal) { return; }
        var willOpen = !modal.classList.contains('open');
        if (willOpen) {
            modalOpener = document.activeElement;
        }
        modal.classList.toggle('open');
        if (willOpen) {
            // Audit F12: move keyboard focus into the dialog when it opens.
            var focusable = modal.querySelector('input, textarea, select, button, a[href]');
            if (focusable) { focusable.focus(); }
        }
        if (!willOpen && modalOpener && modalOpener.focus) {
            modalOpener.focus();
            modalOpener = null;
        }
    }
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') { return; }
        closeAllPostMenus();
        var userDropdown = document.getElementById('nav-user-dropdown');
        if (userDropdown && userDropdown.style.display !== 'none') {
            userDropdown.style.display = 'none';
            var pillBtn = document.getElementById('nav-user-pill-btn');
            if (pillBtn) { pillBtn.setAttribute('aria-expanded', 'false'); }
        }
        var openModals = document.querySelectorAll('.modal-overlay.open');
        if (openModals.length === 0) { return; }
        openModals.forEach(function (m) { m.classList.remove('open'); });
        pendingConfirmForm = null;
        if (modalOpener && modalOpener.focus) {
            modalOpener.focus();
            modalOpener = null;
        }
    });

    // In-App Confirmation Modal Controller (replaces native browser confirm())
    var pendingConfirmForm = null;

    function showConfirmModal(title, message, formToSubmit, confirmBtnText) {
        var modal = document.getElementById('confirm-dialog-modal');
        if (!modal) return;

        var titleEl = document.getElementById('confirm-dialog-title');
        var descEl = document.getElementById('confirm-dialog-desc');
        var btnEl = document.getElementById('confirm-dialog-submit-btn');

        if (titleEl) titleEl.textContent = title || 'Delete Post';
        if (descEl) descEl.textContent = message || 'Are you sure you want to delete this post? This action cannot be undone.';
        if (btnEl) btnEl.textContent = confirmBtnText || 'Delete';

        pendingConfirmForm = formToSubmit || null;
        modal.classList.add('open');

        if (typeof closeAllPostMenus === 'function') {
            closeAllPostMenus();
        }

        if (btnEl) {
            setTimeout(function() { btnEl.focus(); }, 40);
        }
    }

    function closeConfirmModal() {
        var modal = document.getElementById('confirm-dialog-modal');
        if (!modal) return;
        modal.classList.remove('open');
        pendingConfirmForm = null;
    }

    // In-App Report Modal Controller
    function openReportModal(postId) {
        var modal = document.getElementById('report-post-modal');
        if (!modal) return;
        var postInput = document.getElementById('report-post-id-input');
        if (postInput) postInput.value = postId;
        var returnInput = document.getElementById('report-return-input');
        if (returnInput) {
            var path = window.location.pathname.split('/').pop() || 'index.php';
            returnInput.value = path + window.location.search;
        }
        var detailInput = document.getElementById('report-detail-input');
        var counter = document.getElementById('report-char-counter');
        if (detailInput) detailInput.value = '';
        if (counter) counter.textContent = '0';
        modal.classList.add('open');
        if (typeof closeAllPostMenus === 'function') {
            closeAllPostMenus();
        }
    }

    function closeReportModal() {
        var modal = document.getElementById('report-post-modal');
        if (!modal) return;
        modal.classList.remove('open');
    }

    document.addEventListener('input', function(e) {
        if (e.target && e.target.id === 'report-detail-input') {
            var counter = document.getElementById('report-char-counter');
            if (counter) counter.textContent = e.target.value.length;
        }
    });

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form) return;
        if (form.classList && form.classList.contains('js-confirm-delete')) {
            event.preventDefault();
            var title = form.getAttribute('data-confirm-title') || 'Delete Post';
            var message = form.getAttribute('data-confirm-message') || 'Are you sure you want to delete this post? This action cannot be undone.';
            var confirmBtn = form.getAttribute('data-confirm-btn') || 'Delete';
            showConfirmModal(title, message, form, confirmBtn);
        }
    });

    // Delegated: the confirm button's markup sits AFTER this script in the
    // document, so a direct getElementById here would return null and the
    // Delete button would silently do nothing.
    document.addEventListener('click', function (event) {
        var btn = event.target && event.target.closest
            ? event.target.closest('#confirm-dialog-submit-btn') : null;
        if (!btn) { return; }
        if (pendingConfirmForm) {
            var form = pendingConfirmForm;
            closeConfirmModal();
            HTMLFormElement.prototype.submit.call(form);
        }
    });
</script>

<!-- In-App Confirmation Modal (Replaces browser confirm popup) -->
<div id="confirm-dialog-modal" class="modal-overlay confirm-modal-overlay" onclick="if (event.target === this) closeConfirmModal();">
    <div class="modal-box confirm-dialog-box" role="dialog" aria-modal="true" aria-labelledby="confirm-dialog-title" aria-describedby="confirm-dialog-desc">
        <h3 id="confirm-dialog-title" class="confirm-dialog-title">Delete Post</h3>
        <p id="confirm-dialog-desc" class="confirm-dialog-desc">Are you sure you want to delete this post? This action cannot be undone.</p>
        <div class="confirm-dialog-actions">
            <button type="button" id="confirm-dialog-submit-btn" class="confirm-btn-danger">Delete</button>
            <button type="button" class="confirm-btn-cancel" onclick="closeConfirmModal();">Cancel</button>
        </div>
    </div>
</div>

<!-- Report Post Modal (Sprint 5) -->
<div id="report-post-modal" class="modal-overlay report-modal-overlay" onclick="if (event.target === this) closeReportModal();">
    <div class="modal-box report-dialog-box" role="dialog" aria-modal="true" aria-labelledby="report-modal-title">
        <div class="report-modal-head">
            <h3 id="report-modal-title" class="report-modal-title">Report Post</h3>
            <button type="button" class="modal-close" onclick="closeReportModal();" aria-label="Close dialog">&times;</button>
        </div>
        <p class="report-modal-subtitle">Help us understand the issue. What violates campus policies in this post?</p>
        
        <form id="report-post-form" method="POST" action="report_action.php">
            <?php
            $footerCsrf = isset($csrf) && $csrf instanceof Csrf
                ? $csrf
                : (class_exists('Csrf') ? new Csrf() : null);
            if ($footerCsrf) {
                echo $footerCsrf->field();
            }
            ?>
            <input type="hidden" name="post_id" id="report-post-id-input" value="0">
            <input type="hidden" name="return" id="report-return-input" value="index.php">

            <div class="report-reasons-list">
                <label class="report-reason-option">
                    <input type="radio" name="reason" value="spam" required checked>
                    <div class="report-reason-text">
                        <strong>Spam</strong>
                        <span>Malicious links, fake engagement, or commercial ads</span>
                    </div>
                </label>
                <label class="report-reason-option">
                    <input type="radio" name="reason" value="harassment" required>
                    <div class="report-reason-text">
                        <strong>Harassment or hate speech</strong>
                        <span>Targeted bullying, threats, or discrimination</span>
                    </div>
                </label>
                <label class="report-reason-option">
                    <input type="radio" name="reason" value="misinformation" required>
                    <div class="report-reason-text">
                        <strong>Misinformation</strong>
                        <span>False campus announcements or deceptive claims</span>
                    </div>
                </label>
                <label class="report-reason-option">
                    <input type="radio" name="reason" value="inappropriate" required>
                    <div class="report-reason-text">
                        <strong>Sexually explicit or inappropriate content</strong>
                        <span>NSFW material, vulgarity, or graphic imagery</span>
                    </div>
                </label>
                <label class="report-reason-option">
                    <input type="radio" name="reason" value="violence" required>
                    <div class="report-reason-text">
                        <strong>Violence or threats</strong>
                        <span>Physical danger, violent threats, or self-harm</span>
                    </div>
                </label>
                <label class="report-reason-option">
                    <input type="radio" name="reason" value="other" required>
                    <div class="report-reason-text">
                        <strong>Other</strong>
                        <span>Other policy violations (please explain below)</span>
                    </div>
                </label>
            </div>

            <div class="report-detail-group">
                <label for="report-detail-input" class="report-detail-label">Additional details (optional, max 500 characters):</label>
                <textarea id="report-detail-input" name="detail" class="report-detail-textarea" rows="3" maxlength="500" placeholder="Provide extra details for the campus moderators..."></textarea>
                <div class="report-char-count"><span id="report-char-counter">0</span> / 500</div>
            </div>

            <div class="report-modal-actions">
                <button type="button" class="btn-cancel" onclick="closeReportModal();">Cancel</button>
                <button type="submit" class="report-submit-btn">Submit Report</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
