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
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) { return; }
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
            })
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
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) { return; }
                if (!data.bookmarked && bookmarkBtn.getAttribute('data-remove-card') === '1') {
                    var card = bookmarkBtn.closest('[data-post-card]');
                    if (card) { card.remove(); }
                    return;
                }
                bookmarkBtn.textContent = data.bookmarked ? 'Saved' : 'Save';
                bookmarkBtn.classList.toggle('is-bookmarked', data.bookmarked);
                bookmarkBtn.setAttribute('data-bookmarked', data.bookmarked ? '1' : '0');
            })
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
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) { return; }
                document.querySelectorAll('[data-like-toggle][data-post-id="' + postId + '"]').forEach(function (btn) {
                    btn.classList.toggle('is-liked', data.liked);
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
        if (!willOpen && modalOpener && modalOpener.focus) {
            modalOpener.focus();
            modalOpener = null;
        }
    }
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') { return; }
        var openModals = document.querySelectorAll('.modal-overlay.open');
        if (openModals.length === 0) { return; }
        openModals.forEach(function (m) { m.classList.remove('open'); });
        if (modalOpener && modalOpener.focus) {
            modalOpener.focus();
            modalOpener = null;
        }
    });
</script>

</body>
</html>