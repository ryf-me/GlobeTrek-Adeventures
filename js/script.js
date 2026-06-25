/**
 * GlobeTrek Adventures — Main Frontend JavaScript
 *
 * Handles: navigation toggle, password visibility, modals, profile dropdown,
 * stats counter animation, and CSRF token injection for AJAX requests.
 */

// --- Content protection: block copy and cut actions ---
document.addEventListener('copy', function(e) { e.preventDefault(); });
document.addEventListener('cut', function(e) { e.preventDefault(); });

// Global CSRF token — read from the variable set by navbar.php
// Fallback to meta tag if the variable is not available
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
    var start = 0;
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

// --- Hero typewriter effect for "Sri Lanka" ---
(function () {
  var target = document.getElementById('typewriter');
  var cursor = document.getElementById('typewriter-cursor');
  if (!target) return;

  var words = ['Sri Lanka', 'Paradise', 'the Pearl of the Indian Ocean'];
  var wordIndex = 0;
  var charIndex = 0;
  var isDeleting = false;
  var typeSpeed = 100;
  var deleteSpeed = 60;
  var pauseEnd = 2000;
  var pauseStart = 500;

  function tick() {
    var currentWord = words[wordIndex];

    if (!isDeleting) {
      target.textContent = currentWord.substring(0, charIndex + 1);
      charIndex++;

      if (charIndex === currentWord.length) {
        isDeleting = true;
        setTimeout(tick, pauseEnd);
        return;
      }
      setTimeout(tick, typeSpeed);
    } else {
      target.textContent = currentWord.substring(0, charIndex - 1);
      charIndex--;

      if (charIndex === 0) {
        isDeleting = false;
        wordIndex = (wordIndex + 1) % words.length;
        setTimeout(tick, pauseStart);
        return;
      }
      setTimeout(tick, deleteSpeed);
    }
  }

  // Blinking cursor
  setInterval(function () {
    if (cursor) {
      cursor.style.opacity = cursor.style.opacity === '0' ? '1' : '0';
    }
  }, 530);

  setTimeout(tick, 800);
})();

// --- Animated Testimonials Carousel ---
(function () {
  var cards = document.querySelectorAll('.testimonial-card');
  var dots = document.querySelectorAll('.testimonials-dot');
  if (!cards.length || !dots.length) return;

  var activeIndex = 0;
  var autoRotateInterval = 6000;
  var timer = null;

  function setActive(index) {
    cards.forEach(function (card) {
      card.classList.remove('active');
    });
    dots.forEach(function (dot) {
      dot.classList.remove('active');
    });

    cards[index].classList.add('active');
    dots[index].classList.add('active');
    activeIndex = index;
  }

  function next() {
    setActive((activeIndex + 1) % cards.length);
  }

  function startAutoRotate() {
    stopAutoRotate();
    timer = setInterval(next, autoRotateInterval);
  }

  function stopAutoRotate() {
    if (timer) {
      clearInterval(timer);
      timer = null;
    }
  }

  dots.forEach(function (dot, index) {
    dot.addEventListener('click', function () {
      setActive(index);
      startAutoRotate();
    });
  });

  // IntersectionObserver to pause when not visible
  var section = document.getElementById('testimonials');
  if (section) {
    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          startAutoRotate();
        } else {
          stopAutoRotate();
        }
      });
    }, { threshold: 0.2 });

    observer.observe(section);
  }

  // Initialize
  setActive(0);
})();