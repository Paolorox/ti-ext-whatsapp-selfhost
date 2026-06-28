<?php

declare(strict_types=1);

namespace Igniter\Whatsapp\AutomationRules\Actions;

use Igniter\Automation\AutomationException;
use Igniter\Automation\Classes\BaseAction;
use Igniter\Whatsapp\Classes\WhatsappChannel;
use Igniter\Whatsapp\Models\WhatsappSettings;
use Override;

/**
 * SendWhatsappMessage Automation Action
 *
 * Sends a WhatsApp message when triggered by an automation rule.
 * Works with Order and Reservation events.
 */
class SendWhatsappMessage extends BaseAction
{
    #[Override]
    public function actionDetails(): array
    {
        return [
            'name' => 'Send WhatsApp Message',
            'description' => 'Send a WhatsApp message to a recipient via OpenWA',
        ];
    }

    #[Override]
    public function defineFormFields(): array
    {
        return [
            'fields' => [
                'send_to' => [
                    'label' => 'lang:igniter.whatsapp::default.label_send_to',
                    'type' => 'select',
                ],
                'custom_phone' => [
                    'label' => 'lang:igniter.whatsapp::default.label_custom_phone',
                    'type' => 'text',
                    'comment' => 'lang:igniter.whatsapp::default.help_custom_phone',
                    'trigger' => [
                        'action' => 'show',
                        'field' => 'send_to',
                        'condition' => 'value[custom]',
                    ],
                ],
                'message_template' => [
                    'label' => 'lang:igniter.whatsapp::default.label_message_template',
                    'type' => 'textarea',
                    'attributes' => [
                        'rows' => 6,
                    ],
                    'comment' => 'lang:igniter.whatsapp::default.help_message_template',
                ],
            ],
        ];
    }

    #[Override]
    public function defineValidationRules(): array
    {
        return [
            'send_to' => ['required', 'string'],
            'message_template' => ['required', 'string'],
        ];
    }

    #[Override]
    public function triggerAction($params): void
    {
        if (!WhatsappSettings::isEnabled()) {
            return;
        }

        $messageTemplate = $this->model->message_template ?? '';
        if (empty($messageTemplate)) {
            throw new AutomationException('SendWhatsappMessage: Missing message template');
        }

        $phone = $this->getRecipientPhone($params);
        if (empty($phone)) {
            throw new AutomationException('SendWhatsappMessage: Unable to determine recipient phone number');
        }

        $channel = resolve(WhatsappChannel::class);

        // Parse template with event parameters
        $message = $channel->parseTemplate($messageTemplate, $params);

        // Determine order ID if available
        $orderId = null;
        if (isset($params['order']) && is_object($params['order'])) {
            $orderId = $params['order']->order_id ?? null;
        } elseif (isset($params['order_id'])) {
            $orderId = (int) $params['order_id'];
        }

        // Determine event type
        $eventType = 'automation';
        if (isset($params['order'])) {
            $eventType = 'order';
        } elseif (isset($params['reservation'])) {
            $eventType = 'reservation';
        }

        $channel->sendText($phone, $message, $eventType, $orderId);
    }

    /**
     * Options for the "send_to" dropdown
     */
    public function getSendToOptions(): array
    {
        return [
            'customer' => 'lang:igniter.whatsapp::default.text_send_to_customer',
            'restaurant' => 'lang:igniter.whatsapp::default.text_send_to_restaurant',
            'location' => 'lang:igniter.whatsapp::default.text_send_to_location',
            'custom' => 'lang:igniter.whatsapp::default.text_send_to_custom',
        ];
    }

    /**
     * Resolve the recipient phone number based on the send_to setting
     */
    protected function getRecipientPhone(array $params): ?string
    {
        $mode = $this->model->send_to ?? 'customer';

        switch ($mode) {
            case 'customer':
                // Try order telephone first
                if (!empty($params['telephone'])) {
                    return $params['telephone'];
                }
                // Try customer object
                $customer = $params['customer'] ?? null;
                if (is_object($customer) && !empty($customer->telephone)) {
                    return $customer->telephone;
                }
                // Try order object
                $order = $params['order'] ?? null;
                if (is_object($order) && !empty($order->telephone)) {
                    return $order->telephone;
                }
                // Try reservation object
                $reservation = $params['reservation'] ?? null;
                if (is_object($reservation) && !empty($reservation->telephone)) {
                    return $reservation->telephone;
                }

                return null;

            case 'restaurant':
                return setting('site_telephone') ?: null;

            case 'location':
                $location = $params['location'] ?? null;
                if (is_object($location) && !empty($location->location_telephone)) {
                    return $location->location_telephone;
                }
                $order = $params['order'] ?? null;
                if (is_object($order) && $order->location && !empty($order->location->location_telephone)) {
                    return $order->location->location_telephone;
                }

                return null;

            case 'custom':
                return $this->model->custom_phone ?? null;

            default:
                return null;
        }
    }
}
