<div {{ $attributes }}>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Qty</th>
                <th>Each</th>
                <th>Discount</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($order->getItems() as $item)
            <x-dynamic-component :component="$item->getSellable()->getViewComponent('order-item') ?? 'tipoff-order-item'" :order-item="$item" :sellable="$item->getSellable()"/>
        @endforeach
        </tbody>
    </table>
</div>
