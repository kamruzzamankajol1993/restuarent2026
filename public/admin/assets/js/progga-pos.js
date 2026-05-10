/**
 * Progga RMS — POS System
 * progga-pos.js
 * UI interactions only — data is inline in HTML (backend will wire real logic).
 */

(function () {
  'use strict';

  function fmt(n) { return '৳' + parseFloat(n).toFixed(2); }

  /* ─── Step navigation ─── */
  function goToStep(n) {
    document.querySelectorAll('.progga-pos-screen').forEach(function (s) { s.classList.remove('active'); });
    var screen = document.getElementById('posStep' + n);
    if (screen) screen.classList.add('active');
    document.querySelectorAll('.progga-pos-step').forEach(function (el) {
      var sn = parseInt(el.dataset.step);
      el.classList.remove('active', 'done');
      if (sn === n) el.classList.add('active');
      if (sn < n)  el.classList.add('done');
    });
  }

  /* ─── Table filter buttons ─── */
  function initTableFilters() {
    document.querySelectorAll('.progga-pos-filter-btn[data-table-filter]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.querySelectorAll('.progga-pos-filter-btn[data-table-filter]').forEach(function (b) { b.classList.remove('active'); });
        btn.classList.add('active');
        var filter = btn.dataset.tableFilter;
        document.querySelectorAll('.progga-pos-table-card').forEach(function (card) {
          card.style.display = (filter === 'all' || card.dataset.status === filter) ? '' : 'none';
        });
      });
    });

    /* Available / reserved → select table, go to step 2 */
    document.querySelectorAll('.progga-pos-table-card.available, .progga-pos-table-card.reserved').forEach(function (card) {
      card.addEventListener('click', function () {
        var num = card.dataset.tableNum;
        setTableLabel(num);
        goToStep(2);
      });
    });

    /* Occupied → offcanvas */
    document.querySelectorAll('.progga-pos-table-card.occupied').forEach(function (card) {
      card.addEventListener('click', function () {
        var order = JSON.parse(card.dataset.order);
        openOccupiedOffcanvas(card.dataset.tableNum, card.dataset.zone, card.dataset.capacity, order);
      });
    });
  }

  function setTableLabel(num) {
    var el = document.getElementById('posSelectedTable');
    var ct = document.getElementById('posCartTable');
    var pl = document.getElementById('payTableLabel');
    if (el) el.textContent = num;
    if (ct) ct.textContent = num;
    if (pl) pl.textContent = num;
  }

  /* ─── Category filter ─── */
  function initCategoryFilter() {
    document.querySelectorAll('.progga-pos-cat-item').forEach(function (item) {
      item.addEventListener('click', function () {
        document.querySelectorAll('.progga-pos-cat-item').forEach(function (i) { i.classList.remove('active'); });
        item.classList.add('active');
        var catId = item.dataset.catId;
        var search = document.getElementById('posFoodSearch');
        if (search) search.value = '';
        showFoodsByCategory(catId);
      });
    });
  }

  function showFoodsByCategory(catId) {
    document.querySelectorAll('#posFoodGrid .progga-pos-food-card').forEach(function (card) {
      card.style.display = (card.dataset.catId === catId) ? '' : 'none';
    });
  }

  /* ─── Food search ─── */
  function initFoodSearch() {
    var input = document.getElementById('posFoodSearch');
    if (!input) return;
    input.addEventListener('input', function () {
      var q = input.value.toLowerCase().trim();
      if (!q) {
        var active = document.querySelector('.progga-pos-cat-item.active');
        showFoodsByCategory(active ? active.dataset.catId : '1');
        return;
      }
      document.querySelectorAll('#posFoodGrid .progga-pos-food-card').forEach(function (card) {
        var name = card.querySelector('.progga-pos-food-name');
        card.style.display = (name && name.textContent.toLowerCase().includes(q)) ? '' : 'none';
      });
    });
  }

  /* ─── Back button ─── */
  function initBackButtons() {
    var back = document.getElementById('posBackToTables');
    if (back) back.addEventListener('click', function () { goToStep(1); });
  }

  /* ─── Pay button → payment modal ─── */
  function initPayBtn() {
    var btn = document.getElementById('posGoPayment');
    if (!btn) return;
    btn.addEventListener('click', function () {
      var modal = document.getElementById('paymentModal');
      if (modal) {
        var tableEl = document.getElementById('posSelectedTable');
        var payLabel = document.getElementById('payTableLabel');
        if (tableEl && payLabel) payLabel.textContent = tableEl.textContent;
        new bootstrap.Modal(modal).show();
      }
    });
  }

  /* ─── Occupied table offcanvas ─── */
  function openOccupiedOffcanvas(tableNum, zone, capacity, order) {
    document.getElementById('ocTableNum').textContent  = tableNum;
    document.getElementById('ocTableMeta').textContent = zone + ' · ' + capacity + ' seats';
    document.getElementById('ocChips').innerHTML =
      '<span class="progga-oc-chip"><i class="bi bi-receipt"></i> ' + order.orderId + '</span>' +
      '<span class="progga-oc-chip"><i class="bi bi-person"></i> ' + order.waiter + '</span>' +
      '<span class="progga-oc-chip"><i class="bi bi-clock"></i> ' + order.elapsed + ' min</span>';

    var bodyHtml = '<div class="progga-oc-section-label">Current Order</div>';
    var subtotal  = 0;
    order.kots.forEach(function (kot) {
      bodyHtml += '<div class="progga-oc-kot"><div class="progga-oc-kot-head">' +
        '<span class="progga-oc-kot-label">' + kot.id + '</span>' +
        '<span class="progga-oc-kot-time"><i class="bi bi-clock me-1"></i>' + kot.time + '</span></div>';
      kot.items.forEach(function (item) {
        var line = item.qty * item.price;
        subtotal += line;
        bodyHtml += '<div class="progga-oc-item">' +
          '<span class="progga-oc-item-name">' + item.name + '</span>' +
          '<span class="progga-oc-item-qty">×' + item.qty + '</span>' +
          '<span class="progga-oc-item-price">' + fmt(line) + '</span></div>';
      });
      bodyHtml += '</div>';
    });
    document.getElementById('ocBody').innerHTML = bodyHtml;

    var tax   = subtotal * 0.05;
    var total = subtotal + tax;
    document.getElementById('ocTotals').innerHTML =
      '<div class="progga-oc-total-row"><span>Subtotal</span><span>' + fmt(subtotal) + '</span></div>' +
      '<div class="progga-oc-total-row"><span>Tax (5%)</span><span>' + fmt(tax) + '</span></div>' +
      '<div class="progga-oc-total-row grand"><span>Total</span><span>' + fmt(total) + '</span></div>';

    var oc = bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('tableOrderOffcanvas'));

    document.getElementById('ocAddMoreBtn').onclick = function () {
      oc.hide();
      setTableLabel(tableNum);
      goToStep(2);
    };

    document.getElementById('ocPayBtn').onclick = function () {
      oc.hide();
      setTableLabel(tableNum);
      var modal = document.getElementById('paymentModal');
      if (modal) new bootstrap.Modal(modal).show();
    };

    oc.show();
  }

  /* ─── Init ─── */
  document.addEventListener('DOMContentLoaded', function () {
    if (!document.getElementById('posStep1')) return;
    showFoodsByCategory('1');
    goToStep(1);
    initTableFilters();
    initCategoryFilter();
    initFoodSearch();
    initBackButtons();
    initPayBtn();
  });

})();
