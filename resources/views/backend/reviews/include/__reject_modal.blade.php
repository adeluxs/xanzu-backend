<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content site-table-modal">
            <div class="modal-body popup-body">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                
                <div class="popup-body-text">
                    <h3 class="title mb-4">{{ __('Reject Review') }}</h3>
                    
                    <form id="rejectForm" method="POST">
                        @csrf
                        @method('PATCH')
                        
                        <div class="site-input-groups">
                            <label for="admin_notes" class="box-input-label">{{ __('Reason for rejection:') }}</label>
                            <textarea 
                                name="admin_notes" 
                                id="admin_notes" 
                                class="form-textarea" 
                                rows="4" 
                                placeholder="{{ __('Enter reason for rejection...') }}" 
                                required
                            ></textarea>
                        </div>

                        <div class="action-btns">
                            <button type="submit" class="site-btn-sm red-btn me-2">
                                <i data-lucide="x-circle"></i>
                                {{ __('Reject Review') }}
                            </button>
                            <a href="#" class="site-btn-sm primary-btn" data-bs-dismiss="modal" aria-label="Close">
                                <i data-lucide="x"></i>
                                {{ __('Cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>