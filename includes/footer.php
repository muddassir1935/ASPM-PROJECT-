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

    // Sprint 3: AJAX toggles for follow and bookmark buttons.
    // Delegated from the document so buttons rendered anywhere on the
    // page (feed, profile, modals, widgets) all work with one handler.
    document.addEventListener('click', function (event) {
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

    // Sprint 3: follower / following modal toggle (profile page).
    function toggleModal(modalId) {
        var modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.toggle('open');
        }
    }
</script>

</body>
</html>