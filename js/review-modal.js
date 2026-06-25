/**
 * Review Modal – Star rating, character count, modal controls
 *
 * Depends on: script.js (for Escape key & overlay click close)
 */

(function() {
  'use strict';

  // --- Open / Close ---
  window.openReviewModal = function(packageId) {
    var modal = document.getElementById('reviewModal');
    if (!modal) return;

    // Set package_id
    var packageInput = document.getElementById('rv-package-id');
    var packageHint = document.getElementById('rv-package-hint');
    if (packageInput) packageInput.value = packageId || 0;

    // Show package hint
    if (packageHint) {
      if (packageId && packageId > 0) {
        packageHint.textContent = 'Reviewing a specific package — your feedback about this trip.';
      } else {
        packageHint.textContent = 'Writing a general review about your experience with GlobeTrek.';
      }
    }

    // Reset form
    var form = document.getElementById('reviewForm');
    if (form) form.reset();

    // Reset rating
    var ratingValue = document.getElementById('rv-rating-value');
    if (ratingValue) ratingValue.value = 0;
    var stars = document.querySelectorAll('#rv-rating-selector .star');
    stars.forEach(function(s) { s.classList.remove('selected'); });

    // Reset char count
    var count = document.getElementById('rv-content-count');
    if (count) count.textContent = '0';

    // Enable submit button
    var btn = document.getElementById('rv-submit-btn');
    if (btn) btn.disabled = false;

    modal.classList.add('open');
  };

  window.closeReviewModal = function() {
    var modal = document.getElementById('reviewModal');
    if (modal) modal.classList.remove('open');
  };

  // --- Star Rating Selector ---
  function initStarSelector(containerId) {
    var container = document.getElementById(containerId);
    if (!container) return;

    var stars = container.querySelectorAll('.star');
    var hidden = container.querySelector('input[type="hidden"]');
    if (!stars.length || !hidden) return;

    stars.forEach(function(star) {
      star.addEventListener('click', function() {
        var value = parseInt(this.getAttribute('data-value'), 10);
        hidden.value = value;
        stars.forEach(function(s, i) {
          s.classList.toggle('selected', i < value);
        });
      });

      star.addEventListener('mouseenter', function() {
        var value = parseInt(this.getAttribute('data-value'), 10);
        stars.forEach(function(s, i) {
          s.classList.toggle('selected', i < value);
        });
      });

      star.addEventListener('mouseleave', function() {
        var currentValue = parseInt(hidden.value, 10);
        stars.forEach(function(s, i) {
          s.classList.toggle('selected', i < currentValue);
        });
      });
    });
  }

  // --- Character Counter ---
  function initCharCounter(textareaId, countId) {
    var textarea = document.getElementById(textareaId);
    var count = document.getElementById(countId);
    if (!textarea || !count) return;

    textarea.addEventListener('input', function() {
      count.textContent = this.value.length;
    });
  }

  // --- Form Validation (prevent empty submit) ---
  function initFormValidation() {
    var form = document.getElementById('reviewForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
      var rating = document.getElementById('rv-rating-value');
      var content = document.getElementById('rv-content');
      var btn = document.getElementById('rv-submit-btn');

      if (!rating || !content) return;

      if (parseInt(rating.value, 10) < 1) {
        e.preventDefault();
        alert('Please select a star rating.');
        return;
      }

      if (content.value.trim().length < 10) {
        e.preventDefault();
        alert('Your review must be at least 10 characters long.');
        return;
      }

      // Disable button to prevent double-submit
      if (btn) btn.disabled = true;
    });
  }

  // --- Init on DOM ready ---
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  function init() {
    initStarSelector('rv-rating-selector');
    initCharCounter('rv-content', 'rv-content-count');
    initFormValidation();
  }
})();
