@extends('admin.master.master')
@section('title', 'Settings — ' . ($restaurantSettingName ?? 'Progga RMS'))

@section('body')
<main class="progga-content">
    <div class="progga-page-header">
        <div>
            <h1 class="progga-page-title">Settings</h1>
            <div class="progga-breadcrumb">
                <a href="{{ route('home') }}" class="progga-breadcrumb-item">Dashboard</a>
                <span class="progga-breadcrumb-sep">/</span>
                <span class="progga-breadcrumb-item active">Settings</span>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="font-size:13px;"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}</div>
    @endif

    <div class="progga-tab-nav">
        <div class="progga-tab-item active" data-settings-tab="restaurant">Restaurant Info</div>
        <div class="progga-tab-item" data-settings-tab="tax">Tax &amp; Billing</div>
        <div class="progga-tab-item" data-settings-tab="invoice">Invoice Settings</div>
        <div class="progga-tab-item" data-settings-tab="pos">POS Preferences</div>
        <div class="progga-tab-item" data-settings-tab="roles">User Roles</div>
    </div>

    <div id="settingsRestaurant">
        <div class="progga-card">
            <div class="progga-card-header"><div class="progga-card-title"><i class="bi bi-shop me-2"></i>Restaurant Information</div></div>
            <div class="progga-card-body">
                <form action="{{ route('settings.restaurant') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-8">
                            <div class="row g-3">
                                <div class="col-md-6"><div class="progga-form-group"><label class="progga-form-label">Restaurant Name <span class="progga-required">*</span></label><input type="text" name="name" class="progga-form-control" value="{{ $restaurant->name ?? 'Progga Restaurant' }}" required></div></div>
                                <div class="col-md-6"><div class="progga-form-group"><label class="progga-form-label">Phone Number</label><input type="tel" name="phone" class="progga-form-control" value="{{ $restaurant->phone ?? '' }}"></div></div>
                                <div class="col-md-6"><div class="progga-form-group"><label class="progga-form-label">Email Address</label><input type="email" name="email" class="progga-form-control" value="{{ $restaurant->email ?? '' }}"></div></div>
                                <div class="col-md-6"><div class="progga-form-group"><label class="progga-form-label">Website</label><input type="url" name="website" class="progga-form-control" value="{{ $restaurant->website ?? '' }}"></div></div>

                                <div class="col-md-6">
    <div class="progga-form-group">
        <label class="progga-form-label">App Link</label>
        <input type="url" name="app_link" class="progga-form-control" value="{{ $restaurant->app_link ?? '' }}" placeholder="https://play.google.com/store/apps/...">
    </div>
