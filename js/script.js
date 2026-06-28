/**
 * File: js/script.js
 * Purpose: Main frontend JavaScript for GlobeTrek Adventures. Handles navigation,
 *          password visibility, modal controls, profile dropdown, stats counter
 *          animation, destination filters, testimonial/guide carousels, hero
 *          search components, form validation, and CSRF token management.
 * Dependencies: Flatpickr (optional, for date range picker), Font Awesome (for icons)
 * Used By: All public-facing pages via the shared navbar include
 */

// === CONTENT PROTECTION ===
// Block copy and cut actions to discourage content scraping
document.addEventListener('copy', function(e) { e.preventDefault(); });
document.addEventListener('cut', function(e) { e.preventDefault(); });

// === CLIENT-SIDE FORM VALIDATION ===

/**
 * Displays an inline error message beneath a form group.
 * Adds the 'has-error' class for CSS styling and creates
 * or updates a <p> element with the error text.
 */
function showFieldError(formGroup, message) {
    formGroup.classList.add('has-error');
    var existing = formGroup.querySelector('.field-error');
    if (existing) {
        existing.textContent = message;
    } else {
        var p = document.createElement('p');
        p.className = 'field-error';
        p.textContent = message;
        formGroup.appendChild(p);
    }
}

/**
 * Removes any existing inline error from a form group.
 * Clears both the error element and the 'has-error' class.
 */
function clearFieldError(formGroup) {
    formGroup.classList.remove('has-error');
    var err = formGroup.querySelector('.field-error');
    if (err) err.remove();
}

/**
 * Tests an email string against a basic RFC pattern.
 * Returns true if the value matches user@domain.tld format.
 */
