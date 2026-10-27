<?php
  session_start();
  if (!isset($_SESSION['admin'])) { header("Location: index.php"); exit;}
  include 'db.php';
  ?>
  <!DOCTYPE html>
  <html lang="en">
  <head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Current Stocks</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body{font-family:"Poppins",sans-serif;background:linear-gradient(135deg,#081b29,#0f4c75);color:#fff;margin:0;padding:28px}
    .wrap{max-width:1100px;margin:0 auto;background:rgba(255,255,255,0.04);padding:18px;border-radius:12px}
    h1{color:#00adb5;margin:0 0 14px}
    .controls{display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap}
    button.primary{background:#00adb5;border:none;color:#04202a;padding:8px 12px;border-radius:8px;cursor:pointer}
    .search-sort-container{display:flex;gap:10px;margin-bottom:15px;flex-wrap:wrap;align-items:center}
    .search-box{flex:1;min-width:250px;position:relative}
    .search-box input{width:100%;padding:10px 35px 10px 12px;border-radius:8px;border:none;background:rgba(255,255,255,0.08);color:#fff;font-size:14px}
    .search-icon{position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#00adb5}
    .sort-dropdown{padding:10px;border-radius:8px;border:none;background:rgba(255,255,255,0.08);color:#fff;min-width:180px}
    .sort-dropdown option{background:#0f4c75;color:#fff}
    table{width:100%;border-collapse:collapse;margin-top:8px}
    th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,0.06);text-align:center}
    th{background:rgba(255,255,255,0.03);color:#e8f9f9;cursor:pointer;position:relative;user-select:none}
    th.sortable:hover{background:rgba(0,173,181,0.1)}
    th .sort-icon{margin-left:5px;font-size:12px;opacity:0.7}
    .actions button{margin:0 4px;padding:6px 8px;border-radius:6px;border:none;cursor:pointer}
    .edit{background:rgba(0,173,181,0.12);color:#00adb5}
    .del{background:rgba(255,80,80,0.12);color:#ff6b6b}
    .hist{background:rgba(255,193,7,0.12);color:#ffc107}
    .no-results{text-align:center;padding:20px;color:#ff6b6b;font-style:italic}
    /* modal history box */
    .history-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;z-index:2000}
    .history-card{width:680px;max-width:95%;background:#0b1720;padding:18px;border-radius:10px;border:1px solid rgba(255,255,255,0.04)}
    .history-card h3{color:#ffc107;margin:0 0 10px}
    .history-list{max-height:320px;overflow:auto;margin-top:8px}
    .history-row{display:flex;gap:10px;padding:8px;border-bottom:1px solid rgba(255,255,255,0.03)}
    .action-added{color:#28a745;font-weight:700}
    .action-edited{color:#17a2b8;font-weight:700}
    .action-deleted{color:#dc3545;font-weight:700}
  </style>
  </head>
  <body>
  <div class="wrap">
    <h1>📦 Current Stocks</h1>
    
    <div class="controls">
      <button class="primary" onclick="openAdd()">+ Add Stock</button>
      <button class="primary" onclick="loadStocks()">Refresh</button>
      <button class="primary" onclick="location.href='outgoing_dashboard.php'">Go to Outgoing</button>
    </div>

    <!-- Search and Sort Container -->
    <div class="search-sort-container">
      <div class="search-box">
        <input type="text" id="searchInput" placeholder="🔍 Search products..." onkeyup="filterStocks()">
        <div class="search-icon">🔍</div>
      </div>
      
      <select class="sort-dropdown" id="sortSelect" onchange="sortStocks()">
        <option value="name_asc">Sort by: Name (A-Z)</option>
        <option value="name_desc">Sort by: Name (Z-A)</option>
        <option value="quantity_asc">Sort by: Quantity (Low to High)</option>
        <option value="quantity_desc">Sort by: Quantity (High to Low)</option>
        <option value="date_desc">Sort by: Date (Newest First)</option>
        <option value="date_asc">Sort by: Date (Oldest First)</option>
      </select>
    </div>

    <table id="stocks-table" aria-live="polite">
      <thead>
        <tr>
          <th class="sortable" onclick="sortByColumn('name')"># <span class="sort-icon">▼</span></th>
          <th class="sortable" onclick="sortByColumn('name')">Product Name <span class="sort-icon">▼</span></th>
          <th class="sortable" onclick="sortByColumn('quantity')">Quantity <span class="sort-icon">▼</span></th>
          <th class="sortable" onclick="sortByColumn('date')">Last Updated <span class="sort-icon">▼</span></th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody id="stocks-tbody"></tbody>
    </table>
  </div>

  <!-- History Modal -->
  <div id="historyModal" class="history-modal" role="dialog" aria-hidden="true">
    <div class="history-card">
      <h3 id="historyTitle">History</h3>
      <div class="history-list" id="historyList"></div>
      <div style="text-align:right;margin-top:10px">
        <button class="primary" onclick="closeHistory()">Close</button>
      </div>
    </div>
  </div>

  <script>
  /* Utility: safe text */
  function escapeHTML(s){ return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":"&#39;"}[c])); }

  let allStocks = [];
  let currentSort = 'name_asc';

  /* load stocks and render */
  async function loadStocks(){
    const res = await fetch('api/get_stocks.php');
    const data = await res.json();
    allStocks = data;
    renderStocks();
  }

  /* Render stocks with current filter and sort */
  function renderStocks() {
    const tbody = document.querySelector('#stocks-tbody');
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    
    // Filter stocks
    let filteredStocks = allStocks.filter(stock => 
      stock.product_name.toLowerCase().includes(searchTerm)
    );

    // Sort stocks
    filteredStocks = sortStocksArray(filteredStocks, currentSort);

    tbody.innerHTML = '';
    
    if(filteredStocks.length === 0) {
      tbody.innerHTML = '<tr><td colspan="5" class="no-results">No products found matching your search.</td></tr>';
      return;
    }

    filteredStocks.forEach((r,i) => {
      tbody.insertAdjacentHTML('beforeend', `
        <tr data-id="${r.id}">
          <td>${i+1}</td>
          <td>${escapeHTML(r.product_name)}</td>
          <td>${r.quantity}</td>
          <td>${r.created_at}</td>
          <td class="actions">
            <button class="edit" onclick="openEdit(${r.id},'${escapeHTML(r.product_name)}',${r.quantity})">✏️ Edit</button>
            <button class="hist" onclick="openHistory(${r.id},'${escapeHTML(r.product_name)}')">🕓 History</button>
            <button class="del" onclick="deleteStock(${r.id},'${escapeHTML(r.product_name)}')">🗑️ Delete</button>
          </td>
        </tr>`);
    });
  }

  /* Sort stocks array */
  function sortStocksArray(stocks, sortType) {
    return [...stocks].sort((a, b) => {
      switch(sortType) {
        case 'name_asc':
          return a.product_name.localeCompare(b.product_name);
        case 'name_desc':
          return b.product_name.localeCompare(a.product_name);
        case 'quantity_asc':
          return a.quantity - b.quantity;
        case 'quantity_desc':
          return b.quantity - a.quantity;
        case 'date_desc':
          return new Date(b.created_at) - new Date(a.created_at);
        case 'date_asc':
          return new Date(a.created_at) - new Date(b.created_at);
        default:
          return 0;
      }
    });
  }

  /* Filter stocks based on search input */
  function filterStocks() {
    renderStocks();
  }

  /* Sort stocks when dropdown changes */
  function sortStocks() {
    currentSort = document.getElementById('sortSelect').value;
    renderStocks();
  }

  /* Sort by clicking table headers */
  function sortByColumn(column) {
    let newSort = '';
    
    if (column === 'name') {
      newSort = currentSort === 'name_asc' ? 'name_desc' : 'name_asc';
    } else if (column === 'quantity') {
      newSort = currentSort === 'quantity_asc' ? 'quantity_desc' : 'quantity_asc';
    } else if (column === 'date') {
      newSort = currentSort === 'date_asc' ? 'date_desc' : 'date_asc';
    }
    
    document.getElementById('sortSelect').value = newSort;
    currentSort = newSort;
    renderStocks();
  }

  /* ADD -> SweetAlert */
  function openAdd(){
    Swal.fire({
      title: 'Add stock',
      html: '<input id="sa-name" class="swal2-input" placeholder="Product name">' +
            '<input id="sa-qty" type="number" class="swal2-input" placeholder="Quantity" min="1">',
      confirmButtonText: 'Add',
      showCancelButton: true,
      preConfirm: () => {
        const name = document.getElementById('sa-name').value.trim();
        const qty = parseInt(document.getElementById('sa-qty').value);
        if(!name || !qty || qty<=0){ Swal.showValidationMessage('Enter valid name & qty'); return false; }
        return { name, qty };
      }
    }).then(async (res) => {
      if(res.isConfirmed){
        const body = new URLSearchParams();
        body.append('product_name', res.value.name);
        body.append('quantity', res.value.qty);
        const r = await fetch('api/add_stock.php', { method:'POST', body });
        const json = await r.json();
        Swal.fire(json.status==='success' ? 'Added' : 'Error', json.message, json.status==='success' ? 'success' : 'error');
        if(json.status==='success') loadStocks();
      }
    });
  }

  /* EDIT -> SweetAlert number input */
  function openEdit(id, name, qty){
    Swal.fire({
      title: `Edit: ${name}`,
      input: 'number',
      inputValue: qty,
      showCancelButton: true,
      confirmButtonText: 'Update',
      preConfirm: (v) => {
        const n = parseInt(v);
        if(isNaN(n) || n < 0) { Swal.showValidationMessage('Enter valid quantity'); return false; }
        return n;
      }
    }).then(async (res) => {
      if(res.isConfirmed){
        const body = new URLSearchParams();
        body.append('id', id);
        body.append('quantity', res.value);
        const r = await fetch('api/edit_stock.php', { method:'POST', body });
        const json = await r.json();
        Swal.fire(json.status==='success' ? 'Updated' : 'Error', json.message, json.status==='success' ? 'success' : 'error');
        if(json.status==='success') loadStocks();
      }
    });
  }

  /* DELETE */
  function deleteStock(id, name){
    Swal.fire({
      title: `Delete ${name}?`,
      text: "This cannot be undone.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes, delete'
    }).then(async (res) => {
      if(res.isConfirmed){
        const body = new URLSearchParams();
        body.append('id', id);
        const r = await fetch('api/delete_stock.php',{method:'POST', body});
        const json = await r.json();
        Swal.fire(json.status==='success' ? 'Deleted' : 'Error', json.message, json.status==='success' ? 'success' : 'error');
        if(json.status==='success') loadStocks();
      }
    });
  }

  /* HISTORY modal for a single product */
  async function openHistory(product_id, product_name){
    document.getElementById('historyTitle').textContent = `History — ${product_name}`;
    document.getElementById('historyList').innerHTML = '<div style="padding:12px">Loading…</div>';
    document.getElementById('historyModal').style.display = 'flex';

    const res = await fetch('api/get_product_history.php?id=' + product_id);
    const data = await res.json();
    if(!data || data.length===0){
      document.getElementById('historyList').innerHTML = '<div style="padding:12px">No history found for this product.</div>';
      return;
    }
    const list = data.map(h => {
      const cls = (h.action.toLowerCase().includes('add') ? 'action-added' : (h.action.toLowerCase().includes('edit') ? 'action-edited' : 'action-deleted'));
      return `<div class="history-row">
        <div style="flex:1"><span class="${cls}">${escapeHTML(h.action)}</span></div>
        <div style="width:120px;text-align:right">${h.quantity}</div>
        <div style="width:200px;text-align:right">${h.timestamp}</div>
      </div>`;
    }).join('');
    document.getElementById('historyList').innerHTML = list;
  }

  function closeHistory(){ 
    document.getElementById('historyModal').style.display = 'none'; 
  }

  // Initialize on page load
  loadStocks();

  // Add keyboard shortcut for search (Ctrl+F)
  document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
      e.preventDefault();
      document.getElementById('searchInput').focus();
    }
  });
  </script>
  </body>
  </html>