<?php

/**
 * WhatsApp Settings form configuration
 */

return [
    'form' => [
        'toolbar' => [
            'buttons' => [
                'save' => [
                    'label' => 'lang:admin::lang.button_save',
                    'class' => 'btn btn-primary',
                    'data-request' => 'onSave',
                ],
                'saveClose' => [
                    'label' => 'lang:admin::lang.button_save_close',
                    'class' => 'btn btn-default',
                    'data-request' => 'onSave',
                    'data-request-data' => 'close:1',
                ],
            ],
        ],
        'fields' => [
            'is_enabled' => [
                'label' => 'lang:igniter.whatsapp::default.label_enabled',
                'type' => 'switch',
                'span' => 'left',
                'default' => false,
                'comment' => 'lang:igniter.whatsapp::default.help_enabled',
            ],
            'default_country_code' => [
                'label' => 'lang:igniter.whatsapp::default.label_country_code',
                'type' => 'select',
                'span' => 'right',
                'default' => '39',
                'options' => 'getCountryCodeOptions',
                'comment' => 'lang:igniter.whatsapp::default.help_country_code',
            ],
        ],
        'tabs' => [
            'fields' => [
                'api_url' => [
                    'tab' => 'lang:igniter.whatsapp::default.tab_connection',
                    'label' => 'lang:igniter.whatsapp::default.label_api_url',
                    'type' => 'text',
                    'span' => 'left',
                    'comment' => 'lang:igniter.whatsapp::default.help_api_url',
                ],
                'api_key' => [
                    'tab' => 'lang:igniter.whatsapp::default.tab_connection',
                    'label' => 'lang:igniter.whatsapp::default.label_api_key',
                    'type' => 'text',
                    'span' => 'right',
                    'comment' => 'lang:igniter.whatsapp::default.help_api_key',
                ],
                'session_id' => [
                    'tab' => 'lang:igniter.whatsapp::default.tab_connection',
                    'label' => 'lang:igniter.whatsapp::default.label_session_id',
                    'type' => 'text',
                    'span' => 'left',
                    'comment' => 'lang:igniter.whatsapp::default.help_session_id',
                ],
            ],
        ],
        'rules' => [
            ['is_enabled', 'lang:igniter.whatsapp::default.label_enabled', 'required|boolean'],
            ['api_url', 'lang:igniter.whatsapp::default.label_api_url', 'required|url'],
            ['api_key', 'lang:igniter.whatsapp::default.label_api_key', 'required|string'],
            ['session_id', 'lang:igniter.whatsapp::default.label_session_id', 'nullable|string'],
            ['default_country_code', 'lang:igniter.whatsapp::default.label_country_code', 'required|string|max:5'],
        ],
    ],
];
