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
       Image crop & resize tool — shared component.
       Vanilla JS + HTML5 Canvas, no libraries. Each .crop-root on
       the page is initialised with its own configuration read from
       data attributes:
         data-ratio   : width/height lock (e.g. "1"), empty = free-form
         data-circle  : "1" = circular (avatar-style) mask
         data-out-w/h : output resolution
       Listing photos: free-form, rectangular, 1600x1200.
       Profile pictures: 1:1 locked, circular, 400x400.
       Pointer events cover both mouse and touch input.
       ============================================================ */
    function initCropTool(root) {
        var MAX_IMAGE_BYTES = 10 * 1024 * 1024; // 10MB
        var MIN_BOX = 40;

        var ratio = parseFloat(root.dataset.ratio) || null; // width/height; null = free-form
        var outW = parseInt(root.dataset.outW, 10);
        var outH = parseInt(root.dataset.outH, 10);

        var fileInput = root.querySelector('.crop-file');
        var croppedInput = root.querySelector('.crop-data');
        var tool = root.querySelector('.crop-tool');
        var cropImage = root.querySelector('.crop-image');
        var cropBox = root.querySelector('.crop-box');
        var previewWrap = root.querySelector('.crop-preview-wrap');
        var previewImg = root.querySelector('.crop-preview');

        // Crop box state in DISPLAYED-image pixels
        var box = { x: 0, y: 0, w: 0, h: 0 };
        var stageW = 0, stageH = 0;

        function renderBox() {
            cropBox.style.left = box.x + 'px';
            cropBox.style.top = box.y + 'px';
            cropBox.style.width = box.w + 'px';
            cropBox.style.height = box.h + 'px';
        }

        function initBox() {
            stageW = cropImage.clientWidth;
            stageH = cropImage.clientHeight;
            if (ratio) {
                // Largest ratio-locked box that fits the image, centered
                if (stageW / stageH > ratio) {
                    box.h = stageH;
                    box.w = stageH * ratio;
                } else {
                    box.w = stageW;
                    box.h = stageW / ratio;
                }
            } else {
                // Free-form: start with the full image selected
                box.w = stageW;
                box.h = stageH;
            }
            box.x = (stageW - box.w) / 2;
            box.y = (stageH - box.h) / 2;
            renderBox();
        }

        function clamp(value, min, max) {
            return Math.min(Math.max(value, min), max);
        }

        // ---- File selection: validate size/type, then open the crop tool ----
        fileInput.addEventListener('change', function () {
            croppedInput.value = '';
            previewWrap.hidden = true;
            tool.hidden = true;

            var file = fileInput.files[0];
            if (!file) return;

            if (file.size > MAX_IMAGE_BYTES) {
                alert('Image too large. Maximum file size is 10MB. Please compress your image or choose a smaller file.');
                fileInput.value = '';
                return;
            }
            var allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            if (allowedTypes.indexOf(file.type) === -1) {
                alert('Invalid format. Please upload JPG, PNG, or WebP.');
                fileInput.value = '';
                return;
            }

            var reader = new FileReader();
            reader.onload = function (e) {
                cropImage.onload = function () {
                    tool.hidden = false;
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
                startBox: { x: box.x, y: box.y, w: box.w, h: box.h }
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
                box.y = clamp(start.y + dy, 0, stageH - box.h);
            } else {
                // Resize keeping the opposite corner anchored
                var corner = gesture.corner;
                var anchorX = corner === 'nw' || corner === 'sw' ? start.x + start.w : start.x;
                var anchorY = corner === 'nw' || corner === 'ne' ? start.y + start.h : start.y;
                var growRight = corner === 'ne' || corner === 'se';
                var growDown = corner === 'sw' || corner === 'se';

                var newW = growRight ? start.w + dx : start.w - dx;
                var maxW = growRight ? stageW - anchorX : anchorX;

                if (ratio) {
                    // Width drives the resize; height follows the lock
                    var maxH = growDown ? stageH - anchorY : anchorY;
                    newW = clamp(newW, MIN_BOX, Math.min(maxW, maxH * ratio));
                    box.w = newW;
                    box.h = newW / ratio;
                } else {
                    // Free-form: width and height resize independently
                    var newH = growDown ? start.h + dy : start.h - dy;
                    box.w = clamp(newW, MIN_BOX, maxW);
                    box.h = clamp(newH, MIN_BOX, growDown ? stageH - anchorY : anchorY);
                }
                box.x = growRight ? anchorX : anchorX - box.w;
                box.y = growDown ? anchorY : anchorY - box.h;
            }
            renderBox();
        });

        function endGesture() { gesture = null; }
        cropBox.addEventListener('pointerup', endGesture);
        cropBox.addEventListener('pointercancel', endGesture);

        // ---- Confirm: draw the selection onto a canvas and export base64 ----
        root.querySelector('.crop-confirm').addEventListener('click', function () {
            var scaleX = cropImage.naturalWidth / cropImage.clientWidth;
            var scaleY = cropImage.naturalHeight / cropImage.clientHeight;

            var canvas = document.createElement('canvas');
            canvas.width = outW;
            canvas.height = outH;
            var ctx = canvas.getContext('2d');
            ctx.drawImage(
                cropImage,
                box.x * scaleX, box.y * scaleY, box.w * scaleX, box.h * scaleY,
                0, 0, outW, outH
            );

            // 0.85 JPEG quality keeps file size reasonable even at 1600x1200
            var dataURL = canvas.toDataURL('image/jpeg', 0.85);
            croppedInput.value = dataURL;
            previewImg.src = dataURL;
            previewWrap.hidden = false;
            tool.hidden = true;
            // The cropped base64 replaces the raw file — clear it so the
            // original (possibly huge) file is not uploaded as well.
            fileInput.value = '';
        });

        root.querySelector('.crop-reset').addEventListener('click', initBox);

        root.querySelector('.crop-cancel').addEventListener('click', function () {
            tool.hidden = true;
            previewWrap.hidden = true;
            fileInput.value = '';
            croppedInput.value = '';
        });
    }

    document.querySelectorAll('.crop-root').forEach(initCropTool);
});
