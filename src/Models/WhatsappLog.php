<?php

declare(strict_types=1);

namespace Igniter\Whatsapp\Models;

use Igniter\Flame\Database\Model;

/**
 * WhatsApp Log Model - tracks sent messages
 *
 * @property int $id
 * @property int|null $order_id
 * @property string $phone_number
 * @property string $message
 * @property string $event_type
 * @property string $status
 * @property array|null $response_data
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class WhatsappLog extends Model
{
    protected $table = 'igniter_whatsapp_logs';

    protected $fillable = [
        'order_id',
        'phone_number',
        'message',
        'event_type',
        'status',
        'response_data',
        'error_message',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'response_data' => 'json',
    ];

    public const string STATUS_SENT = 'sent';
    public const string STATUS_FAILED = 'failed';
    public const string STATUS_PENDING = 'pending';

    /**
     * Log a sent message
     */
    public static function logMessage(
        string $phone,
        string $message,
        string $eventType,
        string $status,
        ?int $orderId = null,
        ?array $response = null,
        ?string $error = null,
    ): self {
        return self::create([
            'order_id' => $orderId,
            'phone_number' => $phone,
            'message' => $message,
            'event_type' => $eventType,
            'status' => $status,
            'response_data' => $response,
            'error_message' => $error,
        ]);
    }
}
