@extends('support::base')

@section('content')
    @include('checkout::partials._order_identity_tag')

    <h2>ORDER DETAIL</h2>
    <x-tipoff-order :order="$order"/>
    <x-tipoff-order-deductions :deductions="$order->getCodes()"/>
    <x-tipoff-order-total :order="$order"/>
@endsection
