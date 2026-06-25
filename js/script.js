/**
 * GlobeTrek Adventures — Main Frontend JavaScript
 *
 * Handles: navigation toggle, password visibility, modals, profile dropdown,
 * stats counter animation, destination filters, testimonial arrows, and CSRF token injection.
 */

// --- Content protection: block copy and cut actions ---
document.addEventListener('copy', function(e) { e.preventDefault(); });
document.addEventListener('cut', function(e) { e.preventDefault(); });

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

  var scrollAmount = 300;

  nextBtn.addEventListener('click', function () {
    track.parentElement.scrollBy({ left: scrollAmount, behavior: 'smooth' });
  });

  prevBtn.addEventListener('click', function () {
    track.parentElement.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
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
