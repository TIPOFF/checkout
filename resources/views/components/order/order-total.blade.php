<div {{ $attributes }}>

    <p>
        <x-tipoff-money label="Taxes" :amount="$order->getTax()"/><br>
        <x-tipoff-money label="Fees" :amount="$order->getFeeTotal()->getDiscountedAmount()"/>
    </p>

    <h3><x-tipoff-money label="Total" :amount="$order->getBalanceDue()"/> (USD)</h3>

</div>
