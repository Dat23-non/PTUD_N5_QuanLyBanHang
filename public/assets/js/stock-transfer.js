(() => {
    'use strict';
    const form = document.getElementById('st-form');
    if (!form) return;
    const body = document.getElementById('st-lines');
    const addButton = document.getElementById('st-add');
    const saveButton = document.getElementById('st-save');
    const saveAvailable = !saveButton.disabled;
    const errorBox = document.getElementById('st-client-error');
    const moneyFormat = new Intl.NumberFormat('vi-VN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const numberFormat = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 3 });
    let nextIndex = body.rows.length;
    let dirty = Boolean(form.elements.destination_id.value || form.elements.note.value ||
        [...body.querySelectorAll('input, select')].some((field) => field.value !== ''));
    let submitting = false;

    function recalculate() {
        let total = 0;
        let count = 0;
        const selected = new Set();
        for (const row of body.rows) {
            const select = row.querySelector('.st-product');
            const option = select.selectedOptions[0];
            const quantity = row.querySelector('.st-quantity');
            const price = row.querySelector('.st-price');
            const qty = Number(quantity.value);
            const unitPrice = Number(price.value);
            const stock = Number(option?.dataset.stock ?? 0);
            row.querySelector('.st-unit').textContent = option?.dataset.unit ?? '—';
            row.querySelector('.st-stock').textContent = select.value ? numberFormat.format(stock) : '—';
            select.setCustomValidity(select.value && selected.has(select.value) ? 'Mặt hàng đã có trong phiếu. Hãy gộp thành một dòng.' : '');
            if (select.value) { selected.add(select.value); count++; }
            quantity.setCustomValidity(select.value && qty > stock ? `Số lượng vượt tồn hiện tại (${numberFormat.format(stock)}).` : '');
            const amount = Number.isFinite(qty * unitPrice) && qty > 0 && unitPrice >= 0 ? qty * unitPrice : 0;
            row.querySelector('.st-amount').textContent = moneyFormat.format(amount);
            total += amount;
        }
        document.getElementById('st-count').textContent = `${count} mặt hàng`;
        document.getElementById('st-total').textContent = `${moneyFormat.format(total)} đ`;
        addButton.disabled = body.rows.length >= 100;
    }

    addButton.addEventListener('click', () => {
        if (body.rows.length >= 100) return;
        const fragment = document.getElementById('st-row-template').content.cloneNode(true);
        for (const element of fragment.querySelectorAll('[name], [id], [for]')) {
            for (const attr of ['name', 'id', 'for']) {
                if (element.hasAttribute(attr)) element.setAttribute(attr, element.getAttribute(attr).replaceAll('__INDEX__', String(nextIndex)));
            }
        }
        nextIndex++;
        body.append(fragment);
        dirty = true;
        recalculate();
        body.lastElementChild.querySelector('select').focus();
    });
    body.addEventListener('click', (event) => {
        if (!event.target.closest('.st-remove')) return;
        event.target.closest('tr').remove();
        dirty = true;
        recalculate();
    });
    form.addEventListener('input', () => { dirty = true; errorBox.hidden = true; recalculate(); });
    form.addEventListener('change', () => { dirty = true; recalculate(); });
    form.addEventListener('submit', (event) => {
        if (submitting) { event.preventDefault(); return; }
        recalculate();
        if (!body.rows.length) {
            event.preventDefault();
            errorBox.textContent = 'Phiếu phải có ít nhất một mặt hàng.';
            errorBox.hidden = false;
            addButton.focus();
            return;
        }
        if (!form.reportValidity()) { event.preventDefault(); return; }
        submitting = true;
        saveButton.disabled = true;
        saveButton.textContent = 'Đang lưu…';
    });
    document.getElementById('st-exit').addEventListener('click', (event) => {
        if (dirty && !window.confirm('Bỏ dữ liệu chưa lưu và thoát lập phiếu?')) { event.preventDefault(); return; }
        dirty = false;
    });
    window.addEventListener('beforeunload', (event) => {
        if (dirty && !submitting) { event.preventDefault(); event.returnValue = ''; }
    });
    window.addEventListener('pageshow', () => { submitting = false; saveButton.disabled = !saveAvailable; saveButton.textContent = 'Lưu nháp'; });
    recalculate();
})();
