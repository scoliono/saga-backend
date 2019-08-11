<p>Dear {{ $order->to_name }},</p>
<p>You owe {{ $order->sender->getFullName() }} (email: {{ $order->sender->email }}) a sum of {{ $order->value }} SAGA.</p>
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

<p>To pay, please scan {{$order->sender->btc && $order->sender->eth ? 'one of the following QR codes' : 'the following QR code' }}:</p>
@if ($order->sender->btc)
    <img src="{!! $message->embedData(QrCode::format('png')->size(500)->merge('/storage/btc.png', 0.3)->errorCorrection('H')->generate('bitcoin:' . $order->sender->btc), 'QrCode.png', 'image/png') !!}">
    <br>
    <small>Bitcoin Address: <strong>{{ $order->sender->btc }}</strong></small>
    <br>
@endif
@if ($order->sender->eth)
    <img src="{!! $message->embedData(QrCode::format('png')->size(500)->merge('/storage/eth.png', 0.3)->errorCorrection('H')->generate('ethereum:' . \str_start($order->sender->eth, '0x')), 'QrCode.png', 'image/png') !!}">
    <br>
    <small>Ethereum Address: <strong>{{ $order->sender->eth }}</strong></small>
    <br>
@endif
<p>If you receive any emails that you suspect are fraudulent, please contact SAGA through our official website, <a href="https://saga.house">https://saga.house</a>.</p>
<p>This email was automatically sent by SAGA. Please do not reply to it.</p>
