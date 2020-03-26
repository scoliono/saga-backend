@component('mail::message')
# Dear {{ $customer ? $order->from_name : $order->merchant->getFullName() }},

@if ($customer)
Your payment to {{ $order->merchant->getFullName() }} ({{ $order->merchant->email }}) was successfully completed at {{ $order->updated_at }}.
@else
The customer {{ $order->from_name }} ({{ $order->from_email }}) fulfilled their invoice at {{ $order->updated_at }}.
@endif

Here is a copy of the receipt:

@component('mail::table')
| Description | Rate | Quantity | Value |
|:-----------:|:----:|:--------:|:-----:|
@foreach ($order->receipt_list as $item)
| {{ $item['name'] }} | {{ $item['rate'] }} SAGA | {{ $item['quantity'] }} | {{ \round($item['quantity'] * $item['rate'], 2) }} SAGA |
@endforeach
@endcomponent

@if ($order->discount_list)
The following discounts/fees were applied:
@foreach ($order->discount_list as $discount)
    - **{{ $discount['name'] }}**: {{ 100.0 * $discount['rate'] }}%
@endforeach
@endif

@if ($order->memo)
**Memo:** {{ $order->memo }}
@endif

**Customer**: {{ $order->from_name }} ({{ $order->from_email }}) paying from ETH address {{ $order->from_address }}

**Merchant:** {{ $order->merchant->getFullName() }} ({{ $order->merchant->email }}) receiving at ETH address {{ $order->to_address }}

## Balance Due

{{ $order->value }} SAGA

All the details of the transaction are available at [SAGA.money]({{ config('app.frontend_url') }}/history).

Regards,<br>
{{ config('app.name') }}

@slot('subcopy')
If you receive any emails that you suspect are fraudulent, please contact SAGA through our official website, [SAGA.money]({{ config('app.frontend_url') }}).

This email was automatically sent by SAGA. Please do not reply to it. If you believe you were sent this in error, ignore this message.
@endslot
@endcomponent
