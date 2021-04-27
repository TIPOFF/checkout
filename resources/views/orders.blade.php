@extends('support::base')

@section('content')
    @include('checkout::partials._orders_identity_tag')

    <h2>ORDER HISTORY</h2>
    <ul>
        @foreach($orders as $order)
        <li>
            <a href="{{route('checkout.order-show', ['order' => $order]) }}">{{$order->order_number}}</a>
        </li>
        @endforeach
    </ul>
@endsection
