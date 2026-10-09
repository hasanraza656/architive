{{-- One invoice line. $i = row key (a number, or __i__ inside the JS template), $item = ['description','quantity','unit_price'] --}}
<div class="item">
    <div class="item__desc">
        <span class="item__lab">Description</span>
        <textarea class="ptextarea" name="items[{{ $i }}][description]" data-f="description" rows="1" maxlength="500" placeholder="What are you selling? e.g. Exterior render, 3 views">{{ $item['description'] ?? '' }}</textarea>
    </div>
    <div><span class="item__lab">Qty</span><input class="pinput" name="items[{{ $i }}][quantity]" data-f="quantity" inputmode="decimal" value="{{ $item['quantity'] ?? 1 }}" aria-label="Quantity"></div>
    <div><span class="item__lab">Unit price</span><input class="pinput" name="items[{{ $i }}][unit_price]" data-f="unit_price" inputmode="decimal" value="{{ $item['unit_price'] ?? '' }}" placeholder="0.00" aria-label="Unit price"></div>
    <div><span class="item__lab">Amount</span><div class="item__total" data-line>$0.00</div></div>
    <button class="item__rm" type="button" aria-label="Remove this line"><x-icon name="trash" /></button>
</div>