function validateEmailFormat(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

/**
 * Validates the login form on submit.
 * Checks that email is non-empty and properly formatted,
 * and that password is non-empty.
 */
function validateLoginForm(e) {
    var form = e.target;
    var email = form.querySelector('#email');
    var password = form.querySelector('#password');
    var valid = true;

    // Validate email field
    if (email) {
        var emailGroup = email.closest('.form-group');
        clearFieldError(emailGroup);
        if (email.value.trim() === '') {
            showFieldError(emailGroup, 'Please enter your email address.');
            valid = false;
        } else if (!validateEmailFormat(email.value.trim())) {
            showFieldError(emailGroup, 'Please enter a valid email address.');
            valid = false;
        }
    }

    // Validate password field
    if (password) {
        var pwGroup = password.closest('.form-group');
        clearFieldError(pwGroup);
        if (password.value === '') {
            showFieldError(pwGroup, 'Please enter your password.');
            valid = false;
        }
    }

    // Prevent form submission if validation failed
    if (!valid) e.preventDefault();
}

/**
 * Validates the signup form on submit.
 * Enforces: full name required, valid email, password strength
 * (min 8 chars, uppercase, number, special char), confirm match,
 * and terms acceptance.
 */
function validateSignupForm(e) {
    var form = e.target;
    var fields = {
        'full-name': { label: 'Full name', group: '.form-group' },
        'signup-email': { label: 'Email', group: '.form-group' },
        'signup-password': { label: 'Password', group: '.form-group' },
        'confirm-password': { label: 'Confirm password', group: '.form-group' }
    };
    var valid = true;

    // Validate full name — must not be empty
    var nameInput = form.querySelector('#full-name');
    if (nameInput) {
        var nameGroup = nameInput.closest('.form-group');
        clearFieldError(nameGroup);
        if (nameInput.value.trim() === '') {
            showFieldError(nameGroup, 'Please enter your full name.');
            valid = false;
        }
    }

    // Validate email — must be non-empty and match email pattern
    var emailInput = form.querySelector('#signup-email');
    if (emailInput) {
        var emailGroup = emailInput.closest('.form-group');
        clearFieldError(emailGroup);
        if (emailInput.value.trim() === '') {
            showFieldError(emailGroup, 'Please enter your email address.');
            valid = false;
        } else if (!validateEmailFormat(emailInput.value.trim())) {
            showFieldError(emailGroup, 'Please enter a valid email address.');
            valid = false;
        }
    }

    // Validate password — enforce complexity rules in sequence
    var pwInput = form.querySelector('#signup-password');
    if (pwInput) {
        var pwGroup = pwInput.closest('.form-group');
        clearFieldError(pwGroup);
        var pw = pwInput.value;
        if (pw === '') {
            showFieldError(pwGroup, 'Please enter a password.');
            valid = false;
        } else if (pw.length < 8) {
            showFieldError(pwGroup, 'Password must be at least 8 characters.');
            valid = false;
        } else if (!/[A-Z]/.test(pw)) {
            showFieldError(pwGroup, 'Password must contain at least one uppercase letter.');
            valid = false;
        } else if (!/[0-9]/.test(pw)) {
            showFieldError(pwGroup, 'Password must contain at least one number.');
            valid = false;
        } else if (!/[^A-Za-z0-9]/.test(pw)) {
            showFieldError(pwGroup, 'Password must contain at least one special character.');
            valid = false;
        }
    }

    // Validate confirm password — must match the original password
    var confirmInput = form.querySelector('#confirm-password');
    if (confirmInput && pwInput) {
        var confirmGroup = confirmInput.closest('.form-group');
        clearFieldError(confirmGroup);
        if (confirmInput.value === '') {
            showFieldError(confirmGroup, 'Please confirm your password.');
            valid = false;
        } else if (confirmInput.value !== pwInput.value) {
            showFieldError(confirmGroup, 'Passwords must match.');
            valid = false;
        }
    }

    // Validate terms checkbox — must be checked
    var terms = form.querySelector('#terms');
    if (terms) {
        var termsGroup = terms.closest('.terms-group');
        if (termsGroup) {
            var existingErr = termsGroup.parentNode.querySelector('.terms-error');
            if (existingErr) existingErr.remove();
        }
        if (!terms.checked) {
            var errP = document.createElement('p');
            errP.className = 'field-error terms-error';
            errP.textContent = 'Please accept the terms before continuing.';
            termsGroup.parentNode.insertBefore(errP, termsGroup.nextSibling);
            valid = false;
        }
    }

    // Prevent form submission if any validation failed
    if (!valid) e.preventDefault();
}

/**
 * Validates the forgot-password form on submit.
 * Only checks that email is provided and properly formatted.
 */
function validateForgotForm(e) {
    var form = e.target;
    var email = form.querySelector('#forgot-email');
    var valid = true;

    if (email) {
        var emailGroup = email.closest('.form-group');
        clearFieldError(emailGroup);
        if (email.value.trim() === '') {
            showFieldError(emailGroup, 'Please enter your email address.');
            valid = false;
        } else if (!validateEmailFormat(email.value.trim())) {
            showFieldError(emailGroup, 'Please enter a valid email address.');
            valid = false;
        }
    }

    if (!valid) e.preventDefault();
}

/**
 * Validates the password-reset form on submit.
 * Same password complexity rules as signup, plus confirm-match check.
 */
function validateResetForm(e) {
    var form = e.target;
    var pw = form.querySelector('#new-password');
    var confirm = form.querySelector('#confirm-password');
    var valid = true;

    // Validate new password — same rules as signup password
    if (pw) {
        var pwGroup = pw.closest('.form-group');
        clearFieldError(pwGroup);
        var val = pw.value;
        if (val === '') {
            showFieldError(pwGroup, 'Please enter a new password.');
            valid = false;
        } else if (val.length < 8) {
            showFieldError(pwGroup, 'Password must be at least 8 characters.');
            valid = false;
        } else if (!/[A-Z]/.test(val)) {
            showFieldError(pwGroup, 'Password must contain at least one uppercase letter.');
            valid = false;
        } else if (!/[0-9]/.test(val)) {
            showFieldError(pwGroup, 'Password must contain at least one number.');
            valid = false;
        } else if (!/[^A-Za-z0-9]/.test(val)) {
            showFieldError(pwGroup, 'Password must contain at least one special character.');
            valid = false;
        }
    }

    // Validate confirm password — must match new password
    if (confirm && pw) {
        var confirmGroup = confirm.closest('.form-group');
        clearFieldError(confirmGroup);
        if (confirm.value === '') {
            showFieldError(confirmGroup, 'Please confirm your password.');
            valid = false;
        } else if (confirm.value !== pw.value) {
            showFieldError(confirmGroup, 'Passwords must match.');
            valid = false;
        }
    }

    if (!valid) e.preventDefault();
}

// === ATTACH FORM VALIDATORS ===
// Bind the appropriate validator to each form's submit event
// once the DOM is fully loaded. Only attaches if the form exists on the page.
document.addEventListener('DOMContentLoaded', function() {
    var loginForm = document.querySelector('.login-form');
    if (loginForm) loginForm.addEventListener('submit', validateLoginForm);

    var signupForm = document.querySelector('.signup-form');
    if (signupForm) signupForm.addEventListener('submit', validateSignupForm);

    var forgotForm = document.querySelector('.forgot-form');
    if (forgotForm) forgotForm.addEventListener('submit', validateForgotForm);

    var resetForm = document.querySelector('.reset-form');
    if (resetForm) resetForm.addEventListener('submit', validateResetForm);
});

// === PASSWORD STRENGTH METER ===
/**
 * Evaluates password input in real-time and renders a visual
 * strength bar. Score is based on: length >= 8, has uppercase,
 * has digit, has special character. Each criterion adds 1 point.
 * Displays a colored bar (red -> yellow -> green) and a text label.
 */
function updatePasswordStrength(input) {
    var val = input.value;
    // Find the meter container within the same form-group
    var meter = input.closest('.form-group') ? input.closest('.form-group').querySelector('.password-strength-meter') : null;
    if (!meter) return;

    // Score: 0-4 based on complexity criteria
    var score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    var labels = ['Weak', 'Fair', 'Good', 'Strong'];
    var colors = ['#ba1a1a', '#e6a817', '#4caf50', '#2e7d32'];
    var widths = ['25%', '50%', '75%', '100%'];

    // Clear previous meter content
    meter.innerHTML = '';
    // Don't show anything if the field is empty
    if (val.length === 0) return;

    // Create and style the progress bar element
    var bar = document.createElement('div');
    bar.className = 'password-strength-bar';
    bar.style.width = widths[score - 1] || '0%';
    bar.style.backgroundColor = colors[score - 1] || '#ccc';
    meter.appendChild(bar);

    // Create and style the text label beneath the bar
    var label = document.createElement('span');
    label.className = 'password-strength-label';
    label.textContent = labels[score - 1] || '';
    label.style.color = colors[score - 1] || '#ccc';
    meter.appendChild(label);
}

// Attach input listeners to password fields for live strength updates
document.addEventListener('DOMContentLoaded', function() {
    var pwInputs = document.querySelectorAll('#signup-password, #new-password');
    pwInputs.forEach(function(input) {
        input.addEventListener('input', function() { updatePasswordStrength(input); });
    });
});

// === CSRF TOKEN ===
// Expose a global CSRF token for AJAX requests.
// First checks for a value set by navbar.php via window.csrfToken,
// then falls back to reading from a <meta> tag in the document head.
var csrfToken = window.csrfToken || (function () {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
})();

// === NAVIGATION SCROLL HELPER ===
// Smoothly scrolls to a page section by its element ID.
// Used for anchor links in the navigation bar.
function scrollToSection(id) {
  const el = document.getElementById(id);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' });
  }
}

