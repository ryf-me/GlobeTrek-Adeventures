/**
 * Review Modal – Star rating, character count, modal controls, type switching
 *
 * Depends on: script.js (for Escape key & overlay click close)
 */

(function() {
  'use strict';

  // Base path for form actions (set by the page)
  var basePath = '';

  // --- Open / Close ---
  window.openReviewModal = function(packageId) {
    var modal = document.getElementById('reviewModal');
    if (!modal) return;

    // Detect basePath from the form action
    var form = document.getElementById('reviewForm');
    if (form) {
      var action = form.getAttribute('action') || '';
      basePath = action.replace('pages/submit-review.php', '').replace('pages/submit-guide-review.php', '');
    }

    // Set package_id
    var packageInput = document.getElementById('rv-package-id');
    if (packageInput) packageInput.value = packageId || 0;

    // Reset type selector to general (unless overridden)
    var typeSelect = document.getElementById('rv-review-type');
    if (typeSelect && !typeSelect.dataset.override) {
      typeSelect.value = 'general';
    }
    if (typeSelect) delete typeSelect.dataset.override;

    // Show/hide fields based on type
    onReviewTypeChange();

    // If packageId is provided, set type to package
    if (packageId && packageId > 0) {
      if (typeSelect) {
        typeSelect.value = 'package';
        onReviewTypeChange();
      }
      var pkgSelect = document.getElementById('rv-package-select');
      if (pkgSelect) pkgSelect.value = packageId;
    }

    // Reset form
    if (form) form.reset();

    // Reset rating
    var ratingValue = document.getElementById('rv-rating-value');
    if (ratingValue) ratingValue.value = 0;
    var stars = document.querySelectorAll('#rv-rating-selector .star');
    stars.forEach(function(s) { s.classList.remove('selected'); });

    // Reset char count
    var count = document.getElementById('rv-content-count');
    if (count) count.textContent = '0';

    // Reset hidden IDs
    var guideInput = document.getElementById('rv-guide-id');
    if (guideInput) guideInput.value = 0;
    if (packageInput) packageInput.value = packageId || 0;

    // Enable submit button
    var btn = document.getElementById('rv-submit-btn');
    if (btn) btn.disabled = false;

    modal.classList.add('open');
  };

  window.closeReviewModal = function() {
    var modal = document.getElementById('reviewModal');
    if (modal) modal.classList.remove('open');
    // Reset override flag
    var typeSelect = document.getElementById('rv-review-type');
    if (typeSelect) delete typeSelect.dataset.override;
  };

  // --- Review Type Switching ---
  window.onReviewTypeChange = function() {
    var typeSelect = document.getElementById('rv-review-type');
    var pkgField = document.getElementById('rv-package-field');
    var guideField = document.getElementById('rv-guide-field');
    var hint = document.getElementById('rv-package-hint');
    var form = document.getElementById('reviewForm');
    var type = typeSelect ? typeSelect.value : 'general';

    // Show/hide fields
    if (pkgField) pkgField.style.display = (type === 'package') ? 'block' : 'none';
    if (guideField) guideField.style.display = (type === 'guide') ? 'block' : 'none';

    // Update hint
    if (hint) {
      if (type === 'package') {
        hint.textContent = 'Select the package you traveled with and share your feedback.';
      } else if (type === 'guide') {
        hint.textContent = 'Share your experience with a specific guide — feedback and complaints help us improve.';
      } else {
        hint.textContent = 'Writing a general review about your experience with GlobeTrek.';
      }
    }

    // Update form action
    if (form) {
      if (type === 'guide') {
        form.action = basePath + 'pages/submit-guide-review.php';
      } else {
        form.action = basePath + 'pages/submit-review.php';
      }
    }
  };

  // --- Guide Selection → set hidden guide_id ---
  function initGuideSelect() {
    var guideSelect = document.getElementById('rv-guide-select');
    var guideInput = document.getElementById('rv-guide-id');
    if (guideSelect && guideInput) {
      guideSelect.addEventListener('change', function() {
        guideInput.value = this.value || 0;
      });
    }
  }

  // --- Package Selection → set hidden package_id ---
  function initPackageSelect() {
    var pkgSelect = document.getElementById('rv-package-select');
    var pkgInput = document.getElementById('rv-package-id');
    if (pkgSelect && pkgInput) {
      pkgSelect.addEventListener('change', function() {
        pkgInput.value = this.value || 0;
      });
    }
  }

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
      var typeSelect = document.getElementById('rv-review-type');
      var type = typeSelect ? typeSelect.value : 'general';

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

      // Validate entity selection based on type
      if (type === 'package') {
        var pkgSelect = document.getElementById('rv-package-select');
        if (pkgSelect && parseInt(pkgSelect.value, 10) < 1) {
          e.preventDefault();
          alert('Please select a package to review.');
          return;
        }
      } else if (type === 'guide') {
        var guideSelect = document.getElementById('rv-guide-select');
        if (guideSelect && parseInt(guideSelect.value, 10) < 1) {
          e.preventDefault();
          alert('Please select a guide to review.');
          return;
        }
        // Also set the hidden guide_id before submit
        var guideInput = document.getElementById('rv-guide-id');
        if (guideInput && guideSelect) {
          guideInput.value = guideSelect.value || 0;
        }
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
    initGuideSelect();
    initPackageSelect();
    onReviewTypeChange();
  }
})();
