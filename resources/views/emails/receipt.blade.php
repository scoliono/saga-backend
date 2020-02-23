<p>Your SAGA transaction has been confirmed on {{ $order->updated_at }}</p>

<p>Here is a copy of your receipt:</p>
<table>
    <thead>
        <tr>
            <th>Description</th>
            <th>Rate</th>
            <th>Quantity</th>
            <th>Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($order->receipt_list as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td>{{ $item['rate'] }} SAGA</td>
                <td>{{ $item['quantity'] }}</td>
                <td>{{ \round($item['rate'] * $item['quantity'], 2) }} SAGA</td>
            </tr>
        @endforeach
    </tbody>
</table>
@if ($order->discount_list)
    <p>The following discounts/fees were applied to the order:</p>
    <ul>
        @foreach ($order->discount_list as $discount)
            <li><strong>{{ $discount['name'] }}</strong>: {{ 100.0 * $discount['rate'] }}%</li>
        @endforeach
    </ul>
@endif
@if ($order->memo)
    <p><strong>Memo:</strong> <code>{{ $order->memo }}</code></p>
@endif

<p>Customer: {{ $order->from_name }} ({{ $order->from_email }}) paying from ETH address {{ $order->from_address }}</p>
<p>Merchant: {{ $order->merchant->getFullName() }} ({{ $order->merchant->email }}) receiving at ETH address {{ $order->to_address }}</p>
<p>Amount Due: {{ $order->value }} SAGA</p>

<p>If you receive any emails that you suspect are fraudulent, please contact SAGA through our official website, <a href="https://saga.house">https://saga.house</a>.</p>
<p>This email was automatically sent by SAGA. Please do not reply to it.</p>
