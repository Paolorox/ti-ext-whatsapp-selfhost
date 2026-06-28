<?php

declare(strict_types=1);

namespace Igniter\Whatsapp;

use Igniter\Whatsapp\AutomationRules\Actions\SendWhatsappMessage;
use Igniter\Whatsapp\Classes\WhatsappChannel;
use Igniter\Whatsapp\Http\Controllers\Whatsapp;
use Igniter\Whatsapp\Models\WhatsappSettings;
use Igniter\System\Classes\BaseExtension;
use Igniter\System\Models\Settings;
use Override;

/**
 * WhatsApp Extension for TastyIgniter
 *
 * Integrates OpenWA (self-hosted WhatsApp API) to send
 * automated WhatsApp messages on order/reservation events.
 */
class Extension extends BaseExtension
{
    public array $singletons = [
        WhatsappChannel::class,
    ];

    #[Override]
    public function register(): void
    {
        parent::register();

        $this->registerSystemSettings();
    }

    #[Override]
    public function boot(): void
    {
        $viewsDir = __DIR__.'/../resources/views';
        $langDir = __DIR__.'/../resources/lang';

        $this->loadViewsFrom($viewsDir, 'igniter.whatsapp');
        $this->loadViewsFrom($viewsDir, 'paolorox.whatsapp');

        $this->loadTranslationsFrom($langDir, 'igniter.whatsapp');
        $this->loadTranslationsFrom($langDir, 'paolorox.whatsapp');

        if (app()->bound('translator')) {
            app('translator')->addNamespace('igniter.whatsapp', $langDir);
            app('translator')->addNamespace('paolorox.whatsapp', $langDir);
        }
    }

    /**
     * Register the automation action so it appears in the
     * Automation rules "actions" dropdown alongside SendMailTemplate.
     */
    public function registerAutomationRules(): array
    {
        return [
            'events' => [],
            'actions' => [
                SendWhatsappMessage::class,
            ],
            'conditions' => [],
        ];
    }

    #[Override]
    public function registerPermissions(): array
    {
        return [
            'Igniter.Whatsapp.Manage' => [
                'description' => 'Manage WhatsApp integration settings',
                'group' => 'igniter::admin.permissions.name',
            ],
        ];
    }

    #[Override]
    public function registerNavigation(): array
    {
        return [
            'tools' => [
                'child' => [
                    'whatsapp' => [
                        'priority' => 10,
                        'class' => 'whatsapp',
                        'href' => admin_url('igniter/whatsapp/whatsapp'),
                        'title' => lang('igniter.whatsapp::default.text_side_menu'),
                        'permission' => 'Igniter.Whatsapp.Manage',
                    ],
                ],
            ],
        ];
    }

    #[Override]
    public function registerSettings(): array
    {
        return [
            'settings' => [
                'priority' => 10,
                'label' => 'WhatsApp Settings',
                'description' => 'Configure WhatsApp integration with OpenWA API.',
                'icon' => 'fab fa-whatsapp',
                'model' => WhatsappSettings::class,
                'permissions' => ['Igniter.Whatsapp.Manage'],
            ],
        ];
    }

    protected function registerSystemSettings(): void
    {
        Settings::registerCallback(function (Settings $manager): void {
            $manager->registerSettingItems('core', [
                'whatsapp' => [
                    'label' => 'lang:igniter.whatsapp::default.text_title',
                    'description' => 'lang:igniter.whatsapp::default.text_description',
                    'icon' => 'fab fa-whatsapp',
                    'priority' => 10,
                    'permission' => ['Igniter.Whatsapp.Manage'],
                    'url' => admin_url('igniter/whatsapp/whatsapp'),
                ],
            ]);
        });
    }
}