// === MOBILE MENU TOGGLE ===
// Handles the hamburger menu button on small screens.
// Toggles the 'active' class on .nav-links to show/hide the menu,
// and updates the aria-expanded attribute and button text for accessibility.
const menuToggle = document.querySelector('.menu-toggle');
const navLinks = document.querySelector('.nav-links');

if (menuToggle && navLinks) {
  // Close the menu when any nav link is clicked (mobile UX)
  navLinks.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
      navLinks.classList.remove('active');
      menuToggle.setAttribute('aria-expanded', 'false');
      menuToggle.textContent = '☰';
    });
  });
  // Toggle open/close on hamburger button click
  menuToggle.addEventListener('click', () => {
    navLinks.classList.toggle('active');
    const isOpen = navLinks.classList.contains('active');
    menuToggle.setAttribute('aria-expanded', isOpen);
    menuToggle.textContent = isOpen ? '×' : '☰';
  });
}

// === PASSWORD VISIBILITY TOGGLE ===
// Each .password-toggle button has a data-target attribute pointing
// to the input's ID. Clicking toggles between password/text type
// and applies an 'active' class to the button for visual feedback.
document.querySelectorAll('.password-toggle').forEach((btn) => {
  btn.addEventListener('click', () => {
    const targetId = btn.getAttribute('data-target');
    const input = document.getElementById(targetId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.classList.toggle('active', isPassword);
  });
});

// === MODAL OPEN/CLOSE HELPERS ===
// Generic modal controllers used by inquiry and admin modals.
// Modals are shown/hidden via the 'open' CSS class on overlay elements.
function openModal(id) {
  var el = document.getElementById(id);
  if (el) el.classList.add('open');
}

function closeModal(id) {
  var el = document.getElementById(id);
  if (el) el.classList.remove('open');
}

// Close any open modal when the Escape key is pressed
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.inq-modal-overlay.open, .adm-modal-overlay.open').forEach(function(overlay) {
      overlay.classList.remove('open');
    });
  }
});

