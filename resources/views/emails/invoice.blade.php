<p>Dear {{ $order->customer ? $order->customer->getFullName() : $order->from_name }},</p>
<p>You owe {{ $order->merchant->getFullName() }} (email: {{ $order->merchant->email }}) a sum of {{ $order->value }} SAGA.</p>
<p>Pay from the ETH address: {{ $order->from_address }}</p>
<p>Here is a copy of your receipt:</p>
<table>
    <thead>
        <tr>
            <th>Item</th>
            <th>Price</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($order->receipt_list as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td>{{ $item['price'] }} SAGA</td>
            </tr>
        @endforeach
    </tbody>
</table>

<p>To pay, please scan the following QR code:</p>
<img
    src="{!!
        $message->embedData(
            QrCode::format('png')
                ->size(500)
                ->merge('/storage/eth.png', 0.3)
                ->errorCorrection('H')
                ->generate('ethereum:' . \str_start($order->to_address, '0x')),
            'QrCode.png',
            'image/png'
        )
    !!}"
>
<br>
<small>Ethereum Address: <strong>{{ $order->to_address }}</strong></small>
<br>
<p>If you receive any emails that you suspect are fraudulent, please contact SAGA through our official website, <a href="https://saga.house">https://saga.house</a>.</p>
<p>This email was automatically sent by SAGA. Please do not reply to it.</p>
