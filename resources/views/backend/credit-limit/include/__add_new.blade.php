<div class="modal fade" id="addNewCreditLimit" tabindex="-1" aria-labelledby="addNewCreditLimitLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content site-table-modal">
            <div class="modal-body popup-body">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">

                </button>

                <div class="popup-body-text">
                    <h3 class="title mb-4">{{ __('Add New Credit Limit') }}</h3>
                    <form action="{{ route('admin.credit-limit.store') }}" method="post">
                        @csrf
                        <div class="site-input-groups row mb-0">
                            <div class="col-xl-6">
                                <div class="site-input-groups">
                                    <label for="" class="box-input-label">{{ __('Level:') }}</label>
                                    <input type="text" name="level" value="{{ old('level') }}"
                                        class="box-input mb-0" placeholder="Eg: Level 1, Level 2, Level 3..."
                                        required="" />
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="site-input-groups">
                                    <label for=""
                                        class="box-input-label">{{ __('Minimum Transactions:') }}</label>
                                    <input type="number" name="minimum_transactions"
                                        value="{{ old('minimum_transactions') }}" class="box-input mb-0" placeholder="0"
                                        min="0" required="" />
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="site-input-groups">
                                    <label for="" class="box-input-label">{{ __('Credit Amount:') }}</label>
                                    <div class="input-group joint-input">
                                        <input type="text" class="form-control" name="credit_amount"
                                            value="{{ old('credit_amount') }}"
                                            oninput="this.value = validateDouble(this.value)">
                                        <span class="input-group-text">{{ setting('site_currency', 'global') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="site-input-groups">
                                    <label class="box-input-label" for="">{{ __('KYC Required:') }}</label>
                                    <div class="switch-field">
                                        <input type="radio" id="addKycYes" name="is_kyc" value="1">
                                        <label for="addKycYes">{{ __('Yes') }}</label>
                                        <input type="radio" id="addKycNo" name="is_kyc" checked=""
                                            value="0">
                                        <label for="addKycNo">{{ __('No') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="site-input-groups mb-0">
                            <label class="box-input-label" for="">{{ __('Status:') }}</label>
                            <div class="switch-field">
                                <input type="radio" id="addStatusActive" name="status" checked="" value="1">
                                <label for="addStatusActive">{{ __('Active') }}</label>
                                <input type="radio" id="addStatusDisabled" name="status" value="0">
                                <label for="addStatusDisabled">{{ __('Disabled') }}</label>
                            </div>
                        </div>

                        <div class="action-btns">
                            <button type="submit" class="site-btn-sm primary-btn me-2">
                                <i data-lucide="check"></i>
                                {{ __('Add Credit Limit') }}
                            </button>
                            <a href="#" class="site-btn-sm red-btn" data-bs-dismiss="modal" aria-label="Close">
                                <i data-lucide="x"></i>
                                {{ __('Close') }}
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
@push('single-script')
    <script>
        $(document).on('change', '[name="is_kyc"]', function() {
            if ($(this).val() == '1') {
                $('[name="minimum_transactions"]').prop('required', false);
            } else {
                $('[name="minimum_transactions"]').prop('required', true);
            }
        })
    </script>
@endpush
