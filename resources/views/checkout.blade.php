@extends('support::base')

@section('content')
    @include('checkout::partials._cart_identity_tag')

    <h2>CHECKOUT</h2>
    <x-tipoff-cart :cart="$cart"/>
    <x-tipoff-cart-deductions :deductions="$cart->getCodes()"/>
    <x-tipoff-cart-total :cart="$cart"/>

    <form method="POST" action="{{ route('checkout.purchase') }}">
        @csrf
        <button type="submit" value="{{ __('Purchase') }}"/>
    </form>
@endsection
