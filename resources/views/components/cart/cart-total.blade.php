<div {{ $attributes }}>

    <p>
        <x-tipoff-money label="Taxes" :amount="$cart->getTax()"/><br>
        <x-tipoff-money label="Fees" :amount="$cart->getFeeTotal()->getDiscountedAmount()"/>
    </p>

    <h3><x-tipoff-money label="Total" :amount="$cart->getBalanceDue()"/> (USD)</h3>

</div>
