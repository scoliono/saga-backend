@component('mail::message')
# Dear {{ $order->from_name }},

You owe {{ $order->merchant->getFullName() }} ({{ $order->merchant->email }}) a sum of {{ $order->value }} SAGA.

Here is a copy of your receipt:

@component('mail::table')
| Description | Rate | Quantity | Value |
|:-----------:|:----:|:--------:|:-----:|
@foreach ($order->receipt_list as $item)
| {{ $item['name'] }} | {{ $item['rate'] }} SAGA | {{ $item['quantity'] }} | {{ \round($item['quantity'] * $item['rate'], 2) }} SAGA |
@endforeach
@endcomponent

@if ($order->discount_list)
The following discounts/fees were applied to your order:
@foreach ($order->discount_list as $discount)
    - **{{ $discount['name'] }}**: {{ 100.0 * $discount['rate'] }}%
@endforeach
@endif

@if ($order->memo)
**Memo:** {{ $order->memo }}
@endif

## Balance Due

{{ $order->value }} SAGA

Please go to our website with the button below to approve the transaction. Then, we will give you the merchant's Ethereum address for you to pay.

@component('mail::button', ['url' => $url, 'color' => 'primary'])
View Invoice
@endcomponent

Regards,<br>
{{ config('app.name') }}

@slot('subcopy')
If the above button does not work, paste the following URL into your address bar: [{{ $url }}]({{ $url }})

If you receive any emails that you suspect are fraudulent, please contact SAGA through our official website, [SAGA.money]({{ config('app.frontend_url') }}).

This email was automatically sent by SAGA. Please do not reply to it. If you believe you were sent this in error, ignore this message.
@endslot
@endcomponent