</div>

                                <div class="col-md-6">
                                    <div class="progga-form-group">
                                        <label class="progga-form-label">Restaurant Icon (.png)</label>
                                        <div class="d-flex align-items-center gap-3">
                                            <div style="flex-grow: 1;">
                                                <input type="file" name="icon_name" class="progga-form-control" accept=".png" onchange="previewIcon(this)">
                                            </div>
                                            <div>
                                                <img id="iconPreview" src="{{ isset($restaurant->icon_name) ? asset('public/'.$restaurant->icon_name) : '' }}"
                                                     style="display:{{ isset($restaurant->icon_name) ? 'block' : 'none' }}; width:42px; height:42px; object-fit:contain; border:1px solid var(--progga-border-light); background:#f4f6f5; padding: 2px;" alt="Icon">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6"><div class="progga-form-group"><label class="progga-form-label">Currency</label>
                                    <select name="currency" class="progga-select">
                                        <option value="BDT" {{ ($restaurant->currency ?? '') == 'BDT' ? 'selected' : '' }}>BDT — Bangladeshi Taka (৳)</option>
                                        <option value="USD" {{ ($restaurant->currency ?? '') == 'USD' ? 'selected' : '' }}>USD — US Dollar ($)</option>
                                    </select>
                                </div></div>

                                <div class="col-12"><div class="progga-form-group"><label class="progga-form-label">Address</label><textarea name="address" class="progga-form-control progga-form-textarea" rows="2">{{ $restaurant->address ?? '' }}</textarea></div></div>
                                <div class="col-md-6"><div class="progga-form-group"><label class="progga-form-label">Opening Time</label><input type="time" name="opening_time" class="progga-form-control" value="{{ $restaurant->opening_time ?? '08:00' }}"></div></div>
                                <div class="col-md-6"><div class="progga-form-group"><label class="progga-form-label">Closing Time</label><input type="time" name="closing_time" class="progga-form-control" value="{{ $restaurant->closing_time ?? '23:00' }}"></div></div>
                            </div>
                        </div>
                        <div class="col-md-4">
    <div class="progga-form-group">
        <label class="progga-form-label">Restaurant Logo</label>
        <div class="progga-upload-zone" style="padding:24px;">
            <div class="progga-upload-icon"><i class="bi bi-building"></i></div>
            <div class="progga-upload-text">Click to upload logo<br><span>PNG, JPG, SVG</span></div>
            <input type="file" name="logo" id="logoInput" accept="image/*" style="display:none;" onchange="previewLogo(this)">
        </div>
                                <img id="logoPreview" src="{{ isset($restaurant->logo) ? asset('public/'.$restaurant->logo) : '' }}" style="display:{{ isset($restaurant->logo) ? 'block' : 'none' }}; margin-top:10px; width:100px; height:100px; border-radius:8px; object-fit:contain; background:#f4f6f5;" alt="Logo">
                            </div>
                        </div>
                        <div class="col-12" style="padding-top:8px;">
                            <button type="submit" class="progga-btn progga-btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="settingsTax" style="display:none;">
        <div class="progga-card">
            <div class="progga-card-header"><div class="progga-card-title"><i class="bi bi-percent me-2"></i>Tax Configuration</div></div>
            <div class="progga-card-body">
                <form action="{{ route('settings.tax') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4"><div class="progga-form-group"><label class="progga-form-label">VAT / Tax Rate (%)</label><input type="number" name="vat_rate" class="progga-form-control" value="{{ $tax->vat_rate ?? 5 }}" step="0.01"></div></div>
                        <div class="col-md-4"><div class="progga-form-group"><label class="progga-form-label">Tax Label</label><input type="text" name="tax_label" class="progga-form-control" value="{{ $tax->tax_label ?? 'VAT' }}"></div></div>
                        <div class="col-md-4"><div class="progga-form-group"><label class="progga-form-label">Tax Registration #</label><input type="text" name="tax_registration_no" class="progga-form-control" value="{{ $tax->tax_registration_no ?? '' }}"></div></div>
                        <div class="col-md-6">
                            <div class="progga-form-group">
                                <label class="progga-form-label">Tax Included in Price?</label>
                                <label class="progga-toggle" style="margin-top:8px;">
                                    <input type="checkbox" name="is_tax_included" {{ ($tax->is_tax_included ?? false) ? 'checked' : '' }} data-on="Yes (inclusive)" data-off="No (added at checkout)">
                                    <span class="progga-toggle-track"><span class="progga-toggle-thumb"></span></span>
                                    <span class="progga-toggle-label">{{ ($tax->is_tax_included ?? false) ? 'Yes (inclusive)' : 'No (added at checkout)' }}</span>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6"><div class="progga-form-group"><label class="progga-form-label">Service Charge (%)</label><input type="number" name="service_charge" class="progga-form-control" value="{{ $tax->service_charge ?? 0 }}" step="0.01"></div></div>
                        <div class="col-12"><button type="submit" class="progga-btn progga-btn-primary"><i class="bi bi-check-lg"></i> Save Tax Settings</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="settingsInvoice" style="display:none;">
        <div class="progga-card">
            <div class="progga-card-header"><div class="progga-card-title"><i class="bi bi-receipt me-2"></i>Invoice Settings</div></div>
            <div class="progga-card-body">
                <form action="{{ route('settings.invoice') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6"><div class="progga-form-group"><label class="progga-form-label">Invoice Prefix</label><input type="text" name="prefix" class="progga-form-control" value="{{ $invoice->prefix ?? 'INV-' }}"></div></div>
                        <div class="col-md-6"><div class="progga-form-group"><label class="progga-form-label">Starting Number</label><input type="number" name="starting_number" class="progga-form-control" value="{{ $invoice->starting_number ?? 1001 }}"></div></div>
                        <div class="col-12"><div class="progga-form-group"><label class="progga-form-label">Invoice Footer Note</label><textarea name="footer_note" class="progga-form-control progga-form-textarea" rows="2">{{ $invoice->footer_note ?? '' }}</textarea></div></div>
                        <div class="col-md-6">
                            <div class="progga-form-group"><label class="progga-form-label">Print Paper Size</label>
                                <select name="paper_size" class="progga-select">
                                    <option value="80mm" {{ ($invoice->paper_size ?? '') == '80mm' ? 'selected' : '' }}>80mm (Thermal)</option>
                                    <option value="A4" {{ ($invoice->paper_size ?? '') == 'A4' ? 'selected' : '' }}>A4</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="progga-form-group">
                                <label class="progga-form-label">Show Logo on Invoice</label>
                                <label class="progga-toggle" style="margin-top:8px;"><input type="checkbox" name="show_logo" {{ ($invoice->show_logo ?? true) ? 'checked' : '' }} data-on="Yes" data-off="No"><span class="progga-toggle-track"><span class="progga-toggle-thumb"></span></span><span class="progga-toggle-label">{{ ($invoice->show_logo ?? true) ? 'Yes' : 'No' }}</span></label>
                            </div>
                        </div>
                        <div class="col-12"><button type="submit" class="progga-btn progga-btn-primary"><i class="bi bi-check-lg"></i> Save</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="settingsPos" style="display:none;">
        <div class="progga-card">
            <div class="progga-card-header"><div class="progga-card-title"><i class="bi bi-display me-2"></i>POS Preferences</div></div>
            <div class="progga-card-body">
                <form action="{{ route('settings.pos') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6"><div class="progga-form-group"><label class="progga-form-label">Default View</label><select name="default_view" class="progga-select"><option value="Grid" {{ ($pos->default_view ?? '') == 'Grid' ? 'selected' : '' }}>Card Grid</option><option value="List" {{ ($pos->default_view ?? '') == 'List' ? 'selected' : '' }}>List View</option></select></div></div>
                        <div class="col-md-6"><div class="progga-form-group"><label class="progga-form-label">Items per Page</label><select name="items_per_page" class="progga-select"><option value="12" {{ ($pos->items_per_page ?? 12) == 12 ? 'selected' : '' }}>12</option><option value="24" {{ ($pos->items_per_page ?? 12) == 24 ? 'selected' : '' }}>24</option><option value="48" {{ ($pos->items_per_page ?? 12) == 48 ? 'selected' : '' }}>48</option></select></div></div>
                        <div class="col-md-6"><div class="progga-form-group"><label class="progga-form-label">Auto-print Kitchen Receipt</label><label class="progga-toggle" style="margin-top:8px;"><input type="checkbox" name="auto_print_kitchen" {{ ($pos->auto_print_kitchen ?? true) ? 'checked' : '' }} data-on="Enabled" data-off="Disabled"><span class="progga-toggle-track"><span class="progga-toggle-thumb"></span></span><span class="progga-toggle-label">{{ ($pos->auto_print_kitchen ?? true) ? 'Enabled' : 'Disabled' }}</span></label></div></div>
                        <div class="col-md-6"><div class="progga-form-group"><label class="progga-form-label">Auto-print Final Invoice</label><label class="progga-toggle" style="margin-top:8px;"><input type="checkbox" name="auto_print_invoice" {{ ($pos->auto_print_invoice ?? true) ? 'checked' : '' }} data-on="Enabled" data-off="Disabled"><span class="progga-toggle-track"><span class="progga-toggle-thumb"></span></span><span class="progga-toggle-label">{{ ($pos->auto_print_invoice ?? true) ? 'Enabled' : 'Disabled' }}</span></label></div></div>
                        <div class="col-md-6"><div class="progga-form-group"><label class="progga-form-label">Require Table Selection</label><label class="progga-toggle" style="margin-top:8px;"><input type="checkbox" name="require_table_selection" {{ ($pos->require_table_selection ?? true) ? 'checked' : '' }} data-on="Yes" data-off="No"><span class="progga-toggle-track"><span class="progga-toggle-thumb"></span></span><span class="progga-toggle-label">{{ ($pos->require_table_selection ?? true) ? 'Yes' : 'No' }}</span></label></div></div>
                        <div class="col-md-6"><div class="progga-form-group"><label class="progga-form-label">Show Out-of-Stock Items</label><label class="progga-toggle" style="margin-top:8px;"><input type="checkbox" name="show_out_of_stock" {{ ($pos->show_out_of_stock ?? true) ? 'checked' : '' }} data-on="Yes (grayed)" data-off="Hidden"><span class="progga-toggle-track"><span class="progga-toggle-thumb"></span></span><span class="progga-toggle-label">{{ ($pos->show_out_of_stock ?? true) ? 'Yes (grayed)' : 'Hidden' }}</span></label></div></div>
                        <div class="col-12"><button type="submit" class="progga-btn progga-btn-primary"><i class="bi bi-check-lg"></i> Save Preferences</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="settingsRoles" style="display:none;">
        <div class="progga-card">
          <div class="progga-card-header"><div class="progga-card-title"><i class="bi bi-shield-lock me-2"></i>User Roles &amp; Permissions</div></div>
          <div class="progga-card-body" style="padding:0;">
            <div class="progga-table-wrapper" style="border:none;">
              <table class="progga-table">
                <thead>
                    <tr>
                        <th>Module</th>
                        @foreach($roles as $role)
                            <th>{{ $role->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @php
                        // পারমিশনগুলোকে গ্রুপ অনুযায়ী সাজানো (যেমন: Dashboard, POS System)
                        $permissionGroups = \Spatie\Permission\Models\Permission::all()->groupBy('group_name');
                    @endphp

                    @foreach($permissionGroups as $groupName => $permissions)
                    <tr>
                        <td><strong>{{ $groupName }}</strong></td>
                        @foreach($roles as $role)
                        <td>
                            @php
                                // চেক করা হচ্ছে এই রোলের এই গ্রুপে কোনো পারমিশন আছে কি না
                                $hasPermission = $role->permissions->where('group_name', $groupName)->count() > 0;
                            @endphp
                            @if($hasPermission)
                                <i class="bi bi-check-circle-fill" style="color:var(--progga-success);"></i>
                            @else
                                <i class="bi bi-x-circle-fill" style="color:var(--progga-danger);"></i>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

</main>
@endsection

@section('script')
<script>
    // Tab Map and Logic
    var tabMap = { restaurant:'settingsRestaurant', tax:'settingsTax', invoice:'settingsInvoice', pos:'settingsPos', roles:'settingsRoles' };
    document.querySelectorAll('[data-settings-tab]').forEach(function(tab){
        tab.addEventListener('click', function(){
            document.querySelectorAll('[data-settings-tab]').forEach(t=>t.classList.remove('active'));
            tab.classList.add('active');
            Object.values(tabMap).forEach(function(id){ var el=document.getElementById(id); if(el) el.style.display='none'; });
            var target = tabMap[tab.dataset.settingsTab];
            if(target){ var el=document.getElementById(target); if(el) el.style.display=''; }
        });
    });

    // Logo & Icon Preview Functions
    function previewLogo(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) { document.getElementById('logoPreview').src = e.target.result; document.getElementById('logoPreview').style.display = 'block'; }
            reader.readAsDataURL(input.files[0]);
        }
    }
    function previewIcon(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) { document.getElementById('iconPreview').src = e.target.result; document.getElementById('iconPreview').style.display = 'block'; }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
@endsection
