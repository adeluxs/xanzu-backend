<strong
    class="{{ isPlusTransaction($txnType) ? 'green-color': 'red-color'}}">{{ (isPlusTransaction($txnType) ? '+': '-' ).number_format($amount, 2).' '.$currency }}</strong>
