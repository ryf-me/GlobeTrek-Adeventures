/**
 * GlobeTrek Adventures — Main Frontend JavaScript
 *
 * Handles: navigation toggle, password visibility, modals, profile dropdown,
 * stats counter animation, destination filters, testimonial arrows, and CSRF token injection.
 */

// --- Content protection: block copy and cut actions ---
document.addEventListener('copy', function(e) { e.preventDefault(); });
document.addEventListener('cut', function(e) { e.preventDefault(); });

// --- Client-side Form Validation ---
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

function clearFieldError(formGroup) {
    formGroup.classList.remove('has-error');
    var err = formGroup.querySelector('.field-error');
    if (err) err.remove();
}

function validateEmailFormat(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function validateLoginForm(e) {
    var form = e.target;
    var email = form.querySelector('#email');
    var password = form.querySelector('#password');
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

    if (password) {
        var pwGroup = password.closest('.form-group');
        clearFieldError(pwGroup);
        if (password.value === '') {
            showFieldError(pwGroup, 'Please enter your password.');
            valid = false;
        }
    }

    if (!valid) e.preventDefault();
}

function validateSignupForm(e) {
    var form = e.target;
    var fields = {
        'full-name': { label: 'Full name', group: '.form-group' },
        'signup-email': { label: 'Email', group: '.form-group' },
        'signup-password': { label: 'Password', group: '.form-group' },
        'confirm-password': { label: 'Confirm password', group: '.form-group' }
    };
    var valid = true;

    // Full name
    var nameInput = form.querySelector('#full-name');
    if (nameInput) {
        var nameGroup = nameInput.closest('.form-group');
        clearFieldError(nameGroup);
        if (nameInput.value.trim() === '') {
            showFieldError(nameGroup, 'Please enter your full name.');
            valid = false;
        }
    }

    // Email
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

    // Password
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

    // Confirm password
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

    // Terms
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

    if (!valid) e.preventDefault();
}

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

function validateResetForm(e) {
    var form = e.target;
    var pw = form.querySelector('#new-password');
    var confirm = form.querySelector('#confirm-password');
    var valid = true;

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

// Attach form validators
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

// --- Password Strength Meter ---
function updatePasswordStrength(input) {
    var val = input.value;
    var meter = input.closest('.form-group') ? input.closest('.form-group').querySelector('.password-strength-meter') : null;
    if (!meter) return;

    var score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    var labels = ['Weak', 'Fair', 'Good', 'Strong'];
    var colors = ['#ba1a1a', '#e6a817', '#4caf50', '#2e7d32'];
    var widths = ['25%', '50%', '75%', '100%'];

    meter.innerHTML = '';
    if (val.length === 0) return;

    var bar = document.createElement('div');
    bar.className = 'password-strength-bar';
    bar.style.width = widths[score - 1] || '0%';
    bar.style.backgroundColor = colors[score - 1] || '#ccc';
    meter.appendChild(bar);

    var label = document.createElement('span');
    label.className = 'password-strength-label';
    label.textContent = labels[score - 1] || '';
    label.style.color = colors[score - 1] || '#ccc';
    meter.appendChild(label);
}

document.addEventListener('DOMContentLoaded', function() {
    var pwInputs = document.querySelectorAll('#signup-password, #new-password');
    pwInputs.forEach(function(input) {
        input.addEventListener('input', function() { updatePasswordStrength(input); });
    });
});

// Global CSRF token — read from the variable set by navbar.php
var csrfToken = window.csrfToken || (function () {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
})();

// --- Navigation scroll helper ---
function scrollToSection(id) {
  const el = document.getElementById(id);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' });
  }
}

// --- Mobile menu toggle ---
const menuToggle = document.querySelector('.menu-toggle');
const navLinks = document.querySelector('.nav-links');

if (menuToggle && navLinks) {
  navLinks.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => {
      navLinks.classList.remove('active');
      menuToggle.setAttribute('aria-expanded', 'false');
      menuToggle.textContent = '☰';
    });
  });
  menuToggle.addEventListener('click', () => {
    navLinks.classList.toggle('active');
    const isOpen = navLinks.classList.contains('active');
    menuToggle.setAttribute('aria-expanded', isOpen);
    menuToggle.textContent = isOpen ? '×' : '☰';
  });
}

