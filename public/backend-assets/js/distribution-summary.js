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
                : item.purchase_price;
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

    function renderItemRow(item) {
        var price = itemPrice(item);
        var reason = item.allocation_reason
            ? '<span class="cs-dist-reason-badge ' +
              allocationReasonClass(item.allocation_reason) +
              '">' +
              esc(item.allocation_reason) +
              '</span>'
            : '';
        var brand = item.brand ? '<span class="cs-dist-item-brand">' + esc(item.brand) + '</span>' : '';

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

    function renderAllocationCard(alloc, target, options, cardIndex) {
        options = options || {};
        var showProgress = options.showProgress !== false;
        var userName = alloc.user_name || alloc.user_email || 'User';
        var roleLabel = (alloc.user_role || '').toUpperCase();
        var isDefendant = roleLabel === 'DEF' || roleLabel === 'DEFENDANT';
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
            '<article class="cs-dist-alloc-card">' +
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
            (roleLabel
                ? '<span class="cs-dist-role-badge' +
                  (isDefendant ? ' is-defendant' : '') +
                  '">' +
                  esc(roleLabel) +
                  '</span>'
                : '') +
            '</div>' +
            progressHtml +
            '<div class="cs-dist-alloc-items">' +
            renderCollapsibleItems(alloc.items, cardId) +
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
        var dontWantCount = (d.dont_want_items || []).length;
        var alert = '';
        if (dontWantCount > 0) {
            var msg = canConfirm
                ? ' need attention before you confirm.'
                : ' require attention.';
            alert =
                '<div class="cs-dist-attention-banner" role="alert">' +
                '<i class="fas fa-exclamation-triangle" aria-hidden="true"></i>' +
                '<span><strong>' +
                dontWantCount +
                '</strong> unclaimed asset' +
                (dontWantCount === 1 ? '' : 's') +
                msg +
                '</span></div>';
        }

        return (
            alert +
            '<div class="cs-dist-stat-tile"><span class="cs-dist-stat-label">Assets</span><span class="cs-dist-stat-value">' +
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
        var emailUrl = rootEl.getAttribute('data-email-url');
        var successUrl = rootEl.getAttribute('data-success-url');
        var csrfToken = rootEl.getAttribute('data-csrf-token') || '';
        var canConfirm = rootEl.getAttribute('data-can-confirm') === '1';

        var loadingEl = rootEl.querySelector('[data-dist-loading]');
        var errorEl = rootEl.querySelector('[data-dist-error]');
        var summaryRoot = rootEl.querySelector('[data-dist-summary]');
        var statsEl = rootEl.querySelector('[data-dist-stats]');
        var confirmBtn = rootEl.querySelector('[data-dist-confirm]');
        var reviewCheckbox = rootEl.querySelector('[data-dist-reviewed]');
        var previewOk = false;

        initDistDownloadMenus(pageScope);
        initDistEmailModal(
            pageScope,
            emailUrl,
            csrfToken,
            pageScope.querySelector('[data-dist-email-toast]')
        );

        function updateConfirmState() {
            if (!confirmBtn) return;
            var reviewed = reviewCheckbox ? reviewCheckbox.checked : true;
            confirmBtn.disabled = !(previewOk && reviewed);
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

        function updateTabCounts(d) {
            var allocCount = Object.keys(d.allocations || {}).length;
            var nonMaritalCount = Object.keys(d.non_marital_assets || {}).length;
            var dontWantCount = (d.dont_want_items || []).length;
            var donationCount = (d.donation_items || []).length;

            var map = {
                allocations: allocCount,
                non_marital: nonMaritalCount,
                dont_want: dontWantCount,
                donations: donationCount,
            };

            rootEl.querySelectorAll('[data-dist-tab]').forEach(function (btn) {
                var key = btn.getAttribute('data-dist-tab');
                var base = btn.getAttribute('data-dist-tab-label') || btn.textContent.trim();
                btn.innerHTML = tabCountLabel(base, map[key] || 0);
            });
        }

        function renderPanels(d) {
            var target = Number(d.target_value_per_user || 0);
            var allocEntries = Object.entries(d.allocations || {});
            var nonMaritalEntries = Object.entries(d.non_marital_assets || {});

            var allocationsPanel = rootEl.querySelector('[data-dist-panel="allocations"]');
            if (allocationsPanel) {
                if (!allocEntries.length) {
                    allocationsPanel.innerHTML =
                        renderReasonKey() + '<div class="cs-dist-panel-empty">No allocations in this preview.</div>';
                } else {
                    allocationsPanel.innerHTML =
                        renderReasonKey() +
                        '<h3 class="cs-dist-panel-title"><i class="fas fa-tags" aria-hidden="true"></i> Allocations</h3>' +
                        '<div class="cs-dist-alloc-list">' +
                        allocEntries
                            .map(function (entry, i) {
                                return renderAllocationCard(entry[1], target, { showProgress: true }, i);
                            })
                            .join('') +
                        '</div>';
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
                        '<div class="cs-dist-alloc-list">' +
                        nonMaritalEntries
                            .map(function (entry, i) {
                                return renderAllocationCard(entry[1], 0, { showProgress: false }, 'nm-' + i);
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
                          ' — action required</span></div>'
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

        function renderDistributionSummary(d) {
            if (statsEl) statsEl.innerHTML = renderSummaryStats(d, canConfirm);
            updateTabCounts(d);
            renderPanels(d);
            bindItemToggles();

            var defaultTab =
                canConfirm && (d.dont_want_items || []).length > 0 ? 'dont_want' : 'allocations';
            setDistTab(defaultTab);

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

        function loadPreview() {
            previewOk = false;
            updateConfirmState();
            if (loadingEl) loadingEl.hidden = false;
            if (errorEl) {
                errorEl.hidden = true;
                errorEl.textContent = '';
            }
            if (summaryRoot) summaryRoot.hidden = true;
            if (reviewCheckbox) reviewCheckbox.checked = false;

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
                    renderDistributionSummary(result.data.data || {});
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

        if (reviewCheckbox && canConfirm) {
            reviewCheckbox.addEventListener('change', updateConfirmState);
        }

        if (confirmBtn && canConfirm) {
            confirmBtn.addEventListener('click', function () {
                if (!previewOk || confirmBtn.disabled) return;
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
                            confirmBtn.textContent = 'Confirm division';
                            showError(
                                (result.data && result.data.message) || 'Distribution failed.'
                            );
                            return;
                        }
                        window.location.href = successUrl;
                    })
                    .catch(function () {
                        confirmBtn.disabled = false;
                        confirmBtn.textContent = 'Confirm division';
                        showError('Distribution failed. Please try again.');
                    });
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
            if (!toggle || !menu) return;

            toggle.addEventListener('click', function (e) {
                e.stopPropagation();
                var willOpen = menu.hidden;
                closeAll(wrap);
                menu.hidden = !willOpen;
                wrap.classList.toggle('is-open', willOpen);
                toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });
        });

        document.addEventListener('click', function () {
            closeAll(null);
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeAll(null);
        });
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
