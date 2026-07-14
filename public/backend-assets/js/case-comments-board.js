/**
 * PWA-style comments board for case-level and asset-level threads.
 * Case and asset scopes use separate endpoints so comments never mix.
 */
(function (window, $) {
    'use strict';

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatDate(value) {
        if (!value) return '';
        var d = new Date(value);
        if (isNaN(d.getTime())) return String(value);
        return d.toLocaleString(undefined, {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }

    function initialsFromName(name) {
        var parts = String(name || '').trim().split(/\s+/).filter(Boolean);
        if (parts.length >= 2) {
            return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
        }
        return String(name || 'U').slice(0, 2).toUpperCase();
    }

    function create(options) {
        var root = typeof options.root === 'string' ? document.querySelector(options.root) : options.root;
        if (!root) return null;

        var state = {
            page: 0,
            hasMore: true,
            loading: false,
            loadedOnce: false,
            search: '',
            sortOrder: 'desc',
            observer: null,
            searchTimer: null,
            likingId: null,
            openReplyId: null
        };

        var els = {
            collapsed: root.querySelector('[data-comments-composer-collapsed]'),
            expanded: root.querySelector('[data-comments-composer-expanded]'),
            input: root.querySelector('[data-comments-input]'),
            status: root.querySelector('[data-comments-status]'),
            search: root.querySelector('[data-comments-search]'),
            sort: root.querySelector('[data-comments-sort]'),
            count: root.querySelector('[data-comments-count]'),
            list: root.querySelector('[data-comments-list]')
        };

        function setStatus(text, isError) {
            if (!els.status) return;
            if (!text) {
                els.status.hidden = true;
                els.status.textContent = '';
                els.status.classList.remove('is-error');
                return;
            }
            els.status.hidden = false;
            els.status.textContent = text;
            els.status.classList.toggle('is-error', !!isError);
        }

        function updateCount(total) {
            if (!els.count) return;
            var n = Math.max(0, Number(total) || 0);
            els.count.textContent = n + ' Comment' + (n === 1 ? '' : 's');
            if (typeof options.onCountChange === 'function') {
                options.onCountChange(n);
            }
        }

        function buildReplyHtml(reply) {
            var name = reply.created_by_name || 'Unknown';
            return (
                '<div class="cs-comments-reply" data-comment-id="' + Number(reply.id || 0) + '">' +
                '<div class="cs-comments-avatar" aria-hidden="true">' + escapeHtml(initialsFromName(name)) + '</div>' +
                '<div class="cs-comments-reply-body">' +
                '<div class="cs-comments-author">' + escapeHtml(name) + '</div>' +
                '<time class="cs-comments-time">' + escapeHtml(formatDate(reply.created_date)) + '</time>' +
                '<p class="cs-comments-text">' + escapeHtml(reply.comment || '') + '</p>' +
                '<div class="cs-comments-footer">' +
                '<button type="button" class="cs-comments-action' + ((reply.liked_by_me) ? ' is-liked' : '') + '" data-comments-like="' + Number(reply.id || 0) + '" aria-label="' + ((reply.liked_by_me) ? 'Unlike' : 'Like') + '">' +
                '<i class="fas fa-thumbs-up" aria-hidden="true"></i> <span data-like-count>' + Number(reply.likes_count || 0) + '</span>' +
                '</button>' +
                '</div></div></div>'
            );
        }

        function buildCardHtml(comment) {
            var id = Number(comment.id || 0);
            var name = comment.created_by_name || 'Unknown';
            var text = String(comment.comment || '');
            var isLong = text.length > 180;
            var preview = isLong ? text.slice(0, 180) + '...' : text;
            var replies = Number(comment.responses_count || 0);
            var liked = !!comment.liked_by_me;

            return (
                '<article class="cs-comments-card" data-comment-id="' + id + '">' +
                '<div class="cs-comments-avatar" aria-hidden="true">' + escapeHtml(initialsFromName(name)) + '</div>' +
                '<div class="cs-comments-main">' +
                '<div class="cs-comments-author">' + escapeHtml(name) + '</div>' +
                '<time class="cs-comments-time">' + escapeHtml(formatDate(comment.created_date)) + '</time>' +
                '<p class="cs-comments-text" data-full-text="' + escapeHtml(text) + '" data-preview-text="' + escapeHtml(preview) + '">' + escapeHtml(preview) + '</p>' +
                (isLong
                    ? '<button type="button" class="cs-comments-readmore" data-comments-expand="' + id + '">Read more <i class="fas fa-chevron-right" aria-hidden="true"></i></button>'
                    : '') +
                '<div class="cs-comments-footer">' +
                '<button type="button" class="cs-comments-action' + (liked ? ' is-liked' : '') + '" data-comments-like="' + id + '" aria-label="' + (liked ? 'Unlike' : 'Like') + '">' +
                '<i class="fas fa-thumbs-up" aria-hidden="true"></i> <span data-like-count>' + Number(comment.likes_count || 0) + '</span>' +
                '</button>' +
                '<button type="button" class="cs-comments-action" data-comments-reply="' + id + '" aria-label="Reply">' +
                'Reply' + (replies > 0 ? ' <span class="cs-comments-reply-count">(' + replies + ')</span>' : '') +
                '</button>' +
                '</div>' +
                '<div class="cs-comments-thread" data-comments-thread="' + id + '" hidden></div>' +
                '</div></article>'
            );
        }

        function ensureShell() {
            if (!els.list) return null;
            var feed = els.list.querySelector('.cs-comments-feed');
            var sentinel = els.list.querySelector('.cs-timeline-sentinel');
            var status = els.list.querySelector('.cs-timeline-status');
            if (!feed) {
                els.list.innerHTML =
                    '<ul class="cs-comments-feed" aria-label="Comments"></ul>' +
                    '<div class="cs-timeline-sentinel" aria-hidden="true"></div>' +
                    '<div class="cs-timeline-status" role="status"></div>';
                feed = els.list.querySelector('.cs-comments-feed');
                sentinel = els.list.querySelector('.cs-timeline-sentinel');
                status = els.list.querySelector('.cs-timeline-status');
            }
            return { feed: feed, sentinel: sentinel, status: status };
        }

        function disconnectObserver() {
            if (state.observer) {
                state.observer.disconnect();
                state.observer = null;
            }
        }

        function load(reset) {
            if (!els.list || state.loading) return;

            if (reset) {
                state.page = 0;
                state.hasMore = true;
                disconnectObserver();
                els.list.innerHTML = '';
            } else if (!state.hasMore || state.page < 1) {
                return;
            }

            var nextPage = state.page + 1;
            var shell = ensureShell();
            if (shell && shell.status) {
                shell.status.hidden = false;
                shell.status.textContent = nextPage === 1 ? 'Loading comments…' : 'Loading more…';
            }
            state.loading = true;

            var data = {
                page: nextPage,
                limit: 20,
                sort_order: state.sortOrder
            };
            if (state.search) data.search = state.search;

            $.ajax({
                url: options.listUrl,
                type: 'GET',
                data: data,
                success: function (res) {
                    var comments = res.comments || [];
                    var pagination = res.pagination || {};
                    var currentPage = Number(pagination.current_page || nextPage);
                    var lastPage = Number(pagination.last_page || currentPage);

                    if (currentPage === 1 && !comments.length) {
                        els.list.innerHTML = '<p class="cs-comments-empty">No comments yet.</p>';
                        state.hasMore = false;
                        state.page = 1;
                        state.loadedOnce = true;
                        updateCount(0);
                        return;
                    }

                    shell = ensureShell();
                    var html = '';
                    comments.forEach(function (c) {
                        html += '<li>' + buildCardHtml(c) + '</li>';
                    });
                    shell.feed.insertAdjacentHTML('beforeend', html);

                    state.page = currentPage;
                    state.hasMore = currentPage < lastPage;
                    state.loadedOnce = true;
                    if (typeof pagination.total === 'number') {
                        updateCount(pagination.total);
                    }
                    if (shell.status) {
                        shell.status.hidden = true;
                        shell.status.textContent = '';
                    }

                    if (!state.observer && shell.sentinel) {
                        state.observer = new IntersectionObserver(function (entries) {
                            if (!entries.some(function (entry) { return entry.isIntersecting; })) return;
                            if (state.hasMore && !state.loading) load(false);
                        }, {
                            root: options.scrollRoot || null,
                            rootMargin: '160px 0px'
                        });
                        state.observer.observe(shell.sentinel);
                    }
                },
                error: function () {
                    shell = ensureShell();
                    if (!shell || !shell.feed.children.length) {
                        els.list.innerHTML = '<p class="cs-comments-empty">Unable to load comments.</p>';
                    } else if (shell.status) {
                        shell.status.hidden = false;
                        shell.status.textContent = 'Unable to load more comments.';
                    }
                },
                complete: function () {
                    state.loading = false;
                }
            });
        }

        function expandComposer(open) {
            if (els.collapsed) els.collapsed.hidden = !!open;
            if (els.expanded) els.expanded.hidden = !open;
            if (open && els.input) {
                els.input.focus();
            }
            if (!open && els.input) {
                els.input.value = '';
                setStatus('');
            }
        }

        function submitComment() {
            var text = (els.input && els.input.value ? els.input.value : '').trim();
            if (!text) {
                setStatus('Please enter a comment.', true);
                return;
            }
            var submitBtn = root.querySelector('[data-comments-submit]');
            if (submitBtn) submitBtn.disabled = true;
            setStatus('Posting…');
            $.ajax({
                url: options.storeUrl,
                type: 'POST',
                data: { _token: options.csrfToken, comment: text },
                success: function (res) {
                    expandComposer(false);
                    setStatus(res.message || 'Comment added.');
                    state.loadedOnce = false;
                    load(true);
                },
                error: function (xhr) {
                    setStatus((xhr.responseJSON && xhr.responseJSON.message) || 'Unable to post comment.', true);
                },
                complete: function () {
                    if (submitBtn) submitBtn.disabled = false;
                }
            });
        }

        function toggleLike(btn) {
            var commentId = Number(btn.getAttribute('data-comments-like') || 0);
            if (!commentId || state.likingId) return;
            var liked = btn.classList.contains('is-liked');
            state.likingId = commentId;
            $.ajax({
                url: (liked ? options.unlikeUrl : options.likeUrl)(commentId),
                type: liked ? 'DELETE' : 'POST',
                data: liked ? undefined : { _token: options.csrfToken },
                headers: { 'X-CSRF-TOKEN': options.csrfToken, 'Accept': 'application/json' },
                success: function (res) {
                    btn.classList.toggle('is-liked', !!res.liked_by_me);
                    btn.setAttribute('aria-label', res.liked_by_me ? 'Unlike' : 'Like');
                    var countEl = btn.querySelector('[data-like-count]');
                    if (countEl) countEl.textContent = String(Number(res.likes_count || 0));
                },
                error: function () {
                    /* ignore */
                },
                complete: function () {
                    state.likingId = null;
                }
            });
        }

        function openReplyThread(commentId) {
            var thread = root.querySelector('[data-comments-thread="' + commentId + '"]');
            if (!thread) return;

            if (state.openReplyId === commentId && !thread.hidden) {
                thread.hidden = true;
                state.openReplyId = null;
                return;
            }

            // Close previously open thread.
            root.querySelectorAll('[data-comments-thread]').forEach(function (el) {
                el.hidden = true;
            });
            state.openReplyId = commentId;
            thread.hidden = false;
            thread.innerHTML = '<p class="cs-asset-detail-loading">Loading replies…</p>';

            $.ajax({
                url: options.responsesUrl(commentId),
                type: 'GET',
                data: { page: 1, limit: 50, sort_order: 'desc' },
                success: function (res) {
                    var comments = res.comments || [];
                    var html = '<div class="cs-comments-replies-list">';
                    if (!comments.length) {
                        html += '<p class="cs-comments-empty-inline">No replies yet.</p>';
                    } else {
                        comments.forEach(function (reply) {
                            html += buildReplyHtml(reply);
                        });
                    }
                    html +=
                        '</div><form class="cs-comments-reply-form" data-comments-reply-form="' + commentId + '">' +
                        '<textarea rows="2" maxlength="5000" placeholder="Write a reply…" required></textarea>' +
                        '<div class="cs-comments-reply-actions">' +
                        '<button type="button" class="cs-btn-secondary" data-comments-reply-cancel="' + commentId + '">Cancel</button>' +
                        '<button type="submit" class="cs-btn-primary">Reply</button>' +
                        '</div></form>';
                    thread.innerHTML = html;
                },
                error: function () {
                    thread.innerHTML = '<p class="cs-asset-detail-error">Unable to load replies.</p>';
                }
            });
        }

        function submitReply(form) {
            var commentId = Number(form.getAttribute('data-comments-reply-form') || 0);
            var ta = form.querySelector('textarea');
            var text = (ta && ta.value ? ta.value : '').trim();
            if (!commentId || !text) return;
            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;
            $.ajax({
                url: options.responseStoreUrl(commentId),
                type: 'POST',
                data: { _token: options.csrfToken, comment: text },
                success: function (res) {
                    if (ta) ta.value = '';
                    var list = form.parentElement && form.parentElement.querySelector('.cs-comments-replies-list');
                    if (list && res.comment) {
                        var empty = list.querySelector('.cs-comments-empty-inline');
                        if (empty) empty.remove();
                        list.insertAdjacentHTML('afterbegin', buildReplyHtml(res.comment));
                    }
                    var replyBtn = root.querySelector('[data-comments-reply="' + commentId + '"]');
                    if (replyBtn) {
                        var countEl = replyBtn.querySelector('.cs-comments-reply-count');
                        var current = countEl ? parseInt(String(countEl.textContent || '').replace(/\D/g, ''), 10) || 0 : 0;
                        var next = current + 1;
                        replyBtn.innerHTML = 'Reply <span class="cs-comments-reply-count">(' + next + ')</span>';
                    }
                },
                error: function (xhr) {
                    alert((xhr.responseJSON && xhr.responseJSON.message) || 'Unable to post reply.');
                },
                complete: function () {
                    if (submitBtn) submitBtn.disabled = false;
                }
            });
        }

        root.addEventListener('click', function (e) {
            if (e.target.closest('[data-comments-open]')) {
                expandComposer(true);
                return;
            }
            if (e.target.closest('[data-comments-cancel]')) {
                expandComposer(false);
                return;
            }
            if (e.target.closest('[data-comments-submit]')) {
                e.preventDefault();
                submitComment();
                return;
            }
            var expandBtn = e.target.closest('[data-comments-expand]');
            if (expandBtn) {
                var card = expandBtn.closest('.cs-comments-card');
                var textEl = card && card.querySelector('.cs-comments-text');
                if (!textEl) return;
                var expanded = expandBtn.getAttribute('data-expanded') === '1';
                textEl.textContent = expanded
                    ? textEl.getAttribute('data-preview-text')
                    : textEl.getAttribute('data-full-text');
                expandBtn.setAttribute('data-expanded', expanded ? '0' : '1');
                expandBtn.innerHTML = expanded
                    ? 'Read more <i class="fas fa-chevron-right" aria-hidden="true"></i>'
                    : 'Show less <i class="fas fa-chevron-down" aria-hidden="true"></i>';
                return;
            }
            var likeBtn = e.target.closest('[data-comments-like]');
            if (likeBtn) {
                toggleLike(likeBtn);
                return;
            }
            var replyBtn = e.target.closest('[data-comments-reply]');
            if (replyBtn) {
                openReplyThread(Number(replyBtn.getAttribute('data-comments-reply') || 0));
                return;
            }
            var cancelReply = e.target.closest('[data-comments-reply-cancel]');
            if (cancelReply) {
                var id = Number(cancelReply.getAttribute('data-comments-reply-cancel') || 0);
                var thread = root.querySelector('[data-comments-thread="' + id + '"]');
                if (thread) thread.hidden = true;
                state.openReplyId = null;
            }
        });

        root.addEventListener('submit', function (e) {
            var form = e.target.closest('[data-comments-reply-form]');
            if (!form || !root.contains(form)) return;
            e.preventDefault();
            submitReply(form);
        });

        if (els.search) {
            els.search.addEventListener('input', function () {
                clearTimeout(state.searchTimer);
                state.searchTimer = setTimeout(function () {
                    state.search = String(els.search.value || '').trim().slice(0, 100);
                    state.loadedOnce = false;
                    load(true);
                }, 300);
            });
        }

        if (els.sort) {
            els.sort.addEventListener('change', function () {
                state.sortOrder = els.sort.value === 'asc' ? 'asc' : 'desc';
                state.loadedOnce = false;
                load(true);
            });
        }

        return {
            load: function () {
                if (state.loadedOnce) return;
                load(true);
            },
            reload: function () {
                state.loadedOnce = false;
                load(true);
            },
            reset: function () {
                state.loadedOnce = false;
                state.page = 0;
                state.hasMore = true;
                state.search = '';
                state.sortOrder = 'desc';
                state.openReplyId = null;
                disconnectObserver();
                if (els.search) els.search.value = '';
                if (els.sort) els.sort.value = 'desc';
                if (els.list) els.list.innerHTML = '<p class="cs-comments-empty">Open this tab to load comments.</p>';
                expandComposer(false);
                updateCount(0);
            }
        };
    }

    window.CaseCommentsBoard = { create: create };
})(window, window.jQuery);