// --- Password visibility toggle ---
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

// --- Modal open/close helpers ---
function openModal(id) {
  var el = document.getElementById(id);
  if (el) el.classList.add('open');
}

function closeModal(id) {
  var el = document.getElementById(id);
  if (el) el.classList.remove('open');
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    document.querySelectorAll('.inq-modal-overlay.open, .adm-modal-overlay.open').forEach(function(overlay) {
      overlay.classList.remove('open');
    });
  }
});

// Close modal on overlay click
document.querySelectorAll('.inq-modal-overlay, .adm-modal-overlay').forEach(function(overlay) {
  overlay.addEventListener('click', function(e) {
    if (e.target === overlay) {
      overlay.classList.remove('open');
    }
  });
});

// --- Profile dropdown (accessible keyboard + click handling) ---
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

  trigger.addEventListener('click', function (e) {
    e.stopPropagation();
    toggleDropdown();
  });

  trigger.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      toggleDropdown();
    } else if (e.key === 'Escape') {
      closeDropdown();
      trigger.focus();
    }
  });

  document.addEventListener('click', function (e) {
    if (!dropdown.contains(e.target) && !trigger.contains(e.target)) {
      closeDropdown();
    }
  });

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeDropdown();
    }
  });

  dropdown.addEventListener('click', function (e) {
    e.stopPropagation();
  });
})();

// --- Stats counter animation (IntersectionObserver-based) ---
(function () {
  var statNumbers = document.querySelectorAll('.stat-number[data-target]');
  if (!statNumbers.length) return;

  function animateCounter(el) {
    var target = parseInt(el.getAttribute('data-target'), 10);
    var duration = 2000;
    var startTime = null;

    function step(timestamp) {
      if (!startTime) startTime = timestamp;
      var progress = Math.min((timestamp - startTime) / duration, 1);
      var eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = Math.floor(eased * target).toLocaleString();
      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        el.textContent = target.toLocaleString();
      }
    }

    requestAnimationFrame(step);
  }

  var observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        animateCounter(entry.target);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.5 });

  statNumbers.forEach(function (el) {
    observer.observe(el);
  });
})();

