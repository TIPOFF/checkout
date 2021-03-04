<div {{ $attributes }}>

    <ul>
        @foreach ($deductions as $deduction)
            <li>
                <x-dynamic-component :component="$deduction->getViewComponent('cart-deduction') ?? 'tipoff-cart-deduction'" :deduction="$deduction"/>
            </li>
        @endforeach
    </ul>

</div>
