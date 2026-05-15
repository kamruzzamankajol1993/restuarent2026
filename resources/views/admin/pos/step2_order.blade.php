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
    </div>

    <div class="progga-pos-cart">
      <div class="progga-pos-cart-header">
        <div class="progga-pos-cart-title"><i class="bi bi-receipt"></i> Current Order</div>
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
      <div id="posCartBody" style="display:flex; flex-direction:column; flex:1;">
          </div>
    </div>
</div>
