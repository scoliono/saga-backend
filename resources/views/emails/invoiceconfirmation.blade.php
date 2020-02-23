<p>Dear {{ $order->from_name }},</p>
<p>You owe {{ $order->merchant->getFullName() }} (email: {{ $order->merchant->email }}) a sum of {{ $order->value }} SAGA.</p>
<p>Here is a copy of your receipt:</p>
<table>
    <thead>
        <tr>
            <th>Description</th>
            <th>Rate</th>
            <th>Quantity</th>
            <th>Value</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($order->receipt_list as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td>{{ $item['rate'] }} SAGA</td>
                <td>{{ $item['quantity'] }}</td>
                <td>{{ \round($item['quantity'] * $item['rate'], 2) }} SAGA</td>
            </tr>
        @endforeach
    </tbody>
</table>
@if ($order->discount_list)
    <p>The following discounts/fees were applied to your order:</p>
    <ul>
        @foreach ($order->discount_list as $discount)
            <li><strong>{{ $discount['name'] }}</strong>: {{ 100.0 * $discount['rate'] }}%</li>
        @endforeach
    </ul>
@endif
@if ($order->memo)
    <p><strong>Memo:</strong> <code>{{ $order->memo }}</code></p>
@endif
<p><strong>BALANCE DUE:</strong> {{ $order->value }} SAGA</p>

<p>Please go to our website with the button below to approve the transaction. Then, we will give you the merchant's Ethereum address for you to pay.</p>
<p><a class="button" href="{{ $url }}">VIEW INVOICE</a></p>
<p><small>If the above button does not work, paste the following URL into your address bar: <a href="{{ $url }}">{{ $url }}</a></small></p>
<p><strong>- SAGA</strong></p>
<p>If you receive any emails that you suspect are fraudulent, please contact SAGA through our official website, <a href="https://saga.house">https://saga.house</a>.</p>
<p>This email was automatically sent by SAGA. Please do not reply to it. If you believe you were sent this in error, ignore this message.</p>
