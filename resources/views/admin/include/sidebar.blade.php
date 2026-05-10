<?php $current = basename($_SERVER['PHP_SELF']); ?>
<aside class="progga-sidebar" id="proggaSidebar">
  <div class="progga-sidebar-brand">
    <div class="progga-brand-logo">P</div>
    <div>
      <div class="progga-brand-name">Progga RMS</div>
      <div class="progga-brand-tagline">Restaurant System</div>
    </div>
  </div>
  <nav class="progga-sidebar-nav">
    <div class="progga-nav-section"><div class="progga-nav-section-label">Overview</div></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='dashboard.php'?'active':'' ?>" href="dashboard.php"><i class="bi bi-grid-1x2-fill progga-nav-icon"></i><span>Dashboard</span></a></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='pos.php'?'active':'' ?>" href="pos.php"><i class="bi bi-display progga-nav-icon"></i><span>POS System</span><span class="progga-nav-badge">LIVE</span></a></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='kitchen.php'?'active':'' ?>" href="kitchen.php"><i class="bi bi-fire progga-nav-icon"></i><span>Kitchen Board</span></a></div>
    <div class="progga-nav-section" ><div class="progga-nav-section-label">Menu</div></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='food-category.php'?'active':'' ?>" href="food-category.php"><i class="bi bi-tags-fill progga-nav-icon"></i><span>Food Categories</span></a></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='food-subcategory.php'?'active':'' ?>" href="food-subcategory.php"><i class="bi bi-diagram-3-fill progga-nav-icon"></i><span>Subcategories</span></a></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='cuisine-type.php'?'active':'' ?>" href="cuisine-type.php"><i class="bi bi-globe progga-nav-icon"></i><span>Cuisine Types</span></a></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='food-menu.php'||$current==='add-food.php'?'active':'' ?>" href="food-menu.php"><i class="bi bi-journal-richtext progga-nav-icon"></i><span>Food Menu</span></a></div>
    <div class="progga-nav-section" data-roles="super_admin,admin,manager,cashier,waiter"><div class="progga-nav-section-label">Operations</div></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='table-management.php'?'active':'' ?>" href="table-management.php"><i class="bi bi-layout-wtf progga-nav-icon"></i><span>Tables</span></a></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='table-booking.php'?'active':'' ?>" href="table-booking.php"><i class="bi bi-calendar-check-fill progga-nav-icon"></i><span>Table Booking</span></a></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='customers.php'?'active':'' ?>" href="customers.php"><i class="bi bi-people-fill progga-nav-icon"></i><span>Customers</span></a></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='waiters.php'?'active':'' ?>" href="waiters.php"><i class="bi bi-person-badge-fill progga-nav-icon"></i><span>Waiters</span></a></div>
    <div class="progga-nav-section" ><div class="progga-nav-section-label">Analytics</div></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='reports.php'?'active':'' ?>" href="reports.php"><i class="bi bi-bar-chart-fill progga-nav-icon"></i><span>Reports</span></a></div>
    <div class="progga-nav-section" ><div class="progga-nav-section-label">System</div></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='settings.php'?'active':'' ?>" href="settings.php"><i class="bi bi-gear-fill progga-nav-icon"></i><span>Settings</span></a></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='profile.php'?'active':'' ?>" href="profile.php"><i class="bi bi-person-circle progga-nav-icon"></i><span>My Profile</span></a></div>
  </nav>
  <div class="progga-sidebar-footer">
    <div class="progga-sidebar-user" onclick="window.location='profile.php'">
      <img src="https://ui-avatars.com/api/?name=Admin+User&background=21352a&color=d5aa65&size=68" class="progga-user-avatar" alt="User">
      <div>
        <div class="progga-user-name">Admin User</div>
        <div class="progga-user-role">Super Admin</div>
      </div>
    </div>
  </div>
</aside>
