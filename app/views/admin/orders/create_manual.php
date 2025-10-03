<?php use function App\Core\csrf_field; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h1 class="h3 mb-1">Create Manual Order</h1>
    <p class="text-muted mb-0">Scan SKU/barcode or search products, then fill customer details</p>
  </div>
  <div>
    <a class="btn btn-outline-secondary" href="/admin/orders"><i class="bi bi-arrow-left me-2"></i>Back to Orders</a>
  </div>
</div>

<?php if (!empty($_SESSION['error'])): ?>
  <div class="alert alert-danger">
    <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
  </div>
<?php endif; ?>

<form method="post" action="/admin/orders/manual" id="manualOrderForm">
  <?= csrf_field() ?>
  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
          <h5 class="card-title mb-0"><i class="bi bi-upc-scan me-2"></i>Items</h5>
          <div class="d-flex gap-2">
            <input type="text" class="form-control" id="scanInput" placeholder="Scan SKU/barcode or type to search..." autocomplete="off" style="min-width:320px;">
            <button class="btn btn-outline-primary" type="button" id="btnFind"><i class="bi bi-search me-2"></i>Find</button>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" id="itemsTable">
              <thead class="table-light">
                <tr>
                  <th style="width: 60px;">ID</th>
                  <th>Product</th>
                  <th style="width: 120px;" class="text-end">Unit</th>
                  <th style="width: 120px;">Qty</th>
                  <th style="width: 120px;" class="text-end">Line</th>
                  <th style="width: 80px;"></th>
                </tr>
              </thead>
              <tbody></tbody>
              <tfoot>
                <tr>
                  <td colspan="4" class="text-end fw-semibold">Subtotal</td>
                  <td class="text-end" id="subtotalCell">₱0.00</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom"><h5 class="card-title mb-0"><i class="bi bi-person me-2"></i>Customer</h5></div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">Email</label>
            <input class="form-control" type="email" name="email" placeholder="customer@email.com">
            <div class="form-text">Optional. Use for order notifications.</div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Name</label>
            <input class="form-control" type="text" name="name" placeholder="Full name">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Phone</label>
            <input class="form-control" type="text" name="phone" placeholder="Mobile number">
          </div>
        </div>
      </div>
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom"><h5 class="card-title mb-0"><i class="bi bi-truck me-2"></i>Shipping</h5></div>
        <div class="card-body">
          <div class="mb-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="shipping_method" id="shipPickup" value="pickup" checked>
              <label class="form-check-label" for="shipPickup">Pickup</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="shipping_method" id="shipCOD" value="cod">
              <label class="form-check-label" for="shipCOD">Cash on Delivery</label>
            </div>
          </div>
          <div id="addressFields" style="display:none;">
            <div class="mb-3">
              <label class="form-label fw-semibold">Address</label>
              <input class="form-control" type="text" name="address1" placeholder="Street, Barangay">
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">City</label>
              <input class="form-control" type="text" name="city" placeholder="City/Municipality">
            </div>
          </div>
          <div class="border-top pt-3 d-flex justify-content-between">
            <span class="fw-semibold">Shipping Fee</span>
            <span id="shippingCell">₱0.00</span>
          </div>
          <div class="d-flex justify-content-between mt-2">
            <span class="fw-bold">Total</span>
            <span class="fw-bold" id="totalCell">₱0.00</span>
          </div>
          <div class="d-grid mt-3">
            <button class="btn btn-primary" type="submit"><i class="bi bi-check-circle me-2"></i>Create Order</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

<style>
#itemsTable input[type=number] { max-width: 80px; }
#scanInput { font-family: monospace; }
</style>

