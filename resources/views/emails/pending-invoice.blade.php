<p>Dear {{ $order->from_name }},</p>
<p>You owe {{ $order->merchant->getFullName() }} (email: {{ $order->merchant->email }}) a sum of {{ $order->value }} SAGA.</p>
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

<p>Please go to our website and enter the Ethereum address you will be paying with. Then, we will give you the merchant's address.</p>
<button>PAY</button>
<strong>- SAGA</strong>
<br>
<p>If you receive any emails that you suspect are fraudulent, please contact SAGA through our official website, <a href="https://saga.house">https://saga.house</a>.</p>
<p>This email was automatically sent by SAGA. Please do not reply to it. If you believe you were sent this in error, ignore this message.</p>
