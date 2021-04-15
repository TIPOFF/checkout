@extends('support::base')

@section('content')
    @include('checkout::partials._cart_identity_tag')

    <h2>CART</h2>
    <x-tipoff-cart :cart="$cart"/>
    <x-tipoff-cart-deductions :deductions="$cart->getCodes()"/>
    <x-tipoff-cart-total :cart="$cart"/>
    <form method="POST" action="{{ route('checkout.cart.add-code') }}">
        @csrf
        <div>
            <label for="code">{{__('Code')}}</label>
            <input id="code" name="code" required/>
        </div>
        <button type="submit" value="{{ __('Add Code') }}"/>
    </form>
    <a href="{{route('checkout.show')}}" >{{ __('Checkout') }}</a>
@endsection
