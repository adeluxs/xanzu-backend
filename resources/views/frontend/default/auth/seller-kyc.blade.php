@foreach (json_decode($kyc->fields, true) as $key => $field)
    <div class="{{ $field['type'] == 'file' ? 'col-lg-6' : 'col-md-6' }}">
        @if ($field['type'] == 'file')
            <!-- File Upload -->
            <div class="td-form-group">
                <label class="input-label">{{ $field['name'] }}
                    @if ($field['validation'] == 'required')
                        <span>*</span>
                    @endif
                </label>
                <div class="input-field">
                    <div for="fileInput{{ $key }}" class="upload-thumb">
                        <div class="upload-thumb-inner">
                            <input type="file" class="file-upload-input" name="kyc_credential[{{ $field['name'] }}]"
                                id="fileInput{{ $key }}" multiple hidden
                                @if ($field['validation'] == 'required') required @endif>
                            <div class="upload-thumb-img">
                                <!-- Preview images will appear here -->
                            </div>
                            <div class="upload-thumb-content">
                                <h6><a href="#" class="attach-file">{{ __('Attach File') }}</a>
                                    {{ __('Or Drag & Drop') }}</h6>
                                <p>{{ __('JPEG/PNG/PDF/Docs file') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="feedback-invalid d-none">{{ __('This field is required') }}</p>
            </div>
        @elseif($field['type'] == 'textarea')
            <!-- Textarea -->
            <div class="td-form-group">
                <label class="input-label">{{ $field['name'] }}
                    @if ($field['validation'] == 'required')
                        <span>*</span>
                    @endif
                </label>
                <div class="input-field">
                    <textarea class="form-control" name="kyc_credential[{{ $field['name'] }}]" rows="3"
                        @if ($field['validation'] == 'required') required @endif></textarea>
                </div>
                <p class="feedback-invalid d-none">{{ __('This field is required') }}</p>
            </div>
        @elseif($field['type'] == 'date')
            <!-- Date Picker -->
            <div class="td-form-group">
                <label class="input-label">{{ $field['name'] }}
                    @if ($field['validation'] == 'required')
                        <span>*</span>
                    @endif
                </label>
                <div class="input-field">
                    <input type="text" class="form-control flatpickr-date" id="flatpickr-date"
                        name="kyc_credential[{{ $field['name'] }}]" @if ($field['validation'] == 'required') required @endif>
                </div>
                <p class="feedback-invalid d-none">{{ __('This field is required') }}</p>
            </div>
        @elseif($field['type'] == 'select')
            <!-- Select Dropdown -->
            <div class="td-form-group">
                <label class="input-label">{{ $field['name'] }}
                    @if ($field['validation'] == 'required')
                        <span>*</span>
                    @endif
                </label>
                <div class="select-input">
                    <select class="defaultselect2" name="kyc_credential[{{ $field['name'] }}]"
                        @if ($field['validation'] == 'required') required @endif>
                        <option value="">{{ __('Select') }} {{ $field['name'] }}</option>
                        @foreach ($field['options'] ?? [] as $option)
                            <option value="{{ $option['value'] }}" {{ $option['selected'] ? 'selected' : '' }}>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <p class="feedback-invalid d-none">{{ __('This field is required') }}</p>
            </div>
        @else
            <!-- Text Input -->
            <div class="td-form-group">
                <label class="input-label">{{ $field['name'] }}
                    @if ($field['validation'] == 'required')
                        <span>*</span>
                    @endif
                </label>
                <div class="input-field">
                    <input type="text" class="form-control" name="kyc_credential[{{ $field['name'] }}]"
                        @if ($field['validation'] == 'required') required @endif>
                </div>
                <p class="feedback-invalid d-none">{{ __('This field is required') }}</p>
            </div>
        @endif
    </div>
@endforeach
