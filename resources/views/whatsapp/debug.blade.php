@if(!empty($diagnostics))
    <div class="debug-output">
        @foreach($diagnostics as $key => $step)
            <div class="card mb-2 border-{{ ($step['status'] ?? '') === 'ok' ? 'success' : (($step['status'] ?? '') === 'warning' ? 'warning' : 'danger') }}">
                <div class="card-body p-2">
                    <div class="d-flex align-items-center mb-1">
                        @if(($step['status'] ?? '') === 'ok')
                            <span class="badge bg-success me-2"><i class="fa fa-check"></i></span>
                        @elseif(($step['status'] ?? '') === 'warning')
                            <span class="badge bg-warning me-2"><i class="fa fa-exclamation-triangle"></i></span>
                        @else
                            <span class="badge bg-danger me-2"><i class="fa fa-times"></i></span>
                        @endif
                        <strong>{{ $step['label'] ?? ucfirst($key) }}</strong>
                    </div>
                    <p class="mb-1 small {{ ($step['status'] ?? '') === 'ok' ? 'text-success' : (($step['status'] ?? '') === 'warning' ? 'text-warning' : 'text-danger') }}">
                        {{ $step['message'] ?? 'No details' }}
                    </p>

                    {{-- Show URL if available --}}
                    @if(!empty($step['url']))
                        <div class="small text-muted mb-1">
                            <i class="fa fa-link"></i>
                            <code>{{ $step['url'] }}</code>
                            @if(!empty($step['http_status']))
                                → HTTP <span class="badge {{ $step['http_status'] < 300 ? 'bg-success' : ($step['http_status'] < 500 ? 'bg-warning' : 'bg-danger') }}">{{ $step['http_status'] }}</span>
                            @endif
                        </div>
                    @endif

                    {{-- Show config details --}}
                    @if($key === 'config')
                        <div class="small font-monospace bg-dark text-light p-2 rounded mt-1">
                            <div>api_url: <span class="text-info">{{ $step['api_url'] ?? '?' }}</span></div>
                            <div>api_key: <span class="text-info">{{ $step['api_key'] ?? '?' }}</span></div>
                            <div>session_id: <span class="text-info">{{ $step['session_id'] ?? '?' }}</span></div>
                            <div>enabled: <span class="{{ ($step['enabled'] ?? false) ? 'text-success' : 'text-danger' }}">{{ ($step['enabled'] ?? false) ? 'true' : 'false' }}</span></div>
                            <div>country_code: <span class="text-info">{{ $step['country_code'] ?? '?' }}</span></div>
                        </div>
                    @endif

                    {{-- Show session list in auth step --}}
                    @if($key === 'auth' && !empty($step['sessions']))
                        <div class="small mt-1">
                            <strong>Sessions on server:</strong>
                            <div class="font-monospace bg-dark text-light p-2 rounded mt-1">
                                @foreach($step['sessions'] as $sess)
                                    <div>
                                        <span class="text-info">{{ $sess['id'] ?? '?' }}</span>
                                        ({{ $sess['name'] ?? '?' }}) →
                                        <span class="{{ ($sess['status'] ?? '') === 'ready' ? 'text-success' : 'text-warning' }}">{{ $sess['status'] ?? '?' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Show session response details --}}
                    @if($key === 'session' && !empty($step['response']))
                        <div class="small mt-1">
                            <strong>Raw response:</strong>
                            <pre class="font-monospace bg-dark text-light p-2 rounded mt-1 mb-0" style="max-height: 200px; overflow-y: auto; white-space: pre-wrap; word-break: break-all;">{{ json_encode($step['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @endif

                    {{-- Show health response --}}
                    @if($key === 'health' && !empty($step['response']))
                        <div class="small mt-1">
                            <strong>Health response:</strong>
                            <pre class="font-monospace bg-dark text-light p-2 rounded mt-1 mb-0" style="max-height: 120px; overflow-y: auto; white-space: pre-wrap;">{{ json_encode($step['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        <div class="text-muted small mt-2">
            <i class="fa fa-clock"></i> Diagnostic run at {{ now()->format('Y-m-d H:i:s T') }}
        </div>
    </div>
@else
    <p class="text-muted small">Click "Run Diagnostics" to start a full connection test.</p>
@endif
