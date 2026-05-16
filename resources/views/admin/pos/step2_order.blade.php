<div class="progga-pos-screen progga-pos-order-screen" id="posStep2">
    <div class="progga-pos-cats" id="posCatList">
      <div class="progga-pos-cat-item active" data-cat-id=""><div class="progga-pos-cat-emoji">🍽️</div><div class="progga-pos-cat-name">All Items</div></div>
      @foreach($categories as $cat)
      <div class="progga-pos-cat-item" data-cat-id="{{ $cat->id }}"><div class="progga-pos-cat-emoji">🥘</div><div class="progga-pos-cat-name">{{ $cat->name }}</div></div>
      @endforeach
    </div>

    <div class="progga-pos-food-panel">
      <div class="progga-pos-food-toolbar">
        <div class="progga-pos-table-indicator"><i class="bi bi-layout-wtf"></i> Table: <strong id="posSelectedTableMeta">—</strong></div>
        <div class="progga-search progga-pos-food-search-wrap">
          <i class="bi bi-search progga-search-icon"></i>
          <input type="text" class="progga-form-control" id="posFoodSearch" placeholder="Search food items...">
        </div>
        <button class="progga-btn progga-btn-outline progga-btn-sm" id="posBackToTables" type="button"><i class="bi bi-arrow-left"></i> Change Table</button>
      </div>

      <div class="progga-pos-food-grid" id="posFoodGrid">
          </div>

      <div class="pos-cart-fab" id="posCartFab" style="display: none;">
        <span class="pos-cart-fab-count" id="fabCartCount">0</span>
        <span class="pos-cart-fab-label">View Cart</span>
        <span class="pos-cart-fab-total" id="fabCartTotal">৳0.00</span>
        <i class="bi bi-chevron-up"></i>
      </div>
    </div>

    <div class="progga-pos-cart">
      <div class="pos-cart-handle-bar d-md-none"></div>

      <div class="progga-pos-cart-header">
        <div class="progga-pos-cart-title">
            <i class="bi bi-receipt"></i> Current Order <span class="badge bg-light text-dark ms-2" id="headerCartCount" style="display:none; font-size: 12px;">0</span>
        </div>
        <button class="pos-cart-mobile-close d-md-none" id="posMobileCartClose" type="button" style="background: none; border: none; color: white;"><i class="bi bi-x-lg"></i></button>
      </div>

      <div class="pos-cart-info" id="posCartMeta">
        <div class="pos-ci-row1">
          <span class="pos-ci-type" id="metaType">Dine-In</span>
        </div>
        <div class="pos-ci-row2">
          <div class="pos-ci-customer">
            <div class="pos-ci-avatar"><i class="bi bi-person-fill"></i></div>
            <span class="pos-ci-name" id="metaCustomer">Walk-in Customer</span>
          </div>
          <span class="pos-ci-waiter" id="metaWaiter"><i class="bi bi-person-badge"></i> Unassigned</span>
        </div>
      </div>

      <div id="posCartBody" style="display:flex; flex-direction:column; flex:1; overflow-y:auto;">
          </div>
    </div>
</div>

<div class="pos-mobile-backdrop" id="posMobileBackdrop" style="display: none;"></div>
