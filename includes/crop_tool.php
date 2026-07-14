<?php
/**
 * Shared image upload + crop/resize tool markup.
 * Used by create_listing.php and edit_listing.php inside their <form>
 * (the form must have enctype="multipart/form-data").
 * The behaviour lives in js/main.js (vanilla JS + HTML5 Canvas).
 */
?>
<input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp">
<input type="hidden" name="cropped_image" id="croppedImage">

<div class="crop-tool" id="cropTool" hidden>
    <p class="muted">Drag the box to reposition it, or drag a corner to resize (locked to 4:3).
        Click <strong>Confirm Crop</strong> when the framing looks right.</p>
    <div class="crop-stage" id="cropStage">
        <img id="cropImage" alt="Image to crop">
        <div class="crop-box" id="cropBox">
            <span class="crop-handle" data-corner="nw"></span>
            <span class="crop-handle" data-corner="ne"></span>
            <span class="crop-handle" data-corner="sw"></span>
            <span class="crop-handle" data-corner="se"></span>
        </div>
    </div>
    <div class="crop-actions">
        <button type="button" class="btn btn-primary btn-small" id="cropConfirm">Confirm Crop</button>
        <button type="button" class="btn btn-outline btn-small" id="cropReset">Reset</button>
        <button type="button" class="btn btn-outline btn-small" id="cropCancel">Cancel</button>
    </div>
</div>

<div class="crop-preview-wrap" id="cropPreviewWrap" hidden>
    <p class="muted">Cropped photo that will be uploaded (800&times;600):</p>
    <img id="cropPreview" class="crop-preview" alt="Cropped preview">
</div>