// Close modal when clicking on the overlay background (outside the modal content)
document.querySelectorAll('.inq-modal-overlay, .adm-modal-overlay').forEach(function(overlay) {
  overlay.addEventListener('click', function(e) {
    if (e.target === overlay) {
      overlay.classList.remove('open');
    }
  });
});

// === PROFILE DROPDOWN ===
// Accessible dropdown menu for the user profile icon.
// Supports click toggling, keyboard navigation (Enter/Space/Escape),
// and closes when clicking outside the dropdown.
(function () {
  var trigger = document.getElementById('profileTrigger');
  var dropdown = document.getElementById('profileDropdown');
  if (!trigger || !dropdown) return;

  function openDropdown() {
    trigger.setAttribute('aria-expanded', 'true');
    dropdown.classList.add('open');
  }

  function closeDropdown() {
    trigger.setAttribute('aria-expanded', 'false');
    dropdown.classList.remove('open');
  }

  function toggleDropdown() {
    if (trigger.getAttribute('aria-expanded') === 'true') {
      closeDropdown();
    } else {
      openDropdown();
    }
  }

  // Toggle on click, stop propagation to prevent document listener from firing
  trigger.addEventListener('click', function (e) {
    e.stopPropagation();
    toggleDropdown();
  });

  // Keyboard support: Enter/Space to toggle, Escape to close and refocus trigger
  trigger.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      toggleDropdown();
    } else if (e.key === 'Escape') {
      closeDropdown();
      trigger.focus();
    }
  });

  // Close dropdown when clicking anywhere outside it
  document.addEventListener('click', function (e) {
    if (!dropdown.contains(e.target) && !trigger.contains(e.target)) {
      closeDropdown();
    }
  });

  // Also close on global Escape key
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeDropdown();
    }
  });

  // Prevent clicks inside the dropdown from closing it
  dropdown.addEventListener('click', function (e) {
    e.stopPropagation();
  });
})();

// === STATS COUNTER ANIMATION ===
// Animates numbers from 0 to their target value when they scroll
// into the viewport. Uses IntersectionObserver so the animation
// only triggers once per element. The easing function is cubic ease-out
// for a decelerating feel.
(function () {
  var statNumbers = document.querySelectorAll('.stat-number[data-target]');
  if (!statNumbers.length) return;

  /**
   * Animates a single counter element from 0 to its data-target value.
   * Uses requestAnimationFrame for smooth 60fps animation over 2 seconds.
   * Applies cubic ease-out (1 - (1-t)^3) for a natural deceleration.
   */
  function animateCounter(el) {
    var target = parseInt(el.getAttribute('data-target'), 10);
    var duration = 2000; // 2 seconds total animation time
    var startTime = null;

    function step(timestamp) {
      if (!startTime) startTime = timestamp;
      // Progress from 0 to 1 over the duration
      var progress = Math.min((timestamp - startTime) / duration, 1);
      // Cubic ease-out curve for smooth deceleration
      var eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.floor(eased * target).toLocaleString();
      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        // Ensure the final value is exact (avoids rounding artifacts)
        el.textContent = target.toLocaleString();
      }
    }

    requestAnimationFrame(step);
  }

  // Only start animation when element is 50% visible in the viewport
  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        animateCounter(entry.target);
        // Stop observing after animation starts to avoid re-triggering
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.5 });

  statNumbers.forEach(function (el) {
    observer.observe(el);
  });
})();

