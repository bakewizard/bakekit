<!-- Confirm modal -->
<div class="modal" id="confirm-modal" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning-subtle">
                <h5 class="modal-title">Confirm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-2">
                        <i class="fa-solid fa-3x fa-fw fa-circle-question text-warning"></i>
                    </div>
                    <div class="col-md-10" id="confirm-message">

                    </div>
                </div>
                <!--<i class="fa-solid fa-3x fa-fw fa-circle-question text-warning"></i> <span id="confirm-message"></span>-->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-success" id="confirm-button">OK</button>
            </div>
        </div>
    </div>
</div>
<!-- /Confirm modal -->