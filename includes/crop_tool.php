<?php
/**
 * Shared image upload + crop/resize tool (vanilla JS + HTML5 Canvas).
 * The same component serves two contexts with different configuration:
 *
 *   Listing photos (defaults): free-form crop, rectangular mask, 1600x1200 output
 *   Profile pictures:          1:1 locked crop, circular mask, 400x400 output
 *
 * Set these variables BEFORE the require to override the defaults:
 *   $crop_input_id / $crop_input_name  — the file input's id/name
 *   $crop_hidden_name                  — hidden field carrying the base64 result
 *   $crop_ratio                        — width/height lock (e.g. '1'), '' = free-form
 *   $crop_circle                       — 1 = circular (avatar-style) mask
 *   $crop_out_w / $crop_out_h          — output resolution in pixels
 *
 * Behaviour lives in js/main.js (initCropTool), wired up via .crop-root.
 */
$crop_input_id    = $crop_input_id    ?? 'image';
$crop_input_name  = $crop_input_name  ?? 'image';
$crop_hidden_name = $crop_hidden_name ?? 'cropped_image';
$crop_ratio       = $crop_ratio       ?? '';
$crop_circle      = $crop_circle      ?? 0;
$crop_out_w       = $crop_out_w       ?? 1600;
$crop_out_h       = $crop_out_h       ?? 1200;
?>
<div class="crop-root" data-ratio="<?= e((string) $crop_ratio) ?>" data-circle="<?= (int) $crop_circle ?>"
     data-out-w="<?= (int) $crop_out_w ?>" data-out-h="<?= (int) $crop_out_h ?>">
    <input type="file" class="crop-file" id="<?= e($crop_input_id) ?>" name="<?= e($crop_input_name) ?>"
           accept=".jpg,.jpeg,.png,.webp">
    <input type="hidden" class="crop-data" name="<?= e($crop_hidden_name) ?>">

    <div class="crop-tool" hidden>
        <p class="muted">Drag the box to reposition it, or drag a corner to resize.
            Click <strong>Confirm Crop</strong> when the framing looks right.</p>
        <div class="crop-stage">
            <img class="crop-image" alt="Image to crop">
            <div class="crop-box">
                <span class="crop-handle" data-corner="nw"></span>
                <span class="crop-handle" data-corner="ne"></span>
                <span class="crop-handle" data-corner="sw"></span>
                <span class="crop-handle" data-corner="se"></span>
            </div>
        </div>
        <div class="crop-actions">
            <button type="button" class="btn btn-primary btn-small crop-confirm">Confirm Crop</button>
            <button type="button" class="btn btn-outline btn-small crop-reset">Reset</button>
            <button type="button" class="btn btn-outline btn-small crop-cancel">Cancel</button>
        </div>
    </div>

    <div class="crop-preview-wrap" hidden>
        <p class="muted">Cropped photo that will be uploaded (<?= (int) $crop_out_w ?>&times;<?= (int) $crop_out_h ?>):</p>
        <img class="crop-preview" alt="Cropped preview">
    </div>
</div>
<?php
// Reset the config so a later include on the same page starts from defaults
unset($crop_input_id, $crop_input_name, $crop_hidden_name, $crop_ratio, $crop_circle, $crop_out_w, $crop_out_h);
