// Simple script for navigation scroll
function scrollToSection(id) {
  const el = document.getElementById(id);
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' });
  }
}

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

// Modal open/close helpers
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

// User Profile Dropdown
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