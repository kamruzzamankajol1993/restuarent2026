<?php $current = basename($_SERVER['PHP_SELF']); ?>
<aside class="progga-sidebar" id="proggaSidebar">
  <div class="progga-sidebar-brand">
   <div class="progga-brand-logo" style="overflow: hidden; display: flex; align-items: center; justify-content: center;">
        @if(!empty($restaurantSettingIconName))
            <img src="{{ asset('public/'.$restaurantSettingIconName) }}" alt="Icon" style="width: 100%; height: 100%; object-fit: contain;">
        @else
            {{ strtoupper(substr($restaurantSettingName ?? 'P', 0, 1)) }}
        @endif
    </div>
    <div>
      <div class="progga-brand-name">{{ $restaurantSettingName ?? 'Progga RMS' }}</div>
      <div class="progga-brand-tagline">Restaurant System</div>
    </div>
  </div>
  <nav class="progga-sidebar-nav">
    <div class="progga-nav-section"><div class="progga-nav-section-label">Overview</div></div>
     @can('dashboard-view')
    <div class="progga-nav-item" >
        <a class="progga-nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">
            <i class="bi bi-grid-1x2-fill progga-nav-icon"></i><span>Dashboard</span>
        </a>
    </div>
    @endcan
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='pos.php'?'active':'' ?>" href="pos.php"><i class="bi bi-display progga-nav-icon"></i><span>POS System</span><span class="progga-nav-badge">LIVE</span></a></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='kitchen.php'?'active':'' ?>" href="kitchen.php"><i class="bi bi-fire progga-nav-icon"></i><span>Kitchen Board</span></a></div>
    <div class="progga-nav-section" ><div class="progga-nav-section-label">Menu</div></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='food-category.php'?'active':'' ?>" href="food-category.php"><i class="bi bi-tags-fill progga-nav-icon"></i><span>Food Categories</span></a></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='food-subcategory.php'?'active':'' ?>" href="food-subcategory.php"><i class="bi bi-diagram-3-fill progga-nav-icon"></i><span>Subcategories</span></a></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='cuisine-type.php'?'active':'' ?>" href="cuisine-type.php"><i class="bi bi-globe progga-nav-icon"></i><span>Cuisine Types</span></a></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='food-menu.php'||$current==='add-food.php'?'active':'' ?>" href="food-menu.php"><i class="bi bi-journal-richtext progga-nav-icon"></i><span>Food Menu</span></a></div>
    <div class="progga-nav-section" data-roles="super_admin,admin,manager,cashier,waiter"><div class="progga-nav-section-label">Operations</div></div>
   @can('table-view')
<div class="progga-nav-item">
    <a class="progga-nav-link {{ request()->routeIs('table.*') ? 'active' : '' }}" href="{{ route('table.index') }}">
        <i class="bi bi-table progga-nav-icon"></i><span>Table Management</span>
    </a>
</div>
@endcan
   @can('table-booking-view')
    <div class="progga-nav-item">
        <a class="progga-nav-link {{ request()->routeIs('table-booking.*') ? 'active' : '' }}" href="{{ route('table-booking.index') }}">
            <i class="bi bi-calendar-check-fill progga-nav-icon"></i>
            <span>Table Booking</span>

            @php
                $upcomingCount = \App\Models\TableBooking::where('status', 'upcoming')->count();
            @endphp
            @if($upcomingCount > 0)
                <span class="badge bg-danger rounded-pill ms-auto" style="font-size: 10px;">{{ $upcomingCount }}</span>
            @endif
        </a>
    </div>
    @endcan
   @can('customer-view')
    <div class="progga-nav-item">
        <a class="progga-nav-link {{ request()->routeIs('customer.*') || request()->routeIs('reward-points.*') ? 'active' : '' }}" href="{{ route('customer.index') }}">
            <i class="bi bi-people-fill progga-nav-icon"></i><span>Customers</span>
        </a>
    </div>
    @endcan
   @can('waiter-view')
    <div class="progga-nav-item">
        <a class="progga-nav-link {{ request()->routeIs('waiter.*') ? 'active' : '' }}" href="{{ route('waiter.index') }}">
            <i class="bi bi-person-badge-fill progga-nav-icon"></i><span>Waiters</span>
        </a>
    </div>
    @endcan
    <div class="progga-nav-section" ><div class="progga-nav-section-label">Analytics</div></div>
    <div class="progga-nav-item" ><a class="progga-nav-link <?= $current==='reports.php'?'active':'' ?>" href="reports.php"><i class="bi bi-bar-chart-fill progga-nav-icon"></i><span>Reports</span></a></div>
    <div class="progga-nav-section" ><div class="progga-nav-section-label">System</div></div>

    @can('systemsetting-view')
    <div class="progga-nav-item">
    <a class="progga-nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
        <i class="bi bi-gear-fill progga-nav-icon"></i><span>Settings</span>
    </a>
</div>
@endcan
@can('profile-view')
<div class="progga-nav-item">
    <a class="progga-nav-link {{ request()->routeIs('profile.edit') ? 'active' : '' }}" href="{{ route('profile.edit') }}">
        <i class="bi bi-person-circle progga-nav-icon"></i><span>My Profile</span>
    </a>
</div>
@endcan

@can('user-view')
<div class="progga-nav-item">
    <a class="progga-nav-link {{ request()->routeIs('user.*') ? 'active' : '' }}" href="{{ route('user.index') }}">
        <i class="bi bi-people-fill progga-nav-icon"></i><span>User Management</span>
    </a>
</div>
@endcan
    @can('permission-view')
<div class="progga-nav-item">
    <a class="progga-nav-link {{ request()->routeIs('permission.*') ? 'active' : '' }}" href="{{ route('permission.index') }}">
        <i class="bi bi-shield-lock-fill progga-nav-icon"></i><span>Permissions</span>
    </a>
</div>
@endcan

@can('role-view')
    <div class="progga-nav-item">
        <a class="progga-nav-link {{ request()->routeIs('role.*') ? 'active' : '' }}" href="{{ route('role.index') }}">
            <i class="bi bi-person-lines-fill progga-nav-icon"></i><span>Role Management</span>
        </a>
    </div>
    @endcan
  </nav>
  <div class="progga-sidebar-footer">
    <div class="progga-sidebar-user" onclick="window.location='{{ route('profile.edit') }}'">
      <img src="{{ auth()->user()->image ? asset('public/' . auth()->user()->image) : 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=21352a&color=d5aa65&size=68' }}" class="progga-user-avatar" alt="User">
      <div>
        <div class="progga-user-name">{{ auth()->user()->name }}</div>
        <div class="progga-user-role">{{ auth()->user()->getRoleNames()->first() ?? 'No Role' }}</div>
      </div>
    </div>
  </div>
</aside>
