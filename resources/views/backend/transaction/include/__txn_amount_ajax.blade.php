<strong
    class="{{ isPlusTransaction($type) ? 'green-color': 'red-color'}}">{{
    (isPlusTransaction($type) ? '+': '-') . amountWithCurrency($amount) }}</strong>