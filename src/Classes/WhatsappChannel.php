<?php

declare(strict_types=1);

namespace Igniter\Whatsapp\Classes;

use Igniter\Whatsapp\Models\WhatsappLog;
use Igniter\Whatsapp\Models\WhatsappSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Channel - HTTP client for OpenWA 0.7.3 API
 *
 * Handles all communication with the OpenWA self-hosted WhatsApp API.
 *
 * OpenWA 0.7.3 API Reference:
 * - All endpoints are under /api (global prefix)
 * - Sessions: /api/sessions
 * - Messages: /api/sessions/{sessionId}/messages/send-text
 * - Health:   /api/health
 * - Auth:     X-API-Key header
 */
class WhatsappChannel
{
    /**
     * OpenWA 0.7.3 session status → extension status mapping.
     *
     * OpenWA uses lowercase enum values (SessionStatus entity):
     *   created, initializing, qr_ready, authenticating, ready, disconnected, failed
     *
     * The extension views expect uppercase status strings:
     *   CONNECTED, DISCONNECTED, SCAN_QR, INITIALIZING, NOT_CONFIGURED, UNREACHABLE, ERROR
     */
    private const STATUS_MAP = [
        'ready'          => 'CONNECTED',
        'qr_ready'       => 'SCAN_QR',
        'disconnected'   => 'DISCONNECTED',
        'initializing'   => 'INITIALIZING',
        'authenticating' => 'INITIALIZING',
        'created'        => 'DISCONNECTED',
        'failed'         => 'UNREACHABLE',
    ];

