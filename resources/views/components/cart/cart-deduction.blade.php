<div {{ $attributes }}>
    <div>Code {{ $deduction->getCode() }}</div>
    <div><x-tipoff-money label="Deduction" :amount="$deduction->getAmount()"/></div>
</div>
