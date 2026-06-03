<form id="reportFilterForm" class="progga-card-body report-filter-line" style="display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap; padding:15px; border-bottom: 1px solid var(--progga-border-light);">

    <div class="progga-form-group" style="margin:0;">
        <label class="progga-form-label" style="margin-bottom:3px;">Filter Type</label>
        <select name="filter_type" id="filterType" class="progga-select">
            <option value="year" {{ $filterType == 'year' ? 'selected' : '' }}>Year Wise</option>
            <option value="month" {{ $filterType == 'month' ? 'selected' : '' }}>Month Wise</option>
            <option value="date" {{ $filterType == 'date' ? 'selected' : '' }}>Date Range</option>
        </select>
    </div>

    <div class="progga-form-group" id="yearField" style="margin:0; display: {{ in_array($filterType, ['year', 'month']) ? 'block' : 'none' }};">
        <label class="progga-form-label" style="margin-bottom:3px;">Year</label>
        <select name="year" id="filterYear" class="progga-select">
            @foreach($yearOptions as $yr)
                <option value="{{ $yr }}" {{ (int)$year === (int)$yr ? 'selected' : '' }}>{{ $yr }}</option>
            @endforeach
        </select>
    </div>

    <div class="progga-form-group" id="monthField" style="margin:0; display: {{ $filterType == 'month' ? 'block' : 'none' }};">
        <label class="progga-form-label" style="margin-bottom:3px;">Month</label>
        <select name="month" id="filterMonth" class="progga-select">
            @for($m=1; $m<=12; $m++)
                <option value="{{ $m }}" {{ (int)$month === $m ? 'selected' : '' }}>{{ \Carbon\Carbon::create(null,$m,1)->format('F') }}</option>
            @endfor
        </select>
    </div>

    <div id="dateRangeFields" style="display: {{ $filterType == 'date' ? 'flex' : 'none' }}; gap:10px; align-items:flex-end;">
        <div class="progga-form-group" style="margin:0;">
            <label class="progga-form-label" style="margin-bottom:3px;">From Date</label>
            <input type="text" name="start_date" id="startDate" class="progga-form-control datepicker" value="{{ $startDate->format('Y-m-d') }}" placeholder="YYYY-MM-DD">
        </div>
        <div class="progga-form-group" style="margin:0;">
            <label class="progga-form-label" style="margin-bottom:3px;">To Date</label>
            <input type="text" name="end_date" id="endDate" class="progga-form-control datepicker" value="{{ $endDate->format('Y-m-d') }}" placeholder="YYYY-MM-DD">
        </div>
    </div>

    @if(isset($showPaymentFilter) && $showPaymentFilter)
    <div class="progga-form-group" style="margin:0;">
        <label class="progga-form-label" style="margin-bottom:3px;">Payment Type</label>
        <select name="payment_method" id="paymentMethod" class="progga-select">
            <option value="">All Payments</option>
            <option value="Cash" {{ ($paymentMethod ?? '') == 'Cash' ? 'selected' : '' }}>Cash</option>
            <option value="Card" {{ ($paymentMethod ?? '') == 'Card' ? 'selected' : '' }}>Card</option>
            <option value="Mobile Banking" {{ ($paymentMethod ?? '') == 'Mobile Banking' ? 'selected' : '' }}>Mobile Banking</option>
            <option value="Split" {{ ($paymentMethod ?? '') == 'Split' ? 'selected' : '' }}>Split</option>
        </select>
    </div>
    @endif

    <div style="margin-top: auto;">
        <a href="{{ url()->current() }}" class="progga-btn progga-btn-outline progga-btn-sm" style="height: 38px; display: inline-flex; align-items: center;"><i class="bi bi-arrow-clockwise"></i> Reset</a>
    </div>
</form>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

<script>
$(document).ready(function() {
    const $filterType = $('#filterType');
    const $yearField = $('#yearField');
    const $monthField = $('#monthField');
    const $dateRange = $('#dateRangeFields');
    const $form = $('#reportFilterForm');

    // ১. Flatpickr Initialize
    flatpickr(".datepicker", {
        dateFormat: "Y-m-d",
        allowInput: true,
        onChange: function(selectedDates, dateStr, instance) {
            triggerAjaxFetch();
        }
    });

    // ২. ফিল্ড টগল ফাংশন (jQuery)
    function toggleFields() {
        let val = $filterType.val();

        if (val === 'year') {
            $yearField.show();
            $monthField.hide();
            $dateRange.hide().removeClass('d-flex');
        } else if (val === 'month') {
            $yearField.show();
            $monthField.show();
            $dateRange.hide().removeClass('d-flex');
        } else if (val === 'date') {
            $yearField.hide();
            $monthField.hide();
            $dateRange.css('display', 'flex');
        }
    }

    // ৩. Ajax কল ফাংশন
    function triggerAjaxFetch() {
        let formData = $form.serialize();
        let fetchUrl = window.location.pathname + '?' + formData;

        // Update URL dynamically
        window.history.pushState({}, '', fetchUrl);

        $.ajax({
            url: fetchUrl,
            type: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            success: function(data) {
                if(typeof updateReportDOM === 'function') {
                    updateReportDOM(data);
                }
            },
            error: function(err) {
                console.error("Error loading report data: ", err);
            }
        });
    }

    // ৪. Select2 / Choices.js ফ্রেন্ডলি ইভেন্ট লিসেনার
    $filterType.on('change', function() {
        toggleFields();
        triggerAjaxFetch();
    });

    $('#filterYear, #filterMonth, #paymentMethod').on('change', function() {
        triggerAjaxFetch();
    });

    // Initial Check
    toggleFields();
});
</script>