// === DESTINATION FILTER TABS ===
// Filters destination cards by category (e.g., beach, mountain, cultural).
// Clicking a tab shows only matching cards with a fade-in animation,
// or shows all cards when the "all" tab is selected.
(function () {
  var tabs = document.querySelectorAll('.dest-tab');
  var cards = document.querySelectorAll('.dest-card');
  if (!tabs.length || !cards.length) return;

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var filter = tab.getAttribute('data-filter');

      // Update active tab styling
      tabs.forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');

      // Filter cards: show matching ones with fade-in, hide non-matching
      cards.forEach(function (card) {
        var category = card.getAttribute('data-category');
        if (filter === 'all' || category === filter) {
          // Animate in: set initial state, then transition to final state
          card.style.display = '';
          card.style.opacity = '0';
          card.style.transform = 'translateY(10px)';
          // Small delay ensures the browser registers the initial state
          // before applying the transition
          setTimeout(function () {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
            card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
          }, 50);
        } else {
          card.style.display = 'none';
        }
      });
    });
  });
})();

// === WISHLIST TOGGLE ===
// Simple toggle on wishlist heart buttons — adds/removes 'active' class
// on click. No server-side persistence is handled here (client-only UI).
(function () {
  var wishlistBtns = document.querySelectorAll('.pkg-wishlist');
  wishlistBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      btn.classList.toggle('active');
    });
  });
})();

// === TESTIMONIALS CAROUSEL ===
// Horizontal scroll carousel for testimonial cards.
// Prev/Next buttons scroll the container by a fixed pixel amount (340px)
// to move one card at a time.
(function () {
  var track = document.querySelector('.testimonials-track');
  var prevBtn = document.querySelector('.test-prev');
  var nextBtn = document.querySelector('.test-next');
  if (!track || !prevBtn || !nextBtn) return;

  var scrollAmount = 340; // Pixels to scroll per click (~1 card width)

  nextBtn.addEventListener('click', function () {
    track.parentElement.scrollBy({ left: scrollAmount, behavior: 'smooth' });
  });

  prevBtn.addEventListener('click', function () {
    track.parentElement.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
  });
})();

// === GUIDES CAROUSEL ===
// Card carousel for tour guide profiles. Unlike the testimonials carousel,
// this one uses CSS transform-based sliding with calculated offsets.
// Dynamically disables arrows at boundaries and recalculates on window resize.
(function () {
  var track = document.querySelector('.guides-track');
  var prevBtn = document.querySelector('.guides-prev');
  var nextBtn = document.querySelector('.guides-next');
  if (!track || !prevBtn || !nextBtn) return;

  var offset = 0;  // Current horizontal scroll offset in pixels
  var gap = 16;    // 1rem gap between cards

  /** Returns the width of a single guide card including the gap */
  function getCardWidth() {
    var card = track.querySelector('.guide-card');
    return card ? card.offsetWidth + gap : 300;
  }

  /** Calculates how many cards fit in the visible container */
  function getVisibleCards() {
    var containerWidth = track.parentElement.offsetWidth;
    var cardWidth = getCardWidth();
    return Math.floor(containerWidth / cardWidth);
  }

  /**
   * Returns the maximum scroll offset before reaching the end.
   * Returns 0 if all cards are already visible.
   */
  function getMaxOffset() {
    var totalCards = track.querySelectorAll('.guide-card').length;
    var visibleCards = getVisibleCards();
    var cardWidth = getCardWidth();
    if (totalCards <= visibleCards) return 0;
    return (totalCards - visibleCards) * cardWidth;
  }

  /**
   * Enables/disables arrows based on current scroll position.
   * Faded out (opacity 0.4) and non-interactive at boundaries.
   */
  function updateArrows() {
    prevBtn.style.opacity = offset <= 0 ? '0.4' : '1';
    prevBtn.style.pointerEvents = offset <= 0 ? 'none' : 'auto';
    nextBtn.style.opacity = offset >= getMaxOffset() ? '0.4' : '1';
    nextBtn.style.pointerEvents = offset >= getMaxOffset() ? 'none' : 'auto';
  }

  /**
   * Slides the carousel by one card width in the given direction.
   * Clamps offset to [0, maxOffset] to prevent over-scrolling.
   * Applies the offset via CSS transform: translateX.
   */
  function slide(dir) {
    var cardWidth = getCardWidth();
    var max = getMaxOffset();
    offset = Math.max(0, Math.min(offset + dir * cardWidth, max));
    track.style.transform = 'translateX(' + -offset + 'px)';
    updateArrows();
  }

  nextBtn.addEventListener('click', function () { slide(1); });
  prevBtn.addEventListener('click', function () { slide(-1); });

  // Initialize arrow states
  updateArrows();

  // Recalculate on window resize to prevent broken layout
  window.addEventListener('resize', function () {
    offset = Math.min(offset, getMaxOffset());
    track.style.transform = 'translateX(' + -offset + 'px)';
    updateArrows();
  });
})();

