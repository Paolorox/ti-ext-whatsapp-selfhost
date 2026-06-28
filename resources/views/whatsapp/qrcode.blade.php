@if(!empty($qrData['success']) && !empty($qrData['image']))
    <div class="text-center">
        <img src="{{ $qrData['image'] }}" alt="WhatsApp QR Code"
             class="img-fluid border rounded" style="max-width: 280px;">
        <p class="text-muted small mt-2">
            <i class="fa fa-mobile-alt"></i> @lang('igniter.whatsapp::default.text_qr_scan_instructions')
        </p>
        <button type="button" class="btn btn-sm btn-outline-success mt-1"
                data-request="onGetQrCode"
                data-request-loading="true">
            <i class="fa fa-sync"></i> Refresh QR
        </button>
    </div>
@else
    <div class="alert alert-info mb-0">
        <i class="fa fa-info-circle"></i>
        @lang('igniter.whatsapp::default.text_qr_not_available')
        @if(!empty($qrData['error']))
            <br><small class="text-muted">{{ $qrData['error'] }}</small>
        @endif
    </div>
    <button type="button" class="btn btn-sm btn-success mt-2"
            data-request="onGetQrCode"
            data-request-loading="true">
        <i class="fa fa-qrcode"></i> @lang('igniter.whatsapp::default.button_get_qr')
    </button>
@endif
