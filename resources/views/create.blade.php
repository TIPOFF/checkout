@extends('support::base')

@section('content')
    <h2>CREATE</h2>

    <form method="POST" action="{{ route('checkout.cart.create') }}">
        @csrf
        <div>
            <label for="email">{{__('Email')}}</label>
            <input type="email" id="email" name="email" required/>
        </div>
        <button type="submit" value="{{ __('Continue') }}"/>
    </form>
@endsection
