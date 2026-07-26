<div class="modal fade" id="editSplit" tabindex="-1" aria-labelledby="editSplitLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content site-table-modal">
            <div class="modal-body popup-body">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

                <div class="popup-body-text">
                    <h3 class="title mb-4">{{ __('Edit Payment Split') }}</h3>
                    <form id="editSplitForm" method="post">
                        @method('PUT')
                        @csrf
                        <div class="site-input-groups row mb-0">
                            <div class="col-xl-6">
                                <div class="site-input-groups">
                                    <label for="" class="box-input-label">{{ __('Total Split:') }}</label>
                                    <div class="input-group joint-input">
                                        <input type="text" class="form-control" name="total_split"
                                            id="editSplitTotalSplit" oninput="this.value = validateDouble(this.value)"
                                            placeholder="{{ __('Optional') }}">
                                        <span class="input-group-text">{{ setting('site_currency', 'global') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3">
                                <div class="site-input-groups">
                                    <label for="" class="box-input-label">{{ __('Payment Interval:') }}</label>
                                    <input type="number" name="payment_interval_amount"
                                        id="editSplitPaymentIntervalAmount" class="box-input mb-0" min="1"
                                        required="" />
                                </div>
                            </div>
                            <div class="col-xl-3">
                                <div class="site-input-groups">
                                    <label for="" class="box-input-label">{{ __('Interval Type:') }}</label>
                                    <select name="payment_interval_type" id="editSplitPaymentIntervalType"
                                        class="form-select" required>
                                        <option value="day">{{ __('Day') }}</option>
                                        <option value="week">{{ __('Week') }}</option>
                                        <option value="month">{{ __('Month') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="site-input-groups row mb-0">
                            <div class="col-xl-6">
                                <div class="site-input-groups position-relative">
                                    <label class="box-input-label" for="">{{ __('Interest Rate:') }}</label>
                                    <div class="position-relative">
                                        <input type="text" class="box-input" name="interest_rate_amount"
                                            id="editSplitInterestRateAmount"
                                            oninput="this.value = validateDouble(this.value)" required="">
                                        <div class="prcntcurr">
                                            <select name="interest_rate_type" id="editSplitInterestRateType"
                                                class="form-select">
                                                <option value="percentage">%</option>
                                                <option value="fixed">{{ setting('site_currency', 'global') }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-6">
                                <div class="site-input-groups position-relative">
                                    <label class="box-input-label" for="">{{ __('Delay Fine:') }}</label>
                                    <div class="position-relative">
                                        <input type="text" class="box-input" name="delay_fine_amount"
                                            id="editSplitDelayFineAmount"
                                            oninput="this.value = validateDouble(this.value)" required="">
                                        <div class="prcntcurr">
                                            <select name="delay_fine_type" id="editSplitDelayFineType"
                                                class="form-select">
                                                <option value="percentage">%</option>
                                                <option value="fixed">{{ setting('site_currency', 'global') }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="site-input-groups mb-0">
                            <label class="box-input-label" for="">{{ __('Status:') }}</label>
                            <div class="switch-field">
                                <input type="radio" id="editSplitStatusActive" name="status" value="1">
                                <label for="editSplitStatusActive">{{ __('Active') }}</label>
                                <input type="radio" id="editSplitStatusDisabled" name="status" value="0">
                                <label for="editSplitStatusDisabled">{{ __('Disabled') }}</label>
                            </div>
                        </div>

                        <div class="action-btns">
                            <button type="submit" class="site-btn-sm primary-btn me-2">
                                <i data-lucide="check"></i>
                                {{ __('Save Changes') }}
                            </button>
                            <a href="#" class="site-btn-sm red-btn" data-bs-dismiss="modal"
                                aria-label="Close">
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
