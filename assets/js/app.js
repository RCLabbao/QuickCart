document.addEventListener('DOMContentLoaded',()=>{
  // Cart drawer
  const offcanvas = new bootstrap.Offcanvas('#cartDrawer');
  const openBtn = document.getElementById('openCart');
  const openBtnDesktop = document.getElementById('openCartDesktop');
  const cartCount = document.getElementById('cartCount');
  const cartCountDesktop = document.getElementById('cartCountDesktop');

  // Handle both mobile and desktop cart buttons
  openBtn?.addEventListener('click',()=>offcanvas.show());
  openBtnDesktop?.addEventListener('click',()=>offcanvas.show());
  // Initial load of cart drawer and count
  reloadCart();


  // Add to cart forms (delegation)
  document.body.addEventListener('submit', async (e)=>{
    const form = e.target.closest('form.addToCart');
    if(!form) return;
    e.preventDefault();
    const fd = new FormData(form);
    fd.append('_token', window.CSRF || '');
    const res = await fetch('/cart/add',{ method:'POST', body: fd });
    const json = await res.json();
    if(json.ok){
      // Update both cart count badges
      if(cartCount) cartCount.textContent = json.count;
      if(cartCountDesktop) cartCountDesktop.textContent = json.count;
      offcanvas.show();
      loadCart();
    }
  });

  // Quantity buttons on PDP
  document.body.addEventListener('click',(e)=>{
    const btn = e.target.closest('[data-qty]'); if(!btn) return;
    const input = btn.parentElement.querySelector('input[name="qty"]'); if(!input) return;
    let v = parseInt(input.value||'1',10); v += (btn.getAttribute('data-qty')==='-1'?-1:1); if(v<1) v=1; input.value=v;
  });

  async function loadCart(){
    const wrap = document.getElementById('cartItems'); if(!wrap) return;
    const res = await fetch('/cart/summary');
    const html = await res.text();
    wrap.innerHTML = html;
  }

  // Submit helper for sticky CTA
  document.body.addEventListener('click',(e)=>{
    const btn = e.target.closest('[data-submit-form]'); if(!btn) return;
    const sel = btn.getAttribute('data-submit-form'); const form = document.querySelector(sel);
    if(form) form.requestSubmit();
  });

  // Cart summary actions (update/remove)
  document.body.addEventListener('click', async (e)=>{
    const btn = e.target.closest('[data-cart-action]'); if(!btn) return;
    const action = btn.getAttribute('data-cart-action');
    const pid = btn.getAttribute('data-product-id');
    if(action==='remove'){
      const fd = new FormData(); fd.append('_token', window.CSRF||''); fd.append('product_id', pid);
      await fetch('/cart/remove', { method:'POST', body: fd });
      await reloadCart();
    }
    if(action==='qty'){
      const dir = parseInt(btn.getAttribute('data-dir'),10);
      const input = document.querySelector(`#cartQty_${pid}`);
      let v = parseInt(input.value||'1',10)+dir; if(v<0) v=0; input.value=v;
      const fd = new FormData(); fd.append('_token', window.CSRF||''); fd.append('product_id', pid); fd.append('qty', v);
      await fetch('/cart/update', { method:'POST', body: fd });
      await reloadCart();
    }
  });

  async function reloadCart(){
    const wrap = document.getElementById('cartItems'); if(!wrap) return;
    const res = await fetch('/cart/summary'); const html = await res.text(); wrap.innerHTML = html;
    // recompute cart count
    let totalQty = 0; document.querySelectorAll('[data-cart-qty]').forEach(n=>{ totalQty += parseInt(n.getAttribute('data-cart-qty')||'0',10)||0; });
    const cartCount = document.getElementById('cartCount');
    const cartCountDesktop = document.getElementById('cartCountDesktop');
    if(cartCount) cartCount.textContent = totalQty;
    if(cartCountDesktop) cartCountDesktop.textContent = totalQty;
  }

  // Infinite scroll on products page
  if(window.INF_SCROLL){
    const loader = document.getElementById('loader');
    async function loadMore(){
      if(!window.INF_SCROLL.hasMore || window.INF_SCROLL.busy) return;
      window.INF_SCROLL.busy = true; loader.style.display='inline-block';
      const next = window.INF_SCROLL.page + 1;
      const grid = document.getElementById('productGrid');
      // add skeletons
      const placeholders = Array.from({length: 8}).map(()=>`<div class=\"col-6 col-md-4 col-lg-3\"><div class=\"card h-100\"><div class=\"ratio ratio-1x1 skeleton\"></div><div class=\"card-body\"><div class=\"skeleton\" style=\"height:14px; width:70%\"></div><div class=\"skeleton mt-2\" style=\"height:24px; width:40%\"></div></div></div></div>`).join('');
      grid.insertAdjacentHTML('beforeend', placeholders);
      const url = window.INF_SCROLL.url || '/products/load';
      const res = await fetch(`${url}?page=${next}`);
      const json = await res.json();
      // remove skeletons
      grid.querySelectorAll('.skeleton').forEach(el=>el.closest('.col-6, .col-md-4, .col-lg-3')?.remove());
      grid.insertAdjacentHTML('beforeend', json.html);
      window.INF_SCROLL.page = next; window.INF_SCROLL.hasMore = json.hasMore; window.INF_SCROLL.busy = false; loader.style.display='none';
    }
    window.addEventListener('scroll',()=>{
      if((window.innerHeight + window.scrollY) >= (document.body.offsetHeight - 400)){
        loadMore();
      }
    });
  }

  // Realtime search functionality
  let searchTimeout;
  const mobileSearchInput = document.getElementById('mobileSearchInput');
  const desktopSearchInput = document.getElementById('desktopSearchInput');
  const mobileSearchResults = document.getElementById('mobileSearchResults');
  const desktopSearchResults = document.getElementById('desktopSearchResults');

  function setupRealtimeSearch(input, resultsContainer) {
    if (!input || !resultsContainer) return;

    input.addEventListener('input', (e) => {
      const query = e.target.value.trim();

      // Clear previous timeout
      clearTimeout(searchTimeout);

      if (query.length < 2) {
        resultsContainer.style.display = 'none';
        return;
      }

      // Debounce search requests
      searchTimeout = setTimeout(async () => {
        try {
          const response = await fetch(`/api/search?q=${encodeURIComponent(query)}&limit=5`);
          const data = await response.json();

          if (data.products && data.products.length > 0) {
            renderSearchResults(data.products, data.count, query, resultsContainer);
            resultsContainer.style.display = 'block';
          } else {
            resultsContainer.innerHTML = '<div class="p-3 text-muted">No results found</div>';
            resultsContainer.style.display = 'block';
          }
        } catch (error) {
          console.error('Search error:', error);
          resultsContainer.style.display = 'none';
        }
      }, 300);
    });

    // Hide results when clicking outside
    document.addEventListener('click', (e) => {
      if (!input.contains(e.target) && !resultsContainer.contains(e.target)) {
        resultsContainer.style.display = 'none';
      }
    });

    // Hide results when input loses focus (with delay to allow clicks)
    input.addEventListener('blur', () => {
      setTimeout(() => {
        resultsContainer.style.display = 'none';
      }, 200);
    });
  }

  function renderSearchResults(products, totalCount, query, container) {
    let html = '';

    products.forEach(product => {
      const price = product.sale_price && product.sale_price < product.price
        ? `<span class="text-danger fw-bold">₱${parseFloat(product.sale_price).toLocaleString()}</span> <s class="text-muted small">₱${parseFloat(product.price).toLocaleString()}</s>`
        : `<span class="fw-bold">₱${parseFloat(product.price).toLocaleString()}</span>`;

      const imageUrl = product.image_url || `https://picsum.photos/seed/${product.id}/100/100`;

      html += `
        <a href="/products/${product.slug}" class="d-flex align-items-center p-2 text-decoration-none text-dark border-bottom">
          <img src="${imageUrl}" alt="${product.title}" class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
          <div class="flex-grow-1">
            <div class="fw-semibold text-truncate" style="max-width: 200px;">${product.title}</div>
            <div class="small">${price}</div>
          </div>
        </a>
      `;
    });

    if (totalCount > products.length) {
      html += `
        <a href="/search?q=${encodeURIComponent(query)}" class="d-block p-2 text-center text-primary text-decoration-none border-top">
          View all ${totalCount} results
        </a>
      `;
    }

    container.innerHTML = html;
  }

  // Initialize realtime search for both mobile and desktop
  setupRealtimeSearch(mobileSearchInput, mobileSearchResults);
  setupRealtimeSearch(desktopSearchInput, desktopSearchResults);

});

