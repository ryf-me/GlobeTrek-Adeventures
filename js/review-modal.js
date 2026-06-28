/**
 * File: js/review-modal.js
 * Purpose: Manages the review submission modal — star rating selection,
 *          character counter, review type switching (general/package/guide),
 *          entity selection, form validation, and modal open/close controls.
 * Dependencies: script.js (for Escape key and overlay click global handlers)
 * Used By: Package detail pages, guide profiles, and the reviews page
 */

(function() {
  'use strict';

  // === MODULE STATE ===
  // Base path for form actions, auto-detected from the form's action attribute.
  // Used to build the correct submit URL when switching review types.
  var basePath = '';

  // === MODAL OPEN/CLOSE ===

  /**
   * Opens the review modal for a given package (or general review).
   * Resets all form fields, sets the package ID if provided, and
   * determines the correct form action URL based on the review type.
   *
   * @param {number} packageId - The package ID to review, or 0 for general reviews
   */
  window.openReviewModal = function(packageId) {
    var modal = document.getElementById('reviewModal');
    if (!modal) return;

    // Detect basePath from the form action (e.g., "/pages/submit-review.php" -> "/")
    var form = document.getElementById('reviewForm');
    if (form) {
      var action = form.getAttribute('action') || '';
      basePath = action.replace('pages/submit-review.php', '').replace('pages/submit-guide-review.php', '');
    }

    // Set the hidden package_id field
    var packageInput = document.getElementById('rv-package-id');
    if (packageInput) packageInput.value = packageId || 0;

    // Reset the type selector to "general" unless it was overridden externally
    var typeSelect = document.getElementById('rv-review-type');
    if (typeSelect && !typeSelect.dataset.override) {
      typeSelect.value = 'general';
    }
    if (typeSelect) delete typeSelect.dataset.override;

    // Show/hide fields based on the current type selection
    onReviewTypeChange();

    // If a package ID was provided, auto-set the type to "package"
    // and pre-select the package in the dropdown
    if (packageId && packageId > 0) {
      if (typeSelect) {
        typeSelect.value = 'package';
        onReviewTypeChange();
      }
      var pkgSelect = document.getElementById('rv-package-select');
      if (pkgSelect) pkgSelect.value = packageId;
    }

    // Reset the entire form to clear previous inputs
    if (form) form.reset();

    // Reset the star rating to 0 (no stars selected)
    var ratingValue = document.getElementById('rv-rating-value');
    if (ratingValue) ratingValue.value = 0;
    var stars = document.querySelectorAll('#rv-rating-selector .star');
    stars.forEach(function(s) { s.classList.remove('selected'); });

    // Reset the character count display
    var count = document.getElementById('rv-content-count');
    if (count) count.textContent = '0';

    // Reset hidden entity ID fields
    var guideInput = document.getElementById('rv-guide-id');
    if (guideInput) guideInput.value = 0;
    if (packageInput) packageInput.value = packageId || 0;

    // Re-enable the submit button (may have been disabled on previous submit)
    var btn = document.getElementById('rv-submit-btn');
    if (btn) btn.disabled = false;

    // Show the modal overlay
    modal.classList.add('open');
  };

  /**
   * Closes the review modal and clears the type override flag.
   */
  window.closeReviewModal = function() {
    var modal = document.getElementById('reviewModal');
    if (modal) modal.classList.remove('open');
    // Reset the override flag so the next open starts fresh
    var typeSelect = document.getElementById('rv-review-type');
    if (typeSelect) delete typeSelect.dataset.override;
  };

  // === REVIEW TYPE SWITCHING ===
  // Switches between "general", "package", and "guide" review types.
  // Shows/hides the relevant entity selector, updates the hint text,
  // and changes the form action URL to submit to the correct endpoint.

  /**
   * Handles the change event on the review type <select>.
   * Updates field visibility, hint text, and form action URL.
   */
  window.onReviewTypeChange = function() {
    var typeSelect = document.getElementById('rv-review-type');
    var pkgField = document.getElementById('rv-package-field');
    var guideField = document.getElementById('rv-guide-field');
    var hint = document.getElementById('rv-package-hint');
    var form = document.getElementById('reviewForm');
    var type = typeSelect ? typeSelect.value : 'general';

    // Show package selector only for "package" type
    if (pkgField) pkgField.style.display = (type === 'package') ? 'block' : 'none';
    // Show guide selector only for "guide" type
    if (guideField) guideField.style.display = (type === 'guide') ? 'block' : 'none';

    // Update the contextual hint text based on review type
    if (hint) {
      if (type === 'package') {
        hint.textContent = 'Select the package you traveled with and share your feedback.';
      } else if (type === 'guide') {
        hint.textContent = 'Share your experience with a specific guide — feedback and complaints help us improve.';
      } else {
        hint.textContent = 'Writing a general review about your experience with GlobeTrek.';
      }
    }

    // Point the form to the correct server-side handler
    if (form) {
      if (type === 'guide') {
        form.action = basePath + 'pages/submit-guide-review.php';
      } else {
        form.action = basePath + 'pages/submit-review.php';
      }
    }
  };

  // === ENTITY SELECTION HANDLERS ===
  // Sync the selected guide/package from the visible <select> to
  // the corresponding hidden input so it gets submitted with the form.

  /**
   * Initializes the guide <select> to update the hidden guide_id input
   * whenever the user picks a different guide.
   */
  function initGuideSelect() {
    var guideSelect = document.getElementById('rv-guide-select');
    var guideInput = document.getElementById('rv-guide-id');
    if (guideSelect && guideInput) {
      guideSelect.addEventListener('change', function() {
        guideInput.value = this.value || 0;
      });
    }
  }

  /**
   * Initializes the package <select> to update the hidden package_id input
   * whenever the user picks a different package.
   */
  function initPackageSelect() {
    var pkgSelect = document.getElementById('rv-package-select');
    var pkgInput = document.getElementById('rv-package-id');
    if (pkgSelect && pkgInput) {
      pkgSelect.addEventListener('change', function() {
        pkgInput.value = this.value || 0;
      });
    }
  }

  // === STAR RATING SELECTOR ===
  // Interactive star rating widget. Stars light up on hover (preview)
  // and lock in on click. Uses data-value attributes (1-5) on each star.

  /**
   * Initializes a star rating container with click, hover, and leave handlers.
   * Stars up to and including the hovered/clicked star are highlighted.
   *
   * @param {string} containerId - The DOM ID of the star rating container
   */
  function initStarSelector(containerId) {
    var container = document.getElementById(containerId);
    if (!container) return;

    var stars = container.querySelectorAll('.star');
    var hidden = container.querySelector('input[type="hidden"]');
    if (!stars.length || !hidden) return;

    stars.forEach(function(star) {
      // On click: lock in the rating value
      star.addEventListener('click', function() {
        var value = parseInt(this.getAttribute('data-value'), 10);
        hidden.value = value;
        // Highlight all stars up to and including the clicked one
        stars.forEach(function(s, i) {
          s.classList.toggle('selected', i < value);
        });
      });

      // On hover: preview the rating by highlighting stars up to the hovered one
      star.addEventListener('mouseenter', function() {
        var value = parseInt(this.getAttribute('data-value'), 10);
        stars.forEach(function(s, i) {
          s.classList.toggle('selected', i < value);
        });
      });

      // On mouse leave: revert to the currently locked-in rating
      star.addEventListener('mouseleave', function() {
        var currentValue = parseInt(hidden.value, 10);
        stars.forEach(function(s, i) {
          s.classList.toggle('selected', i < currentValue);
        });
      });
    });
  }

  // === CHARACTER COUNTER ===
  // Displays the current character count as the user types in the
  // review content textarea.

  /**
   * Binds an input listener to a textarea that updates a character count display.
   *
   * @param {string} textareaId - ID of the review content textarea
   * @param {string} countId    - ID of the character count display element
   */
  function initCharCounter(textareaId, countId) {
    var textarea = document.getElementById(textareaId);
    var count = document.getElementById(countId);
    if (!textarea || !count) return;

    textarea.addEventListener('input', function() {
      count.textContent = this.value.length;
    });
  }

  // === FORM VALIDATION ===
  // Prevents empty or invalid submissions by checking:
  // - Star rating must be >= 1
  // - Review text must be >= 10 characters
  // - Package must be selected (for package reviews)
  // - Guide must be selected (for guide reviews)
  // Also disables the submit button after first click to prevent double-submit.

  function initFormValidation() {
    var form = document.getElementById('reviewForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
      var rating = document.getElementById('rv-rating-value');
      var content = document.getElementById('rv-content');
      var btn = document.getElementById('rv-submit-btn');
      var typeSelect = document.getElementById('rv-review-type');
      var type = typeSelect ? typeSelect.value : 'general';
      var errorEl = document.getElementById('rv-error');

      if (!rating || !content) return;

      // Helper to show inline error and re-enable button
      function showError(msg) {
        e.preventDefault();
        if (errorEl) {
          errorEl.textContent = msg;
          errorEl.style.display = 'block';
        }
        if (btn) btn.disabled = false;
        return;
      }

      // Hide previous error
      if (errorEl) errorEl.style.display = 'none';

      // Require at least one star
      if (parseInt(rating.value, 10) < 1) {
        showError('Please select a star rating.');
        return;
      }

      // Require minimum review length
      if (content.value.trim().length < 10) {
        showError('Your review must be at least 10 characters long.');
        return;
      }

      // For package reviews: require a package selection
      if (type === 'package') {
        var pkgSelect = document.getElementById('rv-package-select');
        if (pkgSelect && parseInt(pkgSelect.value, 10) < 1) {
          showError('Please select a package to review.');
          return;
        }
      // For guide reviews: require a guide selection
      } else if (type === 'guide') {
        var guideSelect = document.getElementById('rv-guide-select');
        if (guideSelect && parseInt(guideSelect.value, 10) < 1) {
          showError('Please select a guide to review.');
          return;
        }
        // Ensure the hidden guide_id is synced before submission
        var guideInput = document.getElementById('rv-guide-id');
        if (guideInput && guideSelect) {
          guideInput.value = guideSelect.value || 0;
        }
      }

      // Disable submit button to prevent double-submission
      if (btn) btn.disabled = true;
    });
  }

  // === INITIALIZATION ===
  // Wait for DOM ready, then initialize all interactive components.
  // Handles both cases: DOM already loaded or still loading.

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  /**
   * Initializes all review modal components:
   * - Star rating selector
   * - Character counter
   * - Form validation
   * - Guide/package selection sync
   * - Initial type-based field visibility
   */
  function init() {
    initStarSelector('rv-rating-selector');
    initCharCounter('rv-content', 'rv-content-count');
    initFormValidation();
    initGuideSelect();
    initPackageSelect();
    onReviewTypeChange();
  }
})();