    /**
     * Send a text message via OpenWA 0.7.3
     *
     * OpenWA endpoint: POST /api/sessions/{sessionId}/messages/send-text
     * Payload: { chatId: "number@c.us", text: "message content" }
     */
    public function sendText(
        string $phone,
        string $message,
        string $eventType = 'manual',
        ?int $orderId = null,
    ): array {
        if (!WhatsappSettings::isEnabled()) {
            Log::debug('[WhatsApp DEBUG] sendText() → integration is DISABLED');

            return ['success' => false, 'error' => 'WhatsApp integration is disabled'];
        }

        $chatId = $this->formatPhone($phone);
        $sessionId = WhatsappSettings::getSessionId();

        Log::debug('[WhatsApp DEBUG] sendText() → phone='.$phone.', chatId='.$chatId.', sessionId='.$sessionId);

        if (empty($sessionId)) {
            Log::warning('[WhatsApp] sendText() → No session ID configured');
            WhatsappLog::logMessage($phone, $message, $eventType, WhatsappLog::STATUS_FAILED, $orderId, null, 'No session ID configured');

            return ['success' => false, 'error' => 'No WhatsApp session configured'];
        }

        try {
            // OpenWA 0.7.3: POST /sessions/{sessionId}/messages/send-text
            // Body: { chatId: "number@c.us", text: "message" }
            $payload = [
                'chatId' => $chatId,
                'text'   => $message,
            ];

            Log::debug('[WhatsApp DEBUG] sendText() → endpoint: /sessions/'.$sessionId.'/messages/send-text');
            Log::debug('[WhatsApp DEBUG] sendText() → payload: '.json_encode($payload));

            $response = $this->makeRequest('POST', "/sessions/{$sessionId}/messages/send-text", $payload);

            $responseData = $response->json();
            $responseData = is_array($responseData) ? $responseData : [];
            $success = $response->successful();

            Log::debug('[WhatsApp DEBUG] sendText() → HTTP status: '.$response->status());
            Log::debug('[WhatsApp DEBUG] sendText() → response: '.json_encode($responseData));

            WhatsappLog::logMessage(
                $phone,
                $message,
                $eventType,
                $success ? WhatsappLog::STATUS_SENT : WhatsappLog::STATUS_FAILED,
                $orderId,
                $responseData,
                $success ? null : ($responseData['message'] ?? 'Unknown error'),
            );

            if ($success) {
                Log::info('[WhatsApp] Message sent successfully to '.$chatId);
            } else {
                Log::error('[WhatsApp] Failed to send message to '.$chatId.': '.json_encode($responseData));
            }

            return [
                'success' => $success,
                'data'    => $responseData,
            ];
        } catch (\Exception $e) {
            Log::error('[WhatsApp] sendText() exception: '.$e->getMessage());
            Log::debug('[WhatsApp DEBUG] sendText() → stack trace: '.$e->getTraceAsString());

            WhatsappLog::logMessage(
                $phone,
                $message,
                $eventType,
                WhatsappLog::STATUS_FAILED,
                $orderId,
                null,
                $e->getMessage(),
            );

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get current session status from OpenWA 0.7.3
     *
     * OpenWA endpoint: GET /api/sessions/{sessionId}
     * Response: { id, name, status, phone, pushName, connectedAt, lastActive, createdAt, updatedAt }
     *
     * OpenWA SessionStatus enum values:
     *   created, initializing, qr_ready, authenticating, ready, disconnected, failed
     */
    public function getSessionStatus(): array
    {
        $sessionId = WhatsappSettings::getSessionId();

        if (empty($sessionId)) {
            Log::debug('[WhatsApp DEBUG] getSessionStatus() → No session ID configured');

            return ['status' => 'NOT_CONFIGURED', 'message' => 'No session ID configured'];
        }

        Log::debug('[WhatsApp DEBUG] getSessionStatus() → sessionId='.$sessionId);

        try {
            $response = $this->makeRequest('GET', "/sessions/{$sessionId}");

            Log::debug('[WhatsApp DEBUG] getSessionStatus() → HTTP status: '.$response->status());

            if ($response->successful()) {
                $data = $response->json();
                $data = is_array($data) ? $data : [];

                Log::debug('[WhatsApp DEBUG] getSessionStatus() → raw response: '.json_encode($data));

                // Map OpenWA status to extension status
                $owaStatus = $data['status'] ?? 'unknown';
                $mappedStatus = self::STATUS_MAP[$owaStatus] ?? 'UNKNOWN';

                Log::debug('[WhatsApp DEBUG] getSessionStatus() → OWA status: "'.$owaStatus.'" → mapped: "'.$mappedStatus.'"');

                return [
                    'status'      => $mappedStatus,
                    'phoneNumber' => $data['phone'] ?? null,
                    'pushName'    => $data['pushName'] ?? null,
                    'connectedAt' => $data['connectedAt'] ?? null,
                    'lastActive'  => $data['lastActive'] ?? null,
                    'lastError'   => $data['lastError'] ?? null,
                    'data'        => $data,
                ];
            }

            $errorData = $response->json();
            $errorData = is_array($errorData) ? $errorData : [];

            Log::warning('[WhatsApp] getSessionStatus() → error response: '.json_encode($errorData));

            // 404 likely means the session ID doesn't exist
            if ($response->status() === 404) {
                return ['status' => 'NOT_CONFIGURED', 'message' => 'Session "'.$sessionId.'" not found on OpenWA server. Create a new session or check the Session ID.'];
            }

            return ['status' => 'ERROR', 'message' => $errorData['message'] ?? 'Failed to get session status (HTTP '.$response->status().')'];
        } catch (\Exception $e) {
            Log::error('[WhatsApp] getSessionStatus() exception: '.$e->getMessage());

            return ['status' => 'UNREACHABLE', 'message' => 'Cannot reach OpenWA API: '.$e->getMessage()];
        }
    }

    /**
     * Get QR code for session authentication
     *
     * OpenWA endpoint: GET /api/sessions/{sessionId}/qr
     * Response: { qrCode: "data:image/png;base64,...", status: "qr_ready" }
     */
    public function getQrCode(): array
    {
        $sessionId = WhatsappSettings::getSessionId();

        if (empty($sessionId)) {
            return ['success' => false, 'error' => 'No session ID configured'];
        }

        Log::debug('[WhatsApp DEBUG] getQrCode() → sessionId='.$sessionId);

        try {
            $response = $this->makeRequest('GET', "/sessions/{$sessionId}/qr");

            Log::debug('[WhatsApp DEBUG] getQrCode() → HTTP status: '.$response->status());

            if ($response->successful()) {
                $data = $response->json();
                $data = is_array($data) ? $data : [];

                Log::debug('[WhatsApp DEBUG] getQrCode() → qrCode present: '.(!empty($data['qrCode']) ? 'YES ('.strlen($data['qrCode']).' chars)' : 'NO'));

                return [
                    'success' => true,
                    'image'   => $data['qrCode'] ?? null,
                    'status'  => self::STATUS_MAP[$data['status'] ?? ''] ?? ($data['status'] ?? 'UNKNOWN'),
                ];
            }

            $errorData = $response->json();
            $errorData = is_array($errorData) ? $errorData : [];

            Log::debug('[WhatsApp DEBUG] getQrCode() → error: '.json_encode($errorData));

            // 400 typically means session is already authenticated or not started
            $errorMsg = $errorData['message'] ?? 'QR code not available';
            if ($response->status() === 400) {
                $errorMsg = $errorData['message'] ?? 'QR code not ready — session may already be connected or not yet started';
            }

            return ['success' => false, 'error' => $errorMsg];
        } catch (\Exception $e) {
            Log::error('[WhatsApp] getQrCode() exception: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create a new session on OpenWA 0.7.3
     *
     * OpenWA endpoint: POST /api/sessions
     * Payload: { name: "session-name" }
     * Response: { id: "uuid", name: "session-name", status: "created", ... }
     *
     * Note: OpenWA generates a UUID for the session ID. The name must be
     * alphanumeric with hyphens, 3-50 chars.
     */
    public function createSession(string $name): array
    {
        Log::debug('[WhatsApp DEBUG] createSession() → name='.$name);

        try {
            // OpenWA 0.7.3: POST /sessions with { name: "..." }
            // The ID is auto-generated as a UUID by the server
            $response = $this->makeRequest('POST', '/sessions', [
                'name' => $name,
            ]);

            Log::debug('[WhatsApp DEBUG] createSession() → HTTP status: '.$response->status());
            Log::debug('[WhatsApp DEBUG] createSession() → response: '.json_encode($response->json()));

            if ($response->successful() || $response->status() === 201) {
                $data = $response->json();
                return [
                    'success' => true,
                    'data'    => is_array($data) ? $data : [],
                ];
            }

            $errorData = $response->json();
            $errorData = is_array($errorData) ? $errorData : [];

            // 409 = session name already exists
            $errorMsg = $errorData['message'] ?? 'Failed to create session';
            if ($response->status() === 409) {
                $errorMsg = 'A session with name "'.$name.'" already exists. Use a different name or select the existing session.';
            }

            Log::warning('[WhatsApp] createSession() → error: '.$errorMsg);

            return ['success' => false, 'error' => $errorMsg];
        } catch (\Exception $e) {
            Log::error('[WhatsApp] createSession() exception: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Start a session on OpenWA 0.7.3
     *
     * OpenWA endpoint: POST /api/sessions/{sessionId}/start
     */
    public function startSession(): array
    {
        $sessionId = WhatsappSettings::getSessionId();

        if (empty($sessionId)) {
            return ['success' => false, 'error' => 'No session ID configured'];
        }

        Log::debug('[WhatsApp DEBUG] startSession() → sessionId='.$sessionId);

        try {
            $response = $this->makeRequest('POST', "/sessions/{$sessionId}/start");

            Log::debug('[WhatsApp DEBUG] startSession() → HTTP status: '.$response->status());
            Log::debug('[WhatsApp DEBUG] startSession() → response: '.json_encode($response->json()));

            $data = $response->json();
            return [
                'success' => $response->successful(),
                'data'    => is_array($data) ? $data : [],
            ];
        } catch (\Exception $e) {
            Log::error('[WhatsApp] startSession() exception: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Log out a session on OpenWA 0.7.3 (disconnects it)
     *
     * OpenWA endpoint: POST /api/sessions/{sessionId}/logout
     */
    public function logoutSession(?string $sessionId = null): array
    {
        $sessionId = $sessionId ?: WhatsappSettings::getSessionId();

        if (empty($sessionId)) {
            return ['success' => false, 'error' => 'No session ID configured'];
        }

        Log::debug('[WhatsApp DEBUG] logoutSession() → sessionId='.$sessionId);

        try {
            $response = $this->makeRequest('POST', "/sessions/{$sessionId}/logout");

            Log::debug('[WhatsApp DEBUG] logoutSession() → HTTP status: '.$response->status());
            Log::debug('[WhatsApp DEBUG] logoutSession() → response: '.json_encode($response->json()));

            $data = $response->json();
            return [
                'success' => $response->successful(),
                'data'    => is_array($data) ? $data : [],
            ];
        } catch (\Exception $e) {
            Log::error('[WhatsApp] logoutSession() exception: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Delete a session on OpenWA 0.7.3
     *
     * OpenWA endpoint: DELETE /api/sessions/{sessionId}
     */
    public function deleteSession(string $sessionId): array
    {
        if (empty($sessionId)) {
            return ['success' => false, 'error' => 'Session ID is required'];
        }

        Log::debug('[WhatsApp DEBUG] deleteSession() → sessionId='.$sessionId);

        try {
            $response = $this->makeRequest('DELETE', "/sessions/{$sessionId}");

            Log::debug('[WhatsApp DEBUG] deleteSession() → HTTP status: '.$response->status());
            Log::debug('[WhatsApp DEBUG] deleteSession() → response: '.json_encode($response->json()));

            // If we delete the currently active session, we should clear it from settings
            if ($sessionId === WhatsappSettings::getSessionId()) {
                WhatsappSettings::set('session_id', '');
            }

            $data = $response->json();
            return [
                'success' => $response->successful(),
                'data'    => is_array($data) ? $data : [],
            ];
        } catch (\Exception $e) {
            Log::error('[WhatsApp] deleteSession() exception: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }


    /**
     * List all sessions from OpenWA 0.7.3
     *
     * OpenWA endpoint: GET /api/sessions
     * Response: Array of SessionResponseDto objects
     */
    public function listSessions(): array
    {
        Log::debug('[WhatsApp DEBUG] listSessions()');

        try {
            $response = $this->makeRequest('GET', '/sessions');

            Log::debug('[WhatsApp DEBUG] listSessions() → HTTP status: '.$response->status());

            if ($response->successful()) {
                $data = $response->json();
                $sessions = is_array($data) ? $data : [];

                Log::debug('[WhatsApp DEBUG] listSessions() → found '.count($sessions).' sessions');

                // Map OpenWA session response fields to what the views expect
                $mapped = array_map(function ($session) {
                    return [
                        'id'          => $session['id'] ?? '',
                        'name'        => $session['name'] ?? '',
                        'status'      => self::STATUS_MAP[$session['status'] ?? ''] ?? strtoupper($session['status'] ?? 'UNKNOWN'),
                        'phoneNumber' => $session['phone'] ?? null,
                        'pushName'    => $session['pushName'] ?? null,
                    ];
                }, $sessions);

                return [
                    'success'  => true,
                    'sessions' => $mapped,
                ];
            }

            Log::warning('[WhatsApp] listSessions() → failed with HTTP '.$response->status());

            return ['success' => false, 'sessions' => []];
        } catch (\Exception $e) {
            Log::error('[WhatsApp] listSessions() exception: '.$e->getMessage());

            return ['success' => false, 'sessions' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Test the connection to OpenWA API
     *
     * OpenWA 0.7.3: The health endpoint is at /api/health (under the global /api prefix)
     * Response: { status: "ok", timestamp: "...", version: "..." }
     */
    public function testConnection(): array
    {
        $apiUrl = WhatsappSettings::getApiUrl();
        Log::debug('[WhatsApp DEBUG] testConnection() → apiUrl='.$apiUrl);

        try {
            // OpenWA 0.7.3: health endpoint is at /api/health
            // Since apiUrl already ends with /api, we just append /health
            $healthUrl = $apiUrl.'/health';

            Log::debug('[WhatsApp DEBUG] testConnection() → trying: '.$healthUrl);

            $response = Http::timeout(10)
                ->withHeaders($this->getHeaders())
                ->get($healthUrl);

            Log::debug('[WhatsApp DEBUG] testConnection() → health HTTP status: '.$response->status());
            Log::debug('[WhatsApp DEBUG] testConnection() → health response: '.json_encode($response->json()));

            if ($response->successful()) {
                $data = $response->json();
                $version = $data['version'] ?? 'unknown';

                return ['success' => true, 'message' => 'Connected to OpenWA v'.$version];
            }

            // Try the sessions endpoint as fallback
            $sessionsUrl = $apiUrl.'/sessions';
            Log::debug('[WhatsApp DEBUG] testConnection() → fallback trying: '.$sessionsUrl);

            $response = Http::timeout(10)
                ->withHeaders($this->getHeaders())
                ->get($sessionsUrl);

            Log::debug('[WhatsApp DEBUG] testConnection() → sessions HTTP status: '.$response->status());

            return [
                'success' => $response->successful(),
                'message' => $response->successful()
                    ? 'Connected to OpenWA API (health endpoint returned non-200 but sessions endpoint works)'
                    : 'Connection failed: health returned '.$response->status().', sessions returned '.$response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('[WhatsApp] testConnection() exception: '.$e->getMessage());

            return ['success' => false, 'message' => 'Cannot connect to OpenWA at '.$apiUrl.': '.$e->getMessage()];
        }
    }

    /**
     * Run full diagnostic check — used by the debug console
     *
     * Returns detailed info about each step of the connection test.
     */
    public function runDiagnostics(): array
    {
        $results = [];
        $apiUrl = WhatsappSettings::getApiUrl();
        $apiKey = WhatsappSettings::getApiKey();
        $sessionId = WhatsappSettings::getSessionId();

        // Step 1: Configuration check
        $results['config'] = [
            'label'   => 'Configuration',
            'api_url' => $apiUrl,
            'api_key' => !empty($apiKey) ? substr($apiKey, 0, 12).'...' : '(empty)',
            'session_id' => $sessionId ?: '(not set)',
            'enabled' => WhatsappSettings::isEnabled(),
            'country_code' => WhatsappSettings::getDefaultCountryCode(),
            'status'  => !empty($apiUrl) && !empty($apiKey) ? 'ok' : 'error',
            'message' => !empty($apiUrl) && !empty($apiKey)
                ? 'Configuration present'
                : 'Missing '.(!empty($apiUrl) ? '' : 'API URL').(!empty($apiKey) ? '' : ' API Key'),
        ];

        // Step 2: Health check
        try {
            $healthUrl = $apiUrl.'/health';
            $response = Http::timeout(10)->withHeaders($this->getHeaders())->get($healthUrl);
            $healthData = $response->json();
            $results['health'] = [
                'label'    => 'Health Check',
                'url'      => $healthUrl,
                'http_status' => $response->status(),
                'response' => is_array($healthData) ? $healthData : ['raw' => (string) $response->body()],
                'status'   => $response->successful() ? 'ok' : 'error',
                'message'  => $response->successful()
                    ? 'API is healthy (v'.($healthData['version'] ?? '?').')'
                    : 'Health check failed with HTTP '.$response->status(),
            ];
        } catch (\Exception $e) {
            $results['health'] = [
                'label'   => 'Health Check',
                'url'     => $apiUrl.'/health',
                'status'  => 'error',
                'message' => 'Cannot reach API: '.$e->getMessage(),
            ];
        }

        // Step 3: Auth check (try listing sessions)
        try {
            $sessionsUrl = $apiUrl.'/sessions';
            $response = Http::timeout(10)->withHeaders($this->getHeaders())->get($sessionsUrl);
            $sessionsData = $response->json();
            $results['auth'] = [
                'label'       => 'Authentication',
                'url'         => $sessionsUrl,
                'http_status' => $response->status(),
                'status'      => $response->successful() ? 'ok' : ($response->status() === 401 ? 'auth_failed' : 'error'),
                'message'     => $response->successful()
                    ? 'API key is valid ('.count(is_array($sessionsData) ? $sessionsData : []).' sessions found)'
                    : ($response->status() === 401
                        ? 'API key is INVALID — check your API key in settings'
                        : 'Sessions endpoint returned HTTP '.$response->status()),
                'sessions'    => $response->successful() && is_array($sessionsData) ? array_map(fn($s) => [
                    'id' => $s['id'] ?? '?', 'name' => $s['name'] ?? '?', 'status' => $s['status'] ?? '?',
                ], $sessionsData) : [],
            ];
        } catch (\Exception $e) {
            $results['auth'] = [
                'label'   => 'Authentication',
                'status'  => 'error',
                'message' => 'Cannot list sessions: '.$e->getMessage(),
            ];
        }

        // Step 4: Session status check (if configured)
        if (!empty($sessionId)) {
            try {
                $sessionUrl = $apiUrl.'/sessions/'.$sessionId;
                $response = Http::timeout(10)->withHeaders($this->getHeaders())->get($sessionUrl);
                $sessionData = $response->json();
                $sessionData = is_array($sessionData) ? $sessionData : [];
                $results['session'] = [
                    'label'       => 'Session Status',
                    'url'         => $sessionUrl,
                    'http_status' => $response->status(),
                    'response'    => $sessionData,
                    'status'      => $response->successful() ? 'ok' : 'error',
                    'message'     => $response->successful()
                        ? 'Session "'.$sessionId.'" status: '.($sessionData['status'] ?? 'unknown').' → mapped: '.(self::STATUS_MAP[$sessionData['status'] ?? ''] ?? 'UNKNOWN')
                        : ($response->status() === 404
                            ? 'Session "'.$sessionId.'" NOT FOUND — create a new session or update the Session ID'
                            : 'Session check failed with HTTP '.$response->status()),
                ];
            } catch (\Exception $e) {
                $results['session'] = [
                    'label'   => 'Session Status',
                    'status'  => 'error',
                    'message' => 'Cannot check session: '.$e->getMessage(),
                ];
            }
        } else {
            $results['session'] = [
                'label'   => 'Session Status',
                'status'  => 'warning',
                'message' => 'No session ID configured — create or select a session first.',
            ];
        }

        return $results;
    }

    /**
     * Format a phone number for WhatsApp
     *
     * Converts local numbers to international format with @c.us suffix.
     * Examples:
     *   "3401234567" with country code "39" → "393401234567@c.us"
     *   "+39 340 123 4567" → "393401234567@c.us"
     *   "393401234567" → "393401234567@c.us"
     *   "393401234567@c.us" → "393401234567@c.us" (already formatted)
     */
    public function formatPhone(string $phone): string
    {
        // Already formatted for WhatsApp
        if (str_ends_with($phone, '@c.us')) {
            return $phone;
        }

        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Remove leading + if present
        $phone = ltrim($phone, '+');

        // Remove leading zeros (common in local Italian numbers like 0340... or 340...)
        $countryCode = WhatsappSettings::getDefaultCountryCode();

        // If the number doesn't start with the country code, prepend it
        if (!str_starts_with($phone, $countryCode)) {
            // Remove leading zero (Italian mobile numbers: 340... → 340...)
            $phone = ltrim($phone, '0');
            $phone = $countryCode.$phone;
        }

        return $phone.'@c.us';
    }

    /**
     * Replace template variables in a message
     */
    public function parseTemplate(string $template, array $params): string
    {
        $replacements = [
            '{{customer_name}}' => $params['customer_name'] ?? '',
            '{{first_name}}' => $params['first_name'] ?? '',
            '{{last_name}}' => $params['last_name'] ?? '',
            '{{order_id}}' => $params['order_id'] ?? $params['order_number'] ?? '',
            '{{order_number}}' => $params['order_number'] ?? $params['order_id'] ?? '',
            '{{order_total}}' => $params['order_total'] ?? '',
            '{{order_date}}' => $params['order_date'] ?? '',
            '{{order_time}}' => $params['order_time'] ?? '',
            '{{order_type}}' => $params['order_type'] ?? '',
            '{{order_status}}' => $params['status_name'] ?? '',
            '{{order_comment}}' => $params['order_comment'] ?? '',
            '{{delivery_comment}}' => $params['delivery_comment'] ?? '',
            '{{order_address}}' => $params['order_address'] ?? '',
            '{{order_payment}}' => $params['order_payment'] ?? '',
            '{{location_name}}' => $params['location_name'] ?? '',
            '{{location_address}}' => $params['location_address'] ?? '',
            '{{location_telephone}}' => $params['location_telephone'] ?? '',
            '{{telephone}}' => $params['telephone'] ?? '',
            '{{email}}' => $params['email'] ?? '',
            '{{status_comment}}' => $params['status_comment'] ?? '',
            '{{invoice_number}}' => $params['invoice_number'] ?? '',
            '{{order_view_url}}' => $params['order_view_url'] ?? '',
            // Reservation variables
            '{{reservation_id}}' => $params['reservation_id'] ?? '',
            '{{reservation_date}}' => $params['reservation_date'] ?? '',
            '{{reservation_time}}' => $params['reservation_time'] ?? '',
            '{{guest_num}}' => $params['guest_num'] ?? '',
            '{{table_name}}' => $params['table_name'] ?? '',
        ];

        // Also handle order_menus as a readable list
        if (!empty($params['order_menus']) && is_array($params['order_menus'])) {
            $menuLines = [];
            foreach ($params['order_menus'] as $menu) {
                $menuLines[] = ($menu['menu_quantity'] ?? 1).'x '
                    .($menu['menu_name'] ?? '')
                    .' - '.($menu['menu_subtotal'] ?? $menu['menu_price'] ?? '');
            }
            $replacements['{{order_items}}'] = implode("\n", $menuLines);
        } else {
            $replacements['{{order_items}}'] = '';
        }

        // Order totals as readable list
        if (!empty($params['order_totals']) && is_array($params['order_totals'])) {
            $totalLines = [];
            foreach ($params['order_totals'] as $total) {
                $totalLines[] = ($total['order_total_title'] ?? '').': '.($total['order_total_value'] ?? '');
            }
            $replacements['{{order_totals}}'] = implode("\n", $totalLines);
        } else {
            $replacements['{{order_totals}}'] = '';
        }

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Make an HTTP request to the OpenWA API
     *
     * All OpenWA 0.7.3 endpoints are under the /api global prefix.
     * The base URL in settings should be: http://host:2785/api
     */
    protected function makeRequest(string $method, string $endpoint, array $data = []): \Illuminate\Http\Client\Response
    {
        $url = WhatsappSettings::getApiUrl().$endpoint;

        Log::debug('[WhatsApp DEBUG] makeRequest() → '.$method.' '.$url);
        if (!empty($data)) {
            Log::debug('[WhatsApp DEBUG] makeRequest() → body: '.json_encode($data));
        }

        $request = Http::timeout(15)->withHeaders($this->getHeaders());

        $response = match (strtoupper($method)) {
            'GET' => $request->get($url, $data),
            'POST' => $request->post($url, $data),
            'PUT' => $request->put($url, $data),
            'DELETE' => $request->delete($url, $data),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };

        Log::debug('[WhatsApp DEBUG] makeRequest() → response HTTP '.$response->status());

        return $response;
    }

    /**
     * Get headers for OpenWA API requests
     *
     * OpenWA 0.7.3 uses X-API-Key header for authentication.
     */
    protected function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
            'X-API-Key'    => WhatsappSettings::getApiKey(),
        ];
    }
}
