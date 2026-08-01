(function () {
    var select = document.getElementById('distribution_method');
    var helper = document.getElementById('distribution_method_helper');
    if (!select || !helper) return;

    function updateDistributionHelper() {
        var opt = select.options[select.selectedIndex];
        var text = opt && opt.getAttribute('data-helper-text') ? opt.getAttribute('data-helper-text') : '';
        helper.textContent = text;
        var hasText = Boolean(text);
        helper.style.display = hasText ? '' : 'none';
        helper.setAttribute('aria-hidden', hasText ? 'false' : 'true');
    }

    select.addEventListener('change', updateDistributionHelper);
    updateDistributionHelper();
})();
