/* ============================================================
   CampusTrade — main JavaScript (vanilla, no libraries)
   Handles: hamburger menu, dropdowns, filter toggle,
   contact-seller reveal, client-side form validation.
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

    /* ---------- Hamburger menu (mobile) ---------- */
    var hamburger = document.getElementById('hamburger');
    var navLinks = document.getElementById('navLinks');
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', function () {
            var isOpen = navLinks.classList.toggle('open');
            hamburger.classList.toggle('open', isOpen);
            hamburger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    /* ---------- Dropdown menus (tap to open on touch/mobile) ---------- */
    document.querySelectorAll('.dropdown-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function (event) {
            // On narrow screens the toggle opens the menu instead of navigating
            if (window.innerWidth < 768) {
                event.preventDefault();
                var dropdown = toggle.parentElement;
                dropdown.classList.toggle('open');
            }
        });
    });

    /* ---------- Browse page: filter panel toggle (mobile) ---------- */
    var filterToggle = document.getElementById('filterToggle');
    var filterForm = document.getElementById('filterForm');
    if (filterToggle && filterForm) {
        filterToggle.addEventListener('click', function () {
            filterForm.classList.toggle('open');
        });
    }

    /* ---------- Item page: reveal seller contact details ---------- */
    var contactBtn = document.getElementById('contactSellerBtn');
    var contactReveal = document.getElementById('contactReveal');
    if (contactBtn && contactReveal) {
        contactBtn.addEventListener('click', function () {
            contactReveal.hidden = !contactReveal.hidden;
            contactBtn.textContent = contactReveal.hidden ? 'Contact Seller' : 'Hide Contact Details';
        });
    }

    /* ---------- Client-side validation helpers ----------
       Server-side PHP validation remains the authority;
       this just gives users faster feedback. */

    function showError(input, message) {
        input.classList.add('invalid');
        var error = input.parentElement.querySelector('.js-error');
        if (!error) {
            error = document.createElement('p');
            error.className = 'js-error';
            error.style.color = '#c62828';
            error.style.fontSize = '0.82rem';
            error.style.marginTop = '0.2rem';
            input.parentElement.appendChild(error);
        }
        error.textContent = message;
    }

    function clearError(input) {
        input.classList.remove('invalid');
        var error = input.parentElement.querySelector('.js-error');
        if (error) {
            error.remove();
        }
    }

    function isValidEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    /* Validate any form marked with novalidate that we know about */
    function attachValidation(formId, rules) {
        var form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', function (event) {
            var valid = true;

            rules.forEach(function (rule) {
                var input = form.querySelector('[name="' + rule.name + '"]');
                if (!input) return;
                clearError(input);
                var value = input.value.trim();

                if (rule.required && value === '') {
                    showError(input, rule.label + ' is required.');
                    valid = false;
                } else if (value !== '') {
                    if (rule.minLength && value.length < rule.minLength) {
                        showError(input, rule.label + ' must be at least ' + rule.minLength + ' characters.');
                        valid = false;
                    } else if (rule.email && !isValidEmail(value)) {
                        showError(input, 'Please enter a valid email address.');
                        valid = false;
                    } else if (rule.pattern && !rule.pattern.test(value)) {
                        showError(input, rule.message || rule.label + ' format is invalid.');
                        valid = false;
                    } else if (rule.positiveNumber && (isNaN(value) || parseFloat(value) <= 0)) {
                        showError(input, rule.label + ' must be a number greater than 0.');
                        valid = false;
                    }
                }
            });

            // Password confirmation checks
            var pass = form.querySelector('[name="password"], [name="new_password"]');
            var confirm = form.querySelector('[name="confirm_password"]');
            if (pass && confirm && pass.value !== '' && pass.value !== confirm.value) {
                clearError(confirm);
                showError(confirm, 'Passwords do not match.');
                valid = false;
            }

            if (!valid) {
                event.preventDefault();
            }
        });
    }

    attachValidation('registerForm', [
        { name: 'username', label: 'Username', required: true, minLength: 3, pattern: /^[A-Za-z0-9_]+$/, message: 'Username may only contain letters, numbers and underscores.' },
        { name: 'email', label: 'Email', required: true, email: true },
        { name: 'password', label: 'Password', required: true, minLength: 8, pattern: /^(?=.*[A-Za-z])(?=.*[0-9]).+$/, message: 'Password must contain both letters and numbers.' },
        { name: 'confirm_password', label: 'Password confirmation', required: true }
    ]);

    attachValidation('loginForm', [
        { name: 'email', label: 'Email', required: true, email: true },
        { name: 'password', label: 'Password', required: true }
    ]);

    attachValidation('contactForm', [
        { name: 'name', label: 'Name', required: true },
        { name: 'email', label: 'Email', required: true, email: true },
        { name: 'subject', label: 'Subject', required: true },
        { name: 'message', label: 'Message', required: true, minLength: 10 }
    ]);

    attachValidation('listingForm', [
        { name: 'title', label: 'Title', required: true },
        { name: 'description', label: 'Description', required: true, minLength: 10 },
        { name: 'price', label: 'Price', required: true, positiveNumber: true },
        { name: 'category', label: 'Category', required: true },
        { name: 'item_condition', label: 'Condition', required: true }
    ]);

    attachValidation('passwordForm', [
        { name: 'current_password', label: 'Current password', required: true },
        { name: 'new_password', label: 'New password', required: true, minLength: 8, pattern: /^(?=.*[A-Za-z])(?=.*[0-9]).+$/, message: 'Password must contain both letters and numbers.' },
        { name: 'confirm_password', label: 'Password confirmation', required: true }
    ]);

    /* ---------- Password visibility toggle (eye icon) ----------
       Clicking the eye switches the input between type="password"
       (hidden) and type="text" (visible), and swaps the icon. */
    document.querySelectorAll('.toggle-password').forEach(function (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            var input = toggleBtn.parentElement.querySelector('input');
            if (!input) return;
            var type = input.type === 'password' ? 'text' : 'password';
            input.type = type;
            toggleBtn.classList.toggle('eye-open', type === 'text');
            toggleBtn.setAttribute('aria-label', type === 'text' ? 'Hide password' : 'Show password');
        });
    });

    /* ---------- Payment page: show only the selected method's panel ---------- */
    var paymentForm = document.getElementById('paymentForm');
    if (paymentForm) {
        var methodRadios = paymentForm.querySelectorAll('input[name="payment_method"]');

        function showPanel() {
            var selected = paymentForm.querySelector('input[name="payment_method"]:checked');
            ['FPX', 'QR', 'Cash'].forEach(function (method) {
                var panel = document.getElementById('panel-' + method);
                if (panel) {
                    panel.hidden = !(selected && selected.value === method);
                }
            });
        }

        methodRadios.forEach(function (radio) {
            radio.addEventListener('change', showPanel);
        });
        showPanel(); // apply initial state (e.g. after a validation error)
    }

    /* ---------- Chatbox: poll for new messages every 4 seconds ----------
       Calls chat_fetch.php with the last seen message id; new messages
       are appended as bubbles. textContent is used so message text can
       never inject HTML (XSS-safe). */
    var thread = document.getElementById('chatThread');
    if (thread) {
        var conversationId = thread.dataset.conversation;
        var lastId = parseInt(thread.dataset.last, 10) || 0;

        thread.scrollTop = thread.scrollHeight; // start scrolled to the newest message

        function appendMessage(msg) {
            var row = document.createElement('div');
            row.className = 'chat-bubble-row ' + (msg.mine ? 'mine' : 'theirs');

            var bubble = document.createElement('div');
            bubble.className = 'chat-bubble';

            var text = document.createElement('p');
            text.textContent = msg.text;

            var time = document.createElement('span');
            time.className = 'chat-time';
            time.textContent = msg.time;

            bubble.appendChild(text);
            bubble.appendChild(time);
            row.appendChild(bubble);
            thread.appendChild(row);
            thread.scrollTop = thread.scrollHeight;
        }

        setInterval(function () {
            fetch('chat_fetch.php?c=' + encodeURIComponent(conversationId) + '&after=' + lastId)
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (!data.messages) return;
                    data.messages.forEach(function (msg) {
                        if (msg.id > lastId) {
                            appendMessage(msg);
                            lastId = msg.id;
                        }
                    });
                })
                .catch(function () { /* server unreachable — try again next tick */ });
        }, 4000);
    }

    /* ---------- Discount form: live "X% OFF" preview ---------- */
    var discountInput = document.getElementById('discount_price');
    var discountPreview = document.getElementById('discountPctPreview');
    if (discountInput && discountPreview) {
        discountInput.addEventListener('input', function () {
            var original = parseFloat(discountInput.dataset.original);
            var value = parseFloat(discountInput.value);
            if (isNaN(value) || value <= 0) {
                discountPreview.textContent = '';
            } else if (value >= original) {
                discountPreview.textContent = 'Must be lower than the current price';
                discountPreview.classList.add('invalid-pct');
            } else {
                var pct = Math.round((original - value) / original * 100);
                discountPreview.textContent = '= ' + pct + '% OFF';
                discountPreview.classList.remove('invalid-pct');
            }
        });
    }

    /* ============================================================
       Image crop & resize tool (create/edit listing)
       Vanilla JS + HTML5 Canvas, no libraries. The crop box is
       locked to 4:3; output is 800x600 JPEG submitted as base64.
       Pointer events cover both mouse and touch input.
       ============================================================ */
    var itemFileInput = document.getElementById('image');
    var cropTool = document.getElementById('cropTool');

    if (itemFileInput && cropTool) {
        var MAX_IMAGE_BYTES = 10 * 1024 * 1024; // 10MB
        var OUTPUT_W = 800, OUTPUT_H = 600;     // 4:3 output
        var RATIO = OUTPUT_W / OUTPUT_H;        // width / height
        var MIN_BOX = 40;

        var cropStage = document.getElementById('cropStage');
        var cropImage = document.getElementById('cropImage');
        var cropBox = document.getElementById('cropBox');
        var croppedInput = document.getElementById('croppedImage');
        var previewWrap = document.getElementById('cropPreviewWrap');
        var previewImg = document.getElementById('cropPreview');

        // Crop box state in DISPLAYED-image pixels (height derived from width)
        var box = { x: 0, y: 0, w: 0 };
        var stageW = 0, stageH = 0;

        function boxH() { return box.w / RATIO; }

        function renderBox() {
            cropBox.style.left = box.x + 'px';
            cropBox.style.top = box.y + 'px';
            cropBox.style.width = box.w + 'px';
            cropBox.style.height = boxH() + 'px';
        }

        function initBox() {
            stageW = cropImage.clientWidth;
            stageH = cropImage.clientHeight;
            // Largest 4:3 box that fits the image, centered
            if (stageW / stageH > RATIO) {
                box.w = stageH * RATIO;
            } else {
                box.w = stageW;
            }
            box.x = (stageW - box.w) / 2;
            box.y = (stageH - boxH()) / 2;
            renderBox();
        }

        function clamp(value, min, max) {
            return Math.min(Math.max(value, min), max);
        }

        // ---- File selection: validate size/type, then open the crop tool ----
        itemFileInput.addEventListener('change', function () {
            croppedInput.value = '';
            previewWrap.hidden = true;
            cropTool.hidden = true;

            var file = itemFileInput.files[0];
            if (!file) return;

            if (file.size > MAX_IMAGE_BYTES) {
                alert('Image too large. Maximum file size is 10MB. Please compress your image or choose a smaller file.');
                itemFileInput.value = '';
                return;
            }
            if (file.type !== 'image/jpeg' && file.type !== 'image/png') {
                alert('Only JPEG and PNG images are allowed.');
                itemFileInput.value = '';
                return;
            }

            var reader = new FileReader();
            reader.onload = function (e) {
                cropImage.onload = function () {
                    cropTool.hidden = false;
                    // Wait a frame so the browser lays out the displayed size
                    requestAnimationFrame(initBox);
                };
                cropImage.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });

        // ---- Drag / resize with pointer events (mouse + touch) ----
        var gesture = null; // { mode: 'drag'|'resize', corner, startX, startY, startBox }

        cropBox.addEventListener('pointerdown', function (e) {
            e.preventDefault();
            var corner = e.target.dataset ? e.target.dataset.corner : null;
            gesture = {
                mode: corner ? 'resize' : 'drag',
                corner: corner,
                startX: e.clientX,
                startY: e.clientY,
                startBox: { x: box.x, y: box.y, w: box.w }
            };
            cropBox.setPointerCapture(e.pointerId);
        });

        cropBox.addEventListener('pointermove', function (e) {
            if (!gesture) return;
            var dx = e.clientX - gesture.startX;
            var dy = e.clientY - gesture.startY;
            var start = gesture.startBox;

            if (gesture.mode === 'drag') {
                box.x = clamp(start.x + dx, 0, stageW - box.w);
                box.y = clamp(start.y + dy, 0, stageH - boxH());
            } else {
                // Resize keeping the opposite corner anchored and 4:3 locked
                var startH = start.w / RATIO;
                var anchorX, anchorY, newW;

                if (gesture.corner === 'se') {
                    anchorX = start.x; anchorY = start.y;
                    newW = start.w + dx;
                    newW = clamp(newW, MIN_BOX, Math.min(stageW - anchorX, (stageH - anchorY) * RATIO));
                    box.x = anchorX; box.y = anchorY; box.w = newW;
                } else if (gesture.corner === 'sw') {
                    anchorX = start.x + start.w; anchorY = start.y;
                    newW = start.w - dx;
                    newW = clamp(newW, MIN_BOX, Math.min(anchorX, (stageH - anchorY) * RATIO));
                    box.w = newW; box.x = anchorX - newW; box.y = anchorY;
                } else if (gesture.corner === 'ne') {
                    anchorX = start.x; anchorY = start.y + startH;
                    newW = start.w + dx;
                    newW = clamp(newW, MIN_BOX, Math.min(stageW - anchorX, anchorY * RATIO));
                    box.w = newW; box.x = anchorX; box.y = anchorY - newW / RATIO;
                } else { // 'nw'
                    anchorX = start.x + start.w; anchorY = start.y + startH;
                    newW = start.w - dx;
                    newW = clamp(newW, MIN_BOX, Math.min(anchorX, anchorY * RATIO));
                    box.w = newW; box.x = anchorX - newW; box.y = anchorY - newW / RATIO;
                }
            }
            renderBox();
        });

        function endGesture() { gesture = null; }
        cropBox.addEventListener('pointerup', endGesture);
        cropBox.addEventListener('pointercancel', endGesture);

        // ---- Confirm: draw the selection onto a canvas and export base64 ----
        document.getElementById('cropConfirm').addEventListener('click', function () {
            var scaleX = cropImage.naturalWidth / cropImage.clientWidth;
            var scaleY = cropImage.naturalHeight / cropImage.clientHeight;

            var canvas = document.createElement('canvas');
            canvas.width = OUTPUT_W;
            canvas.height = OUTPUT_H;
            var ctx = canvas.getContext('2d');
            ctx.drawImage(
                cropImage,
                box.x * scaleX, box.y * scaleY, box.w * scaleX, boxH() * scaleY,
                0, 0, OUTPUT_W, OUTPUT_H
            );

            var dataURL = canvas.toDataURL('image/jpeg', 0.85);
            croppedInput.value = dataURL;
            previewImg.src = dataURL;
            previewWrap.hidden = false;
            cropTool.hidden = true;
            // The cropped base64 replaces the raw file — clear it so the
            // original (possibly huge) file is not uploaded as well.
            itemFileInput.value = '';
        });

        document.getElementById('cropReset').addEventListener('click', initBox);

        document.getElementById('cropCancel').addEventListener('click', function () {
            cropTool.hidden = true;
            previewWrap.hidden = true;
            itemFileInput.value = '';
            croppedInput.value = '';
        });
    }
});
