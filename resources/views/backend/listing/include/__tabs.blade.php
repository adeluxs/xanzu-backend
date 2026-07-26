<div class="site-tab-bars">
    <ul class="nav nav-pills" id="listingTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <a href="javascript:void(0)" class="nav-link active" id="general-tab" data-bs-toggle="tab"
                data-bs-target="#general" role="tab" aria-controls="general" aria-selected="true">
                <i data-lucide="info"></i>{{ __('General') }}
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a href="javascript:void(0)" class="nav-link" id="description-tab" data-bs-toggle="tab"
                data-bs-target="#description" role="tab" aria-controls="description" aria-selected="false">
                <i data-lucide="file-text"></i>{{ __('Description') }}
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a href="javascript:void(0)" class="nav-link" id="images-tab" data-bs-toggle="tab" data-bs-target="#images"
                role="tab" aria-controls="images" aria-selected="false">
                <i data-lucide="image"></i>{{ __('Images') }}
            </a>
        </li>
        <li class="nav-item" role="presentation" id="attributes-tab-item" style="display:none;">
            <a href="javascript:void(0)" class="nav-link" id="attributes-tab" data-bs-toggle="tab"
                data-bs-target="#attributes" role="tab" aria-controls="attributes" aria-selected="false">
                <i data-lucide="layers"></i>{{ __('Attributes') }}
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a href="javascript:void(0)" class="nav-link" id="delivery-tab" data-bs-toggle="tab"
                data-bs-target="#delivery" role="tab" aria-controls="delivery" aria-selected="false">
                <i data-lucide="truck"></i>{{ __('Delivery') }}
            </a>
        </li>
    </ul>
</div>
