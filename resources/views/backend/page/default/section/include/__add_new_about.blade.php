<div
        class="modal fade"
        id="addNew"
        tabindex="-1"
        aria-labelledby="addNewAboutModalLabel"
        aria-hidden="true"
>
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content site-table-modal">
            <div class="modal-body popup-body">
                <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Close"
                ></button>
                <div class="popup-body-text">
                    <h3 class="title mb-4">{{ __('Add New') }}</h3>
                    <form action="{{ route('admin.page.content-store') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="type" value="about">

                        <div class="site-input-groups">
                            <label for="" class="box-input-label">{{ __('Icon Class:') }}
                                <a class="link"
                                   href="https://fontawesome.com/icons"
                                   target="_blank">{{ __('Fontawesome') }} /</a><a class="link"
                                                                                   href="{{ asset('frontend/default/fonts/demo.html') }}"
                                                                                   target="_blank">{{ __('Icommon') }}</a>:
                            </label>
                            <input type="text" name="icon" class="box-input mb-0" placeholder="icon class" required=""/>
                        </div>
                        <div class="site-input-groups">
                            <label for="" class="box-input-label">{{ __('Title:') }}</label>
                            <input type="text" name="title" class="box-input mb-0" placeholder="Title" required=""/>
                        </div>


                        <div class="action-btns">
                            <button type="submit" class="site-btn-sm primary-btn me-2">
                                <i icon-name="check"></i>
                                {{ __('Add New') }}
                            </button>
                            <a
                                    href="#"
                                    class="site-btn-sm red-btn"
                                    data-bs-dismiss="modal"
                                    aria-label="Close"
                            >
                                <i icon-name="x"></i>
                                {{ __('Close') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
