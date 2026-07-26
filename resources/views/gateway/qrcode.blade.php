<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ themeAsset('css/style.css') }}">
    <title>{{ __('QR Payment') . ' - ' . setting('site_title') }}</title>
</head>

<body class="bnpl-page">
    <main class="bnpl-shell">
        <section class="summary-card" style="max-width: 720px; margin: 48px auto;">
            <div class="summary-block">
                <h1>{{ __('Deposit With') }} {{ $data['gateway'] }}</h1>
                <p>{{ __('Scan the QR code or copy the wallet address to complete the payment.') }}</p>
            </div>

            <div class="summary-block" style="text-align: center;">
                <img src="{{ $data['qrPayment'] }}" alt="{{ __('QR Code') }}"
                    style="max-width: 260px; width: 100%; border-radius: 16px;">
            </div>

            <div class="summary-block">
                <label for="depositAddress">{{ __('Wallet Address') }}</label>
                <div class="form-control">
                    <input id="depositAddress" type="text" class="form-control-input"
                        value="{{ $data['depositAddress'] }}" readonly>
                </div>
                <button class="submit-btn" type="button" id="copyDepositAddress">{{ __('Copy Address') }}</button>
            </div>

            <div class="summary-block">
                <p>{{ __('You can send :amount :currency to the above address', ['amount' => $data['amount'], 'currency' => $data['currency']]) }}</p>
                <p>{{ __('Pay Amount:') }} {{ $data['amount'] }} {{ $data['currency'] }}</p>
            </div>

            <div class="action-block">
                <a href="{{ route('home') }}" class="cancel-link">{{ __('Back') }}</a>
            </div>
        </section>
    </main>

    <script>
        (function() {
            const copyButton = document.getElementById('copyDepositAddress');
            const addressInput = document.getElementById('depositAddress');

            if (!copyButton || !addressInput) {
                return;
            }

            copyButton.addEventListener('click', async function() {
                try {
                    await navigator.clipboard.writeText(addressInput.value);
                    this.textContent = @json(__('Copied'));
                } catch (error) {
                    addressInput.select();
                    document.execCommand('copy');
                    this.textContent = @json(__('Copied'));
                }
            });
        })();
    </script>
</body>

</html>
