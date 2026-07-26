    {{-- Sub Categories List --}}
    <div class="col-xl-6">
        <div class="site-card">
            <div class="site-card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('Sub Categories') }}</h5>
                <button type="button" class="site-btn-xs primary-btn" id="openCreateSubBtnTop"
                    data-bs-toggle="modal" data-bs-target="#createSubModal">
                    <i data-lucide="plus"></i> {{ __('Create Sub Category') }}
                </button>
            </div>
            <div class="site-card-body p-0">
                <div class="site-table table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Image') }}</th>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($subCategories as $idx => $sub)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td>
                                        @if ($sub->image)
                                            <img src="{{ asset($sub->image) }}"
                                                style="height:40px;width:40px;object-fit:cover;border-radius:4px;">
                                        @endif
                                    </td>
                                    <td>{{ $sub->name }}</td>
                                    <td>
                                        @if ($sub->status)
                                            <span class="site-badge success">{{ __('Active') }}</span>
                                        @else
                                            <span class="site-badge danger">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="round-icon-btn primary-btn edit-sub-btn"
                                            data-id="{{ $sub->id }}" data-name="{{ $sub->name }}"
                                            data-status="{{ $sub->status }}"
                                            data-image="{{ $sub->image ? asset($sub->image) : '' }}"
                                            data-update-url="{{ route('admin.category.update', $sub->id) }}">
                                            <i data-lucide="pencil"></i>
                                        </button>
                                        <button type="button" class="round-icon-btn red-btn delete-sub-btn"
                                            data-name="{{ $sub->name }}"
                                            data-delete-url="{{ route('admin.category.delete', $sub->id) }}">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            @if ($subCategories->isEmpty())
                                <tr>
                                    <td colspan="5" class="text-center">
                                        {{ __('No Sub Categories Found') }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    {{-- create sub cat --}}
    <div class="modal fade" id="createSubModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content site-table-modal">
                <div class="modal-body popup-body">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="popup-body-text">
                        <div class="title mb-3">
                            <h4 class="mb-0">{{ __('Create Sub Category') }}</h4>
                        </div>
                        <form action="{{ route('admin.category.store') }}" method="post" enctype="multipart/form-data"
                            id="createSubForm">
                            @csrf
                            <input type="hidden" name="parent_id" value="{{ $category->id }}">
                            <div class="site-input-groups">
                                <label class="box-input-label">{{ __('Image') }} <span
                                        class="text-danger">*</span></label>
                                <div class="wrap-custom-file">
                                    <input type="file" name="image" id="sub_image" accept=".jpeg,.jpg,.png"
                                        required>
                                    <label for="sub_image">
                                        <img class="upload-icon" src="{{ asset('global/materials/upload.svg') }}"
                                            alt="">
                                        <span>{{ __('Upload Image') }}</span>
                                    </label>
                                </div>
                            </div>
                            <div class="site-input-groups">
                                <label class="box-input-label">{{ __('Name') }} <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name" class="box-input" required>
                            </div>
                            <div class="site-input-groups">
                                <label class="box-input-label">{{ __('Status') }} <span
                                        class="text-danger">*</span></label>
                                <div class="switch-field same-type">
                                    <input type="radio" id="create-sub-active" name="status" value="1" checked
                                        required>
                                    <label for="create-sub-active">{{ __('Active') }}</label>
                                    <input type="radio" id="create-sub-inactive" name="status" value="0"
                                        required>
                                    <label for="create-sub-inactive">{{ __('Inactive') }}</label>
                                </div>
                            </div>
                            <div class="action-btns mt-3">
                                <button type="submit" class="site-btn-sm primary-btn me-2">
                                    <i data-lucide="check"></i> {{ __('Create') }}
                                </button>
                                <button type="button" class="site-btn-sm red-btn" data-bs-dismiss="modal">
                                    <i data-lucide="x"></i> {{ __('Cancel') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- edit sub cat --}}
    <div class="modal fade" id="editSubModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content site-table-modal">
                <div class="modal-body popup-body">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="popup-body-text">
                        <div class="title mb-3">
                            <h4 class="mb-0">{{ __('Edit Sub Category') }}</h4>
                        </div>
                        <form method="post" enctype="multipart/form-data" id="editSubForm">
                            @csrf
                            <input type="hidden" name="parent_id" value="{{ $category->id }}">
                            <div class="site-input-groups">
                                <label class="box-input-label">{{ __('Image') }}</label>
                                <div class="wrap-custom-file">
                                    <input type="file" name="image" id="edit_sub_image" accept=".jpeg,.jpg,.png">
                                    <label for="edit_sub_image" id="editImagePreview" class="file-ok">
                                        <span>{{ __('Upload Image') }}</span>
                                    </label>
                                </div>
                            </div>
                            <div class="site-input-groups">
                                <label class="box-input-label">{{ __('Name') }} <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_name" class="box-input" required>
                            </div>
                            <div class="site-input-groups">
                                <label class="box-input-label">{{ __('Status') }} <span
                                        class="text-danger">*</span></label>
                                <div class="switch-field same-type">
                                    <input type="radio" id="edit-sub-active" name="status" value="1" required>
                                    <label for="edit-sub-active">{{ __('Active') }}</label>
                                    <input type="radio" id="edit-sub-inactive" name="status" value="0" required>
                                    <label for="edit-sub-inactive">{{ __('Inactive') }}</label>
                                </div>
                            </div>
                            <div class="action-btns mt-3">
                                <button type="submit" class="site-btn-sm primary-btn me-2">
                                    <i data-lucide="check"></i> {{ __('Update') }}
                                </button>
                                <button type="button" class="site-btn-sm red-btn" data-bs-dismiss="modal">
                                    <i data-lucide="x"></i> {{ __('Cancel') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    {{-- delete sub cat --}}

    <div class="modal fade" id="deleteSubModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content site-table-modal">
                <div class="modal-body popup-body">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="popup-body-text centered">
                        <div class="info-icon">
                            <i data-lucide="alert-triangle"></i>
                        </div>
                        <div class="title">
                            <h4>{{ __('Are you sure?') }}</h4>
                        </div>
                        <p id="deleteMessage" class="mb-3"></p>
                        <form method="post" id="deleteSubForm">
                            @csrf
                            <div class="action-btns">
                                <button type="submit" class="site-btn-sm primary-btn me-2">
                                    <i data-lucide="check"></i> {{ __('Delete') }}
                                </button>
                                <button type="button" class="site-btn-sm red-btn" data-bs-dismiss="modal">
                                    <i data-lucide="x"></i> {{ __('Cancel') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>