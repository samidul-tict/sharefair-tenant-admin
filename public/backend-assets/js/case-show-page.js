/**
 * Case show page: activities timeline, comments boards, assets table/modal.
 * Expects window.initCaseShowPage(config) after jQuery is loaded.
 */
window.initCaseShowPage = function (config) {
    config = config || {};
    var caseId = config.caseId;
    var csrfToken = config.csrfToken;
    var caseCommentsUrl = config.caseCommentsUrl;
    var caseCommentStoreUrl = config.caseCommentStoreUrl;
    var caseCommentResponsesUrlTemplate = config.caseCommentResponsesUrlTemplate;
    var caseCommentResponseStoreUrlTemplate = config.caseCommentResponseStoreUrlTemplate;
    var caseCommentLikeUrlTemplate = config.caseCommentLikeUrlTemplate;
    var caseCommentUnlikeUrlTemplate = config.caseCommentUnlikeUrlTemplate;
    var itemDataElementLabels = config.itemDataElementLabels || {};
    var assetsListUrl = config.assetsListUrl;
    var assetDetailUrlTemplate = config.assetDetailUrlTemplate;
    var assetCommentsUrlTemplate = config.assetCommentsUrlTemplate;
    var assetCommentsStoreUrlTemplate = config.assetCommentsStoreUrlTemplate;
    var assetTimelineUrlTemplate = config.assetTimelineUrlTemplate;
    var assetCommentResponsesUrlTemplate = config.assetCommentResponsesUrlTemplate;
    var assetCommentResponseStoreUrlTemplate = config.assetCommentResponseStoreUrlTemplate;

$(document).ready(function() {
    var notesToggle = document.getElementById('matterNotesToggle');
    if (notesToggle) {
        notesToggle.addEventListener('click', function() {
            var block = document.getElementById('matterNotesBlock');
            var preview = document.getElementById('matterNotesText');
            var full = document.getElementById('matterNotesFull');
            if (!block || !preview || !full) return;
            var expanded = block.getAttribute('data-expanded') === 'true';
            expanded = !expanded;
            block.setAttribute('data-expanded', expanded ? 'true' : 'false');
            preview.hidden = expanded;
            full.hidden = !expanded;
            notesToggle.textContent = expanded ? 'Show less' : 'Read more';
            notesToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        });
    }

    var caseActivityState = {
        page: 0,
        lastPage: 1,
        loading: false,
        hasMore: true,
        observer: null
    };

    function escapeTimelineHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatTimelineDate(value) {
        if (!value) return '';
        var d = new Date(value);
        if (isNaN(d.getTime())) return String(value);
        return d.toLocaleString(undefined, {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    function buildTimelineItemHtml(row) {
        var userName = row.created_by_name || row.user_name || (row.user && row.user.name) || row.activity_by || 'N/A';
        var subject = row.subject || 'Activity';
        var notes = row.notes || '';
        var nextDate = row.next_follow_up_date || '';
        var created = formatTimelineDate(row.created_date) || (row.created_date || '');
        return (
            '<article class="cs-timeline-item">' +
            '<div class="cs-timeline-rail" aria-hidden="true"><span class="cs-timeline-dot"></span><span class="cs-timeline-line"></span></div>' +
            '<div class="cs-timeline-card">' +
            '<h3 class="cs-timeline-subject">' + escapeTimelineHtml(subject) + '</h3>' +
            '<div class="cs-timeline-meta">' + escapeTimelineHtml(created) + ' · ' + escapeTimelineHtml(userName) + '</div>' +
            (notes ? '<p class="cs-timeline-notes">' + escapeTimelineHtml(notes) + '</p>' : '') +
            (nextDate ? '<div class="cs-timeline-followup"><i class="fas fa-calendar-check" aria-hidden="true"></i> Next follow-up: ' + escapeTimelineHtml(nextDate) + '</div>' : '') +
            '</div></article>'
        );
    }

    function syncTimelineRails(timelineEl) {
        if (!timelineEl) return;
        var items = timelineEl.querySelectorAll('.cs-timeline-item');
        items.forEach(function(item, idx) {
            var rail = item.querySelector('.cs-timeline-rail');
            if (!rail) return;
            var line = rail.querySelector('.cs-timeline-line');
            var needsLine = idx < items.length - 1;
            if (needsLine && !line) {
                rail.insertAdjacentHTML('beforeend', '<span class="cs-timeline-line"></span>');
            } else if (!needsLine && line) {
                line.remove();
            }
        });
    }

    function ensureTimelineShell(container, loadingText) {
        var timeline = container.querySelector('.cs-timeline');
        var sentinel = container.querySelector('.cs-timeline-sentinel');
        var status = container.querySelector('.cs-timeline-status');
        if (!timeline) {
            container.innerHTML =
                '<div class="cs-timeline"></div>' +
                '<div class="cs-timeline-sentinel" aria-hidden="true"></div>' +
                '<div class="cs-timeline-status" role="status">' + (loadingText || 'Loading activities…') + '</div>';
            timeline = container.querySelector('.cs-timeline');
            sentinel = container.querySelector('.cs-timeline-sentinel');
            status = container.querySelector('.cs-timeline-status');
        }
        return { timeline: timeline, sentinel: sentinel, status: status };
    }

    function setTimelineStatus(statusEl, text) {
        if (!statusEl) return;
        statusEl.textContent = text || '';
        statusEl.hidden = !text;
    }

    function loadActivities(reset) {
        var list = document.getElementById('activityList');
        if (!list) return;
        if (caseActivityState.loading) return;
        if (!reset && !caseActivityState.hasMore) return;

        if (reset) {
            caseActivityState.page = 0;
            caseActivityState.lastPage = 1;
            caseActivityState.hasMore = true;
            list.innerHTML = '';
            if (caseActivityState.observer) {
                caseActivityState.observer.disconnect();
                caseActivityState.observer = null;
            }
        }

        var nextPage = caseActivityState.page + 1;
        var shell = ensureTimelineShell(list, nextPage === 1 ? 'Loading activities…' : 'Loading more…');
        setTimelineStatus(shell.status, nextPage === 1 ? 'Loading activities…' : 'Loading more…');
        caseActivityState.loading = true;

        $.ajax({
            url: config.activityListUrl,
            type: 'GET',
            data: { page: nextPage },
            success: function(res) {
                var activities = res.activities || [];
                var pagination = res.pagination || {};
                var currentPage = Number(pagination.current_page || nextPage);
                var lastPage = Number(pagination.last_page || currentPage);

                if (currentPage === 1 && !activities.length) {
                    list.innerHTML = '<div class="cs-empty-state"><div class="cs-empty-icon" aria-hidden="true"><i class="fas fa-stream"></i></div><div class="cs-empty-text">No activities found.</div></div>';
                    caseActivityState.hasMore = false;
                    caseActivityState.loading = false;
                    return;
                }

                shell = ensureTimelineShell(list);
                var html = '';
                activities.forEach(function(row) {
                    html += buildTimelineItemHtml(row);
                });
                shell.timeline.insertAdjacentHTML('beforeend', html);
                syncTimelineRails(shell.timeline);

                caseActivityState.page = currentPage;
                caseActivityState.lastPage = lastPage;
                caseActivityState.hasMore = currentPage < lastPage;
                setTimelineStatus(
                    shell.status,
                    caseActivityState.hasMore ? '' : (activities.length || shell.timeline.children.length ? '' : 'No activities found.')
                );

                if (!caseActivityState.observer && shell.sentinel) {
                    caseActivityState.observer = new IntersectionObserver(function(entries) {
                        if (!entries.some(function(entry) { return entry.isIntersecting; })) return;
                        if (caseActivityState.hasMore && !caseActivityState.loading) {
                            loadActivities(false);
                        }
                    }, { root: null, rootMargin: '160px 0px' });
                    caseActivityState.observer.observe(shell.sentinel);
                }
                window.loadMoreCaseActivitiesIfNeeded = function() {
                    var sentinel = document.querySelector('#activityList .cs-timeline-sentinel');
                    if (!sentinel || !caseActivityState.hasMore || caseActivityState.loading) return;
                    var rect = sentinel.getBoundingClientRect();
                    if (rect.top <= (window.innerHeight + 160)) {
                        loadActivities(false);
                    }
                };
            },
            error: function() {
                shell = ensureTimelineShell(list);
                if (!shell.timeline.children.length) {
                    list.innerHTML = '<div class="cs-empty-state"><div class="cs-empty-icon" aria-hidden="true"><i class="fas fa-stream"></i></div><div class="cs-empty-text">Unable to load activities.</div></div>';
                } else {
                    setTimelineStatus(shell.status, 'Unable to load more activities.');
                }
            },
            complete: function() {
                caseActivityState.loading = false;
                if (caseActivityState.hasMore) {
                    var status = document.querySelector('#activityList .cs-timeline-status');
                    if (status && !status.textContent) setTimelineStatus(status, '');
                }
            }
        });
    }

    loadActivities(true);

    function commentUrlReplace(template, commentId) {
        return String(template).replace(/\/comments\/0(\/|$)/, '/comments/' + commentId + '$1');
    }

    function assetCommentPath(template, itemId, commentId) {
        var url = String(template).replace(/\/assets\/0\//, '/assets/' + itemId + '/');
        if (commentId != null) {
            url = url.replace(/\/comments\/0\//, '/comments/' + commentId + '/');
        }
        return url;
    }

    var caseCommentsBoard = window.CaseCommentsBoard && window.CaseCommentsBoard.create({
        root: '#caseCommentsBoard',
        csrfToken: csrfToken,
        listUrl: caseCommentsUrl,
        storeUrl: caseCommentStoreUrl,
        responsesUrl: function(commentId) { return commentUrlReplace(caseCommentResponsesUrlTemplate, commentId); },
        responseStoreUrl: function(commentId) { return commentUrlReplace(caseCommentResponseStoreUrlTemplate, commentId); },
        likeUrl: function(commentId) { return commentUrlReplace(caseCommentLikeUrlTemplate, commentId); },
        unlikeUrl: function(commentId) { return commentUrlReplace(caseCommentUnlikeUrlTemplate, commentId); },
        onCountChange: function(total) {
            var el = document.getElementById('caseCommentsTabCount');
            if (el) el.textContent = String(total);
        }
    });

    window.loadCaseComments = function() {
        if (caseCommentsBoard) caseCommentsBoard.load();
    };

    var assetCommentConfig = {
        root: '#assetCommentsBoard',
        csrfToken: csrfToken,
        itemId: 0,
        listUrl: '',
        storeUrl: '',
        responsesUrl: function(commentId) {
            return assetCommentPath(assetCommentResponsesUrlTemplate, assetCommentConfig.itemId, commentId);
        },
        responseStoreUrl: function(commentId) {
            return assetCommentPath(assetCommentResponseStoreUrlTemplate, assetCommentConfig.itemId, commentId);
        },
        likeUrl: function(commentId) { return commentUrlReplace(caseCommentLikeUrlTemplate, commentId); },
        unlikeUrl: function(commentId) { return commentUrlReplace(caseCommentUnlikeUrlTemplate, commentId); },
        scrollRoot: document.querySelector('#assetDetailModal .cs-asset-detail-body')
    };

    var assetCommentsBoard = window.CaseCommentsBoard && window.CaseCommentsBoard.create(assetCommentConfig);

    window.prepareAssetCommentsBoard = function(itemId) {
        assetCommentConfig.itemId = itemId;
        assetCommentConfig.listUrl = String(assetCommentsUrlTemplate).replace(/\/0(\/comments)/, '/' + itemId + '$1');
        assetCommentConfig.storeUrl = String(assetCommentsStoreUrlTemplate).replace(/\/0(\/comments)/, '/' + itemId + '$1');
        if (assetCommentsBoard) assetCommentsBoard.reset();
    };

    window.loadAssetCommentsBoard = function() {
        if (!assetCommentConfig.itemId || !assetCommentsBoard) return;
        assetCommentsBoard.load();
    };

    (function initAssetsTable() {
        var table = document.getElementById('assetsTable');
        if (!table) return;

        var tbody = document.getElementById('assetsTableBody');
        var searchInput = document.getElementById('assetsSearch');
        var filterStatus = document.getElementById('assetsFilterStatus');
        var filterCategory = document.getElementById('assetsFilterCategory');
        var filterLocation = document.getElementById('assetsFilterLocation');
        var perPageSelect = document.getElementById('assetsPerPage');
        var paginationEl = document.getElementById('assetsPagination');
        var emptyFiltered = document.getElementById('assetsEmptyFiltered');
        var countSubtitle = document.getElementById('assetsCountSubtitle');
        var columnsBtn = document.getElementById('assetsColumnsBtn');
        var columnsPanel = document.getElementById('assetsColumnsPanel');
        var columnsReset = document.getElementById('assetsColumnsReset');
        var columnCheckboxes = columnsPanel
            ? Array.prototype.slice.call(columnsPanel.querySelectorAll('input[data-col]'))
            : [];

        var ASSET_COLS = [
            'index', 'name', 'location', 'category', 'other_category', 'condition', 'brand',
            'other_brand', 'purchase_year', 'purchase_price', 'estimated_value', 'concluded_price',
            'accessories_status', 'original_packaging', 'valid_warranty', 'marital_asset',
            'assigned_to', 'assigned_reason', 'status'
        ];

        var STORAGE_KEY = 'case-' + caseId + '-assets-columns';
        var ORDER_STORAGE_KEY = STORAGE_KEY + '-order';
        var DEFAULT_COLS = {
            index: true,
            name: true,
            location: true,
            category: true,
            other_category: false,
            condition: true,
            brand: true,
            other_brand: false,
            purchase_year: false,
            purchase_price: true,
            estimated_value: true,
            concluded_price: true,
            accessories_status: false,
            original_packaging: false,
            valid_warranty: false,
            marital_asset: true,
            assigned_to: true,
            assigned_reason: false,
            status: true
        };

        var currentPage = 1;
        var sortBy = 'name';
        var sortOrder = 'asc';
        var searchTimer = null;
        var assetsLoading = false;
        var assetsLoadedOnce = false;
        var filtersPopulated = false;
        var totalInCase = config.assetCount;

        function escapeHtml(text) {
            if (text === null || text === undefined) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function itemLabel(value) {
            if (value === null || value === '') return value;
            return itemDataElementLabels[value] || value;
        }

        function loadColumnPrefs() {
            try {
                var stored = localStorage.getItem(STORAGE_KEY);
                if (stored) return Object.assign({}, DEFAULT_COLS, JSON.parse(stored));
            } catch (e) { /* ignore */ }
            return Object.assign({}, DEFAULT_COLS);
        }

        function saveColumnPrefs(prefs) {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(prefs));
            } catch (e) { /* ignore */ }
        }

        function loadColumnOrder() {
            try {
                var stored = JSON.parse(localStorage.getItem(ORDER_STORAGE_KEY) || '[]');
                if (Array.isArray(stored)) {
                    var valid = stored.filter(function(col, index) {
                        return ASSET_COLS.indexOf(col) !== -1 && stored.indexOf(col) === index;
                    });
                    ASSET_COLS.forEach(function(col) {
                        if (valid.indexOf(col) === -1) valid.push(col);
                    });
                    return valid;
                }
            } catch (e) { /* ignore */ }
            return ASSET_COLS.slice();
        }

        function saveColumnOrder(order) {
            try {
                localStorage.setItem(ORDER_STORAGE_KEY, JSON.stringify(order));
            } catch (e) { /* ignore */ }
        }

        function reorderChildren(container, selector, order) {
            if (!container) return;
            var nodes = {};
            container.querySelectorAll(selector).forEach(function(node) {
                var col = node.getAttribute('data-col');
                if (col) nodes[col] = node;
            });
            order.forEach(function(col) {
                if (nodes[col]) container.appendChild(nodes[col]);
            });
        }

        function applyColumnOrder(order) {
            var headerRow = table.querySelector('thead tr');
            reorderChildren(headerRow, 'th[data-col]', order);

            table.querySelectorAll('tbody tr[data-asset-id]').forEach(function(row) {
                reorderChildren(row, 'td[data-col]', order);
            });

            if (columnsPanel) {
                var grid = columnsPanel.querySelector('.cs-assets-columns-grid');
                var labels = {};
                grid.querySelectorAll('.cs-assets-col-toggle').forEach(function(label) {
                    var input = label.querySelector('input[data-col]');
                    if (input) labels[input.getAttribute('data-col')] = label;
                });
                order.forEach(function(col) {
                    if (labels[col]) grid.appendChild(labels[col]);
                });
            }
        }

        function applyColumns(prefs) {
            table.querySelectorAll('[data-col]').forEach(function(cell) {
                var col = cell.getAttribute('data-col');
                var visible = prefs[col] !== false;
                cell.classList.toggle('cs-assets-col-hidden', !visible);
            });
            columnCheckboxes.forEach(function(cb) {
                var col = cb.getAttribute('data-col');
                if (cb.disabled) return;
                cb.checked = prefs[col] !== false;
            });
        }

        function initializeSortableHeaders() {
            table.querySelectorAll('thead th[data-col]').forEach(function(th) {
                var col = th.getAttribute('data-col');
                if (col === 'index' || th.querySelector('[data-asset-sort]')) return;
                var label = th.textContent.trim();
                th.classList.add('cs-assets-sortable');
                th.innerHTML =
                    '<button type="button" class="cs-assets-sort-btn" data-asset-sort="' +
                    col +
                    '" aria-label="Sort by ' +
                    escapeHtml(label) +
                    '">' +
                    '<span>' +
                    escapeHtml(label) +
                    '</span><i class="fas fa-sort cs-assets-sort-icon" aria-hidden="true"></i></button>';
            });
            updateSortHeaders();
        }

        function updateSortHeaders() {
            table.querySelectorAll('thead th[data-col]').forEach(function(th) {
                var col = th.getAttribute('data-col');
                var active = col === sortBy;
                var button = th.querySelector('[data-asset-sort]');
                var icon = button ? button.querySelector('.cs-assets-sort-icon') : null;

                th.removeAttribute('aria-sort');
                if (active) {
                    th.setAttribute('aria-sort', sortOrder === 'asc' ? 'ascending' : 'descending');
                }
                if (button) button.classList.toggle('is-active', active);
                if (icon) {
                    icon.className = 'fas cs-assets-sort-icon ' +
                        (active ? (sortOrder === 'asc' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort');
                }
            });
        }

        function populateFilterSelect(selectEl, values, allLabel) {
            if (!selectEl) return;
            var current = selectEl.value;
            selectEl.innerHTML = '<option value="">' + allLabel + '</option>';
            (values || []).forEach(function(value) {
                var option = document.createElement('option');
                option.value = value;
                option.textContent = itemLabel(value) || value;
                selectEl.appendChild(option);
            });
            if (current) selectEl.value = current;
        }

        function populateLocationSelect(locations) {
            if (!filterLocation) return;
            var current = filterLocation.value;
            filterLocation.innerHTML = '<option value="">All locations</option>';
            (locations || []).forEach(function(loc) {
                var option = document.createElement('option');
                option.value = String(loc.id);
                option.textContent = loc.name || ('#' + loc.id);
                filterLocation.appendChild(option);
            });
            if (current) filterLocation.value = current;
        }

        function populateFilters(filters) {
            if (filtersPopulated || !filters) return;
            filtersPopulated = true;
            populateFilterSelect(filterStatus, filters.statuses, 'All statuses');
            populateFilterSelect(filterCategory, filters.categories, 'All categories');
            populateLocationSelect(filters.locations);
        }

        function renderAssetRow(row) {
            var html = '<tr class="cs-assets-row-clickable" tabindex="0" role="button" data-asset-id="' +
                Number(row.id || 0) +
                '" aria-label="View details for ' +
                escapeHtml(row.name || 'asset') +
                '">';
            columnOrder.forEach(function(col) {
                var cls = col === 'name' ? ' class="cs-table-name"' : '';
                html += '<td data-col="' + col + '"' + cls + '>' + escapeHtml(row[col]) + '</td>';
            });
            html += '</tr>';
            return html;
        }

        function assetDetailUrl(itemId) {
            return String(assetDetailUrlTemplate).replace(/\/0(\?|$)/, '/' + itemId + '$1');
        }

        function assetCommentsUrl(itemId) {
            return String(assetCommentsUrlTemplate).replace(/\/0(\/comments)/, '/' + itemId + '$1');
        }

        function assetTimelineUrl(itemId) {
            return String(assetTimelineUrlTemplate).replace(/\/0(\/timeline)/, '/' + itemId + '$1');
        }

        function assetCommentResponsesUrl(itemId, commentId) {
            return String(assetCommentResponsesUrlTemplate)
                .replace(/\/assets\/0\//, '/assets/' + itemId + '/')
                .replace(/\/comments\/0\//, '/comments/' + commentId + '/');
        }

        var currentAssetId = 0;
        var assetDetailCache = {
            details: null,
            commentsLoadedFor: 0,
            commentsPage: 1,
            timelineLoadedFor: 0,
            timelinePage: 0,
            timelineHasMore: true,
            timelineLoading: false,
            timelineObserver: null
        };

        function setAssetDetailTab(tabId) {
            document.querySelectorAll('[data-asset-tab]').forEach(function(tab) {
                var active = tab.getAttribute('data-asset-tab') === tabId;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            document.querySelectorAll('[data-asset-panel]').forEach(function(panel) {
                var active = panel.getAttribute('data-asset-panel') === tabId;
                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
            });
            if (tabId === 'comments' && typeof window.loadAssetCommentsBoard === 'function') {
                window.loadAssetCommentsBoard();
            }
            if (tabId === 'timeline') {
                if (assetDetailCache.timelineLoadedFor === currentAssetId && assetDetailCache.timelinePage > 0) {
                    return;
                }
                loadAssetTimeline(true);
            }
        }

        function disconnectAssetTimelineObserver() {
            if (assetDetailCache.timelineObserver) {
                assetDetailCache.timelineObserver.disconnect();
                assetDetailCache.timelineObserver = null;
            }
        }

        function openAssetDetail(itemId) {
            var modal = document.getElementById('assetDetailModal');
            var detailsEl = document.getElementById('assetDetailPanel');
            var commentsEl = document.getElementById('assetDetailComments');
            var timelineEl = document.getElementById('assetDetailTimeline');
            var title = document.getElementById('assetDetailTitle');
            var subtitle = document.getElementById('assetDetailSubtitle');
            if (!modal || !detailsEl || !itemId) return;

            currentAssetId = itemId;
            disconnectAssetTimelineObserver();
            assetDetailCache = {
                details: null,
                commentsLoadedFor: 0,
                commentsPage: 1,
                timelineLoadedFor: 0,
                timelinePage: 0,
                timelineHasMore: true,
                timelineLoading: false,
                timelineObserver: null
            };

            modal.hidden = false;
            document.body.classList.add('cs-asset-detail-open');
            if (title) title.textContent = 'Asset details';
            if (subtitle) subtitle.textContent = 'Loading…';
            detailsEl.innerHTML = '<p class="cs-asset-detail-loading">Loading asset details…</p>';
            if (typeof window.prepareAssetCommentsBoard === 'function') {
                window.prepareAssetCommentsBoard(itemId);
            }
            if (timelineEl) timelineEl.innerHTML = '<p class="cs-asset-detail-loading">Open this tab to load timeline.</p>';
            setAssetDetailTab('details');

            $.ajax({
                url: assetDetailUrl(itemId),
                type: 'GET',
                success: function(res) {
                    var item = (res && res.item) || {};
                    assetDetailCache.details = item;
                    if (title) title.textContent = item.name || 'Asset details';
                    if (subtitle) subtitle.textContent = item.status ? ('Status: ' + item.status) : '';
                    detailsEl.innerHTML = renderAssetDetail(item);
                },
                error: function() {
                    if (subtitle) subtitle.textContent = '';
                    detailsEl.innerHTML = '<p class="cs-asset-detail-error">Unable to load asset details. Please try again.</p>';
                }
            });
        }

        function closeAssetDetail() {
            var modal = document.getElementById('assetDetailModal');
            if (!modal) return;
            disconnectAssetTimelineObserver();
            modal.hidden = true;
            document.body.classList.remove('cs-asset-detail-open');
            currentAssetId = 0;
        }

        function formatAssetDate(value) {
            if (!value) return '';
            var d = new Date(value);
            if (isNaN(d.getTime())) return String(value);
            return d.toLocaleString(undefined, {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit'
            });
        }

        function renderAssetDetail(item) {
            var fields = [
                ['Status', item.status],
                ['Location', item.location],
                ['Category', item.category],
                ['Other category', item.other_category],
                ['Condition', item.condition],
                ['Brand', item.brand],
                ['Other brand', item.other_brand],
                ['Purchase year', item.purchase_year],
                ['Purchase price', item.purchase_price],
                ['Estimated value', item.estimated_value],
                ['Concluded price', item.concluded_price],
                ['Accessories', item.accessories_status],
                ['Original packaging', item.original_packaging],
                ['Valid warranty', item.valid_warranty],
                ['Marital asset', item.marital_asset],
                ['Assigned to', item.assigned_to],
                ['Assigned reason', item.assigned_reason]
            ];

            var media = item.image_url
                ? '<div class="cs-asset-detail-media"><img src="' + escapeHtml(item.image_url) + '" alt="' + escapeHtml(item.name || 'Asset') + '"></div>'
                : '<div class="cs-asset-detail-media is-empty" aria-hidden="true"><i class="fas fa-box-open"></i></div>';

            var grid = fields.map(function(pair) {
                return (
                    '<div class="cs-asset-detail-field">' +
                    '<dt>' + escapeHtml(pair[0]) + '</dt>' +
                    '<dd>' + escapeHtml(pair[1] == null || pair[1] === '' ? '—' : pair[1]) + '</dd>' +
                    '</div>'
                );
            }).join('');

            return (
                '<div class="cs-asset-detail-layout">' +
                media +
                '<div class="cs-asset-detail-main">' +
                '<dl class="cs-asset-detail-grid">' + grid + '</dl>' +
                '<div class="cs-asset-detail-block"><h3>Description</h3><p>' +
                escapeHtml(item.description || '—') +
                '</p></div>' +
                '</div></div>'
            );
        }

        function loadAssetTimeline(reset) {
            var el = document.getElementById('assetDetailTimeline');
            if (!el || !currentAssetId) return;
            if (assetDetailCache.timelineLoading) return;

            if (reset) {
                disconnectAssetTimelineObserver();
                assetDetailCache.timelineLoadedFor = currentAssetId;
                assetDetailCache.timelinePage = 0;
                assetDetailCache.timelineHasMore = true;
                el.innerHTML = '';
            } else if (
                assetDetailCache.timelineLoadedFor !== currentAssetId ||
                !assetDetailCache.timelineHasMore ||
                assetDetailCache.timelinePage < 1
            ) {
                return;
            }

            var nextPage = assetDetailCache.timelinePage + 1;
            var shell = ensureTimelineShell(el, nextPage === 1 ? 'Loading timeline…' : 'Loading more…');
            setTimelineStatus(shell.status, nextPage === 1 ? 'Loading timeline…' : 'Loading more…');
            assetDetailCache.timelineLoading = true;

            $.ajax({
                url: assetTimelineUrl(currentAssetId),
                type: 'GET',
                data: { page: nextPage, limit: 10 },
                success: function(res) {
                    if (currentAssetId !== assetDetailCache.timelineLoadedFor) return;

                    var activities = res.activities || [];
                    var pagination = res.pagination || {};
                    var currentPage = Number(pagination.current_page || nextPage);
                    var lastPage = Number(pagination.last_page || currentPage);

                    if (currentPage === 1 && !activities.length) {
                        el.innerHTML = '<div class="cs-empty-state cs-asset-feed-empty"><div class="cs-empty-icon" aria-hidden="true"><i class="fas fa-stream"></i></div><div class="cs-empty-text">No activities found.</div></div>';
                        assetDetailCache.timelineHasMore = false;
                        assetDetailCache.timelinePage = 1;
                        return;
                    }

                    shell = ensureTimelineShell(el);
                    var html = '';
                    activities.forEach(function(row) {
                        html += buildTimelineItemHtml(row);
                    });
                    shell.timeline.insertAdjacentHTML('beforeend', html);
                    syncTimelineRails(shell.timeline);

                    assetDetailCache.timelinePage = currentPage;
                    assetDetailCache.timelineHasMore = currentPage < lastPage;
                    setTimelineStatus(shell.status, '');

                    var scrollRoot = document.querySelector('#assetDetailModal .cs-asset-detail-body');
                    if (!assetDetailCache.timelineObserver && shell.sentinel) {
                        assetDetailCache.timelineObserver = new IntersectionObserver(function(entries) {
                            if (!entries.some(function(entry) { return entry.isIntersecting; })) return;
                            if (assetDetailCache.timelineHasMore && !assetDetailCache.timelineLoading) {
                                loadAssetTimeline(false);
                            }
                        }, { root: scrollRoot || null, rootMargin: '120px 0px' });
                        assetDetailCache.timelineObserver.observe(shell.sentinel);
                    }
                },
                error: function() {
                    shell = ensureTimelineShell(el);
                    if (!shell.timeline.children.length) {
                        el.innerHTML = '<p class="cs-asset-detail-error">Unable to load timeline.</p>';
                    } else {
                        setTimelineStatus(shell.status, 'Unable to load more activities.');
                    }
                },
                complete: function() {
                    assetDetailCache.timelineLoading = false;
                }
            });
        }
        function renderAssetFeedPagination(pagination, feed) {
            if (!pagination || Number(pagination.last_page || 0) <= 1) return '';
            var page = Number(pagination.current_page || 1);
            var last = Number(pagination.last_page || 1);
            var html = '<nav class="cs-asset-feed-pagination" aria-label="Asset ' + feed + ' pagination"><ul class="cs-assets-page-list">';
            if (page > 1) {
                html += '<li><button type="button" class="cs-assets-page-btn" data-asset-feed-page="' + feed + '" data-page="' + (page - 1) + '">Previous</button></li>';
            }
            for (var i = 1; i <= last; i++) {
                if (last > 7 && i > 2 && i < last - 1 && Math.abs(i - page) > 1) {
                    if (i === 3 || i === last - 2) {
                        html += '<li><span class="cs-assets-page-ellipsis" aria-hidden="true">…</span></li>';
                    }
                    continue;
                }
                html +=
                    '<li><button type="button" class="cs-assets-page-btn' +
                    (i === page ? ' is-active' : '') +
                    '" data-asset-feed-page="' +
                    feed +
                    '" data-page="' +
                    i +
                    '"' +
                    (i === page ? ' aria-current="page"' : '') +
                    '>' +
                    i +
                    '</button></li>';
            }
            if (page < last) {
                html += '<li><button type="button" class="cs-assets-page-btn" data-asset-feed-page="' + feed + '" data-page="' + (page + 1) + '">Next</button></li>';
            }
            html += '</ul></nav>';
            return html;
        }

        function renderPagination(pagination) {
            if (!paginationEl || !pagination) {
                if (paginationEl) paginationEl.innerHTML = '';
                return;
            }

            var total = pagination.total;
            var page = pagination.current_page;
            var perPage = pagination.per_page;
            var lastPage = pagination.last_page;

            if (total <= perPage) {
                paginationEl.innerHTML = total
                    ? '<p class="cs-assets-pagination-info">Showing all ' + total + ' matching asset' + (total === 1 ? '' : 's') + '</p>'
                    : '';
                return;
            }

            var start = (page - 1) * perPage + 1;
            var end = Math.min(page * perPage, total);
            var html = '<ul class="cs-assets-page-list">';
            if (page > 1) {
                html += '<li><button type="button" class="cs-assets-page-btn" data-page="' + (page - 1) + '" aria-label="Previous page">Previous</button></li>';
            }
            for (var i = 1; i <= lastPage; i++) {
                if (lastPage > 7 && i > 2 && i < lastPage - 1 && Math.abs(i - page) > 1) {
                    if (i === 3 || i === lastPage - 2) {
                        html += '<li><span class="cs-assets-page-ellipsis" aria-hidden="true">…</span></li>';
                    }
                    continue;
                }
                var active = i === page ? ' is-active' : '';
                html += '<li><button type="button" class="cs-assets-page-btn' + active + '" data-page="' + i + '"' + (i === page ? ' aria-current="page"' : '') + '>' + i + '</button></li>';
            }
            if (page < lastPage) {
                html += '<li><button type="button" class="cs-assets-page-btn" data-page="' + (page + 1) + '" aria-label="Next page">Next</button></li>';
            }
            html += '</ul>';
            html += '<p class="cs-assets-pagination-info">Showing ' + start + '–' + end + ' of ' + total + ' matching assets</p>';
            paginationEl.innerHTML = html;
        }

        function updateCountSubtitle(pagination, filteredTotal) {
            if (!countSubtitle) return;
            var total = filteredTotal != null ? filteredTotal : (pagination ? pagination.total : 0);
            if (typeof pagination !== 'undefined' && pagination && totalInCase && total < totalInCase) {
                countSubtitle.textContent = total + ' of ' + totalInCase + ' assets shown';
            } else {
                countSubtitle.textContent = totalInCase + ' asset' + (totalInCase === 1 ? '' : 's') + ' in this case';
            }
        }

        function loadCaseAssets(page) {
            page = page || 1;
            currentPage = page;
            if (assetsLoading) return;
            assetsLoading = true;

            if (tbody) {
                tbody.innerHTML = '<tr><td colspan="19" class="text-center text-muted py-4">Loading assets…</td></tr>';
            }
            if (emptyFiltered) emptyFiltered.hidden = true;
            table.hidden = false;

            $.ajax({
                url: assetsListUrl,
                type: 'GET',
                data: {
                    page: page,
                    per_page: perPageSelect ? perPageSelect.value : 25,
                    search: searchInput ? searchInput.value : '',
                    status: filterStatus ? filterStatus.value : '',
                    category: filterCategory ? filterCategory.value : '',
                    location_id: filterLocation ? filterLocation.value : '',
                    sort_by: sortBy,
                    sort_order: sortOrder
                },
                success: function(res) {
                    assetsLoadedOnce = true;
                    populateFilters(res.filters);
                    if (res.total_in_case != null) totalInCase = res.total_in_case;

                    var items = res.items || [];
                    if (!items.length) {
                        tbody.innerHTML = '';
                        table.hidden = true;
                        if (emptyFiltered) emptyFiltered.hidden = false;
                        if (countSubtitle) {
                            countSubtitle.textContent = 'No assets match your filters (' + totalInCase + ' total in this case)';
                        }
                        renderPagination(res.pagination);
                        return;
                    }

                    tbody.innerHTML = items.map(renderAssetRow).join('');
                    table.hidden = false;
                    if (emptyFiltered) emptyFiltered.hidden = true;
                    updateCountSubtitle(res.pagination);
                    renderPagination(res.pagination);
                    applyColumnOrder(columnOrder);
                    applyColumns(columnPrefs);
                    updateSortHeaders();
                },
                error: function() {
                    tbody.innerHTML = '<tr><td colspan="19" class="text-center text-danger py-4">Unable to load assets. Please try again.</td></tr>';
                },
                complete: function() {
                    assetsLoading = false;
                }
            });
        }

        window.scheduleCaseAssetsLoad = function() {
            if (!assetsLoadedOnce && !assetsLoading) {
                loadCaseAssets(1);
            }
        };
        window.loadCaseAssets = loadCaseAssets;

        var columnPrefs = loadColumnPrefs();
        var columnOrder = loadColumnOrder();
        initializeSortableHeaders();
        if (columnsPanel) {
            columnsPanel.querySelectorAll('.cs-assets-col-toggle').forEach(function(label) {
                label.draggable = true;
                label.setAttribute('data-column-item', '');
                label.insertAdjacentHTML(
                    'afterbegin',
                    '<span class="cs-assets-column-drag" aria-hidden="true"><i class="fas fa-grip-vertical"></i></span>'
                );
            });
        }
        applyColumnOrder(columnOrder);
        applyColumns(columnPrefs);

        function onFilterChange() {
            currentPage = 1;
            loadCaseAssets(1);
        }

        if (tbody) {
            tbody.addEventListener('click', function(e) {
                var row = e.target.closest('tr[data-asset-id]');
                if (!row) return;
                var itemId = Number(row.getAttribute('data-asset-id') || 0);
                if (itemId) openAssetDetail(itemId);
            });
            tbody.addEventListener('keydown', function(e) {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                var row = e.target.closest('tr[data-asset-id]');
                if (!row) return;
                e.preventDefault();
                var itemId = Number(row.getAttribute('data-asset-id') || 0);
                if (itemId) openAssetDetail(itemId);
            });
        }

        document.querySelectorAll('[data-asset-tab]').forEach(function(tab) {
            tab.addEventListener('click', function() {
                setAssetDetailTab(tab.getAttribute('data-asset-tab'));
            });
        });

        document.querySelectorAll('[data-asset-detail-close]').forEach(function(el) {
            el.addEventListener('click', closeAssetDetail);
        });
        document.addEventListener('keydown', function(e) {
            if (e.key !== 'Escape') return;
            var modal = document.getElementById('assetDetailModal');
            if (modal && !modal.hidden) closeAssetDetail();
        });

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(onFilterChange, 300);
            });
        }
        [filterStatus, filterCategory, filterLocation, perPageSelect].forEach(function(el) {
            if (el) el.addEventListener('change', onFilterChange);
        });

        if (paginationEl) {
            paginationEl.addEventListener('click', function(e) {
                var btn = e.target.closest('[data-page]');
                if (!btn) return;
                e.preventDefault();
                var page = parseInt(btn.getAttribute('data-page'), 10) || 1;
                loadCaseAssets(page);
                table.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        }

        var tableHead = table.querySelector('thead');
        if (tableHead) {
            tableHead.addEventListener('click', function(e) {
                var button = e.target.closest('[data-asset-sort]');
                if (!button || assetsLoading) return;
                var requestedSort = button.getAttribute('data-asset-sort');
                if (requestedSort === sortBy) {
                    sortOrder = sortOrder === 'asc' ? 'desc' : 'asc';
                } else {
                    sortBy = requestedSort;
                    sortOrder = 'asc';
                }
                updateSortHeaders();
                loadCaseAssets(1);
            });
        }

        columnCheckboxes.forEach(function(cb) {
            cb.addEventListener('change', function() {
                var col = cb.getAttribute('data-col');
                columnPrefs[col] = cb.checked;
                saveColumnPrefs(columnPrefs);
                applyColumns(columnPrefs);
            });
        });

        if (columnsReset) {
            columnsReset.addEventListener('click', function() {
                columnPrefs = Object.assign({}, DEFAULT_COLS);
                columnOrder = ASSET_COLS.slice();
                saveColumnPrefs(columnPrefs);
                saveColumnOrder(columnOrder);
                applyColumnOrder(columnOrder);
                applyColumns(columnPrefs);
            });
        }

        if (columnsBtn && columnsPanel) {
            var draggedColumnItem = null;
            var columnsGrid = columnsPanel.querySelector('.cs-assets-columns-grid');

            columnsGrid.addEventListener('dragstart', function(e) {
                draggedColumnItem = e.target.closest('[data-column-item]');
                if (!draggedColumnItem) return;
                draggedColumnItem.classList.add('is-dragging');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData(
                    'text/plain',
                    draggedColumnItem.querySelector('input[data-col]').getAttribute('data-col')
                );
            });

            columnsGrid.addEventListener('dragover', function(e) {
                if (!draggedColumnItem) return;
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                var target = e.target.closest('[data-column-item]');
                if (!target || target === draggedColumnItem) return;
                var rect = target.getBoundingClientRect();
                var insertAfter = e.clientY > rect.top + (rect.height / 2);
                columnsGrid.insertBefore(draggedColumnItem, insertAfter ? target.nextSibling : target);
            });

            columnsGrid.addEventListener('drop', function(e) {
                if (draggedColumnItem) e.preventDefault();
            });

            columnsGrid.addEventListener('dragend', function() {
                if (!draggedColumnItem) return;
                draggedColumnItem.classList.remove('is-dragging');
                draggedColumnItem = null;
                columnOrder = Array.prototype.map.call(
                    columnsGrid.querySelectorAll('input[data-col]'),
                    function(input) { return input.getAttribute('data-col'); }
                );
                saveColumnOrder(columnOrder);
                applyColumnOrder(columnOrder);
            });

            columnsBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                var open = columnsPanel.hidden;
                columnsPanel.hidden = !open;
                columnsBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            document.addEventListener('click', function(e) {
                if (columnsPanel.hidden) return;
                if (columnsPanel.contains(e.target) || columnsBtn.contains(e.target)) return;
                columnsPanel.hidden = true;
                columnsBtn.setAttribute('aria-expanded', 'false');
            });
        }
    })();

});
};
