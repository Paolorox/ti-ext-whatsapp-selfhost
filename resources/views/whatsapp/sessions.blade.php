@if(!empty($sessions) && is_array($sessions))
    <div class="table-responsive mt-2">
        <table class="table table-sm table-bordered mb-0">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Status</th>
                <th>Phone</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            @foreach($sessions as $session)
                @php
                    $isActive = !empty($currentSessionId) && ($session['id'] ?? '') === $currentSessionId;
                @endphp
                <tr class="{{ $isActive ? 'table-success' : '' }}">
                    <td>
                        <code>{{ $session['id'] ?? '-' }}</code>
                        @if($isActive)
                            <span class="badge bg-success ms-1">Active</span>
                        @endif
                    </td>
                    <td>{{ $session['name'] ?? '-' }}</td>
                    <td>
                        @php
                            $s = $session['status'] ?? 'UNKNOWN';
                            $badgeClass = match($s) {
                                'CONNECTED' => 'bg-success',
                                'DISCONNECTED', 'FAILED', 'UNREACHABLE' => 'bg-danger',
                                'SCAN_QR' => 'bg-warning',
                                'INITIALIZING' => 'bg-info',
                                default => 'bg-secondary',
                            };
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $s }}</span>
                    </td>
                    <td>{{ $session['phoneNumber'] ?? '-' }}</td>
                    <td>
                        @if(!$isActive)
                            <button type="button" class="btn btn-xs btn-outline-primary"
                                    data-request="onSelectSession"
                                    data-request-data="session_id: '{{ $session['id'] ?? '' }}'"
                                    data-request-loading="true">
                                @lang('igniter.whatsapp::default.text_select_session')
                            </button>
                        @else
                            <span class="text-success small"><i class="fa fa-check"></i> Selected</span>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="text-muted small mt-2 mb-0">No sessions found. Create one above or check your OpenWA connection.</p>
@endif
