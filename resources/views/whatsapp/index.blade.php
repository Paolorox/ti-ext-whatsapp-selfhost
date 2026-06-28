<form id="whatsapp-form" role="form">
<style>
    .wa-dashboard .card {
        border: 1px solid rgba(0, 0, 0, 0.08);
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        margin-bottom: 24px;
        overflow: hidden;
    }
    .wa-dashboard .card:hover {
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
    }
    .wa-dashboard .card-body {
        padding: 1.5rem;
    }
    .wa-dashboard .card-title {
        font-weight: 600;
        font-size: 1.1rem;
        color: #1e293b;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding-bottom: 12px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .wa-dashboard .badge {
        display: inline-flex;
        align-items: center;
        padding: 6px 14px;
        font-size: 0.85rem;
        font-weight: 600;
        border-radius: 30px;
        letter-spacing: 0.3px;
    }
    .wa-dashboard .badge.bg-success {
        background-color: #d1fae5 !important;
        color: #065f46 !important;
        border: 1px solid #a7f3d0;
    }
    .wa-dashboard .badge.bg-danger {
        background-color: #fee2e2 !important;
        color: #991b1b !important;
        border: 1px solid #fca5a5;
    }
    .wa-dashboard .badge.bg-warning {
        background-color: #fef3c7 !important;
        color: #92400e !important;
        border: 1px solid #fde68a;
    }
    .wa-dashboard .badge.bg-info {
        background-color: #e0f2fe !important;
        color: #075985 !important;
        border: 1px solid #bae6fd;
    }
    .wa-dashboard .badge.bg-secondary {
        background-color: #f3f4f6 !important;
        color: #374151 !important;
        border: 1px solid #e5e7eb;
    }
    .wa-dashboard code {
        font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
        background-color: #f1f5f9;
        color: #e11d48;
        padding: 3px 6px;
        border-radius: 6px;
        font-size: 0.85rem;
    }
    .wa-dashboard pre, .wa-dashboard .font-monospace {
        font-family: SFMono-Regular, Consolas, "Liberation Mono", Menlo, monospace;
    }
    .wa-dashboard .table-responsive {
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }
    .wa-dashboard .table {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }
    .wa-dashboard .table th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 12px 16px;
        border-bottom: 1px solid #e2e8f0;
    }
    .wa-dashboard .table td {
        padding: 14px 16px;
        vertical-align: middle;
        border-bottom: 1px solid #e2e8f0;
        color: #334155;
    }
    .wa-dashboard .table tr:last-child td {
        border-bottom: none;
    }
    .wa-dashboard .btn {
        border-radius: 8px;
        font-weight: 500;
        padding: 6px 14px;
        transition: all 0.15s ease-in-out;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .wa-dashboard .btn-outline-primary {
        color: #3b82f6;
        border-color: #3b82f6;
    }
    .wa-dashboard .btn-outline-primary:hover {
        background-color: #3b82f6;
        color: #ffffff;
    }
    .wa-dashboard .btn-outline-success {
        color: #10b981;
        border-color: #10b981;
    }
    .wa-dashboard .btn-outline-success:hover {
        background-color: #10b981;
        color: #ffffff;
    }
    .wa-dashboard .btn-outline-danger {
        color: #ef4444;
        border-color: #ef4444;
    }
    .wa-dashboard .btn-outline-danger:hover {
        background-color: #ef4444;
        color: #ffffff;
    }
    .wa-dashboard .btn-outline-info {
        color: #0ea5e9;
        border-color: #0ea5e9;
    }
    .wa-dashboard .btn-outline-info:hover {
        background-color: #0ea5e9;
        color: #ffffff;
    }
</style>
<div class="row-fluid wa-dashboard">
    {{-- Top Section: Status and QR Code --}}
    <div class="d-flex flex-wrap gap-3 mb-4">
        {{-- Connection Status --}}
        <div class="card flex-fill" style="min-width: 300px;">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fab fa-whatsapp text-success"></i>
                    @lang('igniter.whatsapp::default.text_connection_status')
                </h5>
                <div id="whatsapp-status-container">
                    @include('igniter.whatsapp::whatsapp.status', ['sessionStatus' => $sessionStatus])
                </div>
            </div>
        </div>

        {{-- QR Code --}}
        <div class="card flex-fill" style="min-width: 300px;">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="fa fa-qrcode"></i>
                    @lang('igniter.whatsapp::default.text_qr_code')
                </h5>
                <div id="whatsapp-qrcode-container">
                    <p class="text-muted">@lang('igniter.whatsapp::default.text_qr_scan_instructions')</p>
                    <button type="button" class="btn btn-sm btn-success"
                            data-request="onGetQrCode"
                            data-request-loading="true">
                        <i class="fa fa-qrcode"></i> @lang('igniter.whatsapp::default.button_get_qr')
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Debug Console --}}
    <div class="card mb-4 border-info">
        <div class="card-body">
            <h5 class="card-title">
                <i class="fa fa-bug text-info"></i>
                @lang('igniter.whatsapp::default.text_debug_console')
            </h5>
            <p class="text-muted small mb-2">
                @lang('igniter.whatsapp::default.text_debug_description')
            </p>
            <button type="button" class="btn btn-sm btn-info"
                    data-request="onRunDiagnostics"
                    data-request-loading="true">
                <i class="fa fa-stethoscope"></i> @lang('igniter.whatsapp::default.button_run_diagnostics')
            </button>
            <div id="whatsapp-debug-container" class="mt-3">
                {{-- Diagnostics output will be loaded here --}}
            </div>
        </div>
    </div>

    {{-- Session Management --}}
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">
                <i class="fa fa-server"></i>
                @lang('igniter.whatsapp::default.text_session_info')
            </h5>

            {{-- Current session ID display --}}
            @if(!empty($settings['session_id']))
                <div class="alert alert-info small mb-3 py-2">
                    <i class="fa fa-info-circle"></i>
                    <strong>Current Session ID:</strong>
                    <code>{{ $settings['session_id'] }}</code>
                </div>
            @else
                <div class="alert alert-warning small mb-3 py-2">
                    <i class="fa fa-exclamation-triangle"></i>
                    No session ID configured. Create a new session or select an existing one below.
                </div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <div class="input-group mb-2">
                        <input type="text" name="session_name" class="form-control form-control-sm"
                               placeholder="@lang('igniter.whatsapp::default.text_session_name_placeholder')"
                               value="tastyigniter">
                        <button type="button" class="btn btn-sm btn-primary"
                                data-request="onCreateSession"
                                data-request-loading="true">
                            <i class="fa fa-plus"></i> @lang('igniter.whatsapp::default.button_create_session')
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                            data-request="onListSessions"
                            data-request-loading="true">
                        <i class="fa fa-list"></i> @lang('igniter.whatsapp::default.button_list_sessions')
                    </button>
                </div>
            </div>
            <div id="whatsapp-sessions-container"></div>
        </div>
    </div>

    {{-- Test Message --}}
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">
                <i class="fa fa-paper-plane"></i>
                @lang('igniter.whatsapp::default.text_test_message')
            </h5>
            <div class="row g-2">
                <div class="col-md-4">
                    <input type="text" name="test_phone" class="form-control form-control-sm"
                           placeholder="@lang('igniter.whatsapp::default.text_test_phone_placeholder')">
                </div>
                <div class="col-md-5">
                    <input type="text" name="test_message" class="form-control form-control-sm"
                           placeholder="@lang('igniter.whatsapp::default.text_test_message_placeholder')"
                           value="✅ Test message from TastyIgniter WhatsApp Integration!">
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-sm btn-success w-100"
                            data-request="onSendTestMessage"
                            data-request-loading="true">
                        <i class="fab fa-whatsapp"></i> @lang('igniter.whatsapp::default.button_send_test')
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Template Variables Reference --}}
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">
                <i class="fa fa-code"></i>
                @lang('igniter.whatsapp::default.text_template_variables')
            </h5>
            <p class="text-muted small mb-2">
                Use these variables in your automation message templates. They will be replaced with actual data when the message is sent.
            </p>
            <div class="d-flex flex-wrap gap-1">
                @foreach($templateVariables as $var)
                    <span class="badge bg-secondary font-monospace">{{ $var }}</span>
                @endforeach
            </div>
        </div>
    </div>
</div>

<script>
    (function($) {
        $(document).ready(function() {
            var qrInterval = setInterval(function() {
                var container = $('#whatsapp-qrcode-container');
                if (container.length) {
                    var isConnected = $('#whatsapp-status-container .bg-success').length > 0;
                    if (!isConnected) {
                        $.request('onGetQrCode', {
                            success: function(data) {
                                this.success(data);
                            },
                            error: function() {
                                // ignore background errors
                            }
                        });
                    }
                } else {
                    clearInterval(qrInterval);
                }
            }, 8000); // Poll every 8 seconds for a fresh QR code

            var statusInterval = setInterval(function() {
                var container = $('#whatsapp-status-container');
                if (container.length) {
                    $.request('onRefreshStatus', {
                        success: function(data) {
                            this.success(data);
                        },
                        error: function() {
                            // ignore background errors
                        }
                    });
                } else {
                    clearInterval(statusInterval);
                }
            }, 12000); // Poll status every 12 seconds
        });
    })(jQuery);
</script>
</form>
