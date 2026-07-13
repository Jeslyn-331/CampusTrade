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
});