// === HERO SEARCH: DATE RANGE PICKER ===
// Initializes Flatpickr on the hero search date input with range mode.
// Formats selected dates as YYYY-MM-DD and writes them to hidden
// inputs for server-side processing.
(function () {
  var dateInput = document.getElementById('hero-date-range');
  var checkinHidden = document.getElementById('hero-checkin');
  var checkoutHidden = document.getElementById('hero-checkout');
  // Skip initialization if Flatpickr is not loaded
  if (!dateInput || typeof flatpickr === 'undefined') return;

  flatpickr(dateInput, {
    mode: 'range',
    dateFormat: 'Y-m-d',
    minDate: 'today',       // Prevent selecting past dates
    disableMobile: true,    // Use the custom UI even on mobile
    onChange: function (selectedDates) {
      if (selectedDates.length === 2) {
        // Format both selected dates as YYYY-MM-DD
        var fmt = function (d) {
          var y = d.getFullYear();
          var m = String(d.getMonth() + 1).padStart(2, '0');
          var day = String(d.getDate()).padStart(2, '0');
          return y + '-' + m + '-' + day;
        };
        checkinHidden.value = fmt(selectedDates[0]);
        checkoutHidden.value = fmt(selectedDates[1]);
      } else {
        // Clear hidden inputs if range is incomplete
        checkinHidden.value = '';
        checkoutHidden.value = '';
      }
    }
  });
})();

// === HERO SEARCH: TRAVELERS POPUP ===
// Increment/decrement counter for the number of travelers.
// Default is 2, minimum is 1, maximum is 20.
// Updates the display text, a hidden input, and a visible count element.
(function () {
  var display = document.getElementById('hero-travelers-display');
  var popup = document.getElementById('travelers-popup');
  var countEl = document.getElementById('travelers-count');
  var hiddenInput = document.getElementById('hero-travelers');
  if (!display || !popup || !countEl || !hiddenInput) return;

  var count = 2; // Default traveler count

  // Toggle popup visibility on display click; stopPropagation prevents
  // the document click listener from immediately closing it
  display.addEventListener('click', function (e) {
    e.stopPropagation();
    popup.classList.toggle('open');
  });

  // Close popup when clicking anywhere outside it
  document.addEventListener('click', function (e) {
    if (!popup.contains(e.target) && e.target !== display) {
      popup.classList.remove('open');
    }
  });

  // Handle increment/decrement buttons inside the popup
  popup.querySelectorAll('.travelers-btn').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation(); // Prevent popup from closing on button click
      var action = btn.getAttribute('data-action');
      if (action === 'increase' && count < 20) {
        count++;
      } else if (action === 'decrease' && count > 1) {
        count--;
      }
      // Update all display elements and the hidden form input
      countEl.textContent = count;
      hiddenInput.value = count;
      display.value = count + ' Traveler' + (count > 1 ? 's' : '');
    });
  });
})();
