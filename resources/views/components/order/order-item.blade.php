<tr>
    <td>{{ $orderItem->getDescription() }}</td>
    <td>{{ $orderItem->getQuantity() }}</td>
    <td><x-tipoff-money :amount="$orderItem->getAmountEach()->getOriginalAmount()"/></td>
    <td><x-tipoff-money :amount="$orderItem->getAmountEach()->getDiscounts()"/></td>
    <td><x-tipoff-money :amount="$orderItem->getAmountTotal()->getDiscountedAmount()"/></td>
</tr>
