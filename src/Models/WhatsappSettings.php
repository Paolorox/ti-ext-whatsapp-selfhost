<?php

declare(strict_types=1);

namespace Igniter\Whatsapp\Models;

use Igniter\Flame\Database\Model;
use Igniter\System\Actions\SettingsModel;

/**
 * WhatsApp Settings Model
 *
 * @method static mixed get(string $key, mixed $default = null)
 * @method static bool set(string|array $key, mixed $value)
 * @mixin SettingsModel
 */
class WhatsappSettings extends Model
{
    public array $implement = [SettingsModel::class];

    public string $settingsCode = 'igniter_whatsapp_settings';

    public string $settingsFieldsConfig = 'whatsappsettings';

    /**
     * Get the full API URL for OpenWA
     */
    public static function getApiUrl(): string
    {
        return rtrim((string) self::get('api_url', ''), '/');
    }

    /**
     * Get the API key for OpenWA
     */
    public static function getApiKey(): string
    {
        return (string) self::get('api_key', '');
    }

    /**
     * Get the active session ID
     */
    public static function getSessionId(): string
    {
        return (string) self::get('session_id', '');
    }

    /**
     * Get default country code
     */
    public static function getDefaultCountryCode(): string
    {
        return (string) self::get('default_country_code', '39');
    }

    /**
     * Check if WhatsApp integration is enabled
     */
    public static function isEnabled(): bool
    {
        return (bool) self::get('is_enabled', false);
    }

    /**
     * Get country code options for the settings form dropdown
     */
    public function getCountryCodeOptions(): array
    {
        return trans('igniter.whatsapp::default.text_country_codes');
    }
}
