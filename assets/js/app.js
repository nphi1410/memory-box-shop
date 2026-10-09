document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('[data-menu-toggle]');
  const menu = document.querySelector('[data-menu]');
  toggle?.addEventListener('click', () => menu?.classList.toggle('open'));

  document.querySelectorAll('[data-toast]').forEach((toast) => {
    setTimeout(() => toast.classList.add('hide'), 3200);
    setTimeout(() => toast.remove(), 3800);
  });

  const formatMoney = (value) => new Intl.NumberFormat('vi-VN').format(value) + '₫';
  const cartSelectionForm = document.querySelector('[data-cart-selection-form]');
  if (cartSelectionForm) {
    const boxes = [...document.querySelectorAll('[data-cart-select]')];
    const selectAll = document.querySelector('[data-cart-select-all]');
    const checkoutButton = document.querySelector('[data-cart-checkout]');
    const renderCartSelection = () => {
      const selected = boxes.filter((box) => box.checked);
      const total = selected.reduce((sum, box) => sum + Number(box.dataset.selectionPrice || 0), 0);
      document.querySelectorAll('[data-cart-selected-count], [data-cart-selected-count-summary]').forEach((node) => {
        node.textContent = node.hasAttribute('data-cart-selected-count-summary') ? `${selected.length} món` : `${selected.length} món được chọn`;
      });
      document.querySelector('[data-cart-selected-total]')?.replaceChildren(document.createTextNode(formatMoney(total)));
      document.querySelector('[data-cart-grand-total]')?.replaceChildren(document.createTextNode(formatMoney(total)));
      if (selectAll) selectAll.checked = boxes.length > 0 && selected.length === boxes.length;
      if (checkoutButton) checkoutButton.disabled = selected.length === 0;
    };
    boxes.forEach((box) => box.addEventListener('change', renderCartSelection));
    selectAll?.addEventListener('change', () => {
      boxes.forEach((box) => { box.checked = selectAll.checked; });
      renderCartSelection();
    });
    cartSelectionForm.addEventListener('submit', (event) => {
      if (!boxes.some((box) => box.checked)) {
        event.preventDefault();
        renderCartSelection();
      }
    });
    renderCartSelection();
  }

  const checkoutForm = document.querySelector('[data-checkout-form]');
  if (checkoutForm) {
    const boxes = [...checkoutForm.querySelectorAll('[data-checkout-select]')];
    const selectAll = checkoutForm.querySelector('[data-checkout-select-all]');
    const submit = checkoutForm.querySelector('[data-checkout-submit]');
    const renderCheckoutSelection = () => {
      const selected = boxes.filter((box) => box.checked);
      const total = selected.reduce((sum, box) => sum + Number(box.closest('[data-checkout-item]')?.dataset.lineTotal || 0), 0);
      const count = `${selected.length} món được chọn`;
      const countNode = checkoutForm.querySelector('[data-checkout-selected-count]');
      if (countNode) countNode.textContent = count;
      checkoutForm.querySelector('[data-checkout-total]')?.replaceChildren(document.createTextNode(formatMoney(total)));
      checkoutForm.querySelector('[data-checkout-grand-total]')?.replaceChildren(document.createTextNode(formatMoney(total)));
      if (selectAll) selectAll.checked = boxes.length > 0 && selected.length === boxes.length;
      if (submit) submit.disabled = selected.length === 0;
    };
    boxes.forEach((box) => box.addEventListener('change', renderCheckoutSelection));
    selectAll?.addEventListener('change', () => {
      boxes.forEach((box) => { box.checked = selectAll.checked; });
      renderCheckoutSelection();
    });
    checkoutForm.addEventListener('submit', (event) => {
      if (!boxes.some((box) => box.checked)) {
        event.preventDefault();
        renderCheckoutSelection();
      }
    });
    renderCheckoutSelection();
  }

  const tabs = document.querySelectorAll('[data-filter-tabs] button');
  const productGroups = document.querySelectorAll('[data-product-group]');
  tabs.forEach((tab) => tab.addEventListener('click', () => {
    tabs.forEach((x) => x.classList.remove('active'));
    tab.classList.add('active');
    const filter = tab.dataset.filter;
    productGroups.forEach((group) => {
      group.hidden = filter !== 'all' && group.dataset.productGroup !== filter;
    });
  }));

  const builder = document.querySelector('[data-builder]');
  if (builder) {
    const colorInput = builder.querySelector('[data-color-input]');
    const colorValue = builder.querySelector('[data-color-value]');
    const preview = builder.querySelector('[data-preview-box]');
    const previewItems = builder.querySelector('[data-preview-items]');
    const chosenList = builder.querySelector('[data-chosen-gifts-list]');
    const giftInputs = [...builder.querySelectorAll('[data-gift-price]')];
    const shapeInputs = [...builder.querySelectorAll('input[name="box_shape"]')];
    const count = builder.querySelector('[data-selected-count]');
    const total = builder.querySelector('[data-builder-total]');
    const formatMoney = (value) => new Intl.NumberFormat('vi-VN').format(value) + '₫';

    const render = () => {
      const selected = giftInputs.filter((input) => input.checked);
      const giftTotal = selected.reduce((sum, input) => sum + Number(input.dataset.giftPrice || 0), 0);
      count.textContent = `${selected.length} món`;
      total.textContent = formatMoney(89000 + giftTotal);
      preview.style.setProperty('--box-color', colorInput.value);
      preview.dataset.shape = shapeInputs.find((input) => input.checked)?.value || 'square';
      colorValue.textContent = colorInput.value;

      previewItems.replaceChildren();
      chosenList.replaceChildren();
      if (!selected.length) {
        const empty = document.createElement('span');
        empty.className = 'preview-empty';
        empty.innerHTML = 'Quà bạn chọn sẽ<br>xuất hiện ở đây';
        previewItems.append(empty);
        const hint = document.createElement('p');
        hint.textContent = 'Chưa có món quà nào. Chọn một món ở trên để bắt đầu nhé.';
        chosenList.append(hint);
      } else {
        selected.forEach((input) => {
          const previewGift = document.createElement('div');
          previewGift.className = 'preview-gift';
          const photo = document.createElement('div');
          photo.className = 'preview-gift-photo';
          const image = document.createElement('img');
          image.src = input.dataset.giftImage;
          image.alt = input.dataset.giftName;
          image.className = 'preview-gift-image';
          photo.append(image);
          // The gift artwork already includes its own product name; keep the
          // preview focused on the illustration instead of printing it twice.
          previewGift.append(photo);
          previewItems.append(previewGift);

          const chosen = document.createElement('div');
          chosen.className = 'chosen-gift';
          const chosenImage = image.cloneNode();
          chosenImage.alt = '';
          chosenImage.className = 'chosen-gift-image';
          const name = document.createElement('span');
          name.textContent = input.dataset.giftName;
          chosen.append(chosenImage, name);
          chosenList.append(chosen);
        });
      }
    };
    colorInput.addEventListener('input', render);
    giftInputs.forEach((input) => input.addEventListener('change', render));
    shapeInputs.forEach((input) => input.addEventListener('change', render));
    render();
  }
});