<script>
(function(){
  const scan = document.getElementById('scanInput');
  const btnFind = document.getElementById('btnFind');
  const tbody = document.querySelector('#itemsTable tbody');
  const subtotalCell = document.getElementById('subtotalCell');
  const shippingCell = document.getElementById('shippingCell');
  const totalCell = document.getElementById('totalCell');
  const form = document.getElementById('manualOrderForm');
  const shipPickup = document.getElementById('shipPickup');
  const shipCOD = document.getElementById('shipCOD');
  const addressFields = document.getElementById('addressFields');
  const shippingFees = {
    cod: <?= json_encode((float)\App\Core\setting('shipping_fee_cod', 0.0)) ?>,
    pickup: <?= json_encode((float)\App\Core\setting('shipping_fee_pickup', 0.0)) ?>
  };
  let items = [];

  function money(n){ return '₱' + (n).toFixed(2); }
  function render(){
    tbody.innerHTML = '';
    let subtotal = 0;
    items.forEach((it, idx) => {
      const line = it.price * it.qty;
      subtotal += line;
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><span class="badge bg-light text-dark">#${it.id}</span></td>
        <td>
          <div class="fw-semibold">${escapeHtml(it.title)}</div>
          <div class="small text-muted">${it.sku ? 'SKU: '+escapeHtml(it.sku) : ''} ${it.barcode ? ' '+escapeHtml(it.barcode) : ''}</div>
        </td>
        <td class="text-end">${money(it.price)}</td>
        <td>
          <div class="input-group input-group-sm">
            <button class="btn btn-outline-secondary" type="button" data-act="dec" data-idx="${idx}">-</button>
            <input class="form-control text-center" type="number" min="1" value="${it.qty}" data-idx="${idx}">
            <button class="btn btn-outline-secondary" type="button" data-act="inc" data-idx="${idx}">+</button>
          </div>
        </td>
        <td class="text-end">${money(line)}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-danger" type="button" data-act="rm" data-idx="${idx}"><i class="bi bi-x"></i></button>
        </td>`;
      tbody.appendChild(tr);
    });
    subtotalCell.textContent = money(subtotal);
    const ship = shipCOD.checked ? shippingFees.cod : shippingFees.pickup;
    shippingCell.textContent = money(ship);
    totalCell.textContent = money(subtotal + ship);
  }
  function addItem(p){
    const existing = items.find(it => it.id === p.id);
    if (existing) { existing.qty += 1; }
    else { items.push({ id:p.id, title:p.title, sku:p.sku||'', barcode:p.barcode||'', price:parseFloat(p.price), qty:1 }); }
    render();
  }
  function fetchAndAdd(q){
    if(!q) return;
    fetch('/admin/products/search?q=' + encodeURIComponent(q))
      .then(r => r.json())
      .then(data => {
        const arr = data.items || [];
        if (arr.length === 0) { alert('No product found for "' + q + '"'); return; }
        // Prefer exact SKU/barcode match
        const exact = arr.find(x => (x.sku && x.sku.toLowerCase() === q.toLowerCase()) || (x.barcode && x.barcode.toLowerCase() === q.toLowerCase()));
        addItem(exact || arr[0]);
        scan.value='';
        scan.focus();
      })
      .catch(()=>{});
  }
  btnFind.addEventListener('click', ()=> fetchAndAdd(scan.value.trim()));
  scan.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); fetchAndAdd(scan.value.trim()); }});
  document.addEventListener('click', (e)=>{
    const act = e.target.getAttribute('data-act') || (e.target.parentElement && e.target.parentElement.getAttribute('data-act'));
    const idx = e.target.getAttribute('data-idx') || (e.target.parentElement && e.target.parentElement.getAttribute('data-idx'));
    if (act && idx!==null) {
      const i = parseInt(idx,10);
      if (act==='inc') items[i].qty += 1;
      else if (act==='dec') items[i].qty = Math.max(1, items[i].qty - 1);
      else if (act==='rm') items.splice(i,1);
      render();
    }
  });
  tbody.addEventListener('input', (e)=>{
    const idx = e.target.getAttribute('data-idx');
    if (idx!==null) { items[parseInt(idx,10)].qty = Math.max(1, parseInt(e.target.value||'1',10)); render(); }
  });
  function toggleAddress(){ addressFields.style.display = shipCOD.checked ? 'block' : 'none'; render(); }
  shipPickup.addEventListener('change', toggleAddress); shipCOD.addEventListener('change', toggleAddress); toggleAddress();

  form.addEventListener('submit', function(){
    // Build hidden item inputs
    document.querySelectorAll('input[name^="items["]').forEach(el=>el.remove());
    items.forEach((it, i)=>{
      const pid = document.createElement('input'); pid.type='hidden'; pid.name=`items[${i}][product_id]`; pid.value=it.id; form.appendChild(pid);
      const qty = document.createElement('input'); qty.type='hidden'; qty.name=`items[${i}][quantity]`; qty.value=it.qty; form.appendChild(qty);
    });
  });

  function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c])); }
  scan.focus();
})();
</script>

