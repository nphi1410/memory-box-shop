document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.querySelector('[data-menu-toggle]');
  const menu = document.querySelector('[data-menu]');
  toggle?.addEventListener('click', () => menu?.classList.toggle('open'));

  document.querySelectorAll('[data-toast]').forEach((toast) => {
    setTimeout(() => toast.classList.add('hide'), 3200);
    setTimeout(() => toast.remove(), 3800);
  });

  const tabs = document.querySelectorAll('[data-filter-tabs] button');
  const cards = document.querySelectorAll('[data-product-grid] [data-category]');
  tabs.forEach((tab) => tab.addEventListener('click', () => {
    tabs.forEach((x) => x.classList.remove('active'));
    tab.classList.add('active');
    const filter = tab.dataset.filter;
    cards.forEach((card) => {
      card.hidden = filter !== 'all' && card.dataset.category !== filter;
    });
  }));

  const builder = document.querySelector('[data-builder]');
  if (builder) {
    const colorInput = builder.querySelector('[data-color-input]');
    const colorValue = builder.querySelector('[data-color-value]');
    const preview = builder.querySelector('[data-preview-box]');
    const giftInputs = [...builder.querySelectorAll('[data-gift-price]')];
    const count = builder.querySelector('[data-selected-count]');
    const total = builder.querySelector('[data-builder-total]');
    const formatMoney = (value) => new Intl.NumberFormat('vi-VN').format(value) + '₫';

    const render = () => {
      const selected = giftInputs.filter((input) => input.checked);
      const giftTotal = selected.reduce((sum, input) => sum + Number(input.dataset.giftPrice || 0), 0);
      count.textContent = `${selected.length} món`;
      total.textContent = formatMoney(89000 + giftTotal);
      preview.style.background = `linear-gradient(145deg, ${colorInput.value}, #fff)`;
      colorValue.textContent = colorInput.value;
    };
    colorInput.addEventListener('input', render);
    giftInputs.forEach((input) => input.addEventListener('change', render));
    render();
  }
});
