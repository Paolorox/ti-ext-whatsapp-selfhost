@php
    $statusMap = [
        'CONNECTED' => ['class' => 'success', 'icon' => 'fa-check-circle', 'text' => lang('igniter.whatsapp::default.text_status_connected')],
        'DISCONNECTED' => ['class' => 'danger', 'icon' => 'fa-times-circle', 'text' => lang('igniter.whatsapp::default.text_status_disconnected')],
        'SCAN_QR' => ['class' => 'warning', 'icon' => 'fa-qrcode', 'text' => lang('igniter.whatsapp::default.text_status_scan_qr')],
        'INITIALIZING' => ['class' => 'info', 'icon' => 'fa-spinner fa-spin', 'text' => lang('igniter.whatsapp::default.text_status_initializing')],
        'NOT_CONFIGURED' => ['class' => 'secondary', 'icon' => 'fa-cog', 'text' => lang('igniter.whatsapp::default.text_status_not_configured')],
        'UNREACHABLE' => ['class' => 'danger', 'icon' => 'fa-exclamation-triangle', 'text' => lang('igniter.whatsapp::default.text_status_unreachable')],
        'ERROR' => ['class' => 'danger', 'icon' => 'fa-exclamation-circle', 'text' => lang('igniter.whatsapp::default.text_status_error')],
        'FAILED' => ['class' => 'danger', 'icon' => 'fa-skull-crossbones', 'text' => lang('igniter.whatsapp::default.text_status_failed')],
    ];

    $status = $sessionStatus['status'] ?? 'UNKNOWN';
    $info = $statusMap[$status] ?? ['class' => 'secondary', 'icon' => 'fa-question-circle', 'text' => lang('igniter.whatsapp::default.text_status_unknown')];
@endphp

<div class="d-flex align-items-center mb-2">
    <span class="badge bg-{{ $info['class'] }} fs-6 me-2">
        <i class="fa {{ $info['icon'] }}"></i> {{ $info['text'] }}
    </span>
</div>

@if(!empty($sessionStatus['phoneNumber']))
    <p class="mb-1"><strong>Phone:</strong> {{ $sessionStatus['phoneNumber'] }}</p>
@endif

@if(!empty($sessionStatus['pushName']))
    <p class="mb-1"><strong>Name:</strong> {{ $sessionStatus['pushName'] }}</p>
@endif

@if(!empty($sessionStatus['connectedAt']))
    <p class="mb-1 small text-muted"><strong>Connected at:</strong> {{ $sessionStatus['connectedAt'] }}</p>
@endif

@if(!empty($sessionStatus['lastError']))
    <div class="alert alert-danger small py-1 px-2 mt-1 mb-2">
        <i class="fa fa-exclamation-triangle"></i>
        <strong>Last Error:</strong> {{ $sessionStatus['lastError'] }}
    </div>
@endif

@if(!empty($sessionStatus['message']))
    <p class="mb-2 text-muted small">{{ $sessionStatus['message'] }}</p>
@endif

<div class="mt-3 d-flex flex-wrap gap-2">
    <button type="button" class="btn btn-sm btn-outline-primary"
            data-request="onTestConnection"
            data-request-loading="true">
        <i class="fa fa-plug"></i> @lang('igniter.whatsapp::default.button_test_connection')
    </button>
    <button type="button" class="btn btn-sm btn-outline-success"
            data-request="onRefreshStatus"
            data-request-loading="true">
        <i class="fa fa-sync"></i> @lang('igniter.whatsapp::default.button_refresh_status')
    </button>
    @if($status !== 'CONNECTED' && $status !== 'NOT_CONFIGURED')
        <button type="button" class="btn btn-sm btn-outline-info"
                data-request="onStartSession"
                data-request-loading="true">
            <i class="fa fa-play"></i> @lang('igniter.whatsapp::default.button_start_session')
        </button>
    @endif
    @if($status === 'CONNECTED' || $status === 'SCAN_QR' || $status === 'INITIALIZING')
        <button type="button" class="btn btn-sm btn-outline-danger"
                data-request="onLogoutSession"
                data-confirm="Are you sure you want to disconnect this WhatsApp session?"
                data-request-loading="true">
            <i class="fa fa-power-off"></i> Disconnect
        </button>
    @endif
</div>
