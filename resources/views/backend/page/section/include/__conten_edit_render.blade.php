<h3 class="title">{{ __('Edit Content') }}</h3>

<div class="site-tab-bars mb-0">
    <ul class="nav nav-pills" id="pills-tab-render" role="tablist">
        @foreach ($languages as $language)
            <li class="nav-item" role="presentation">
                <a href="" class="nav-link {{ $loop->first ? 'active' : '' }}" id="pills-render-tab"
                    data-bs-toggle="pill" data-bs-target="#{{ $language->locale }}-render" type="button"
                    role="tab" aria-controls="pills-render" aria-selected="true"><i
                        data-lucide="languages"></i>{{ $language->name }}</a>
            </li>
        @endforeach
    </ul>
</div>

<div class="tab-content" id="pills-tabContent">
    @foreach ($groupData as $key => $landingContent)
        @php
            $usesUploadedIcon = in_array($landingContent['type'], [
                'how-it-works',
                'stats',
                'why-choose-us',
                'faq',
                'features',
            ]);
        @endphp

        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $key }}-render" role="tabpanel"
            aria-labelledby="pills-render-tab">
            <div class="row">
                <div class="col-xl-12">
                    <form action="{{ route('admin.page.content-update') }}" method="post"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="id" value="{{ $landingContent['id'] }}">
                        <input type="hidden" name="locale" value="{{ $key }}">

                        @if ($key == 'en' && $usesUploadedIcon)
                            <div class="site-input-groups">
                                <label class="box-input-label">{{ __('Icon') }}</label>
                                <div class="wrap-custom-file">
                                    <input type="file" name="icon" id="uploadIcon" accept=".gif, .jpg, .png, .webp" />
                                    <label for="uploadIcon" id="iconPreview"
                                        @if ($landingContent['icon']) class="file-ok"
                                        style="background-image: url({{ asset($landingContent['icon']) }})" @endif>
                                        <img class="upload-icon" src="{{ asset('global/materials/upload.svg') }}"
                                            alt="" />
                                        <span>{{ __('Upload') }}</span>
                                    </label>
                                </div>
                            </div>
                        @endif

                        <div class="site-input-groups">
                            <label for="" class="box-input-label">{{ __('Title') }}</label>
                            <input type="text" name="title" value="{{ $landingContent['title'] }}"
                                class="box-input mb-0" required />
                        </div>

                        @if (!in_array($landingContent['type'], []))
                            <div class="site-input-groups mb-0">
                                <label for="" class="box-input-label">{{ __('Description') }}</label>
                                <textarea name="description" class="form-textarea">{{ $landingContent['description'] }}</textarea>
                            </div>
                        @endif

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
                    </form>
                </div>
            </div>
        </div>
    @endforeach
</div>

<script>
    "use strict";

    $('#uploadIcon').on('change', function() {
        var file = $(this);
        var label = $('label[for=uploadIcon]');
        var labelText = label.find('span');
        var fileName = file.val().split('\\').pop();
        var tmppath = URL.createObjectURL(file.get(0).files[0]);

        label.css('background-image', 'url(' + tmppath + ')');
        labelText.text(fileName);
    });
</script>
