<div {{ $attributes }}>

    <ul>
        @foreach ($deductions as $deduction)
            <li>
                <x-dynamic-component :component="$deduction->getViewComponent() ?? 'tipoff-cart-deduction'" :deduction="$deduction"/>
            </li>
        @endforeach
    </ul>

</div>
