@extends('layouts.bulma')

@section('title')
Confirm Invoice
@endsection

@section('head')
<meta id="order_id" name="order_id" content="{{ $tx->id }}">
@endsection

@section('content')
<nav class="panel">
    <p class="panel-heading">Confirm Invoice</p>
    <div class="panel-block">
        <div class="columns">
            <div class="column is-narrow">
                <small>FROM</small>
                <p class="has-text-weight-bold">{{ $tx->merchant->getFullName() }}</p>
                <p>{{ $tx->merchant->location }}</p>
                <p>{{ $tx->merchant->phone }}</p>
            </div>
            <div class="column is-narrow">
                <small>TO</small>
                <p class="has-text-weight-bold">{{ $tx->customer ? $tx->customer->getFullName() : $tx->from_name }}</p>
                <p><a href="mailto:{{ $tx->customer->email ?? $tx->from_email }}">{{ $tx->customer->email ?? $tx->from_email }}</a></p>
            </div>
        </div>
    </div>
    <div class="panel-block">
        <div class="content">
            <p><span class="has-text-weight-bold">Invoice #:</span> {{ $tx->id }}</p>
            <p><span class="has-text-weight-bold">Date:</span> {{ $tx->created_at->toRfc7231String() }}</p>
        </div>
    </div>
    <div class="panel-block">
        <table class="table is-fullwidth is-hoverable">
            <thead>
                <tr>
                    <th style="width:60%;">DESCRIPTION</th>
                    <th style="width:15%;">RATE</th>
                    <th style="width:10%;">QTY</th>
                    <th style="width:15%;">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($tx->receipt_list as $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td>{{ $item['rate'] }} SAGA</td>
                        <td>{{ $item['quantity'] }}</td>
                        <td>{{ \round($item['rate'] * $item['quantity'], 2) }} SAGA</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="panel-block">
        <div class="columns" style="margin-left:auto;">
            <div class="column has-text-right is-narrow">
                <p class="has-text-weight-bold">Total</p>
                <p class="subtitle">Balance Due</p>
            </div>
            <div class="column has-text-left is-narrow">
                <p class="has-text-weight-bold">{{ $tx->value }} SAGA</p>
                {{-- allow for deductions, discounts --}}
                <p class="subtitle">{{ $tx->value }} SAGA</p>
            </div>
        </div>
    </div>
</nav>
<div class="is-pulled-right">
    <form target="_blank" method="POST" action="{{ url()->full() }}">
        @csrf
        <div class="control">
            <button type="submit" class="button is-info">Confirm</button>
        </div>
    </form>
</div>
<p class="is-hidden">Payment status: <span id="status" class="has-text-weight-bold"></span></p>
@endsection

@section('scripts')
<script>
    let url = new URL(window.location.href);
    let signature = url.searchParams.get('signature');
    let csrf = document.getElementById('csrf').content;
    let order_id = document.getElementById('order_id').content;
    let status_label = document.getElementById('status');
    let form = document.querySelector('form');
    Echo.channel(`saga_database_order.${order_id}`)
    .listen('PaymentStatusUpdated', e => {
        bulmaToast({
            message: `Payment ${e.update.payment_status}`,
            type: ['failed', 'expired'].includes(e.update.payment_status) ? 'is-danger' : 'is-success',
            position: 'top-center',
            dismissible: true,
            pauseOnHover: true,
            animate: { in: 'fadeIn', out: 'fadeOut' },
            duration: 5000
        });
        status_label.parentElement.classList.remove('is-hidden');
        if (e.update.payment_status === 'confirmed') {
            form.classList.add('is-hidden');
            form.parentElement.innerHTML = 'Payment confirmed successfully. You may now close this window.';
            window.onbeforeunload = null;
        }
        status_label.innerHTML = e.update.payment_status;
    });

    window.onbeforeunload = () => 'Your payment has not yet been confirmed. Are you sure you want to exit?';
</script>
@endsection
