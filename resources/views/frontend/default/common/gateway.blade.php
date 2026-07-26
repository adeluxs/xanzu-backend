<!-- Payment Gateway Section -->
<div class="method-item" id="method-item-1">
    <div class="method-button {{ $gateways->isNotEmpty() ? 'open' : '' }}">
        <div class="form-check">
            <input class="form-check-input" type="radio" name="" id="paymentGateway" value="gateway"
                {{ $gateways->isNotEmpty() ? 'checked' : '' }} />
            <label class="form-check-label" for="paymentGateway">
                {{ __('Payment Gateway') }}
            </label>
        </div>
    </div>
    <div class="method-content {{ $gateways->isNotEmpty() ? 'open' : '' }}">
        <div class="all-payment-checkbox">
            @foreach ($gateways as $gateway)
                <div class="payment-method-checkbox">
                    <input type="radio" name="paymentMethod" value="{{ $gateway->gateway_code }}"
                        id="paymentMethod{{ $loop->index }}" {{ $loop->first ? 'checked' : '' }} />
                    <label for="paymentMethod{{ $loop->index }}" class="check-box-image"
                        data-label="{{ $gateway->gateway_code }}">
                        <div class="img">
                            <img src="{{ asset($gateway->logo) }}" alt="{{ $gateway->name }}" />
                        </div>
                        <div class="check-box-image-text">
                            <h5>{{ $gateway->name }}</h5>
                        </div>
                    </label>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Balance Section -->
@if (!isset($fromTopup) && auth()->user()->balance >= $checkout['total'])
    <div class="method-item" id="method-item-2">
        <div class="method-button">
            <div class="form-check">
                <input class="form-check-input" type="radio" id="paymentMethodTopup" value="topup" />
                <label class="form-check-label" for="topupPayment">
                    {{ __('Balance') }}
                    {{ setting('currency_symbol', 'global') }}{{ auth()->user()->balance }}
                </label>
            </div>
        </div>
        <div class="method-content">
            <div class="all-payment-checkbox">
                <div class="payment-method-checkbox">
                    <input type="radio" name="paymentMethod" value="topup" id="topupPayment" checked />
                    <label for="topupPayment" class="check-box-image" data-label="topup">
                        <div class="img">
                            <img src="{{ themeAsset('images/payment/topup.png') }}" alt="PAYMENT CARD" />
                            <i class="fas fa-arrow-to-top"></i>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>
@endif

<!-- Balance Section -->
@if (!isset($fromTopup) && auth()->user()->balance >= $checkout['total'])
    <div class="method-item" id="method-item-3">
        <div class="method-button">
            <div class="form-check">
                <input class="form-check-input" type="radio" id="paymentMethodBalance" value="balance" />
                <label class="form-check-label" for="balancePayment">
                    {{ __('Balance') }} {{ setting('currency_symbol', 'global') }}{{ auth()->user()->balance }}
                </label>
            </div>
        </div>
        <div class="method-content">
            <div class="all-payment-checkbox">
                <div class="payment-method-checkbox">
                    <input type="radio" name="paymentMethod" value="balance" id="balancePayment" />
                    <label for="balancePayment" class="check-box-image" data-label="balance">
                        <div class="img">
                            <img src="{{ themeAsset('images/payment/balance.png') }}" alt="PAYMENT CARD" />
                            <i class="fas fa-arrow-to-top"></i>
                        </div>
                    </label>
                </div>
            </div>
        </div>
    </div>
@endif

@push('js')
    <script>
        'use strict';
        var payGateway = "{{ $gateways->first()->gateway_code ?? '' }}"

        $(document).ready(function() {
            $(".payment-method-checkbox").on("click", function() {
                $(".payment-method-checkbox input[type='radio']").prop("checked", false);
                $(this).find("input[type='radio']").prop("checked", true);
            });

            $(".payment-method-box").find(".payment-method-checkbox").eq(0).find("input[type='radio']").prop(
                "checked", true);
        });
    </script>
@endpush
