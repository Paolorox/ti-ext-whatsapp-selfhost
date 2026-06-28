<?php

declare(strict_types=1);

namespace Igniter\Whatsapp\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Whatsapp\Classes\WhatsappChannel;
use Igniter\Whatsapp\Models\WhatsappLog;
use Igniter\Whatsapp\Models\WhatsappSettings;

/**
 * WhatsApp Admin Controller
 *
 * Provides the admin interface for WhatsApp settings,
 * QR code scanning, connection testing, and message logs.
 */
class Whatsapp extends AdminController
{
    public array $viewPath = ['igniter.whatsapp::whatsapp', 'whatsapp'];

    protected null|string|array $requiredPermissions = 'Igniter.Whatsapp.Manage';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('tools', 'whatsapp');
    }

    /**
     * Main settings/dashboard page
     */
    public function index()
    {
        $this->pageTitle = lang('igniter.whatsapp::default.text_title');

        $channel = resolve(WhatsappChannel::class);

        $this->vars['settings'] = [
            'api_url' => WhatsappSettings::getApiUrl(),
            'api_key' => WhatsappSettings::getApiKey(),
            'session_id' => WhatsappSettings::getSessionId(),
            'default_country_code' => WhatsappSettings::getDefaultCountryCode(),
            'is_enabled' => WhatsappSettings::isEnabled(),
        ];

        $this->vars['sessionStatus'] = $channel->getSessionStatus();
        
        $sessionsResult = $channel->listSessions();
        $this->vars['sessions'] = $sessionsResult['sessions'] ?? [];

        // Available template variables for reference
        $this->vars['templateVariables'] = [
            '{{customer_name}}', '{{first_name}}', '{{last_name}}',
            '{{order_id}}', '{{order_number}}', '{{order_total}}',
            '{{order_date}}', '{{order_time}}', '{{order_type}}',
            '{{order_status}}', '{{order_items}}', '{{order_totals}}',
            '{{order_address}}', '{{order_payment}}', '{{order_comment}}',
            '{{location_name}}', '{{location_address}}', '{{location_telephone}}',
            '{{telephone}}', '{{email}}', '{{status_comment}}',
            '{{invoice_number}}', '{{order_view_url}}',
            '{{reservation_id}}', '{{reservation_date}}', '{{reservation_time}}',
            '{{guest_num}}', '{{table_name}}',
        ];

        return $this->makeView('igniter.whatsapp::whatsapp.index');
    }

    /**
     * AJAX: Test connection to OpenWA API
     */
    public function onTestConnection(): array
    {
        $channel = resolve(WhatsappChannel::class);
        $result = $channel->testConnection();

        if ($result['success']) {
            flash()->success(lang('igniter.whatsapp::default.alert_connection_success').': '.($result['message'] ?? ''));
        } else {
            flash()->error(lang('igniter.whatsapp::default.alert_connection_failed').': '.($result['message'] ?? ''));
        }

        return ['~flash' => true];
    }

    /**
     * AJAX: Get QR code from OpenWA
     */
    public function onGetQrCode(): array
    {
        $channel = resolve(WhatsappChannel::class);
        $result = $channel->getQrCode();

        return [
            '#whatsapp-qrcode-container' => view('igniter.whatsapp::whatsapp.qrcode', ['qrData' => $result])->render(),
        ];
    }

    /**
     * AJAX: Refresh session status
     */
    public function onRefreshStatus(): array
    {
        $channel = resolve(WhatsappChannel::class);
        $status = $channel->getSessionStatus();
        $listResult = $channel->listSessions();
        $qrResult = $channel->getQrCode();

        return [
            '#whatsapp-status-container' => view('igniter.whatsapp::whatsapp.status', ['sessionStatus' => $status])->render(),
            '#whatsapp-qrcode-container' => view('igniter.whatsapp::whatsapp.qrcode', ['qrData' => $qrResult])->render(),
            '#whatsapp-sessions-container' => view('igniter.whatsapp::whatsapp.sessions', [
                'sessions'         => $listResult['sessions'] ?? [],
                'currentSessionId' => WhatsappSettings::getSessionId(),
            ])->render(),
        ];
    }

    /**
     * AJAX: Send a test message
     */
    public function onSendTestMessage(): array
    {
        $phone = post('test_phone');
        $message = post('test_message', 'Test message from TastyIgniter WhatsApp Integration! ✅');

        if (empty($phone)) {
            flash()->error(lang('igniter.whatsapp::default.alert_test_phone_required'));

            return ['~flash' => true];
        }

        $channel = resolve(WhatsappChannel::class);
        $result = $channel->sendText($phone, $message, 'test');

        if ($result['success']) {
            flash()->success(lang('igniter.whatsapp::default.alert_test_sent'));
        } else {
            flash()->error(lang('igniter.whatsapp::default.alert_test_failed').': '.($result['error'] ?? ''));
        }

        return ['~flash' => true];
    }

    /**
     * AJAX: Create and start a new session
     *
     * OpenWA 0.7.3 creates sessions with {name} and returns a UUID as ID.
     */
    public function onCreateSession(): array
    {
        $sessionName = post('session_name', 'tastyigniter');
        $channel = resolve(WhatsappChannel::class);

        $result = $channel->createSession($sessionName);

        if ($result['success']) {
            $sessionData = $result['data'];
            // OpenWA 0.7.3 returns the UUID in the 'id' field
            $sessionId = $sessionData['id'] ?? '';

            if (!empty($sessionId)) {
                WhatsappSettings::set('session_id', $sessionId);

                // Start the session immediately after creation
                $startResult = $channel->startSession();
                if ($startResult['success']) {
                    flash()->success(lang('igniter.whatsapp::default.alert_session_created').' Session ID: '.$sessionId);
                } else {
                    flash()->warning(lang('igniter.whatsapp::default.alert_session_created').' (but start failed: '.($startResult['error'] ?? 'unknown').')');
                }
            } else {
                flash()->warning('Session created but no ID returned — check OpenWA logs.');
            }
        } else {
            flash()->error(lang('igniter.whatsapp::default.alert_session_failed').': '.($result['error'] ?? ''));
        }

        $status = $channel->getSessionStatus();
        $listResult = $channel->listSessions();
        $qrResult = $channel->getQrCode();

        return [
            '~flash' => true,
            '#whatsapp-status-container' => view('igniter.whatsapp::whatsapp.status', ['sessionStatus' => $status])->render(),
            '#whatsapp-qrcode-container' => view('igniter.whatsapp::whatsapp.qrcode', ['qrData' => $qrResult])->render(),
            '#whatsapp-sessions-container' => view('igniter.whatsapp::whatsapp.sessions', [
                'sessions'         => $listResult['sessions'] ?? [],
                'currentSessionId' => WhatsappSettings::getSessionId(),
            ])->render(),
        ];
    }

    /**
     * AJAX: List available sessions from OpenWA
     */
    public function onListSessions(): array
    {
        $channel = resolve(WhatsappChannel::class);
        $result = $channel->listSessions();

        return [
            '#whatsapp-sessions-container' => view('igniter.whatsapp::whatsapp.sessions', [
                'sessions'         => $result['sessions'] ?? [],
                'currentSessionId' => WhatsappSettings::getSessionId(),
            ])->render(),
        ];
    }

    /**
     * AJAX: Select a session
     */
    public function onSelectSession(): array
    {
        $sessionId = post('session_id');

        if (empty($sessionId)) {
            flash()->error('Session ID is required');
            return ['~flash' => true];
        }

        WhatsappSettings::set('session_id', $sessionId);
        flash()->success('Session selected: '.$sessionId);

        $channel = resolve(WhatsappChannel::class);
        $status = $channel->getSessionStatus();
        $listResult = $channel->listSessions();
        $qrResult = $channel->getQrCode();

        return [
            '~flash' => true,
            '#whatsapp-status-container' => view('igniter.whatsapp::whatsapp.status', ['sessionStatus' => $status])->render(),
            '#whatsapp-qrcode-container' => view('igniter.whatsapp::whatsapp.qrcode', ['qrData' => $qrResult])->render(),
            '#whatsapp-sessions-container' => view('igniter.whatsapp::whatsapp.sessions', [
                'sessions'         => $listResult['sessions'] ?? [],
                'currentSessionId' => WhatsappSettings::getSessionId(),
            ])->render(),
        ];
    }

    /**
     * AJAX: Log out/Disconnect a session
     */
    public function onLogoutSession(): array
    {
        $sessionId = post('session_id') ?: WhatsappSettings::getSessionId();

        $channel = resolve(WhatsappChannel::class);
        $result = $channel->logoutSession($sessionId);

        if ($result['success']) {
            flash()->success('Session disconnected successfully!');
        } else {
            flash()->error('Failed to disconnect session: '.($result['error'] ?? 'unknown'));
        }

        $status = $channel->getSessionStatus();
        $listResult = $channel->listSessions();
        $qrResult = $channel->getQrCode();

        return [
            '~flash' => true,
            '#whatsapp-status-container' => view('igniter.whatsapp::whatsapp.status', ['sessionStatus' => $status])->render(),
            '#whatsapp-qrcode-container' => view('igniter.whatsapp::whatsapp.qrcode', ['qrData' => $qrResult])->render(),
            '#whatsapp-sessions-container' => view('igniter.whatsapp::whatsapp.sessions', [
                'sessions'         => $listResult['sessions'] ?? [],
                'currentSessionId' => WhatsappSettings::getSessionId(),
            ])->render(),
        ];
    }

    /**
     * AJAX: Delete an existing session
     */
    public function onDeleteSession(): array
    {
        $sessionId = post('session_id');

        if (empty($sessionId)) {
            flash()->error('Session ID is required');
            return ['~flash' => true];
        }

        $channel = resolve(WhatsappChannel::class);
        $result = $channel->deleteSession($sessionId);

        if ($result['success']) {
            flash()->success('Session deleted successfully!');
        } else {
            flash()->error('Failed to delete session: '.($result['error'] ?? 'unknown'));
        }

        $status = $channel->getSessionStatus();
        $listResult = $channel->listSessions();
        $qrResult = $channel->getQrCode();

        return [
            '~flash' => true,
            '#whatsapp-status-container' => view('igniter.whatsapp::whatsapp.status', ['sessionStatus' => $status])->render(),
            '#whatsapp-qrcode-container' => view('igniter.whatsapp::whatsapp.qrcode', ['qrData' => $qrResult])->render(),
            '#whatsapp-sessions-container' => view('igniter.whatsapp::whatsapp.sessions', [
                'sessions'         => $listResult['sessions'] ?? [],
                'currentSessionId' => WhatsappSettings::getSessionId(),
            ])->render(),
        ];
    }

    /**
     * AJAX: Run full diagnostics — debug console
     */
    public function onRunDiagnostics(): array
    {
        $channel = resolve(WhatsappChannel::class);
        $diagnostics = $channel->runDiagnostics();

        return [
            '#whatsapp-debug-container' => view('igniter.whatsapp::whatsapp.debug', ['diagnostics' => $diagnostics])->render(),
        ];
    }

    /**
     * AJAX: Start an existing session
     */
    public function onStartSession(): array
    {
        $channel = resolve(WhatsappChannel::class);
        $result = $channel->startSession();

        if ($result['success']) {
            flash()->success('Session started successfully!');
        } else {
            flash()->error('Failed to start session: '.($result['error'] ?? $result['data']['message'] ?? 'unknown'));
        }

        $status = $channel->getSessionStatus();
        $listResult = $channel->listSessions();
        $qrResult = $channel->getQrCode();

        return [
            '~flash' => true,
            '#whatsapp-status-container' => view('igniter.whatsapp::whatsapp.status', ['sessionStatus' => $status])->render(),
            '#whatsapp-qrcode-container' => view('igniter.whatsapp::whatsapp.qrcode', ['qrData' => $qrResult])->render(),
            '#whatsapp-sessions-container' => view('igniter.whatsapp::whatsapp.sessions', [
                'sessions'         => $listResult['sessions'] ?? [],
                'currentSessionId' => WhatsappSettings::getSessionId(),
            ])->render(),
        ];
    }
}
