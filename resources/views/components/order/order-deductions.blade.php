<div {{ $attributes }}>

    <ul>
        @foreach ($deductions as $deduction)
            <li>
                <x-dynamic-component :component="$deduction->getViewComponent('order-deduction') ?? 'tipoff-order-deduction'" :deduction="$deduction"/>
            </li>
        @endforeach
    </ul>

</div>
