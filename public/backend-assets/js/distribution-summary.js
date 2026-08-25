/**
 * Distribution preview summary (PWA-style). Used on the dedicated review page.
 */
(function (window) {
    'use strict';

    var ITEMS_COLLAPSE_LIMIT = 5;

    function esc(s) {
        if (s == null || s === undefined) return '';
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatMoney(value) {
        if (value === null || value === undefined || value === '') return '—';
        return '$' + Number(value).toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function spellCount(n) {
        n = Math.abs(parseInt(n, 10) || 0);
        var ones = [
            'zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine',
            'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen',
            'seventeen', 'eighteen', 'nineteen',
        ];
        var tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
        function underHundred(x) {
            if (x < 20) return ones[x];
            var t = Math.floor(x / 10);
            var o = x % 10;
            return tens[t] + (o ? '-' + ones[o] : '');
        }
        var words;
        if (n < 100) {
            words = underHundred(n);
        } else if (n < 1000) {
            var h = Math.floor(n / 100);
            var rest = n % 100;
            words = ones[h] + ' hundred' + (rest ? ' ' + underHundred(rest) : '');
        } else {
            words = String(n);
        }
        words = words.charAt(0).toUpperCase() + words.slice(1);
        return words + ' (' + n + ')';
    }

    function getInitials(name) {
        if (!name) return '?';
        return String(name)
            .trim()
            .split(/\s+/)
            .slice(0, 2)
            .map(function (p) {
                return p.charAt(0).toUpperCase();
            })
            .join('');
    }

    function itemPrice(item) {
        var raw =
            item.concluded_price != null && item.concluded_price !== ''
                ? item.concluded_price
                : item.purchase_price != null && item.purchase_price !== ''
                  ? item.purchase_price
                  : item.estimated_value;
        return raw != null && raw !== '' ? formatMoney(raw) : null;
    }

    function allocationReasonClass(reason) {
        var r = (reason || '').toLowerCase();
        if (r.indexOf('must have') !== -1) return 'cs-dist-reason-must';
        if (r.indexOf('wish to have') !== -1) return 'cs-dist-reason-wish';
        if (r.indexOf('want to have') !== -1) return 'cs-dist-reason-want';
        if (r.indexOf('equal value') !== -1) return 'cs-dist-reason-equal';
        if (r.indexOf('non-marital') !== -1) return 'cs-dist-reason-nonmarital';
        if (r.indexOf('dont_want') !== -1 || r.indexOf("don't want") !== -1) return 'cs-dist-reason-want';
        return 'cs-dist-reason-default';
    }

    function itemDisplayReason(item) {
        if (!item) return '';
        if (item.status_name) return item.status_name;
        var reason = item.allocation_reason || '';
        if (reason) return reason;
        return item.status || '';
    }

    function renderItemRow(item) {
        var price = itemPrice(item);
        var displayReason = itemDisplayReason(item);
        var reason = displayReason
            ? '<span class="cs-dist-reason-badge ' +
              allocationReasonClass(displayReason) +
              '">' +
              esc(displayReason) +
              '</span>'
            : '';
        var brand = item.brand || item.brand_name
            ? '<span class="cs-dist-item-brand">' + esc(item.brand || item.brand_name) + '</span>'
            : '';

        return (
            '<div class="cs-dist-item-row">' +
            '<div class="cs-dist-item-icon" aria-hidden="true"><i class="fas fa-box"></i></div>' +
            '<div class="cs-dist-item-body">' +
            '<p class="cs-dist-item-name">' +
            esc(item.name || 'Unnamed asset') +
            '</p>' +
            '<div class="cs-dist-item-meta">' +
            (price ? '<span class="cs-dist-item-price">' + price + '</span>' : '') +
            reason +
            brand +
            '</div></div></div>'
        );
    }

    function renderCollapsibleItems(items, cardId) {
        items = items || [];
        if (!items.length) {
            return '<p class="cs-dist-empty-inline">No items in this allocation.</p>';
        }

        var visible = items.slice(0, ITEMS_COLLAPSE_LIMIT);
        var hidden = items.slice(ITEMS_COLLAPSE_LIMIT);
        var html = visible.map(renderItemRow).join('');

        if (hidden.length) {
            html +=
                '<div class="cs-dist-items-more" id="' +
                cardId +
                '-more" hidden>' +
                hidden.map(renderItemRow).join('') +
                '</div>' +
                '<button type="button" class="cs-dist-items-toggle" data-target="' +
                cardId +
                '-more" data-count="' +
                hidden.length +
                '">Show ' +
                hidden.length +
                ' more</button>';
        }

        return html;
    }

    function renderAllItems(items) {
        items = items || [];
        return items.length
            ? items.map(renderItemRow).join('')
            : '<p class="cs-dist-empty-inline">No items in this allocation.</p>';
    }

    function renderAllocationCard(alloc, target, options, cardIndex) {
        options = options || {};
        var showProgress = options.showProgress !== false;
        var showAllItems = options.showAllItems === true;
        var userName = alloc.user_name || alloc.user_email || 'User';
        var roleLabel = (alloc.user_role || '').toUpperCase();
        var isDefendant = roleLabel === 'DEF' || roleLabel === 'DEFENDANT' || roleLabel === 'SPOUSE';
        var roleDisplayLabel =
            roleLabel === 'PL' || roleLabel === 'PLAINTIFF' || roleLabel === 'CLIENT'
                ? 'Client'
                : isDefendant
                  ? 'Spouse'
                  : roleLabel;
        var received = Number(alloc.allocated_value || 0);
        var pct = target > 0 ? Math.min(100, (received / target) * 100) : 0;
        var pctRounded = Math.round(pct);
        var diff = alloc.value_difference;
        var diffHtml = '';
        var cardId = 'dist-card-' + cardIndex;

        if (diff != null && diff !== 0) {
            var diffClass = diff >= 0 ? 'cs-dist-diff-pos' : 'cs-dist-diff-neg';
            var sign = diff >= 0 ? '+' : '';
            diffHtml = '<span class="' + diffClass + '">' + sign + formatMoney(diff) + '</span>';
        }

        var progressHtml = showProgress
            ? '<div class="cs-dist-alloc-progress">' +
              '<div class="cs-dist-alloc-progress-label">Value received vs target</div>' +
              '<div class="cs-dist-alloc-progress-row">' +
              '<div class="cs-dist-alloc-bar" role="progressbar" aria-valuenow="' +
              pctRounded +
              '" aria-valuemin="0" aria-valuemax="100">' +
              '<div class="cs-dist-alloc-bar-fill" style="width:' +
              pct +
              '%"></div></div>' +
              '<span class="cs-dist-alloc-count">' +
              (alloc.allocated_item_count || 0) +
              ' items</span></div>' +
              '<div class="cs-dist-alloc-values">' +
              '<span>Received <strong>' +
              formatMoney(received) +
              '</strong></span>' +
              '<span class="cs-dist-alloc-target">Target <strong>' +
              formatMoney(target) +
              '</strong></span>' +
              diffHtml +
              '</div></div>'
            : '<div class="cs-dist-alloc-progress">' +
              '<div class="cs-dist-alloc-values">' +
              '<span>' +
              (alloc.allocated_item_count || 0) +
              ' item' +
              ((alloc.allocated_item_count || 0) === 1 ? '' : 's') +
              '</span>' +
              '<span>Value <strong>' +
              formatMoney(received) +
              '</strong></span>' +
              '</div></div>';

        return (
            '<article class="cs-dist-alloc-card' +
            (options.boardColumn ? ' is-board-column' : '') +
            '">' +
            '<div class="cs-dist-alloc-head">' +
            '<div class="cs-dist-alloc-avatar' +
            (isDefendant ? ' is-defendant' : '') +
            '" aria-hidden="true">' +
            esc(getInitials(userName)) +
            '</div>' +
            '<div class="cs-dist-alloc-user">' +
            '<p class="cs-dist-alloc-name">' +
            esc(userName) +
            '</p>' +
            (alloc.user_email && alloc.user_name !== alloc.user_email
                ? '<p class="cs-dist-alloc-email">' + esc(alloc.user_email) + '</p>'
                : '') +
            '</div>' +
            (roleDisplayLabel
                ? '<span class="cs-dist-role-badge' +
                  (isDefendant ? ' is-defendant' : '') +
                  '">' +
                  esc(roleDisplayLabel) +
                  '</span>'
                : '') +
            '</div>' +
            progressHtml +
            '<div class="cs-dist-alloc-items">' +
            (showAllItems ? renderAllItems(alloc.items) : renderCollapsibleItems(alloc.items, cardId)) +
            '</div></article>'
        );
    }

    function renderReasonKey() {
        return (
            '<div class="cs-dist-reason-key">' +
            '<p class="cs-dist-reason-key-title">Allocation reason key</p>' +
            '<div class="cs-dist-reason-key-badges">' +
            '<span class="cs-dist-reason-badge cs-dist-reason-must">Must Have</span>' +
            '<span class="cs-dist-reason-badge cs-dist-reason-wish">Wish to Have</span>' +
            '<span class="cs-dist-reason-badge cs-dist-reason-want">Want to Have</span>' +
            '<span class="cs-dist-reason-badge cs-dist-reason-equal">Equal Value Dist.</span>' +
            '</div></div>'
        );
    }

    function renderSummaryStats(d, canConfirm) {
        var unresolvedCount = (d.unresolved_items || []).length;
        var alert = '';
        if (unresolvedCount > 0 && canConfirm) {
            alert +=
                '<div class="cs-dist-attention-banner cs-dist-attention-banner-static" role="status">' +
                '<i class="fas fa-hourglass-half" aria-hidden="true"></i>' +
                '<span><strong class="cs-dist-attention-banner-title">Asset Distribution Available</strong>' +
                spellCount(unresolvedCount) +
                ' <button type="button" class="cs-dist-attention-inline-link" data-dist-goto-unresolved>unresolved asset' +
                (unresolvedCount === 1 ? '' : 's') +
                '</button>' +
                ' will be excluded from this distribution. You may proceed with the asset distribution and manually adjust these assets on the subsequent screen, or ' +
                '<button type="button" class="cs-dist-attention-inline-link" data-dist-request-resolve>click the link</button> ' +
                'to request resolution from the client or spouse prior to final submission.</span></div>';
        }

        return (
            alert +
            '<div class="cs-dist-stat-tile"><span class="cs-dist-stat-label">Assets ready for distribution</span><span class="cs-dist-stat-value">' +
            esc(d.item_count != null ? d.item_count : '—') +
            '</span></div>' +
            '<div class="cs-dist-stat-tile"><span class="cs-dist-stat-label">Total value</span><span class="cs-dist-stat-value">' +
            formatMoney(d.total_value) +
            '</span></div>' +
            '<div class="cs-dist-stat-tile"><span class="cs-dist-stat-label">Participants</span><span class="cs-dist-stat-value">' +
            esc(d.total_users != null ? d.total_users : '—') +
            '</span></div>' +
            '<div class="cs-dist-stat-tile"><span class="cs-dist-stat-label">Target per user</span><span class="cs-dist-stat-value">' +
            formatMoney(d.target_value_per_user) +
            '</span></div>'
        );
    }

    function tabCountLabel(label, count) {
        if (!count) return label;
        return label + ' <span class="cs-dist-tab-count">' + count + '</span>';
    }

    function initDistributionSummary(rootEl) {
        if (!rootEl) return;

        var pageScope = rootEl.closest('.cs-distribute-page') || document;
        var previewUrl = rootEl.getAttribute('data-preview-url');
        var distributeUrl = rootEl.getAttribute('data-distribute-url');
        var rewindUrl = rootEl.getAttribute('data-rewind-url');
        var adjustDraftUrl = rootEl.getAttribute('data-adjust-draft-url');
        var emailUrl = rootEl.getAttribute('data-email-url');
        var successUrl = rootEl.getAttribute('data-success-url');
        var csrfToken = rootEl.getAttribute('data-csrf-token') || '';
        var canConfirm = rootEl.getAttribute('data-can-confirm') === '1';
        var isPendDis = rootEl.getAttribute('data-is-pend-dis') === '1';
        var canAdjust = rootEl.getAttribute('data-can-adjust') === '1';
        var showCaps = rootEl.getAttribute('data-show-caps') === '1';
        var capPl = rootEl.getAttribute('data-cap-pl');
        var capDef = rootEl.getAttribute('data-cap-def');
        var valueCaps = {
            PL: capPl !== '' && capPl != null ? Number(capPl) : null,
            DEF: capDef !== '' && capDef != null ? Number(capDef) : null,
        };

        var loadingEl = rootEl.querySelector('[data-dist-loading]');
        var errorEl = rootEl.querySelector('[data-dist-error]');
        var summaryRoot = rootEl.querySelector('[data-dist-summary]');
        var adjustRoot = rootEl.querySelector('[data-dist-adjust]');
        var adjustBoard = rootEl.querySelector('[data-dist-adjust-board]');
        var adjustCapsEl = rootEl.querySelector('[data-dist-adjust-caps]');
        var adjustSearchInput = rootEl.querySelector('[data-dist-adjust-search]');
        var adjustSearchClear = rootEl.querySelector('[data-dist-adjust-search-clear]');
        var adjustSearchStatus = rootEl.querySelector('[data-dist-adjust-search-status]');
        var statsEl = rootEl.querySelector('[data-dist-stats]');
        var confirmBtn = rootEl.querySelector('[data-dist-confirm]');
        var unresolvedModal = document.querySelector('[data-dist-unresolved-modal]');
        var unresolvedModalLead = document.querySelector('[data-dist-unresolved-modal-lead]');
        var unresolvedProceedBtn = document.querySelector('[data-dist-unresolved-proceed]');
        var rewindModal = document.querySelector('[data-dist-rewind-modal]');
        var rewindStatusEl = document.querySelector('[data-dist-rewind-status]');
        var rewindConfirmBtn = document.querySelector('[data-dist-rewind-confirm]');
        var adjustOpenBtn = pageScope.querySelector('[data-dist-adjust-open]');
        var actionsAside = pageScope.querySelector('.cs-distribute-page-actions');
        var summaryHeadingEl = pageScope.querySelector('[data-dist-summary-heading]');
        var previewOk = false;
        var currentData = null;
        var pendingAssignments = null;
        var adjustState = null;
        var dragItemId = null;
        var adjustSearchTerm = '';

        initDistDownloadMenus(pageScope);
        initDistEmailModal(
            pageScope,
            emailUrl,
            csrfToken,
            pageScope.querySelector('[data-dist-email-toast]')
        );

        function updateConfirmState() {
            if (!confirmBtn) return;
            confirmBtn.disabled = !previewOk;
            confirmBtn.removeAttribute('title');
        }

        function unresolvedItemCount() {
            return (currentData && (currentData.unresolved_items || []).length) || 0;
        }

        function goToConfirmActions() {
            if (!actionsAside) return;
            actionsAside.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            if (confirmBtn && typeof confirmBtn.focus === 'function') {
                confirmBtn.focus();
            }
        }

        function openRewindConfirmModal() {
            if (!rewindModal) return;
            if (rewindStatusEl) {
                rewindStatusEl.hidden = true;
                rewindStatusEl.textContent = '';
            }
            if (rewindConfirmBtn) {
                rewindConfirmBtn.disabled = false;
                rewindConfirmBtn.innerHTML =
                    '<i class="fas fa-check" aria-hidden="true"></i> Confirm';
            }
            rewindModal.hidden = false;
            if (rewindConfirmBtn && typeof rewindConfirmBtn.focus === 'function') {
                rewindConfirmBtn.focus();
            }
        }

        function closeRewindConfirmModal() {
            if (!rewindModal) return;
            rewindModal.hidden = true;
        }

        function submitRewind() {
            if (!rewindUrl || !rewindConfirmBtn) return;
            rewindConfirmBtn.disabled = true;
            rewindConfirmBtn.textContent = 'Returning…';
            if (rewindStatusEl) {
                rewindStatusEl.hidden = true;
                rewindStatusEl.textContent = '';
            }

            fetch(rewindUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({}),
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.data.status) {
                        rewindConfirmBtn.disabled = false;
                        rewindConfirmBtn.innerHTML =
                            '<i class="fas fa-check" aria-hidden="true"></i> Confirm';
                        if (rewindStatusEl) {
                            rewindStatusEl.hidden = false;
                            rewindStatusEl.textContent =
                                (result.data && result.data.message) ||
                                'Unable to return this case to parties.';
                        }
                        return;
                    }
                    window.location.href = result.data.redirect_url || successUrl;
                })
                .catch(function () {
                    rewindConfirmBtn.disabled = false;
                    rewindConfirmBtn.innerHTML =
                        '<i class="fas fa-check" aria-hidden="true"></i> Confirm';
                    if (rewindStatusEl) {
                        rewindStatusEl.hidden = false;
                        rewindStatusEl.textContent = 'Unable to return this case to parties.';
                    }
                });
        }

        function openUnresolvedConfirmModal(unresolvedCount) {
            if (!unresolvedModal) return false;
            if (unresolvedModalLead) {
                unresolvedModalLead.textContent =
                    'This case has ' +
                    unresolvedCount +
                    ' unresolved asset' +
                    (unresolvedCount === 1 ? '' : 's') +
                    '. Are you okay with keeping them unresolved and proceeding with distribution?';
            }
            unresolvedModal.hidden = false;
            if (unresolvedProceedBtn && typeof unresolvedProceedBtn.focus === 'function') {
                unresolvedProceedBtn.focus();
            }
            return true;
        }

        function closeUnresolvedConfirmModal() {
            if (!unresolvedModal) return;
            unresolvedModal.hidden = true;
        }

        function submitDistribution() {
            if (!confirmBtn) return;
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Dividing…';
            if (errorEl) errorEl.hidden = true;

            fetch(distributeUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({}),
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    });
                })
                .then(function (result) {
                    if (!result.ok || !result.data.status) {
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = 'Run Distribution Model';
                        showError((result.data && result.data.message) || 'Distribution failed.');
                        return;
                    }
                    window.location.href = successUrl;
                })
                .catch(function () {
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'Run Distribution Model';
                    showError('Distribution failed. Please try again.');
                });
        }

        function itemPriceNum(item) {
            var raw =
                item.concluded_price != null && item.concluded_price !== ''
                    ? item.concluded_price
                    : item.purchase_price != null && item.purchase_price !== ''
                      ? item.purchase_price
                      : item.estimated_value;
            return raw != null && raw !== '' ? Number(raw) : 0;
        }

        function itemBrand(item) {
            return item.brand || item.brand_name || '';
        }

        function cloneData(d) {
            return JSON.parse(JSON.stringify(d || {}));
        }

        function buildAssignmentsFromAllocations(allocations) {
            var assignments = [];
            Object.keys(allocations || {}).forEach(function (key) {
                var alloc = allocations[key] || {};
                var userId = Number(alloc.user_id || 0);
                (alloc.items || []).forEach(function (item) {
                    var itemId = Number(item.id || item.item_id || 0);
                    if (!itemId || !userId) return;
                    assignments.push({
                        item_id: itemId,
                        assigned_to_user_id: userId,
                        allocation_reason: item.allocation_reason || 'Attorney Adjusted',
                    });
                });
            });
            return assignments;
        }

        function recalculateAllocations(allocations, target) {
            Object.keys(allocations || {}).forEach(function (key) {
                var alloc = allocations[key];
                var items = alloc.items || [];
                var received = items.reduce(function (sum, item) {
                    return sum + itemPriceNum(item);
                }, Number(alloc.carry_forward_value || 0));
                alloc.allocated_item_count = items.length;
                alloc.allocated_value = Math.round(received * 100) / 100;
                alloc.value_difference = Math.round((received - Number(target || 0)) * 100) / 100;
            });
            return allocations;
        }

        function setDistTab(tabId) {
            rootEl.querySelectorAll('[data-dist-tab]').forEach(function (btn) {
                var active = btn.getAttribute('data-dist-tab') === tabId;
                btn.classList.toggle('is-active', active);
                btn.setAttribute('aria-selected', active ? 'true' : 'false');
            });
            rootEl.querySelectorAll('[data-dist-panel]').forEach(function (panel) {
                var active = panel.getAttribute('data-dist-panel') === tabId;
                panel.classList.toggle('is-active', active);
                panel.hidden = !active;
            });
        }

        function goToUnresolvedTab() {
            setDistTab('unresolved');
            var tabBtn = rootEl.querySelector('[data-dist-tab="unresolved"]');
            var panel = rootEl.querySelector('[data-dist-panel="unresolved"]');
            var target = tabBtn || panel;
            if (target && typeof target.scrollIntoView === 'function') {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            if (tabBtn && typeof tabBtn.focus === 'function') {
                tabBtn.focus({ preventScroll: true });
            }
        }

        function updateTabCounts(d) {
            var allocCount = Object.keys(d.allocations || {}).length;
            var nonMaritalCount = Object.keys(d.non_marital_assets || {}).length;
            var dontWantCount = (d.dont_want_items || []).length;
            var donationCount = (d.donation_items || []).length;
            var unresolvedCount = (d.unresolved_items || []).length;

            var map = {
                allocations: allocCount,
                non_marital: nonMaritalCount,
                dont_want: dontWantCount,
                donations: donationCount,
                unresolved: unresolvedCount,
            };

            rootEl.querySelectorAll('[data-dist-tab]').forEach(function (btn) {
                var key = btn.getAttribute('data-dist-tab');
                var base = btn.getAttribute('data-dist-tab-label') || btn.textContent.trim();
                if (btn.getAttribute('data-dist-tab-hide-count') === '1') {
                    btn.textContent = base;
                    return;
                }
                btn.innerHTML = tabCountLabel(base, map[key] || 0);
            });
        }

        function renderPanels(d) {
            var target = Number(d.target_value_per_user || 0);
            var allocEntries = Object.entries(d.allocations || {});
            var nonMaritalEntries = Object.entries(d.non_marital_assets || {});
            function roleOrder(entry) {
                var role = String((entry[1] || {}).user_role || '').toUpperCase();
                if (role === 'PL' || role === 'PLAINTIFF' || role === 'CLIENT') return 0;
                if (role === 'DEF' || role === 'DEFENDANT' || role === 'SPOUSE') return 1;
                return 2;
            }
            function sortByRole(left, right) {
                return roleOrder(left) - roleOrder(right);
            }
            allocEntries.sort(sortByRole);
            nonMaritalEntries.sort(sortByRole);

            var allocationsPanel = rootEl.querySelector('[data-dist-panel="allocations"]');
            if (allocationsPanel) {
                var allocationsHtml;
                if (!allocEntries.length) {
                    allocationsHtml =
                        renderReasonKey() + '<div class="cs-dist-panel-empty">No allocations in this preview.</div>';
                } else {
                    allocationsHtml =
                        renderReasonKey() +
                        '<h3 class="cs-dist-panel-title"><i class="fas fa-tags" aria-hidden="true"></i> Allocation</h3>' +
                        '<div class="cs-dist-alloc-list cs-dist-allocation-board">' +
                        allocEntries
                            .map(function (entry, i) {
                                return renderAllocationCard(
                                    entry[1],
                                    target,
                                    { showProgress: true, showAllItems: true, boardColumn: true },
                                    i
                                );
                            })
                            .join('') +
                        '</div>';
                }
                if (isPendDis) {
                    allocationsPanel.classList.add('cs-dist-panel-is-blurred');
                    allocationsPanel.innerHTML =
                        '<div class="cs-dist-alloc-blur-target" aria-hidden="true">' +
                        allocationsHtml +
                        '</div>' +
                        '<div class="cs-dist-alloc-blur-overlay" role="status">' +
                        '<p class="cs-dist-alloc-blur-message">Run the distribution model to view allocations.</p>' +
                        '</div>';
                } else {
                    allocationsPanel.classList.remove('cs-dist-panel-is-blurred');
                    allocationsPanel.innerHTML = allocationsHtml;
                }
            }

            var nonMaritalPanel = rootEl.querySelector('[data-dist-panel="non_marital"]');
            if (nonMaritalPanel) {
                var key =
                    '<div class="cs-dist-reason-key">' +
                    '<p class="cs-dist-reason-key-title">Reason</p>' +
                    '<div class="cs-dist-reason-key-badges">' +
                    '<span class="cs-dist-reason-badge cs-dist-reason-nonmarital">Non-marital Asset</span>' +
                    '</div></div>';
                if (!nonMaritalEntries.length) {
                    nonMaritalPanel.innerHTML =
                        key +
                        '<h3 class="cs-dist-panel-title"><i class="fas fa-box" aria-hidden="true"></i> Non-marital assets</h3>' +
                        '<p class="cs-dist-panel-desc">Assets allocated to a party as non-marital (e.g. separate property).</p>' +
                        '<div class="cs-dist-panel-empty">No non-marital assets in this distribution.</div>';
                } else {
                    nonMaritalPanel.innerHTML =
                        key +
                        '<h3 class="cs-dist-panel-title"><i class="fas fa-box" aria-hidden="true"></i> Non-marital assets</h3>' +
                        '<p class="cs-dist-panel-desc">Assets allocated to a party as non-marital (e.g. separate property).</p>' +
                        '<div class="cs-dist-alloc-list cs-dist-allocation-board cs-dist-nonmarital-board">' +
                        nonMaritalEntries
                            .map(function (entry, i) {
                                return renderAllocationCard(
                                    entry[1],
                                    0,
                                    { showProgress: false, showAllItems: true, boardColumn: true },
                                    'nm-' + i
                                );
                            })
                            .join('') +
                        '</div>';
                }
            }

            var dontWantPanel = rootEl.querySelector('[data-dist-panel="dont_want"]');
            if (dontWantPanel) {
                var items = d.dont_want_items || [];
                var count = items.length;
                var alertHtml =
                    count > 0
                        ? '<div class="cs-dist-alert cs-dist-alert-danger" role="alert">' +
                          '<i class="fas fa-exclamation-triangle" aria-hidden="true"></i>' +
                          '<span>' +
                          count +
                          ' unclaimed asset' +
                          (count === 1 ? '' : 's') +
                          '. Please note that such assets remain eligible for allocation via the Adjust Distribution functionality prior to case closure.</span></div>'
                        : '';
                var listHtml =
                    count === 0
                        ? '<div class="cs-dist-panel-empty cs-dist-panel-empty-danger">No don\'t want items.</div>'
                        : '<div class="cs-dist-item-list cs-dist-item-list-danger">' +
                          items.map(renderItemRow).join('') +
                          '</div>';
                dontWantPanel.innerHTML =
                    '<h3 class="cs-dist-panel-title cs-dist-panel-title-danger"><i class="fas fa-times-circle" aria-hidden="true"></i> Don\'t Want</h3>' +
                    '<p class="cs-dist-panel-desc">No party wants these assets.</p>' +
                    alertHtml +
                    listHtml;
            }

            var donationsPanel = rootEl.querySelector('[data-dist-panel="donations"]');
            if (donationsPanel) {
                var donationItems = d.donation_items || [];
                var donationCount = donationItems.length;
                var total = d.total_donation_items_value || 0;
                if (donationCount === 0) {
                    donationsPanel.innerHTML =
                        '<h3 class="cs-dist-panel-title cs-dist-panel-title-success"><i class="fas fa-heart" aria-hidden="true"></i> Donations</h3>' +
                        '<p class="cs-dist-panel-desc">Assets agreed to be donated.</p>' +
                        '<div class="cs-dist-donation-empty">' +
                        '<div class="cs-dist-donation-empty-icon" aria-hidden="true"><i class="fas fa-heart"></i></div>' +
                        '<p class="cs-dist-donation-empty-title">No donations in this case</p>' +
                        '<p class="cs-dist-donation-empty-desc">Donated assets (mutual agreement) will appear here.</p>' +
                        '</div>';
                } else {
                    donationsPanel.innerHTML =
                        '<h3 class="cs-dist-panel-title cs-dist-panel-title-success"><i class="fas fa-heart" aria-hidden="true"></i> Donations</h3>' +
                        '<p class="cs-dist-panel-desc">Assets agreed to be donated.</p>' +
                        '<div class="cs-dist-donation-meta">' +
                        '<span>' +
                        donationCount +
                        ' item' +
                        (donationCount === 1 ? '' : 's') +
                        '</span>' +
                        (total > 0
                            ? '<span>Total value <strong>' + formatMoney(total) + '</strong></span>'
                            : '') +
                        '</div>' +
                        '<div class="cs-dist-item-list cs-dist-item-list-success">' +
                        donationItems.map(renderItemRow).join('') +
                        '</div>';
                }
            }

            var unresolvedPanel = rootEl.querySelector('[data-dist-panel="unresolved"]');
            if (unresolvedPanel) {
                var unresolvedItems = d.unresolved_items || [];
                var unresolvedCount = unresolvedItems.length;
                var unresolvedTotal = d.total_unresolved_items_value || 0;
                var unresolvedNotice =
                    '<div class="cs-dist-alert cs-dist-alert-danger cs-dist-alert-multiline" role="status">' +
                    '<i class="fas fa-exclamation-triangle" aria-hidden="true"></i>' +
                    '<span class="cs-dist-alert-body">Assets pending confirmation, valuation, conflict resolution, or third-party action.' +
                    '<span class="cs-dist-alert-follow">Please note that such assets remain eligible for allocation via the Adjust Distribution functionality prior to case closure. In the event that these items are not resolved prior to the closure of the case, all outstanding issues must be adjudicated or settled independent of, and external to, the ShareFair software platform.</span></span></div>';
                if (unresolvedCount === 0) {
                    unresolvedPanel.innerHTML =
                        '<h3 class="cs-dist-panel-title cs-dist-panel-title-muted"><i class="fas fa-hourglass-half" aria-hidden="true"></i> Unresolved</h3>' +
                        unresolvedNotice +
                        '<div class="cs-dist-panel-empty">No unresolved assets in this case.</div>';
                } else {
                    unresolvedPanel.innerHTML =
                        '<h3 class="cs-dist-panel-title cs-dist-panel-title-muted"><i class="fas fa-hourglass-half" aria-hidden="true"></i> Unresolved</h3>' +
                        unresolvedNotice +
                        '<div class="cs-dist-donation-meta">' +
                        '<span>' +
                        unresolvedCount +
                        ' item' +
                        (unresolvedCount === 1 ? '' : 's') +
                        '</span>' +
                        (unresolvedTotal > 0
                            ? '<span>Total value <strong>' + formatMoney(unresolvedTotal) + '</strong></span>'
                            : '') +
                        '</div>' +
                        '<div class="cs-dist-item-list cs-dist-item-list-muted">' +
                        unresolvedItems.map(renderItemRow).join('') +
                        '</div>';
                }
            }
        }

        function bindItemToggles() {
            rootEl.querySelectorAll('.cs-dist-items-toggle').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var target = document.getElementById(btn.getAttribute('data-target'));
                    if (!target) return;
                    var expanded = !target.hidden;
                    target.hidden = expanded;
                    btn.textContent = expanded
                        ? 'Show ' + btn.getAttribute('data-count') + ' more'
                        : 'Show fewer';
                });
            });
        }

        function computeEqualTargetPerUser(totalValue, participantCount) {
            var n = Number(participantCount || 0);
            var total = Number(totalValue || 0);
            if (n <= 0 || total <= 0) return 0;
            return Math.ceil(total / n);
        }

        function syncAllocationSummaryTotals(d) {
            var maritalCount = 0;
            var maritalValue = 0;
            var participantCount = 0;
            Object.keys(d.allocations || {}).forEach(function (key) {
                var alloc = d.allocations[key] || {};
                var items = alloc.items || [];
                var received = items.reduce(function (sum, item) {
                    return sum + itemPriceNum(item);
                }, Number(alloc.carry_forward_value || 0));
                received = Math.round(received * 100) / 100;
                alloc.allocated_item_count = items.length;
                alloc.allocated_value = received;
                maritalCount += items.length;
                maritalValue += received;
                if (Number(alloc.user_id || 0) > 0) participantCount += 1;
            });
            maritalValue = Math.round(maritalValue * 100) / 100;
            var target = computeEqualTargetPerUser(
                maritalValue,
                participantCount || Number(d.total_users || 0)
            );
            Object.keys(d.allocations || {}).forEach(function (key) {
                var alloc = d.allocations[key] || {};
                alloc.value_difference =
                    Math.round((Number(alloc.allocated_value || 0) - target) * 100) / 100;
            });
            d.item_count = maritalCount;
            d.total_value = maritalValue;
            d.total_marital_assets_count = maritalCount;
            d.total_marital_assets_value = maritalValue;
            d.target_value_per_user = target;
            return d;
        }

        function renderDistributionSummary(d) {
            currentData = cloneData(syncAllocationSummaryTotals(d || {}));
            if (statsEl) statsEl.innerHTML = renderSummaryStats(currentData, canConfirm);
            updateTabCounts(currentData);
            renderPanels(currentData);
            bindItemToggles();
            setDistTab('allocations');

            if (summaryRoot) summaryRoot.hidden = false;
            previewOk = true;
            updateConfirmState();
        }

        function showError(message) {
            previewOk = false;
            updateConfirmState();
            if (errorEl) {
                errorEl.hidden = false;
                errorEl.textContent = message;
            }
        }

        function availablePoolItems(d) {
            var assignedIds = {};
            // Party-held PEND_DST stay out of Available; IDs already in adjust_available
            // are moved into the pool (e.g. DST_REJ).
            var poolPreferred = {};
            (d.adjust_available_items || []).forEach(function (item) {
                var id = Number(item.id || item.item_id || 0);
                if (id) poolPreferred[id] = true;
            });

            Object.keys(d.allocations || {}).forEach(function (key) {
                ((d.allocations[key] || {}).items || []).forEach(function (item) {
                    var id = Number(item.id || item.item_id || 0);
                    if (!id || poolPreferred[id]) return;
                    assignedIds[id] = true;
                });
            });

            var pool = [];
            function pushPool(list) {
                (list || []).forEach(function (item) {
                    var id = Number(item.id || item.item_id || 0);
                    if (!id || assignedIds[id]) return;
                    var reason = String(item.allocation_reason || '').toLowerCase();
                    if (reason.indexOf('non-marital') !== -1) return;
                    if (reason.indexOf('donation') !== -1) return;
                    pool.push(item);
                    assignedIds[id] = true;
                });
            }

            pushPool(d.adjust_available_items);
            pushPool(d.dont_want_items);
            pushPool(d.unassigned_items);
            return pool;
        }

        function participantBuckets(allocations, poolIds) {
            poolIds = poolIds || {};
            return Object.keys(allocations || {}).map(function (key) {
                var alloc = allocations[key] || {};
                return {
                    key: key,
                    user_id: Number(alloc.user_id || 0),
                    user_name: alloc.user_name || alloc.user_email || 'User',
                    user_role: (alloc.user_role || '').toUpperCase(),
                    items: (alloc.items || []).filter(function (item) {
                        var id = Number(item.id || item.item_id || 0);
                        return !id || !poolIds[id];
                    }),
                    carry_forward_value: alloc.carry_forward_value || 0,
                };
            }).filter(function (bucket) {
                return bucket.user_id > 0;
            });
        }

        function roleCap(role) {
            if (!showCaps) return null;
            if (role === 'PL' || role === 'CLIENT') return valueCaps.PL;
            if (role === 'DEF' || role === 'DEFENDANT' || role === 'SPOUSE') return valueCaps.DEF;
            return null;
        }

        function updateCapBanner(buckets) {
            if (!adjustCapsEl) return;
            if (!showCaps) {
                adjustCapsEl.hidden = true;
                return;
            }
            var warnings = [];
            buckets.forEach(function (bucket) {
                if (bucket.isPool) return;
                var cap = roleCap(bucket.user_role);
                if (cap == null) return;
                var received = bucket.items.reduce(function (sum, item) {
                    return sum + itemPriceNum(item);
                }, Number(bucket.carry_forward_value || 0));
                if (received > cap) {
                    warnings.push(
                        esc(bucket.user_name) +
                            ' is over the value cap (' +
                            formatMoney(received) +
                            ' vs ' +
                            formatMoney(cap) +
                            ').'
                    );
                }
            });
            if (!warnings.length) {
                adjustCapsEl.hidden = true;
                adjustCapsEl.innerHTML = '';
                return;
            }
            adjustCapsEl.hidden = false;
            adjustCapsEl.innerHTML =
                '<i class="fas fa-exclamation-triangle" aria-hidden="true"></i> ' +
                warnings.join(' ');
        }

        function findItemOwner(itemId) {
            if (!adjustState) return null;
            for (var i = 0; i < adjustState.buckets.length; i++) {
                var bucket = adjustState.buckets[i];
                for (var j = 0; j < bucket.items.length; j++) {
                    var id = Number(bucket.items[j].id || bucket.items[j].item_id || 0);
                    if (id === itemId) {
                        return { bucketIndex: i, itemIndex: j, item: bucket.items[j] };
                    }
                }
            }
            return null;
        }

        function refreshAdjustTarget() {
            if (!adjustState) return;
            var parties = adjustState.buckets.filter(function (bucket) {
                return !bucket.isPool;
            });
            var total = parties.reduce(function (sum, bucket) {
                return (
                    sum +
                    bucket.items.reduce(function (itemSum, item) {
                        return itemSum + itemPriceNum(item);
                    }, Number(bucket.carry_forward_value || 0))
                );
            }, 0);
            adjustState.target = computeEqualTargetPerUser(total, parties.length);
        }

        function moveItemToBucket(itemId, targetKey) {
            var found = findItemOwner(itemId);
            if (!found || !adjustState) return;
            var targetBucket = adjustState.buckets.find(function (bucket) {
                return bucket.key === targetKey;
            });
            if (!targetBucket) return;
            if (adjustState.buckets[found.bucketIndex].key === targetKey) return;

            var item = found.item;
            adjustState.buckets[found.bucketIndex].items.splice(found.itemIndex, 1);
            if (!targetBucket.isPool) {
                item.allocation_reason = 'Attorney Adjusted';
            }
            targetBucket.items.push(item);
            refreshAdjustTarget();
            renderAdjustBoard();
        }

        function moveOptionsHtml(currentKey) {
            return adjustState.buckets
                .filter(function (other) {
                    return other.key !== currentKey;
                })
                .map(function (other) {
                    return (
                        '<option value="' +
                        esc(other.key) +
                        '">' +
                        esc(other.user_name) +
                        '</option>'
                    );
                })
                .join('');
        }

        function normalizeAdjustSearch(value) {
            return String(value || '')
                .trim()
                .toLocaleLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
        }

        function adjustItemMatchesSearch(item) {
            if (!adjustSearchTerm) return true;
            return normalizeAdjustSearch(item && item.name).indexOf(adjustSearchTerm) !== -1;
        }

        function updateAdjustSearchStatus() {
            if (!adjustSearchStatus || !adjustState) return;
            if (!adjustSearchTerm) {
                adjustSearchStatus.hidden = true;
                adjustSearchStatus.textContent = '';
                return;
            }

            var matchCount = adjustState.buckets.reduce(function (count, bucket) {
                return count + bucket.items.filter(adjustItemMatchesSearch).length;
            }, 0);
            adjustSearchStatus.hidden = false;
            adjustSearchStatus.textContent =
                matchCount +
                ' matching asset' +
                (matchCount === 1 ? '' : 's') +
                ' found across all tables.';
        }

        function renderAdjustColumn(bucket, target) {
            var isPool = !!bucket.isPool;
            var visibleItems = bucket.items.filter(adjustItemMatchesSearch);
            var received = bucket.items.reduce(function (sum, item) {
                return sum + itemPriceNum(item);
            }, Number(bucket.carry_forward_value || 0));
            var diff = received - target;
            var cap = isPool ? null : roleCap(bucket.user_role);
            var overCap = cap != null && received > cap;
            var moveOptions = moveOptionsHtml(bucket.key);

            var rows =
                visibleItems.length === 0
                    ? '<p class="cs-dist-adjust-empty-tile">' +
                      (adjustSearchTerm
                          ? 'No assets matching this name in this table.'
                          : isPool
                            ? 'No unassigned marital assets. Drop assets here to unassign.'
                            : 'No assets in this bucket. Drop one here.') +
                      '</p>'
                    : visibleItems
                          .map(function (item) {
                              var itemId = Number(item.id || item.item_id || 0);
                              var price = itemPriceNum(item);
                              var displayReason = itemDisplayReason(item);
                              var reason = displayReason
                                  ? '<span class="cs-dist-reason-badge ' +
                                    allocationReasonClass(displayReason) +
                                    '">' +
                                    esc(displayReason) +
                                    '</span>'
                                  : '';
                              var brand = itemBrand(item)
                                  ? '<span class="cs-dist-item-brand">' + esc(itemBrand(item)) + '</span>'
                                  : '';
                              return (
                                  '<article class="cs-dist-adjust-row cs-dist-adjust-tile" draggable="true" data-item-id="' +
                                  itemId +
                                  '">' +
                                  '<div class="cs-dist-adjust-tile-main">' +
                                  '<span class="cs-dist-adjust-grip" aria-hidden="true"><i class="fas fa-grip-vertical"></i></span>' +
                                  '<span class="cs-dist-adjust-tile-icon" aria-hidden="true"><i class="fas fa-box"></i></span>' +
                                  '<div class="cs-dist-adjust-tile-content">' +
                                  '<strong class="cs-dist-adjust-tile-name">' +
                                  esc(item.name || 'Unnamed asset') +
                                  '</strong>' +
                                  '<div class="cs-dist-adjust-tile-meta">' +
                                  (price ? '<span class="cs-dist-item-price">' + formatMoney(price) + '</span>' : '') +
                                  reason +
                                  brand +
                                  '</div></div></div>' +
                                  '<div class="cs-dist-adjust-tile-move">' +
                                  '<label for="move-' +
                                  itemId +
                                  '">Move asset</label>' +
                                  '<select id="move-' +
                                  itemId +
                                  '" class="cs-dist-adjust-move" data-item-id="' +
                                  itemId +
                                  '">' +
                                  '<option value="">Move to…</option>' +
                                  moveOptions +
                                  '</select>' +
                                  '</div>' +
                                  '</article>'
                              );
                          })
                          .join('');

            var totalsHtml = isPool
                ? '<div class="cs-dist-adjust-column-totals">' +
                  '<div><span>Items</span><strong>' +
                  bucket.items.length +
                  '</strong></div>' +
                  '<div><span>Value</span><strong>' +
                  formatMoney(received) +
                  '</strong></div></div>'
                : '<div class="cs-dist-adjust-column-totals">' +
                  '<div><span>Items</span><strong>' +
                  bucket.items.length +
                  '</strong></div>' +
                  '<div><span>Received</span><strong>' +
                  formatMoney(received) +
                  '</strong></div>' +
                  '<div><span>Target</span><strong>' +
                  formatMoney(target) +
                  '</strong></div>' +
                  '<div><span>Diff</span><strong class="' +
                  (diff >= 0 ? 'cs-dist-diff-pos' : 'cs-dist-diff-neg') +
                  '">' +
                  (diff >= 0 ? '+' : '') +
                  formatMoney(diff) +
                  '</strong></div>' +
                  (cap != null
                      ? '<div><span>Cap</span><strong>' + formatMoney(cap) + '</strong></div>'
                      : '') +
                  '</div>';

            return (
                '<section class="cs-dist-adjust-column' +
                (isPool ? ' is-pool' : '') +
                (overCap ? ' is-over-cap' : '') +
                '" data-bucket-key="' +
                esc(bucket.key) +
                '">' +
                '<header class="cs-dist-adjust-column-head">' +
                '<div>' +
                '<h3 class="cs-dist-adjust-column-title">' +
                esc(bucket.user_name) +
                '</h3>' +
                '<p class="cs-dist-adjust-column-role">' +
                esc(bucket.user_role || '') +
                '</p>' +
                '</div>' +
                totalsHtml +
                '</header>' +
                '<div class="cs-dist-adjust-tiles">' +
                rows +
                '</div></section>'
            );
        }

        function renderAdjustBoard() {
            if (!adjustBoard || !adjustState) return;
            var target = Number(adjustState.target || 0);
            updateCapBanner(adjustState.buckets);

            adjustBoard.innerHTML = adjustState.buckets
                .map(function (bucket) {
                    return renderAdjustColumn(bucket, target);
                })
                .join('');

            updateAdjustSearchStatus();
            bindAdjustInteractions();
        }

        function bindAdjustInteractions() {
            if (!adjustBoard) return;

            adjustBoard.querySelectorAll('.cs-dist-adjust-row').forEach(function (row) {
                row.addEventListener('dragstart', function (e) {
                    dragItemId = Number(row.getAttribute('data-item-id'));
                    row.classList.add('is-dragging');
                    if (e.dataTransfer) {
                        e.dataTransfer.effectAllowed = 'move';
                        e.dataTransfer.setData('text/plain', String(dragItemId));
                    }
                });
                row.addEventListener('dragend', function () {
                    row.classList.remove('is-dragging');
                    dragItemId = null;
                    adjustBoard.querySelectorAll('.cs-dist-adjust-column').forEach(function (col) {
                        col.classList.remove('is-drop-target');
                    });
                });
            });

            adjustBoard.querySelectorAll('.cs-dist-adjust-column').forEach(function (col) {
                col.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    col.classList.add('is-drop-target');
                    if (e.dataTransfer) e.dataTransfer.dropEffect = 'move';
                });
                col.addEventListener('dragleave', function () {
                    col.classList.remove('is-drop-target');
                });
                col.addEventListener('drop', function (e) {
                    e.preventDefault();
                    col.classList.remove('is-drop-target');
                    var itemId = dragItemId || Number((e.dataTransfer && e.dataTransfer.getData('text/plain')) || 0);
                    var bucketKey = col.getAttribute('data-bucket-key');
                    if (itemId && bucketKey) moveItemToBucket(itemId, bucketKey);
                });
            });

            adjustBoard.querySelectorAll('.cs-dist-adjust-move').forEach(function (select) {
                select.addEventListener('change', function () {
                    var itemId = Number(select.getAttribute('data-item-id'));
                    var bucketKey = select.value;
                    if (itemId && bucketKey) moveItemToBucket(itemId, bucketKey);
                    select.value = '';
                });
            });
        }

        function openAdjustMode() {
            if (!canAdjust || !currentData || !adjustRoot) return;
            var poolItems = availablePoolItems(currentData);
            var poolIds = {};
            poolItems.forEach(function (item) {
                var id = Number(item.id || item.item_id || 0);
                if (id) poolIds[id] = true;
            });
            var parties = participantBuckets(currentData.allocations || {}, poolIds);
            adjustState = {
                target: Number(currentData.target_value_per_user || 0),
                buckets: parties.concat([
                    {
                        key: '__pool__',
                        user_id: 0,
                        user_name: 'Available marital assets',
                        user_role: 'In-progress / rejected / other',
                        items: poolItems,
                        carry_forward_value: 0,
                        isPool: true,
                    },
                ]),
            };
            if (!parties.length) {
                showError('No participants available to adjust.');
                return;
            }
            adjustSearchTerm = '';
            if (adjustSearchInput) adjustSearchInput.value = '';
            if (adjustSearchClear) adjustSearchClear.hidden = true;
            if (adjustSearchStatus) {
                adjustSearchStatus.hidden = true;
                adjustSearchStatus.textContent = '';
            }
            refreshAdjustTarget();
            if (summaryRoot) summaryRoot.hidden = true;
            if (summaryHeadingEl) summaryHeadingEl.hidden = true;
            if (actionsAside) actionsAside.hidden = true;
            adjustRoot.hidden = false;
            renderAdjustBoard();
            adjustRoot.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        function closeAdjustMode(restoreSummary) {
            if (adjustRoot) adjustRoot.hidden = true;
            if (actionsAside) actionsAside.hidden = false;
            if (summaryHeadingEl) summaryHeadingEl.hidden = false;
            if (restoreSummary && summaryRoot) summaryRoot.hidden = false;
            adjustState = null;
            adjustSearchTerm = '';
        }

        function applyAdjustments() {
            if (!adjustState || !currentData || !adjustDraftUrl) return;
            var next = cloneData(currentData);
            var nextAllocations = {};
            var poolItems = [];
            adjustState.buckets.forEach(function (bucket) {
                if (bucket.isPool) {
                    poolItems = bucket.items.slice();
                    return;
                }
                nextAllocations[bucket.key] = {
                    user_id: bucket.user_id,
                    user_name: bucket.user_name,
                    user_role: bucket.user_role,
                    user_email: (currentData.allocations[bucket.key] || {}).user_email || null,
                    carry_forward_value: bucket.carry_forward_value,
                    items: bucket.items,
                };
            });
            next.allocations = recalculateAllocations(nextAllocations, adjustState.target);
            next.dont_want_items = poolItems.filter(function (item) {
                var reason = String(item.allocation_reason || '').toLowerCase();
                return reason.indexOf("don't want") !== -1 || reason.indexOf('dont want') !== -1;
            });
            next.unassigned_items = poolItems.filter(function (item) {
                var reason = String(item.allocation_reason || '').toLowerCase();
                return reason.indexOf("don't want") === -1 && reason.indexOf('dont want') === -1;
            });
            next.adjust_available_items = poolItems.slice();
            pendingAssignments = buildAssignmentsFromAllocations(next.allocations);

            var applyBtn = rootEl.querySelector('[data-dist-adjust-apply]');
            if (applyBtn) {
                applyBtn.disabled = true;
                applyBtn.textContent = 'Saving…';
            }
            if (errorEl) {
                errorEl.hidden = true;
                errorEl.textContent = '';
            }

            fetch(adjustDraftUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ assignments: pendingAssignments }),
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    });
                })
                .then(function (result) {
                    if (applyBtn) {
                        applyBtn.disabled = false;
                        applyBtn.textContent = 'Save adjustments';
                    }
                    if (!result.ok || !result.data.status) {
                        showError(
                            (result.data && result.data.message) ||
                                'Unable to save distribution adjustments.'
                        );
                        return;
                    }
                    closeAdjustMode(false);
                    if (result.data.data) {
                        renderDistributionSummary(result.data.data);
                    } else {
                        renderDistributionSummary(next);
                    }
                    if (summaryRoot) {
                        summaryRoot.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                })
                .catch(function () {
                    if (applyBtn) {
                        applyBtn.disabled = false;
                        applyBtn.textContent = 'Save adjustments';
                    }
                    showError('Unable to save distribution adjustments.');
                });
        }

        function cancelAdjustments() {
            closeAdjustMode(true);
            pendingAssignments = null;
        }

        function loadPreview() {
            previewOk = false;
            updateConfirmState();
            if (loadingEl) loadingEl.hidden = false;
            if (errorEl) {
                errorEl.hidden = true;
                errorEl.textContent = '';
            }
            if (summaryRoot) summaryRoot.hidden = true;
            if (adjustRoot) adjustRoot.hidden = true;

            fetch(previewUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    });
                })
                .then(function (result) {
                    if (loadingEl) loadingEl.hidden = true;
                    if (!result.ok || !result.data.status) {
                        showError(
                            (result.data && result.data.message) ||
                                'Unable to load distribution preview.'
                        );
                        return;
                    }
                    var d = result.data.data || {};
                    pendingAssignments = buildAssignmentsFromAllocations(d.allocations || {});
                    renderDistributionSummary(d);
                })
                .catch(function () {
                    if (loadingEl) loadingEl.hidden = true;
                    showError('Unable to load distribution preview.');
                });
        }

        rootEl.querySelectorAll('[data-dist-tab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                setDistTab(btn.getAttribute('data-dist-tab'));
            });
        });

        pageScope.addEventListener('click', function (e) {
            if (e.target && e.target.closest('[data-dist-goto-unresolved]')) {
                e.preventDefault();
                goToUnresolvedTab();
                return;
            }
            if (e.target && e.target.closest('[data-dist-goto-confirm]')) {
                e.preventDefault();
                goToConfirmActions();
                return;
            }
            if (e.target && e.target.closest('[data-dist-request-resolve]')) {
                e.preventDefault();
                openRewindConfirmModal();
            }
        });

        if (adjustOpenBtn && canAdjust) {
            adjustOpenBtn.addEventListener('click', openAdjustMode);
        }

        if (adjustSearchInput) {
            adjustSearchInput.addEventListener('input', function () {
                adjustSearchTerm = normalizeAdjustSearch(adjustSearchInput.value);
                if (adjustSearchClear) adjustSearchClear.hidden = !adjustSearchTerm;
                renderAdjustBoard();
            });
            adjustSearchInput.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape' || !adjustSearchInput.value) return;
                e.preventDefault();
                adjustSearchInput.value = '';
                adjustSearchTerm = '';
                if (adjustSearchClear) adjustSearchClear.hidden = true;
                renderAdjustBoard();
            });
        }
        if (adjustSearchClear) {
            adjustSearchClear.addEventListener('click', function () {
                if (adjustSearchInput) {
                    adjustSearchInput.value = '';
                    adjustSearchInput.focus();
                }
                adjustSearchTerm = '';
                adjustSearchClear.hidden = true;
                renderAdjustBoard();
            });
        }

        var adjustCancelBtn = rootEl.querySelector('[data-dist-adjust-cancel]');
        var adjustApplyBtn = rootEl.querySelector('[data-dist-adjust-apply]');
        if (adjustCancelBtn) adjustCancelBtn.addEventListener('click', cancelAdjustments);
        if (adjustApplyBtn) adjustApplyBtn.addEventListener('click', applyAdjustments);

        if (rewindModal && canConfirm) {
            rewindModal.querySelectorAll('[data-dist-rewind-cancel]').forEach(function (btn) {
                btn.addEventListener('click', closeRewindConfirmModal);
            });
            if (rewindConfirmBtn) {
                rewindConfirmBtn.addEventListener('click', submitRewind);
            }
            pageScope.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && rewindModal && !rewindModal.hidden) {
                    closeRewindConfirmModal();
                }
            });
        }

        if (unresolvedModal && canConfirm) {
            unresolvedModal.querySelectorAll('[data-dist-unresolved-cancel]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    closeUnresolvedConfirmModal();
                    goToUnresolvedTab();
                });
            });
            if (unresolvedProceedBtn) {
                unresolvedProceedBtn.addEventListener('click', function () {
                    closeUnresolvedConfirmModal();
                    submitDistribution();
                });
            }
            pageScope.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && unresolvedModal && !unresolvedModal.hidden) {
                    closeUnresolvedConfirmModal();
                }
            });
        }

        if (confirmBtn && canConfirm) {
            confirmBtn.addEventListener('click', function () {
                if (!previewOk || confirmBtn.disabled) return;
                var unresolvedCount = unresolvedItemCount();
                if (unresolvedCount > 0 && openUnresolvedConfirmModal(unresolvedCount)) {
                    return;
                }
                submitDistribution();
            });
        }

        loadPreview();
    }

    function initDistDownloadMenus(scope) {
        var wraps = (scope || document).querySelectorAll('.cs-dist-download-wrap');
        if (!wraps.length) return;

        function closeAll(exceptWrap) {
            wraps.forEach(function (wrap) {
                if (wrap === exceptWrap) return;
                wrap.classList.remove('is-open');
                var menu = wrap.querySelector('[data-dist-download-menu], .cs-dist-download-menu');
                var toggle = wrap.querySelector('[data-dist-download-toggle], .cs-dist-download-toggle');
                if (menu) menu.hidden = true;
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
            });
        }

        wraps.forEach(function (wrap) {
            var toggle = wrap.querySelector('[data-dist-download-toggle], .cs-dist-download-toggle');
            var menu = wrap.querySelector('[data-dist-download-menu], .cs-dist-download-menu');
            if (!toggle || !menu || toggle.getAttribute('data-dist-download-bound') === '1') return;
            toggle.setAttribute('data-dist-download-bound', '1');

            toggle.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var willOpen = menu.hidden;
                closeAll(wrap);
                menu.hidden = !willOpen;
                wrap.classList.toggle('is-open', willOpen);
                toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });

            menu.addEventListener('click', function (e) {
                e.stopPropagation();
            });
        });

        if (!document.documentElement.hasAttribute('data-dist-download-doc-bound')) {
            document.documentElement.setAttribute('data-dist-download-doc-bound', '1');
            document.addEventListener('click', function () {
                document.querySelectorAll('.cs-dist-download-wrap.is-open').forEach(function (wrap) {
                    wrap.classList.remove('is-open');
                    var menu = wrap.querySelector('[data-dist-download-menu], .cs-dist-download-menu');
                    var toggle = wrap.querySelector('[data-dist-download-toggle], .cs-dist-download-toggle');
                    if (menu) menu.hidden = true;
                    if (toggle) toggle.setAttribute('aria-expanded', 'false');
                });
            });
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                document.querySelectorAll('.cs-dist-download-wrap.is-open').forEach(function (wrap) {
                    wrap.classList.remove('is-open');
                    var menu = wrap.querySelector('[data-dist-download-menu], .cs-dist-download-menu');
                    var toggle = wrap.querySelector('[data-dist-download-toggle], .cs-dist-download-toggle');
                    if (menu) menu.hidden = true;
                    if (toggle) toggle.setAttribute('aria-expanded', 'false');
                });
            });
        }

    }

    function initDistEmailModal(scope, emailUrl, csrfToken, toastEl) {
        var page = scope || document;
        var modal = document.querySelector('[data-dist-email-modal]');
        var openButtons = page.querySelectorAll('[data-dist-email-open]');
        if (!modal || !openButtons.length || !emailUrl) return;

        var closeButtons = modal.querySelectorAll('[data-dist-email-close]');
        var sendButton = modal.querySelector('[data-dist-email-send]');
        var statusEl = modal.querySelector('[data-dist-email-status]');
        var recipientInputs = modal.querySelectorAll('[data-dist-email-recipient]');
        var toastTimer = null;

        function selectedRecipients() {
            return Array.prototype.slice
                .call(recipientInputs)
                .filter(function (input) {
                    return input.checked;
                })
                .map(function (input) {
                    return input.value;
                });
        }

        function setStatus(message, isError) {
            if (!statusEl) return;
            statusEl.hidden = false;
            statusEl.textContent = message;
            statusEl.classList.toggle('is-error', !!isError);
            statusEl.classList.toggle('is-success', !isError);
        }

        function clearStatus() {
            if (!statusEl) return;
            statusEl.hidden = true;
            statusEl.textContent = '';
            statusEl.classList.remove('is-error', 'is-success');
        }

        function showToast(message, isError) {
            if (!toastEl) return;
            toastEl.hidden = false;
            toastEl.textContent = message;
            toastEl.classList.toggle('is-error', !!isError);
            toastEl.classList.toggle('is-success', !isError);
            if (toastTimer) window.clearTimeout(toastTimer);
            toastTimer = window.setTimeout(function () {
                toastEl.hidden = true;
                toastEl.textContent = '';
                toastEl.classList.remove('is-error', 'is-success');
            }, 5000);
        }

        function resetRecipients() {
            recipientInputs.forEach(function (input) {
                input.checked = false;
            });
        }

        function openModal() {
            modal.hidden = false;
            clearStatus();
            document.body.classList.add('cs-dist-email-modal-open');
        }

        function closeModal() {
            modal.hidden = true;
            document.body.classList.remove('cs-dist-email-modal-open');
        }

        openButtons.forEach(function (button) {
            button.addEventListener('click', openModal);
        });

        closeButtons.forEach(function (button) {
            button.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hidden) closeModal();
        });

        if (sendButton) {
            sendButton.addEventListener('click', function () {
                var recipients = selectedRecipients();
                if (!recipients.length) {
                    setStatus('Please select at least one recipient.', true);
                    return;
                }

                resetRecipients();
                closeModal();
                showToast('Sending distribution summary emails in the background...', false);

                fetch(emailUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ recipients: recipients }),
                })
                    .then(function (res) {
                        return res.json().then(function (data) {
                            return { ok: res.ok, data: data };
                        });
                    })
                    .then(function (result) {
                        if (!result.ok || !result.data.status) {
                            showToast(
                                (result.data && result.data.message) ||
                                    'Unable to queue distribution summary email.',
                                true
                            );
                            return;
                        }

                        showToast(
                            result.data.message ||
                                'Distribution summary emails are being sent in the background.',
                            false
                        );
                    })
                    .catch(function () {
                        showToast('Unable to queue distribution summary email.', true);
                    });
            });
        }
    }

    window.initDistributionSummary = initDistributionSummary;
})(window);
