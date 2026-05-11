<footer class="progga-footer" id="proggaFooter">
  <div class="progga-footer-inner">
    <div class="progga-footer-brand">
      <div class="progga-footer-logo">P</div>
      <span class="progga-footer-name">Progga RMS</span>
      <span class="progga-footer-version">v1.0.0</span>
    </div>
    <div class="progga-footer-copy">
      &copy; {{ date('Y') }} Progga Restaurant Management System &mdash; All rights reserved.
    </div>
    <div class="progga-footer-links">
        @can('dashboard-view')
      <a href="{{ route('home') }}" class="progga-footer-link"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
      @endcan
      <a href="reports.php" class="progga-footer-link"><i class="bi bi-bar-chart-fill"></i> Reports</a>
      @can('systemsetting-view')
      <a href="{{ route('settings.index') }}" class="progga-footer-link"><i class="bi bi-gear-fill"></i> Settings</a>
      @endcan
      @can('profile-view')
      <a href="{{ route('profile.edit') }}" class="progga-footer-link"><i class="bi bi-person-circle"></i> Profile</a>
      @endcan
    </div>
  </div>
</footer>
