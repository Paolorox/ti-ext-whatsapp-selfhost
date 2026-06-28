<p align="center">
    <a href="https://packagist.org/packages/paolorox/ti-ext-whatsapp-selfhost"><img src="https://img.shields.io/packagist/dt/paolorox/ti-ext-whatsapp-selfhost" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/paolorox/ti-ext-whatsapp-selfhost"><img src="https://img.shields.io/packagist/v/paolorox/ti-ext-whatsapp-selfhost" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/paolorox/ti-ext-whatsapp-selfhost"><img src="https://img.shields.io/packagist/l/paolorox/ti-ext-whatsapp-selfhost" alt="License"></a>
</p>

## Introduction

The **WhatsApp Integration** extension for TastyIgniter allows you to send automated WhatsApp messages on critical events like order placements, status updates, and reservations. 


By integrating with a self-hosted **OpenWA (WhatsApp Automate)** server API, this free version ensures quick, cost-effective, and fully customizable messaging directly to your customers' and staff's mobile phones.

## Features

- **Event-Driven Automations**: Fully integrated with TastyIgniter's core `Automation` rules. Trigger WhatsApp notifications on order events, reservation events, status changes, and more.
- **Dynamic Message Templates**: Compose messages using a standard template format containing rich, live details such as customer names, order totals, order items, and URLs.
- **Admin Dashboard**: Scan QR codes to start your session, check real-time connection logs, and run diagnostics directly from the TastyIgniter backend.
- **Message Logs**: A built-in log viewer records all sent, pending, or failed WhatsApp messages with details to trace API response statuses.
- **Flexible Recipients**: Send notifications to customers, specific store locations, restaurant administrators, or custom phone numbers.

## Requirements

Ensure your setup meets the following requirements:
- **TastyIgniter**: `^4.0`
- **Automation Extension** (`tastyigniter/ti-ext-automation`): `^4.0`
- A running, self-hosted instance of the [OpenWA API](https://github.com/rmyndharis/OpenWA).

## Installation

You can install this extension via Composer:

```bash
composer require paolorox/ti-ext-whatsapp-selfhost
```

Alternatively, you can upload the files to your extension folder (`extensions/igniter/whatsapp`) and run migrations:

```bash
php artisan igniter:up
```

## Configuration

### 1. Configure Connection Settings
Go to **System > Settings > WhatsApp Settings** in the TastyIgniter admin panel and configure:
- **API URL**: Your self-hosted OpenWA server URL (e.g. `http://your-server-ip:2785/api`).
- **API Key**: The authentication key set up in your OpenWA configuration.
- **Default Country Code**: The default international country prefix (e.g., `39` for Italy) to use when phone numbers do not include a country code.
- **Enable WhatsApp**: Toggle to enable/disable message sending globally.

### 2. Session Activation & QR Code Scan
Go to **Tools > WhatsApp** (the WhatsApp Dashboard):
1. Click **Create Session** (using a session ID matching your configuration).
2. Click **Start Session** and wait a moment for the status to change.
3. Click **Show QR Code** and scan the QR code using your WhatsApp mobile app to authorize the connection.
4. You can use the **Send Test Message** feature on the dashboard to test the configuration.

## Usage in Automation Rules

This extension registers a new action called **Send WhatsApp Message** in TastyIgniter's Automation module:

1. Navigate to **Tools > Automation** and create or edit a Rule (e.g., *Order Created* or *Reservation Confirmed*).
2. Under the **Actions** tab, click **Add action** and select **Send WhatsApp Message**.
3. Choose who to send the message to (*Customer*, *Restaurant*, *Location*, or *Custom Number*).
4. Draft your message in the **Message Template** text area.

### Available Template Variables
Depending on the event triggering the action, you can use the following variables:
- `{{customer_name}}` - Customer full name
- `{{order_id}}` / `{{reservation_id}}` - Unique identifier
- `{{order_total}}` - Order total amount formatted
- `{{order_items}}` - Summary of items in the order
- `{{order_type}}` - Delivery, Pick-up, etc.
- `{{order_date}}` & `{{order_time}}` - Date and time of order/delivery
- `{{order_address}}` - Delivery address
- `{{location_name}}` - Restaurant location name
- `{{order_view_url}}` - Link to view the order online

## Support & Contributing

If you encounter any issues or want to contribute to this extension:
- Open a GitHub issue on [ti-ext-whatsapp-selfhost Issues](https://github.com/Paolorox/ti-ext-whatsapp-selfhost/issues).
- Submit a pull request with bug fixes or new features.

## License

This extension is open-sourced software licensed under the [MIT license](LICENSE).
