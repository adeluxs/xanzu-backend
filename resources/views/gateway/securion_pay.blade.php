<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ themeAsset('css/style.css') }}">
    <title>{{ __('Card Payment') . ' - ' . setting('site_title') }}</title>
</head>

<body class="login-page">
    <main class="login-card">
        <h1>{{ __('Card Payment') }}</h1>
        <p>{{ __('Enter your card details to complete the payment.') }}</p>

        <form action="{{ route('ipn.non-hosted.securionpay') }}" method="POST">
            @csrf
            <div class="form-group">
                <label for="card_number">{{ __('Card Number') }}</label>
                <div class="form-control">
                    <input id="card_number" name="card_number" type="text" class="form-control-input"
                        onkeypress="return validateNumber(event)" placeholder="{{ __('Card Number') }}" required>
                </div>
            </div>

            <div class="form-group">
                <label for="cardDate">{{ __('Expiry Date') }}</label>
                <div class="form-control">
                    <input id="cardDate" name="card_date" type="text" class="form-control-input"
                        onkeypress="return validateNumber(event)" placeholder="MM/YY" required>
                </div>
            </div>

            <div class="form-group">
                <label for="card_cvc">{{ __('CVC') }}</label>
                <div class="form-control">
                    <input id="card_cvc" name="card-number" type="text" class="form-control-input"
                        onkeypress="return validateNumber(event)" placeholder="{{ __('CVC') }}" required>
                </div>
            </div>

            <button class="submit-btn" type="submit">{{ __('Pay') }} {{ $amountInfo }}</button>
        </form>
    </main>

    <script>
        (function() {
            const cardDate = document.getElementById('cardDate');

            if (!cardDate) {
                return;
            }

            cardDate.addEventListener('keyup', function() {
                if (this.value.length === 2 && !this.value.includes('/')) {
                    this.value += '/';
                }
            });
        })();
    </script>
</body>

</html>
