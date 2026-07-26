@if (setting('language_switcher', 'permission'))
@php
    $languages = App\Models\Language::where('status', 1)->get();
@endphp
    <div class="site-tab-bars">
        <ul class="nav nav-pills" id="pills-tab" role="tablist">
            @foreach ($languages as $language)
                <li class="nav-item" role="presentation">
                    <a href="" class="nav-link  {{ $loop->index == 0 ? 'active' : '' }}" id="pills-informations-tab"
                        data-bs-toggle="pill" data-bs-target="#{{ $language->locale.(isset($editMode) ? '-render' : '') }}" type="button" role="tab"
                        aria-controls="pills-informations" aria-selected="true"><i
                            data-lucide="languages"></i>{{ $language->name }}</a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
