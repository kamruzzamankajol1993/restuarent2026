@extends('admin.master.master')
@section('title', 'Edit Food Item — Progga RMS')
@section('css')
 <style>
    /* ── Section card headers ── */
    .af-card { border-radius: var(--progga-radius); background: #ffffff; border: 1px solid var(--progga-border); box-shadow: 0 1px 8px rgba(0,0,0,.05); margin-bottom: 20px; overflow: hidden; }
    .af-card-head { display: flex; align-items: center; gap: 12px; padding: 14px 22px; border-bottom: 1px solid var(--progga-border); background: #f8f9fa; }
    .af-card-num { width: 26px; height: 26px; border-radius: 7px; background: var(--progga-primary); color: var(--progga-secondary); font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .af-card-title { font-size: 13px; font-weight: 700; color: var(--progga-primary); letter-spacing: .2px; }
    .af-card-body { padding: 22px; background: #ffffff; }

    /* ── Upload zone ── */
    .af-upload { border: 2px dashed var(--progga-border); border-radius: 14px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; cursor: pointer; padding: 28px 16px; transition: border-color .2s, background .2s; text-align: center; position: relative; overflow: hidden; }
    .af-upload:hover { border-color: var(--progga-secondary); background: rgba(213,170,101,.04); }
    .af-upload i { font-size: 2rem; color: var(--progga-secondary); }
    .af-upload-label { font-size: 13px; font-weight: 600; color: var(--progga-primary); }
    .af-upload-sub { font-size: 11px; color: var(--progga-text-muted); }
    .af-upload-preview { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; border-radius: 12px; display: none; }
    .af-upload-overlay { position: absolute; inset: 0; background: rgba(0,0,0,.45); border-radius: 12px; display: none; align-items: center; justify-content: center; color: #fff; font-size: 13px; font-weight: 600; gap: 6px; }
    .af-upload:hover .af-upload-overlay { display: flex; }

    /* ── Allergen chips ── */
    .af-allergen-grid { display: flex; flex-wrap: wrap; gap: 6px; }
    .af-allergen { position: relative; }
    .af-allergen input { position: absolute; opacity: 0; width: 0; height: 0; }
    .af-allergen-label { display: inline-flex; align-items: center; gap: 5px; padding: 5px 11px; border: 1.5px solid var(--progga-border); border-radius: 20px; cursor: pointer; font-size: 11px; font-weight: 600; color: var(--progga-text-muted); transition: all .18s; white-space: nowrap; }
    .af-allergen-label i { font-size: 12px; }
    .af-allergen input:checked + .af-allergen-label { border-color: #dc3545; background: rgba(220,53,69,.07); color: #dc3545; }

    /* ── Status toggles ── */
    .af-toggle-row { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid var(--progga-border); }
    .af-toggle-row:last-child { border-bottom: none; padding-bottom: 0; }
    .af-toggle-row:first-child { padding-top: 0; }
    .af-toggle-info {}
    .af-toggle-name { font-size: 13px; font-weight: 600; color: var(--progga-primary); }
    .af-toggle-hint { font-size: 11px; color: var(--progga-text-muted); margin-top: 2px; }

    /* ── Add-on row ── */
    .af-addon-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
    .af-addon-row .progga-form-control { flex: 1; }
    .af-addon-price { width: 130px; flex-shrink: 0; }

    /* ── Day pills ── */
    .af-day-grid { display: flex; gap: 6px; flex-wrap: wrap; }
    .af-day { position: relative; }
    .af-day input { position: absolute; opacity: 0; width: 0; height: 0; }
    .af-day-label { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 10px; border: 1.5px solid var(--progga-border); font-size: 11px; font-weight: 700; color: var(--progga-text-muted); cursor: pointer; transition: all .18s; }
    .af-day input:checked + .af-day-label { background: var(--progga-primary); border-color: var(--progga-primary); color: var(--progga-secondary); }

    /* ── Publish card ── */
    .af-publish-card { border: 2px solid var(--progga-primary); border-radius: var(--progga-radius); overflow: hidden; }
    .af-publish-head { background: var(--progga-primary); padding: 14px 20px; display: flex; align-items: center; gap: 10px; }
    .af-publish-head-title { font-size: 13px; font-weight: 700; color: var(--progga-secondary); }
    .af-publish-body { background: #ffffff; padding: 18px; }

    /* ── Input with prefix ── */
    .af-input-wrap { position: relative; }
    .af-input-prefix { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 14px; font-weight: 700; color: var(--progga-text-muted); pointer-events: none; }
    .af-input-wrap .progga-form-control { padding-left: 30px; }
  </style>
@endsection
@section('body')
<main class="progga-content">
    <div class="progga-page-header">
        <div>
            <h1 class="progga-page-title">Edit Food Item</h1>
            <div class="progga-breadcrumb">
                <a href="{{ route('food-item.index') }}" class="progga-breadcrumb-item">Food Menu</a>
                <span class="progga-breadcrumb-sep">/</span>
                <span class="progga-breadcrumb-item active">{{ $foodItem->name }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('food-item.index') }}" class="progga-btn progga-btn-outline progga-btn-sm"><i class="bi bi-arrow-left"></i> Back to Menu</a>
        </div>
    </div>

    <form id="editFoodForm" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="af-card">
                    <div class="af-card-head">
                        <div class="af-card-num">01</div><div class="af-card-title">Basic Information</div>
                    </div>
                    <div class="af-card-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="progga-form-group">
                                    <label class="progga-form-label">Food Item Name <span class="progga-required">*</span></label>
                                    <input type="text" name="name" value="{{ $foodItem->name }}" class="progga-form-control" style="font-size:15px;font-weight:600;" required>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="progga-form-group">
                                    <label class="progga-form-label">Short Description</label>
                                    <input type="text" name="short_description" value="{{ $foodItem->short_description }}" class="progga-form-control" maxlength="120">
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="progga-form-group mb-0">
                                    <label class="progga-form-label">Full Description</label>
                                    <textarea name="description" class="progga-form-control progga-form-textarea" rows="3">{{ $foodItem->description }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="af-card">
                    <div class="af-card-head">
                        <div class="af-card-num">02</div><div class="af-card-title">Classification</div>
                    </div>
                    <div class="af-card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="progga-form-group">
                                    <label class="progga-form-label">Category <span class="progga-required">*</span></label>
                                    <select class="progga-select" name="food_category_id" required>
                                        <option value="">Select category</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" {{ $foodItem->food_category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="progga-form-group">
                                    <label class="progga-form-label">Sub Category</label>
                                    <select class="progga-select" name="sub_category_id">
                                        <option value="">Select sub-category</option>
                                        @foreach($subCategories as $sub)
                                            <option value="{{ $sub->id }}" {{ $foodItem->sub_category_id == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="progga-form-group">
                                    <label class="progga-form-label">Cuisine Type</label>
                                    <select class="progga-select" name="cuisine_type_id">
                                        <option value="">Select cuisine</option>
                                        @foreach($cuisines as $cui)
                                            <option value="{{ $cui->id }}" {{ $foodItem->cuisine_type_id == $cui->id ? 'selected' : '' }}>{{ $cui->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="progga-form-group mb-0">
                                    <label class="progga-form-label">Spice Level</label>
                                    <select class="progga-select" name="spice_level">
                                        <option value="">Select Option</option>
                                        <option value="Not Spicy" {{ $foodItem->spice_level == 'Not Spicy' ? 'selected' : '' }}>Not Spicy</option>
                                        <option value="Mild" {{ $foodItem->spice_level == 'Mild' ? 'selected' : '' }}>Mild</option>
                                        <option value="Medium" {{ $foodItem->spice_level == 'Medium' ? 'selected' : '' }}>Medium</option>
                                        <option value="Hot" {{ $foodItem->spice_level == 'Hot' ? 'selected' : '' }}>Hot</option>
                                        <option value="Extra Hot" {{ $foodItem->spice_level == 'Extra Hot' ? 'selected' : '' }}>Extra Hot</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="progga-form-group mb-0">
                                    <label class="progga-form-label">Course Type</label>
                                    <select class="progga-select" name="course_type_id">
                                        <option value="">Select Course</option>
                                        @foreach($courseTypes as $crs)
                                            <option value="{{ $crs->id }}" {{ $foodItem->course_type_id == $crs->id ? 'selected' : '' }}>{{ $crs->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="progga-form-group mb-0">
                                    <label class="progga-form-label">Serving Size</label>
                                    <input type="text" name="serving_size" value="{{ $foodItem->serving_size }}" class="progga-form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="af-card">
                    <div class="af-card-head"><div class="af-card-num">03</div><div class="af-card-title">Pricing &amp; Tax</div></div>
                    <div class="af-card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="progga-form-group">
                                    <label class="progga-form-label">Regular Price <span class="progga-required">*</span></label>
                                    <div class="af-input-wrap"><span class="af-input-prefix">৳</span><input type="number" name="base_price" value="{{ $foodItem->base_price }}" class="progga-form-control" required></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="progga-form-group">
                                    <label class="progga-form-label">Discount Price</label>
                                    <div class="af-input-wrap"><span class="af-input-prefix">৳</span><input type="number" name="discount_price" value="{{ $foodItem->discount_price }}" class="progga-form-control"></div>
                                </div>
                            </div>

                            <div class="col-md-4" style="display:none;">
                                <div class="progga-form-group">
                                    <label class="progga-form-label">Loyalty Points Earned</label>
                                    <input type="number" name="point" class="progga-form-control" value="{{ $foodItem->point ?? '' }}">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="progga-form-group">
                                    <label class="progga-form-label">Tax Rate</label>
                                    <select class="progga-select" name="tax_rate">
                                        <option value="">Select Tax</option>
                                        <option value="VAT (15%)" {{ $foodItem->tax_rate == 'VAT (15%)' ? 'selected' : '' }}>VAT (15%)</option>
                                        <option value="Standard (5%)" {{ $foodItem->tax_rate == 'Standard (5%)' ? 'selected' : '' }}>Standard (5%)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="progga-form-group mb-0">
                                    <label class="progga-form-label">Preparation Time</label>
                                    <div class="af-input-wrap"><span class="af-input-prefix" style="font-size:12px;left:10px;">min</span><input type="number" name="preparation_time" value="{{ $foodItem->preparation_time }}" class="progga-form-control" style="padding-left:36px;"></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="progga-form-group mb-0">
                                    <label class="progga-form-label">Calories</label>
                                    <div class="af-input-wrap"><span class="af-input-prefix" style="font-size:12px;left:10px;">kcal</span><input type="number" name="calories" value="{{ $foodItem->calories }}" class="progga-form-control" style="padding-left:38px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="af-card">
                    <div class="af-card-head"><div class="af-card-num">04</div><div class="af-card-title">Allergen Information</div></div>
                    <div class="af-card-body">
                        <div class="af-allergen-grid">
                            @php $itemAllergens = is_array($foodItem->allergens) ? $foodItem->allergens : []; @endphp
                            @foreach($allergens as $allergen)
                            <label class="af-allergen">
                                <input type="checkbox" name="allergens[]" value="{{ $allergen->name }}" {{ in_array($allergen->name, $itemAllergens) ? 'checked' : '' }}>
                                <div class="af-allergen-label"><i class="bi bi-exclamation-circle"></i>{{ $allergen->name }}</div>
                            </label>
                            @endforeach
                        </div>
                        <div class="progga-form-group" style="margin-top:14px;margin-bottom:0;">
                            <label class="progga-form-label">Additional Notes</label>
                            <input type="text" name="allergen_notes" value="{{ $foodItem->allergen_notes }}" class="progga-form-control">
                        </div>
                    </div>
                </div>

                <div class="af-card" style="margin-bottom:0;">
                    <div class="af-card-head"><div class="af-card-num">05</div><div class="af-card-title">Add-ons</div></div>
                    <div class="af-card-body">
                        <div id="addonList">
                            @foreach($foodItem->addons as $addon)
                            <div class="af-addon-row">
                                <input type="text" name="addon_name[]" value="{{ $addon->name }}" class="progga-form-control" placeholder="Add-on name">
                                <div class="af-input-wrap af-addon-price">
                                    <span class="af-input-prefix">৳</span>
                                    <input type="number" name="addon_price[]" value="{{ $addon->price }}" class="progga-form-control" placeholder="Price">
                                </div>
                                <button type="button" class="progga-btn progga-btn-outline progga-btn-icon progga-btn-sm" style="color:var(--progga-danger);border-color:var(--progga-danger);flex-shrink:0;" onclick="this.closest('.af-addon-row').remove()"><i class="bi bi-trash"></i></button>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" class="progga-btn progga-btn-outline progga-btn-sm mt-2" onclick="addAddon()"><i class="bi bi-plus-lg"></i> Add Option</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="af-card">
                    <div class="af-card-head"><div class="af-card-num">06</div><div class="af-card-title">Item Photo</div></div>
                    <div class="af-card-body">
                        <div class="af-upload" id="mainUploadZone" onclick="document.getElementById('mainThumb').click()">
                            <input type="file" id="mainThumb" name="main_image" class="d-none" accept="image/*" onchange="handleMainUpload(this)">
                            @if($foodItem->main_image)
                                <img class="af-upload-preview" id="mainThumbPreview" src="{{ asset('public/uploads/foods/'.$foodItem->main_image) }}" alt="" style="display:block;">
                            @else
                                <img class="af-upload-preview" id="mainThumbPreview" src="" alt="">
                                <i class="bi bi-cloud-arrow-up" id="upIcon"></i>
                                <div class="af-upload-label" id="upText">Upload Main Photo</div>
                            @endif
                            <div class="af-upload-overlay" id="mainThumbOverlay" style="display:none;"><i class="bi bi-arrow-repeat"></i> Change Photo</div>
                        </div>
                        <div class="row g-2 mt-2">
                            <div class="col-6">
                                <div class="af-upload" style="padding:16px;min-height:80px;" onclick="document.getElementById('gallery1').click()">
                                    <input type="file" id="gallery1" name="gallery_images[]" class="d-none" accept="image/*">
                                    <i class="bi bi-plus-lg" style="font-size:1.2rem;"></i><div class="af-upload-sub">Gallery Image</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="af-upload" style="padding:16px;min-height:80px;" onclick="document.getElementById('gallery2').click()">
                                    <input type="file" id="gallery2" name="gallery_images[]" class="d-none" accept="image/*">
                                    <i class="bi bi-plus-lg" style="font-size:1.2rem;"></i><div class="af-upload-sub">Gallery Image</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="af-card">
                    <div class="af-card-head"><div class="af-card-num">07</div><div class="af-card-title">Status & Visibility</div></div>
                    <div class="af-card-body">
                        <div class="af-toggle-row"><div class="af-toggle-info"><div class="af-toggle-name">Item Available</div></div><label class="progga-toggle"><input type="checkbox" name="is_available" value="1" {{ $foodItem->is_available ? 'checked' : '' }}><span class="progga-toggle-track"><span class="progga-toggle-thumb"></span></span></label></div>
                        <div class="af-toggle-row"><div class="af-toggle-info"><div class="af-toggle-name">Featured Item</div></div><label class="progga-toggle"><input type="checkbox" name="is_featured" value="1" {{ $foodItem->is_featured ? 'checked' : '' }}><span class="progga-toggle-track"><span class="progga-toggle-thumb"></span></span></label></div>
                        <div class="af-toggle-row"><div class="af-toggle-info"><div class="af-toggle-name">Chef's Special</div></div><label class="progga-toggle"><input type="checkbox" name="is_chefs_special" value="1" {{ $foodItem->is_chefs_special ? 'checked' : '' }}><span class="progga-toggle-track"><span class="progga-toggle-thumb"></span></span></label></div>
                        <div class="af-toggle-row"><div class="af-toggle-info"><div class="af-toggle-name">Dine-In</div></div><label class="progga-toggle"><input type="checkbox" name="is_dine_in" value="1" {{ $foodItem->is_dine_in ? 'checked' : '' }}><span class="progga-toggle-track"><span class="progga-toggle-thumb"></span></span></label></div>
                        <div class="af-toggle-row"><div class="af-toggle-info"><div class="af-toggle-name">Takeaway / Delivery</div></div><label class="progga-toggle"><input type="checkbox" name="is_takeaway" value="1" {{ $foodItem->is_takeaway ? 'checked' : '' }}><span class="progga-toggle-track"><span class="progga-toggle-thumb"></span></span></label></div>
                    </div>
                </div>

                <div class="af-card">
                    <div class="af-card-head"><div class="af-card-num">08</div><div class="af-card-title">Availability Schedule</div></div>
                    <div class="af-card-body">
                        <div class="progga-form-group">
                            <label class="progga-form-label" style="margin-bottom:10px;">Active Days</label>
                            <div class="af-day-grid">
                                @php $activeDays = is_array($foodItem->active_days) ? $foodItem->active_days : []; @endphp
                                @foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day)
                                <label class="af-day"><input type="checkbox" name="active_days[]" value="{{ $day }}" {{ in_array($day, $activeDays) ? 'checked' : '' }}><span class="af-day-label">{{ $day }}</span></label>
                                @endforeach
                            </div>
                        </div>
                        <div class="row g-3 mb-0">
                            <div class="col-6"><div class="progga-form-group mb-0"><label class="progga-form-label">Start Time</label><input type="time" name="start_time" value="{{ $foodItem->start_time }}" class="progga-form-control"></div></div>
                            <div class="col-6"><div class="progga-form-group mb-0"><label class="progga-form-label">End Time</label><input type="time" name="end_time" value="{{ $foodItem->end_time }}" class="progga-form-control"></div></div>
                        </div>
                    </div>
                </div>

                <div class="af-publish-card">
                    <div class="af-publish-head"><i class="bi bi-pencil-square" style="color:var(--progga-secondary);font-size:16px;"></i><div class="af-publish-head-title">Save Changes</div></div>
                    <div class="af-publish-body">
                        <button type="submit" class="progga-btn progga-btn-primary w-100" id="updateBtn" style="padding:12px;font-size:14px;"><i class="bi bi-check-lg"></i> Update Food Item</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</main>
@endsection

@section('script')
<script>
// Image Preview Script
function handleMainUpload(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function (e) {
        document.getElementById('mainThumbPreview').src = e.target.result;
        document.getElementById('mainThumbPreview').style.display = 'block';
        document.getElementById('mainThumbOverlay').style.display = 'flex';
        let upIcon = document.getElementById('upIcon');
        let upText = document.getElementById('upText');
        if(upIcon) upIcon.style.display = 'none';
        if(upText) upText.style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
}

// Addon Script
function addAddon() {
    const list = document.getElementById('addonList');
    const row  = document.createElement('div');
    row.className = 'af-addon-row';
    row.innerHTML = `
        <input type="text" name="addon_name[]" class="progga-form-control" placeholder="Add-on name">
        <div class="af-input-wrap af-addon-price">
            <span class="af-input-prefix">৳</span>
            <input type="number" name="addon_price[]" class="progga-form-control" placeholder="Price">
        </div>
        <button type="button" class="progga-btn progga-btn-outline progga-btn-icon progga-btn-sm" style="color:var(--progga-danger);border-color:var(--progga-danger);flex-shrink:0;" onclick="this.closest('.af-addon-row').remove()"><i class="bi bi-trash"></i></button>
    `;
    list.appendChild(row);
}

// Ensure overlay appears when hovering an existing image
$('#mainUploadZone').hover(function() {
    if ($('#mainThumbPreview').attr('src') !== '') {
        $('#mainThumbOverlay').css('display', 'flex');
    }
}, function() {
    $('#mainThumbOverlay').css('display', 'none');
});

// Update Form Submit (AJAX)
$('#editFoodForm').on('submit', function(e) {
    e.preventDefault();
    let btn = $('#updateBtn');
    btn.html('<i class="spinner-border spinner-border-sm"></i> Updating...');

    $.ajax({
        url: "{{ route('food-item.update', $foodItem->id) }}",
        type: 'POST', // Method PUT is passed inside FormData (_method=PUT)
        data: new FormData(this),
        contentType: false,
        processData: false,
        success: function(res) {
            if(res.status === 'success') {
                showToast('Success', res.message, 'success');
                setTimeout(() => { window.location.href = "{{ route('food-item.index') }}"; }, 1500);
            } else {
                showToast('Error', res.message, 'error');
                btn.html('<i class="bi bi-check-lg"></i> Update Food Item');
            }
        },
        error: function() {
            showToast('Error', 'Something went wrong!', 'error');
            btn.html('<i class="bi bi-check-lg"></i> Update Food Item');
        }
    });
});
</script>
@endsection