// --- Destination filter tabs ---
(function () {
  var tabs = document.querySelectorAll('.dest-tab');
  var cards = document.querySelectorAll('.dest-card');
  if (!tabs.length || !cards.length) return;

  tabs.forEach(function (tab) {
    tab.addEventListener('click', function () {
      var filter = tab.getAttribute('data-filter');

      tabs.forEach(function (t) { t.classList.remove('active'); });
      tab.classList.add('active');

      cards.forEach(function (card) {
        var category = card.getAttribute('data-category');
        if (filter === 'all' || category === filter) {
          card.style.display = '';
          card.style.opacity = '0';
          card.style.transform = 'translateY(10px)';
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

// --- Wishlist toggle ---
(function () {
  var wishlistBtns = document.querySelectorAll('.pkg-wishlist');
  wishlistBtns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      btn.classList.toggle('active');
    });
  });
})();

// --- Testimonials carousel (horizontal scroll with arrows) ---
(function () {
  var track = document.querySelector('.testimonials-track');
  var prevBtn = document.querySelector('.test-prev');
  var nextBtn = document.querySelector('.test-next');
  if (!track || !prevBtn || !nextBtn) return;

  var scrollAmount = 340;

  nextBtn.addEventListener('click', function () {
    track.parentElement.scrollBy({ left: scrollAmount, behavior: 'smooth' });
  });

  prevBtn.addEventListener('click', function () {
    track.parentElement.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
  });
})();

// --- Guides carousel arrows ---
(function () {
  var track = document.querySelector('.guides-track');
  var prevBtn = document.querySelector('.guides-prev');
  var nextBtn = document.querySelector('.guides-next');
  if (!track || !prevBtn || !nextBtn) return;

  var offset = 0;
  var gap = 16; // 1rem gap

  function getCardWidth() {
    var card = track.querySelector('.guide-card');
    return card ? card.offsetWidth + gap : 300;
  }

  function getVisibleCards() {
    var containerWidth = track.parentElement.offsetWidth;
    var cardWidth = getCardWidth();
    return Math.floor(containerWidth / cardWidth);
  }

  function getMaxOffset() {
    var totalCards = track.querySelectorAll('.guide-card').length;
    var visibleCards = getVisibleCards();
    var cardWidth = getCardWidth();
    if (totalCards <= visibleCards) return 0;
    return (totalCards - visibleCards) * cardWidth;
  }

  function updateArrows() {
    prevBtn.style.opacity = offset <= 0 ? '0.4' : '1';
    prevBtn.style.pointerEvents = offset <= 0 ? 'none' : 'auto';
    nextBtn.style.opacity = offset >= getMaxOffset() ? '0.4' : '1';
    nextBtn.style.pointerEvents = offset >= getMaxOffset() ? 'none' : 'auto';
  }

  function slide(dir) {
    var cardWidth = getCardWidth();
    var max = getMaxOffset();
    offset = Math.max(0, Math.min(offset + dir * cardWidth, max));
    track.style.transform = 'translateX(' + -offset + 'px)';
    updateArrows();
  }

  nextBtn.addEventListener('click', function () { slide(1); });
  prevBtn.addEventListener('click', function () { slide(-1); });

  updateArrows();
  window.addEventListener('resize', function () {
    offset = Math.min(offset, getMaxOffset());
    track.style.transform = 'translateX(' + -offset + 'px)';
    updateArrows();
  });
})();

// --- Hero Search: Flatpickr Date Range ---
(function () {
  var dateInput = document.getElementById('hero-date-range');
  var checkinHidden = document.getElementById('hero-checkin');
  var checkoutHidden = document.getElementById('hero-checkout');
  if (!dateInput || typeof flatpickr === 'undefined') return;

  flatpickr(dateInput, {
    mode: 'range',
    dateFormat: 'Y-m-d',
    minDate: 'today',
    disableMobile: true,
    onChange: function (selectedDates) {
      if (selectedDates.length === 2) {
        var fmt = function (d) {
          var y = d.getFullYear();
          var m = String(d.getMonth() + 1).padStart(2, '0');
          var day = String(d.getDate()).padStart(2, '0');
          return y + '-' + m + '-' + day;
        };
        checkinHidden.value = fmt(selectedDates[0]);
        checkoutHidden.value = fmt(selectedDates[1]);
      } else {
        checkinHidden.value = '';
        checkoutHidden.value = '';
      }
    }
  });
})();

// --- Hero Search: Travelers Popup ---
(function () {
  var display = document.getElementById('hero-travelers-display');
  var popup = document.getElementById('travelers-popup');
  var countEl = document.getElementById('travelers-count');
  var hiddenInput = document.getElementById('hero-travelers');
  if (!display || !popup || !countEl || !hiddenInput) return;

  var count = 2;

  display.addEventListener('click', function (e) {
    e.stopPropagation();
    popup.classList.toggle('open');
  });

  document.addEventListener('click', function (e) {
    if (!popup.contains(e.target) && e.target !== display) {
      popup.classList.remove('open');
    }
  });

  popup.querySelectorAll('.travelers-btn').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var action = btn.getAttribute('data-action');
      if (action === 'increase' && count < 20) {
        count++;
      } else if (action === 'decrease' && count > 1) {
        count--;
      }
      countEl.textContent = count;
      hiddenInput.value = count;
      display.value = count + ' Traveler' + (count > 1 ? 's' : '');
    });
  });
})();
