<div class="modal fade" id="editCreditLimit" tabindex="-1" aria-labelledby="editCreditLimitLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content site-table-modal">
            <div class="modal-body popup-body">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <form id="creditLimitEditForm" method="post">
                    @method('PUT')
                    @csrf
                    <div class="popup-body-text">
                        <h3 class="title mb-4">{{ __('Edit Credit Limit') }}</h3>
                        <div class="site-input-groups row mb-0">
                            <div class="col-xl-6">
                                <div class="site-input-groups">
                                    <label for="" class="box-input-label">{{ __('Level:') }}</label>
                                    <input type="text" name="level" id="editLevel" class="box-input mb-0"
                                        required="" />
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="site-input-groups">
                                    <label for=""
                                        class="box-input-label">{{ __('Minimum Transactions:') }}</label>
                                    <input type="number" name="minimum_transactions" id="editMinTransactions"
                                        class="box-input mb-0" min="0" required="" />
                                </div>
                            </div>
                        </div>

                        <div class="site-input-groups row mb-0">
                            <div class="col-xl-6">
                                <div class="site-input-groups">
                                    <label for="" class="box-input-label">{{ __('Credit Amount:') }}</label>
                                    <div class="input-group joint-input">
                                        <input type="text" class="form-control" name="credit_amount"
                                            id="editCreditAmount" oninput="this.value = validateDouble(this.value)">
                                        <span class="input-group-text">{{ setting('site_currency', 'global') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="site-input-groups">
                                    <label class="box-input-label" for="">{{ __('KYC Required:') }}</label>
                                    <div class="switch-field">
                                        <input type="radio" id="editKycYes" name="is_kyc" value="1">
                                        <label for="editKycYes">{{ __('Yes') }}</label>
                                        <input type="radio" id="editKycNo" name="is_kyc" value="0">
                                        <label for="editKycNo">{{ __('No') }}</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="site-input-groups mb-0">
                            <label class="box-input-label" for="">{{ __('Status:') }}</label>
                            <div class="switch-field">
                                <input type="radio" id="editStatusActive" name="status" value="1">
                                <label for="editStatusActive">{{ __('Active') }}</label>
                                <input type="radio" id="editStatusDisabled" name="status" value="0">
                                <label for="editStatusDisabled">{{ __('Disabled') }}</label>
                            </div>
                        </div>

                        <div class="action-btns">
                            <button type="submit" class="site-btn-sm primary-btn me-2">
                                <i data-lucide="check"></i>
                                {{ __('Save Changes') }}
                            </button>
                            <a href="#" class="site-btn-sm red-btn" data-bs-dismiss="modal" aria-label="Close">
                                <i data-lucide="x"></i>
                                {{ __('Close') }}
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
