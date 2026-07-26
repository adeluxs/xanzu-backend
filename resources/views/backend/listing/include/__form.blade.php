@include('backend.listing.include.__tabs')

<div class="tab-content mt-4" id="listingTabsContent">
    <!-- General Tab -->
    <div class="tab-pane fade show active" id="general" role="tabpanel">
        <div class="site-input-groups row">
            <label class="col-sm-4 col-label">{{ __('Categories') }}<span class="text-danger">*</span></label>
            <div class="col-sm-8">
                <select name="category_id" id="category_id" class="form-select mb-0" required>
                    <option value="" disabled selected>{{ __('Select Category') }}</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $listing?->category_id) == $category->id)>{{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="site-input-groups row" id="subcategory_group"
            style="{{ isset($subcategories) && count($subcategories) > 0 ? '' : 'display:none;' }}">
            <label class="col-sm-4 col-label">{{ __('Subcategory') }}</label>
            <div class="col-sm-8">
                <select name="subcategory_id" id="subcategory_id" class="form-select mb-0">
                    <option value="">{{ __('Select Subcategory') }}</option>
                    @if (isset($subcategories))
                        @foreach ($subcategories as $sub)
                            <option value="{{ $sub->id }}" @selected(old('subcategory_id', $listing?->subcategory_id) == $sub->id)>{{ $sub->name }}
                            </option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>

        <div class="site-input-groups row">
            <label class="col-sm-4 col-label">{{ __('Brand') }}</label>
            <div class="col-sm-8">
                <select name="brand_id" id="brand_id" class="form-select mb-0">
                    <option value="">{{ __('Select Brand') }}</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}" @selected(old('brand_id', $listing?->brand_id) == $brand->id)>
                            {{ $brand->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="site-input-groups row">
            <label class="col-sm-4 col-label">{{ __('Provider') }}</label>
            <div class="col-sm-8">
                <select name="provider_id" id="provider_id" class="form-select mb-0">
                    <option value="">{{ __('Select Provider (Source)') }}</option>
                    @foreach (\App\Models\Provider::active()->get() as $provider)
                        <option value="{{ $provider->id }}" @selected(old('provider_id', $listing?->provider_id) == $provider->id)>{{ $provider->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="site-input-groups row" id="product_url_group"
            style="{{ old('provider_id', $listing?->provider_id) ? '' : 'display:none;' }}">
            <label class="col-sm-4 col-label">{{ __('Product URL') }}</label>
            <div class="col-sm-8">
                <input type="url" name="product_url" id="product_url" class="box-input"
                    value="{{ old('product_url', $listing?->product_url) }}"
                    placeholder="{{ __('https://example.com/product/...') }}">
                <small class="text-muted">{{ __('Shown for provider linked products only.') }}</small>
            </div>
        </div>

        <div class="site-input-groups row">
            <label class="col-sm-4 col-label">{{ __('Product Type') }}<span class="text-danger">*</span></label>
            <div class="col-sm-8">
                <div class="form-switch ps-0">
                    <div class="switch-field same-type m-0">
                        <input type="radio" id="type_physical" name="type" value="physical"
                            @checked(old('type', $listing?->type->value ?? 'physical') == 'physical') />
                        <label for="type_physical">{{ __('Physical') }}</label>
                        <input type="radio" id="type_digital" name="type" value="digital"
                            @checked(old('type', $listing?->type->value ?? 'digital') == 'digital') />
                        <label for="type_digital">{{ __('Digital') }}</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Has Attributes toggle (only for physical) --}}
        <div class="site-input-groups row" id="has_attributes_group"
            style="{{ old('type', $listing?->type->value ?? 'physical') == 'physical' ? '' : 'display:none;' }}">
            <label class="col-sm-4 col-label pt-0">{{ __('Has Attributes') }}</label>
            <div class="col-sm-8">
                <div class="form-switch ps-0">
                    <div class="switch-field same-type m-0">
                        <input type="radio" id="has_attributes_no" name="has_attributes" value="0"
                            @checked(!old('has_attributes', $listing?->has_attributes)) />
                        <label for="has_attributes_no">{{ __('No') }}</label>
                        <input type="radio" id="has_attributes_yes" name="has_attributes" value="1"
                            @checked(old('has_attributes', $listing?->has_attributes)) />
                        <label for="has_attributes_yes">{{ __('Yes') }}</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="site-input-groups row">
            <label class="col-sm-4 col-label">{{ __('Product Name') }}<span class="text-danger">*</span></label>
            <div class="col-sm-8">
                <input type="text" name="product_name" class="box-input"
                    value="{{ old('product_name', $listing?->product_name) }}" required>
            </div>
        </div>

        {{-- Base Price and Discount are always visible. Quantity is hidden when has_attributes is enabled. --}}
        <div id="price_qty_section">
            <div class="site-input-groups row">
                <label class="col-sm-4 col-label">{{ __('Base Price') }}<span class="text-danger">*</span></label>
                <div class="col-sm-8">
                    <div class="input-group joint-input">
                        <input type="number" step="0.01" name="price" class="form-control"
                            value="{{ old('price', $listing?->price) }}" required>
                        <span class="input-group-text">{{ setting('currency_symbol', 'global') }}</span>
                    </div>
                </div>
            </div>

            <div class="site-input-groups row" id="quantity_section"
                style="{{ old('has_attributes', $listing?->has_attributes) ? 'display:none;' : '' }}">
                <label class="col-sm-4 col-label">{{ __('Quantity') }}<span class="text-danger">*</span></label>
                <div class="col-sm-8">
                    <input type="number" name="quantity" class="box-input"
                        value="{{ old('quantity', $listing?->quantity) }}"
                        {{ old('has_attributes', $listing?->has_attributes) ? '' : 'required' }}>
                </div>
            </div>

            <div class="site-input-groups row">
                <label class="col-sm-4 col-label">{{ __('Discount') }}</label>
                <div class="col-sm-8">
                    <div class="site-input-groups position-relative mb-0">
                        <div class="position-relative">
                            <input type="number" step="0.01" name="discount_value" class="box-input"
                                value="{{ old('discount_value', $listing?->discount_value ?? 0) }}">
                            <div class="prcntcurr">
                                <select name="discount_type" class="form-select">
                                    <option value="percentage" @selected(old('discount_type', $listing?->discount_type) == 'percentage')>%</option>
                                    <option value="amount" @selected(old('discount_type', $listing?->discount_type) == 'amount')>
                                        {{ setting('currency_symbol', 'global') }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- next btn --}}
        <div class="site-input-groups row">
            <div class="col-sm-12 text-end">
                <button type="button" class="site-btn-xs primary-btn btn-next-tab"
                    data-target="#description-tab">{{ __('Next') }}</button>
            </div>
        </div>
    </div>

    <!-- Description Tab -->
    <div class="tab-pane fade" id="description" role="tabpanel">
        <div class="site-input-groups row">
            <label class="col-sm-12 col-label">{{ __('Description') }}<span class="text-danger">*</span></label>
            <div class="col-sm-12 mt-2">
                <textarea name="description" class="form-control summernote" required>{{ old('description', $listing?->description) }}</textarea>
            </div>
        </div>
        {{-- next btn --}}
        <div class="site-input-groups row">
            <div class="col-sm-12 text-end">
                <button type="button" class="site-btn-xs primary-btn btn-next-tab"
                    data-target="#images-tab">{{ __('Next') }}</button>
            </div>
        </div>
    </div>

    <!-- Images Tab -->
    <div class="tab-pane fade" id="images" role="tabpanel">
        <div class="site-input-groups row">
            <div class="col-sm-4 col-label">
                {{ __('Thumbnail') }}<span class="text-danger">*</span>
            </div>
            <div class="col-sm-8">
                <div class="wrap-custom-file {{ $errors->has('thumbnail') ? 'has-error' : '' }}">
                    <input type="file" name="thumbnail" id="thumbnail" accept=".jpeg, .jpg, .png"
                        {{ $listing ? '' : 'required' }}>
                    <label for="thumbnail"
                        @if ($listing?->thumbnail) class="file-ok" style="background-image: url({{ asset($listing->thumbnail) }})" @endif>
                        <img class="upload-icon" src="{{ asset('global/materials/upload.svg') }}" alt="">
                        <span>{{ __('Upload Thumbnail') }}</span>
                    </label>
                    @if ($listing?->thumbnail)
                        <div data-name="thumbnail" data-title="{{ __('Upload Thumbnail') }}"
                            class="close remove-img">
                            <i data-lucide="x"></i>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="site-input-groups row">
            <div class="col-sm-4 col-label">
                {{ __('Gallery Images') }} ({{ __('Max 4') }})
            </div>
            <div class="col-sm-8">
                <div class="row">
                    @for ($i = 0; $i < 4; $i++)
                        @php
                            $galleryImage = $listing?->images[$i] ?? null;
                        @endphp
                        <div class="col-md-3 mb-3">
                            <div class="wrap-custom-file">
                                <input type="file" name="gallery[{{ $i }}]" id="gallery_{{ $i }}"
                                    accept=".jpeg, .jpg, .png">
                                <label for="gallery_{{ $i }}"
                                    @if ($galleryImage) class="file-ok" style="background-image: url({{ asset($galleryImage->image_path) }})" @endif>
                                    <img class="upload-icon" src="{{ asset('global/materials/upload.svg') }}"
                                        alt="">
                                    <span>{{ __('Upload') }}</span>
                                </label>
                                @if ($galleryImage)
                                    <div data-id="{{ $galleryImage->id }}" class="close remove-gallery-img"><i
                                            data-lucide="x"></i></div>
                                @endif
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>

        {{-- next btn --}}
        <div class="site-input-groups row">
            <div class="col-sm-12 text-end">
                <button type="button" class="site-btn-xs primary-btn btn-next-tab images-next-btn"
                    data-target="#delivery-tab">{{ __('Next') }}</button>
            </div>
        </div>

    </div>

    <!-- Attributes Tab -->
    <div class="tab-pane fade" id="attributes" role="tabpanel">
        <div class="mb-3">
            <p class="text-muted">
                {{ __('Define product variations like Color, Size, Storage etc. Each group can have multiple attributes with their own price and quantity.') }}
            </p>
        </div>

        <div id="attribute-groups-container" class="row">
            @if ($listing && $listing->has_attributes && $listing->listingAttributes->isNotEmpty())
                @foreach ($listing->listingAttributes->groupBy('group') as $groupName => $attrs)
                    <div class="attribute-group col-xl-4 col-lg-4 col-md-6 col-sm-12 mb-3"
                        data-group-index="{{ $loop->index }}">
                        <div class="site-card">
                            <div class="site-card-header d-flex justify-content-between align-items-center">
                                <input type="text" name="attribute_groups[{{ $loop->index }}][group_name]"
                                    class="form-control group-name-input border-0 bg-transparent p-0 fw-bold"
                                    value="{{ $groupName }}" placeholder="{{ __('e.g. Color, Size') }}" required
                                    style="box-shadow:none; font-size:14px;">
                                <a href="javascript:void(0)" class="round-icon-btn red-btn remove-group-btn"
                                    title="{{ __('Remove Group') }}">
                                    <i data-lucide="trash-2"></i>
                                </a>
                            </div>
                            <div class="site-card-body">
                                <div class="attribute-rows">
                                    @foreach ($attrs as $attrIndex => $attr)
                                        <div class="attribute-row mb-3 pb-3 border-bottom">
                                            <input type="hidden"
                                                name="attribute_groups[{{ $loop->parent->index }}][attributes][{{ $attrIndex }}][id]"
                                                value="{{ $attr->id }}">

                                            <div class="site-input-groups">
                                                <label class="box-input-label">{{ __('Label') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="text"
                                                    name="attribute_groups[{{ $loop->parent->index }}][attributes][{{ $attrIndex }}][label]"
                                                    class="box-input mb-0" value="{{ $attr->label }}"
                                                    placeholder="{{ __('e.g. Red, 128GB, XL') }}" required>
                                            </div>

                                            <div class="site-input-groups">
                                                <label class="box-input-label">{{ __('Add-on Price') }}</label>
                                                <div class="input-group joint-input">
                                                    <input type="number" step="0.01"
                                                        name="attribute_groups[{{ $loop->parent->index }}][attributes][{{ $attrIndex }}][price]"
                                                        class="form-control" value="{{ $attr->price }}"
                                                        placeholder="{{ __('Price') }}">
                                                    <span
                                                        class="input-group-text">{{ setting('currency_symbol', 'global') }}</span>
                                                </div>
                                            </div>

                                            <div class="site-input-groups position-relative">
                                                <label class="box-input-label">{{ __('Discount') }}</label>
                                                <div class="position-relative">
                                                    <input type="number" step="0.01"
                                                        name="attribute_groups[{{ $loop->parent->index }}][attributes][{{ $attrIndex }}][discount_amount]"
                                                        class="box-input" value="{{ $attr->discount_amount }}"
                                                        placeholder="{{ __('Discount') }}">
                                                    <div class="prcntcurr">
                                                        <select
                                                            name="attribute_groups[{{ $loop->parent->index }}][attributes][{{ $attrIndex }}][discount_type]"
                                                            class="form-select">
                                                            <option value="percentage" @selected($attr->discount_type == 'percentage')>%
                                                            </option>
                                                            <option value="amount" @selected($attr->discount_type == 'amount')>
                                                                {{ setting('currency_symbol', 'global') }}</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="site-input-groups">
                                                <label class="box-input-label">{{ __('Quantity') }} <span
                                                        class="text-danger">*</span></label>
                                                <input type="number"
                                                    name="attribute_groups[{{ $loop->parent->index }}][attributes][{{ $attrIndex }}][qty]"
                                                    class="box-input mb-0" value="{{ $attr->qty }}"
                                                    placeholder="{{ __('Quantity') }}" required>
                                            </div>

                                            <div class="text-end">
                                                <a href="javascript:void(0)"
                                                    class="round-icon-btn red-btn remove-attr-btn"
                                                    title="{{ __('Remove Attribute') }}">
                                                    <i data-lucide="trash-2"></i>
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="text-center mt-2">
                                    <a href="javascript:void(0)" class="round-icon-btn blue-btn add-attr-btn"
                                        title="{{ __('Add Attribute') }}">
                                        <i data-lucide="plus"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="mt-2">
            <a href="javascript:void(0)" class="round-icon-btn blue-btn" id="add-group-btn"
                title="{{ __('Add Attribute Group') }}">
                <i data-lucide="plus"></i>
            </a>
            <span class="ms-2 text-muted">{{ __('Add Attribute Group') }}</span>
        </div>

        {{-- next btn --}}
        <div class="site-input-groups row mt-3">
            <div class="col-sm-12 text-end">
                <button type="button" class="site-btn-xs primary-btn btn-next-tab"
                    data-target="#delivery-tab">{{ __('Next') }}</button>
            </div>
        </div>
    </div>

    <!-- Delivery Tab -->
    <div class="tab-pane fade" id="delivery" role="tabpanel">
        <div class="site-input-groups row delivery-method"
            style="{{ old('type', $listing?->type ?? 'physical') == 'digital' ? 'pointer-events:none; opacity:0.6;' : '' }}">
            <label class="col-sm-4 col-label">{{ __('Delivery Method') }}<span class="text-danger">*</span></label>
            <div class="col-sm-8">
                <div class="form-switch ps-0">
                    <div class="switch-field same-type m-0">
                        <input type="radio" id="delivery_manual" name="delivery_method" value="manual"
                            @checked(old('delivery_method', $listing?->delivery_method ?? 'manual') == 'manual') />
                        <label for="delivery_manual">{{ __('Manual') }}</label>
                        <input type="radio" id="delivery_auto" name="delivery_method" value="auto"
                            @checked(old('delivery_method', $listing?->delivery_method) == 'auto') />
                        <label for="delivery_auto">{{ __('Automatic') }}</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="site-input-groups row">
            <label class="col-sm-4 col-label">{{ __('Delivery Speed') }}</label>
            <div class="col-sm-8">
                <div class="site-input-groups position-relative mb-0">
                    <div class="position-relative">
                        <input type="number" name="delivery_speed" class="box-input"
                            value="{{ old('delivery_speed', $listing?->delivery_speed) }}">
                        <div class="prcntcurr prcntcurr-large">
                            <select name="delivery_speed_unit" class="form-select">
                                <option value="second" @selected(old('delivery_speed_unit', $listing?->delivery_speed_unit) == 'second')>{{ __('Second') }}</option>
                                <option value="minute" @selected(old('delivery_speed_unit', $listing?->delivery_speed_unit) == 'minute')>{{ __('Minute') }}</option>
                                <option value="hour" @selected(old('delivery_speed_unit', $listing?->delivery_speed_unit) == 'hour')>{{ __('Hour') }}</option>
                                <option value="day" @selected(old('delivery_speed_unit', $listing?->delivery_speed_unit) == 'day')>{{ __('Day') }}</option>
                                <option value="week" @selected(old('delivery_speed_unit', $listing?->delivery_speed_unit) == 'week')>{{ __('Week') }}</option>
                                <option value="month" @selected(old('delivery_speed_unit', $listing?->delivery_speed_unit) == 'month')>{{ __('Month') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="site-input-groups row">
            <label class="col-sm-4 col-label">{{ __('Shipping Charge') }}</label>
            <div class="col-sm-8">
                <div class="site-input-groups position-relative mb-0">
                    <div class="position-relative">
                        <input type="number" step="0.01" name="shipping_charge" class="box-input"
                            value="{{ old('shipping_charge', $listing?->shipping_charge) }}"
                            placeholder="{{ __('Leave empty to use global shipping charge') }}">
                        <div class="prcntcurr">
                            <select name="shipping_charge_type" class="form-select">
                                <option value="">{{ __('Use Global') }}</option>
                                <option value="fixed" @selected(old('shipping_charge_type', $listing?->shipping_charge_type) == 'fixed')>
                                    {{ setting('currency_symbol', 'global') }}
                                </option>
                                <option value="percentage" @selected(old('shipping_charge_type', $listing?->shipping_charge_type) == 'percentage')>
                                    {{ __('%') }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="site-input-groups row">
            <label class="col-sm-4 col-label">{{ __('Status') }}<span class="text-danger">*</span></label>
            <div class="col-sm-8">
                <select name="status" class="form-select mb-0" required>
                    @foreach (\App\Enums\ListingStatus::cases() as $status)
                        <option value="{{ $status->value }}" @selected(old('status', $listing?->status) == $status->value)>
                            {{ str($status->value)->headline() }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="site-input-groups row">
            <label class="col-sm-4 col-label pt-0">{{ __('Flash Sale') }}</label>
            <div class="col-sm-8">
                <div class="form-switch ps-0">
                    <input class="form-check-input" type="hidden" value="0" name="is_flash" />
                    <div class="switch-field same-type m-0">
                        <input type="radio" id="is_flash_disable" name="is_flash" value="0"
                            @checked(!old('is_flash', $listing?->is_flash)) />
                        <label for="is_flash_disable">{{ __('Disable') }}</label>
                        <input type="radio" id="is_flash_active" name="is_flash" value="1"
                            @checked(old('is_flash', $listing?->is_flash)) />
                        <label for="is_flash_active">{{ __('Enable') }}</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="site-input-groups row">
            <label class="col-sm-4 col-label pt-0">{{ __('Is Trending') }}</label>
            <div class="col-sm-8">
                <div class="form-switch ps-0">
                    <input class="form-check-input" type="hidden" value="0" name="is_trending" />
                    <div class="switch-field same-type m-0">
                        <input type="radio" id="is_trending_disable" name="is_trending" value="0"
                            @checked(!old('is_trending', $listing?->is_trending)) />
                        <label for="is_trending_disable">{{ __('Disable') }}</label>
                        <input type="radio" id="is_trending_active" name="is_trending" value="1"
                            @checked(old('is_trending', $listing?->is_trending)) />
                        <label for="is_trending_active">{{ __('Enable') }}</label>
                    </div>
                </div>
            </div>
        </div>
        @if (!isset($listing))
            <div class="row mt-4">
                <div class="col-sm-12 text-end">
                    <button type="submit"
                        class="site-btn primary-btn">{{ $listing ? __('Update Listing') : __('Create Listing') }}</button>
                </div>
            </div>
        @endif
    </div>
</div>

@isset($listing)
    <div class="row mt-4">
        <div class="col-sm-12 text-end">
            <button type="submit"
                class="site-btn primary-btn">{{ $listing ? __('Update Listing') : __('Create Listing') }}</button>
        </div>
    </div>
@endisset

@section('script')
    <script>
        (function($) {
            'use strict';

            var currencySymbol = '{{ setting('currency_symbol', 'global') }}';

            // ========== Toggle visibility helpers ==========
            function toggleHasAttributesVisibility() {
                var type = $('[name="type"]:checked').val();
                var hasAttr = $('[name="has_attributes"]:checked').val();

                // Show "Has Attributes" toggle only for physical
                if (type === 'physical') {
                    $('#has_attributes_group').show();
                } else {
                    $('#has_attributes_group').hide();
                    // Reset to No when switching to digital
                    $('#has_attributes_no').prop('checked', true);
                }

                // Determine effective hasAttr value
                var effectiveHasAttr = (type === 'physical' && hasAttr == '1');

                // Show/hide price+qty section
                if (effectiveHasAttr) {
                    $('#price_qty_section').show();
                    $('#quantity_section').hide();
                    $('[name="price"]').attr('required', 'required');
                    $('[name="quantity"]').removeAttr('required');
                    $('#attributes-tab-item').show();
                    // Images next button -> Attributes tab
                    $('.images-next-btn').data('target', '#attributes-tab');
                } else {
                    $('#price_qty_section').show();
                    $('#quantity_section').show();
                    $('[name="price"], [name="quantity"]').attr('required', 'required');
                    $('#attributes-tab-item').hide();
                    // Images next button -> Delivery tab
                    $('.images-next-btn').data('target', '#delivery-tab');
                }
            }

            function toggleProductUrlVisibility() {
                var hasProvider = !!$('#provider_id').val();

                if (hasProvider) {
                    $('#product_url_group').show();
                    $('#product_url').attr('required', 'required');
                } else {
                    $('#product_url_group').hide();
                    $('#product_url').removeAttr('required');
                }
            }

            // ========== Product type change ==========
            $('#type_physical, #type_digital').on('change', function() {
                var type = $('[name="type"]:checked').val();
                if (type === 'physical') {
                    $('.delivery-method').css({
                        'pointer-events': 'none',
                        'opacity': '0.6'
                    });
                } else {
                    $('.delivery-method').css({
                        'pointer-events': 'auto',
                        'opacity': '1'
                    });
                }
                toggleHasAttributesVisibility();
            });

            // ========== Has Attributes toggle ==========
            $('[name="has_attributes"]').on('change', function() {
                toggleHasAttributesVisibility();
            });

            $('#provider_id').on('change', function() {
                toggleProductUrlVisibility();
            });

            // Initial state on page load
            toggleHasAttributesVisibility();
            toggleProductUrlVisibility();

            @if ($listing)
                $('#type_physical, #type_digital').trigger('change');
            @endif

            // ========== Handle "Next" tab buttons ==========
            $(document).on('click', '.btn-next-tab', function() {
                var target = $(this).data('target');
                if (target) {
                    var tabEl = document.querySelector(target);
                    if (tabEl) {
                        var tab = new bootstrap.Tab(tabEl);
                        tab.show();
                    }
                }
            });

            // ========== Subcategory fetching ==========
            $('#category_id').on('change', function() {
                var categoryId = $(this).val();
                if (categoryId) {
                    var url = "{{ route('admin.listing.get.sub.cat', ':id') }}";
                    url = url.replace(':id', categoryId);
                    $.get(url, function(response) {
                        if (response.success) {
                            var options = '<option value="">{{ __('Select Subcategory') }}</option>';
                            $.each(response.data, function(key, value) {
                                options += '<option value="' + value.id + '">' + value.name +
                                    '</option>';
                            });
                            $('#subcategory_id').html(options);
                            $('#subcategory_group').show();
                        } else {
                            $('#subcategory_group').hide();
                        }
                    });
                } else {
                    $('#subcategory_group').hide();
                }
            });

            // ========== Remove gallery image AJAX ==========
            $('.remove-gallery-img').on('click', function() {
                var id = $(this).data('id');
                var $this = $(this);
                var url = "{{ route('admin.listing.gallery.delete', ':id') }}";
                url = url.replace(':id', id);

                if (confirm('{{ __('Are you sure you want to delete this image?') }}')) {
                    $.get(url, function(response) {
                        if (response.success) {
                            $this.closest('.wrap-custom-file').find('label').removeAttr('style')
                                .removeClass('file-ok');
                            $this.remove();
                        }
                    });
                }
            });

            // ========== Attribute Groups Dynamic Logic ==========
            var groupIndex = $('#attribute-groups-container .attribute-group').length;

            function getAttrRowHtml(gIdx, aIdx) {
                return `
                    <div class="attribute-row mb-3 pb-3 border-bottom">
                        <div class="site-input-groups">
                            <label class="box-input-label">{{ __('Label') }} <span class="text-danger">*</span></label>
                            <input type="text"
                                name="attribute_groups[${gIdx}][attributes][${aIdx}][label]"
                                class="box-input mb-0"
                                placeholder="{{ __('e.g. Red, 128GB, XL') }}" required>
                        </div>
                        <div class="site-input-groups">
                            <label class="box-input-label">{{ __('Price') }}</label>
                            <div class="input-group joint-input">
                                <input type="number" step="0.01"
                                    name="attribute_groups[${gIdx}][attributes][${aIdx}][price]"
                                    class="form-control"
                                    placeholder="{{ __('Price') }}">
                                <span class="input-group-text">${currencySymbol}</span>
                            </div>
                        </div>
                        <div class="site-input-groups position-relative">
                            <label class="box-input-label">{{ __('Discount') }}</label>
                            <div class="position-relative">
                                <input type="number" step="0.01"
                                    name="attribute_groups[${gIdx}][attributes][${aIdx}][discount_amount]"
                                    class="box-input"
                                    placeholder="{{ __('Discount') }}">
                                <div class="prcntcurr">
                                    <select name="attribute_groups[${gIdx}][attributes][${aIdx}][discount_type]"
                                        class="form-select">
                                        <option value="percentage">%</option>
                                        <option value="amount">${currencySymbol}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="site-input-groups">
                            <label class="box-input-label">{{ __('Quantity') }} <span class="text-danger">*</span></label>
                            <input type="number"
                                name="attribute_groups[${gIdx}][attributes][${aIdx}][qty]"
                                class="box-input mb-0"
                                placeholder="{{ __('Quantity') }}" required>
                        </div>
                        <div class="text-end">
                            <a href="javascript:void(0)" class="round-icon-btn red-btn remove-attr-btn" title="{{ __('Remove Attribute') }}">
                                <i data-lucide="trash-2"></i>
                            </a>
                        </div>
                    </div>`;
            }

            function getGroupHtml(gIdx) {
                return `
                    <div class="attribute-group col-xl-4 col-lg-4 col-md-6 col-sm-12 mb-3" data-group-index="${gIdx}">
                        <div class="site-card">
                            <div class="site-card-header d-flex justify-content-between align-items-center">
                                <input type="text" name="attribute_groups[${gIdx}][group_name]"
                                    class="form-control group-name-input border-0 bg-transparent p-0 fw-bold"
                                    placeholder="{{ __('e.g. Color, Size') }}" required
                                    style="box-shadow:none; font-size:14px;">
                                <a href="javascript:void(0)" class="round-icon-btn red-btn remove-group-btn" title="{{ __('Remove Group') }}">
                                    <i data-lucide="trash-2"></i>
                                </a>
                            </div>
                            <div class="site-card-body">
                                <div class="attribute-rows">
                                    ${getAttrRowHtml(gIdx, 0)}
                                </div>
                                <div class="text-center mt-2">
                                    <a href="javascript:void(0)" class="round-icon-btn blue-btn add-attr-btn" title="{{ __('Add Attribute') }}">
                                        <i data-lucide="plus"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>`;
            }

            // Add new attribute group
            $('#add-group-btn').on('click', function() {
                var html = getGroupHtml(groupIndex);
                $('#attribute-groups-container').append(html);
                groupIndex++;
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });

            // Add new attribute row within a group
            $(document).on('click', '.add-attr-btn', function() {
                var $group = $(this).closest('.attribute-group');
                var gIdx = $group.data('group-index');
                var aIdx = $group.find('.attribute-row').length;
                var html = getAttrRowHtml(gIdx, aIdx);
                $group.find('.attribute-rows').append(html);
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            });

            // Remove attribute row
            $(document).on('click', '.remove-attr-btn', function() {
                var $group = $(this).closest('.attribute-group');
                if ($group.find('.attribute-row').length > 1) {
                    $(this).closest('.attribute-row').remove();
                } else {
                    alert('{{ __('Each group must have at least one attribute.') }}');
                }
            });

            // Remove entire group
            $(document).on('click', '.remove-group-btn', function() {
                if (confirm('{{ __('Are you sure you want to remove this attribute group?') }}')) {
                    $(this).closest('.attribute-group').remove();
                }
            });

            // ========== Re-activate Lucide icons ==========
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

        })(jQuery);
    </script>
@endsection
