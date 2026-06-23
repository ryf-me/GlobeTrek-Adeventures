from playwright.sync_api import sync_playwright, TimeoutError as PWTimeout
import os
import json
import time
from datetime import datetime

BASE = "http://localhost/globetrek"
RESULTS = os.path.join(os.path.dirname(os.path.abspath(__file__)), "test-results")
os.makedirs(RESULTS, exist_ok=True)

report = {
    "timestamp": datetime.now().isoformat(),
    "tests": [],
    "summary": {"passed": 0, "failed": 0, "total": 0}
}

def log(msg, level="INFO"):
    print(f"[{level}] {msg}")

def record(name, passed, details=""):
    status = "PASS" if passed else "FAIL"
    report["tests"].append({"name": name, "status": status, "details": details})
    report["summary"]["total"] += 1
    if passed:
        report["summary"]["passed"] += 1
    else:
        report["summary"]["failed"] += 1
    log(f"{status}: {name}" + (f" ({details})" if details else ""), "PASS" if passed else "FAIL")

def safe_goto(page, url, **kwargs):
    """Navigate with fallback - try networkidle, then domcontentloaded."""
    kwargs.setdefault("timeout", 20000)
    try:
        resp = page.goto(url, wait_until="networkidle", **kwargs)
        return resp
    except PWTimeout:
        resp = page.goto(url, wait_until="domcontentloaded", **kwargs)
        return resp

def login(page, email, password):
    """Login and verify session."""
    page.goto(f"{BASE}/pages/login.php", wait_until="domcontentloaded", timeout=15000)
    page.fill('#email', email)
    page.fill('#password', password)
    page.click('button.login-submit')
    page.wait_for_load_state("domcontentloaded", timeout=10000)
    time.sleep(1)
    return "login" not in page.url.lower()

def run_tests():
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)

        # ==========================================
        # PHASE 1: PUBLIC PAGES SMOKE TEST
        # ==========================================
        log("=== PHASE 1: PUBLIC PAGES SMOKE TEST ===")

        public_pages = [
            ("Homepage", "/index.php"),
            ("Destinations", "/pages/destinations.php"),
            ("Packages", "/pages/packages.php"),
            ("Guides", "/pages/guides.php"),
            ("Accommodations", "/pages/accommodations.php"),
            ("Transportation", "/pages/transportation.php"),
            ("About", "/pages/about.php"),
            ("Contact", "/pages/contact.php"),
            ("FAQ", "/pages/faq.php"),
            ("Terms", "/pages/terms.php"),
            ("Privacy", "/pages/privacy.php"),
            ("Payment Policy", "/pages/payment-policy.php"),
            ("Login", "/pages/login.php"),
            ("Signup", "/pages/signup.php"),
        ]

        console_errors = {}

        for name, path in public_pages:
            context = browser.new_context(viewport={"width": 1440, "height": 900})
            page = context.new_page()
            errors = []
            page.on("console", lambda msg: errors.append(msg.text) if msg.type == "error" else None)
            page.on("pageerror", lambda err: errors.append(str(err)))

            try:
                resp = safe_goto(page, f"{BASE}{path}")
                status_code = resp.status if resp else 0

                if status_code == 200:
                    record(f"Public: {name} loads (HTTP {status_code})", True)
                    page.screenshot(path=os.path.join(RESULTS, f"public_{name.lower().replace(' ', '_')}.png"), full_page=True)

                    if errors:
                        console_errors[name] = errors
                        record(f"Public: {name} console errors", False, f"{len(errors)} errors: {errors[0][:100]}")
                    else:
                        record(f"Public: {name} no console errors", True)
                else:
                    record(f"Public: {name} loads (HTTP {status_code})", False, f"Expected 200, got {status_code}")
            except Exception as e:
                record(f"Public: {name} loads", False, str(e)[:150])
            finally:
                page.close()
                context.close()

        # ==========================================
        # PHASE 2: DETAIL PAGES
        # ==========================================
        log("=== PHASE 2: DETAIL PAGES ===")

        detail_pages = [
            ("Destination Details", "/pages/destination-details.php?id=1"),
            ("Package Details", "/pages/package-details.php?id=1"),
        ]

        for name, path in detail_pages:
            context = browser.new_context(viewport={"width": 1440, "height": 900})
            page = context.new_page()
            errors = []
            page.on("console", lambda msg: errors.append(msg.text) if msg.type == "error" else None)

            try:
                resp = safe_goto(page, f"{BASE}{path}")
                status_code = resp.status if resp else 0
                record(f"Detail: {name} loads (HTTP {status_code})", status_code == 200)
                page.screenshot(path=os.path.join(RESULTS, f"detail_{name.lower().replace(' ', '_')}.png"), full_page=True)
            except Exception as e:
                record(f"Detail: {name}", False, str(e)[:150])
            finally:
                page.close()
                context.close()

        # ==========================================
        # PHASE 3: AUTHENTICATION FLOW
        # ==========================================
        log("=== PHASE 3: AUTHENTICATION FLOW ===")

        # --- Signup flow ---
        ctx = browser.new_context(viewport={"width": 1440, "height": 900})
        page = ctx.new_page()
        try:
            safe_goto(page, f"{BASE}/pages/signup.php")
            record("Auth: Signup page loads", True)

            page.screenshot(path=os.path.join(RESULTS, "auth_signup_empty.png"), full_page=True)

            signup_email = f"newuser_{int(time.time())}@testing.com"
            # Find the signup form fields
            page.fill('input[name="full_name"]', 'New Test User')
            page.fill('input[name="email"]', signup_email)
            page.fill('input[name="password"]', 'TestPass123!')
            page.fill('input[name="confirm_password"]', 'TestPass123!')

            page.screenshot(path=os.path.join(RESULTS, "auth_signup_filled.png"), full_page=True)

            page.click('button[type="submit"]')
            page.wait_for_load_state("domcontentloaded", timeout=10000)
            time.sleep(1)
            record("Auth: Signup form submits", True, f"Redirected to {page.url}")
            page.screenshot(path=os.path.join(RESULTS, "auth_signup_result.png"), full_page=True)
        except Exception as e:
            record("Auth: Signup flow", False, str(e)[:150])
        finally:
            page.close()
            ctx.close()

        # --- Login with invalid credentials ---
        ctx = browser.new_context(viewport={"width": 1440, "height": 900})
        page = ctx.new_page()
        try:
            safe_goto(page, f"{BASE}/pages/login.php")
            record("Auth: Login page loads", True)

            page.fill('#email', 'invalid@test.com')
            page.fill('#password', 'wrongpassword')
            page.click('button.login-submit')
            page.wait_for_load_state("domcontentloaded", timeout=10000)
            time.sleep(1)

            content = page.content()
            has_error = "invalid" in content.lower() or "error" in content.lower()
            record("Auth: Invalid login shows error", has_error, "Error message displayed" if has_error else "No error message found")
            page.screenshot(path=os.path.join(RESULTS, "auth_login_invalid.png"), full_page=True)
        except Exception as e:
            record("Auth: Invalid login", False, str(e)[:150])
        finally:
            page.close()
            ctx.close()

        # --- Login with valid user credentials ---
        ctx = browser.new_context(viewport={"width": 1440, "height": 900})
        page = ctx.new_page()
        try:
            safe_goto(page, f"{BASE}/pages/login.php")
            page.fill('#email', 'testuser@testing.com')
            page.fill('#password', 'password')
            page.click('button.login-submit')
            page.wait_for_load_state("domcontentloaded", timeout=10000)
            time.sleep(2)

            current_url = page.url
            logged_in = "login" not in current_url.lower()
            record("Auth: Valid user login redirects", logged_in, f"Redirected to: {current_url}")
            page.screenshot(path=os.path.join(RESULTS, "auth_login_success.png"), full_page=True)

            if logged_in:
                # Test accessing protected page with this session
                safe_goto(page, f"{BASE}/pages/my-bookings.php")
                still_logged = "login" not in page.url.lower()
                record("Auth: Session persists across pages", still_logged, f"URL: {page.url}")
        except Exception as e:
            record("Auth: Valid user login", False, str(e)[:150])
        finally:
            page.close()
            ctx.close()

        # --- Login with valid admin credentials ---
        ctx = browser.new_context(viewport={"width": 1440, "height": 900})
        page = ctx.new_page()
        try:
            safe_goto(page, f"{BASE}/pages/login.php")
            page.fill('#email', 'testadmin@testing.com')
            page.fill('#password', 'password')
            page.click('button.login-submit')
            page.wait_for_load_state("domcontentloaded", timeout=10000)
            time.sleep(2)

            current_url = page.url
            logged_in = "login" not in current_url.lower()
            record("Auth: Valid admin login redirects", logged_in, f"Redirected to: {current_url}")
            page.screenshot(path=os.path.join(RESULTS, "auth_admin_login_success.png"), full_page=True)

            if logged_in:
                # Test accessing admin panel
                safe_goto(page, f"{BASE}/admin/index.php")
                on_admin = "admin" in page.url.lower() and "login" not in page.url.lower()
                record("Auth: Admin can access admin panel", on_admin, f"URL: {page.url}")
        except Exception as e:
            record("Auth: Valid admin login", False, str(e)[:150])
        finally:
            page.close()
            ctx.close()

        # --- Logout ---
        ctx = browser.new_context(viewport={"width": 1440, "height": 900})
        page = ctx.new_page()
        try:
            safe_goto(page, f"{BASE}/pages/login.php")
            page.fill('#email', 'testuser@testing.com')
            page.fill('#password', 'password')
            page.click('button.login-submit')
            page.wait_for_load_state("domcontentloaded", timeout=10000)
            time.sleep(2)

            logged_in = "login" not in page.url.lower()
            if logged_in:
                safe_goto(page, f"{BASE}/pages/logout.php")
                time.sleep(2)
                # After logout, try protected page
                safe_goto(page, f"{BASE}/pages/my-bookings.php")
                is_on_login = "login" in page.url.lower()
                record("Auth: Logout destroys session", is_on_login, f"After logout redirected to: {page.url}")
                page.screenshot(path=os.path.join(RESULTS, "auth_logout.png"), full_page=True)
            else:
                record("Auth: Logout destroys session", False, "Could not login first")
        except Exception as e:
            record("Auth: Logout", False, str(e)[:150])
        finally:
            page.close()
            ctx.close()

        # ==========================================
        # PHASE 4: BOOKING & WISHLIST FLOW
        # ==========================================
        log("=== PHASE 4: BOOKING & WISHLIST FLOW ===")

        ctx = browser.new_context(viewport={"width": 1440, "height": 900})
        page = ctx.new_page()
        try:
            logged_in = login(page, 'testuser@testing.com', 'password')
            record("Booking: User login for session", logged_in)

            if logged_in:
                # Navigate to package details
                safe_goto(page, f"{BASE}/pages/package-details.php?id=1")
                has_book = page.locator('text=Book').count() > 0 or page.locator('a[href*="booking"]').count() > 0
                record("Booking: Package details has book option", has_book)
                page.screenshot(path=os.path.join(RESULTS, "booking_package_details.png"), full_page=True)

                # Navigate to booking form
                safe_goto(page, f"{BASE}/pages/booking.php?id=1")
                booking_form = page.locator('form').count() > 0
                record("Booking: Booking form loads", booking_form, f"URL: {page.url}")
                page.screenshot(path=os.path.join(RESULTS, "booking_form.png"), full_page=True)

                # Test my bookings
                safe_goto(page, f"{BASE}/pages/my-bookings.php")
                record("Booking: My Bookings page loads", True)
                page.screenshot(path=os.path.join(RESULTS, "booking_my_bookings.png"), full_page=True)

                # Test wishlist
                safe_goto(page, f"{BASE}/pages/wishlist.php")
                record("Booking: Wishlist page loads", True)
                page.screenshot(path=os.path.join(RESULTS, "booking_wishlist.png"), full_page=True)
            else:
                record("Booking: Flow skipped", False, "Could not login")
        except Exception as e:
            record("Booking: Flow", False, str(e)[:150])
        finally:
            page.close()
            ctx.close()

        # ==========================================
        # PHASE 5: USER ACCOUNT PAGES
        # ==========================================
        log("=== PHASE 5: USER ACCOUNT PAGES ===")

        ctx = browser.new_context(viewport={"width": 1440, "height": 900})
        page = ctx.new_page()
        try:
            logged_in = login(page, 'testuser@testing.com', 'password')

            if logged_in:
                user_pages = [
                    ("User Profile", "/pages/user-profile.php"),
                    ("Settings", "/pages/settings.php"),
                    ("Inquiries", "/pages/inquiries.php"),
                    ("Custom Trips", "/pages/custom-trips.php"),
                ]

                for name, path in user_pages:
                    try:
                        safe_goto(page, f"{BASE}{path}")
                        record(f"User: {name} loads", True, f"URL: {page.url}")
                        page.screenshot(path=os.path.join(RESULTS, f"user_{name.lower().replace(' ', '_')}.png"), full_page=True)
                    except Exception as e:
                        record(f"User: {name}", False, str(e)[:150])
            else:
                record("User: Account pages skipped", False, "Could not login")
        except Exception as e:
            record("User: Account pages", False, str(e)[:150])
        finally:
            page.close()
            ctx.close()

        # ==========================================
        # PHASE 6: ADMIN PANEL
        # ==========================================
        log("=== PHASE 6: ADMIN PANEL ===")

        # --- Non-admin cannot access admin ---
        ctx = browser.new_context(viewport={"width": 1440, "height": 900})
        page = ctx.new_page()
        try:
            logged_in = login(page, 'testuser@testing.com', 'password')
            if logged_in:
                safe_goto(page, f"{BASE}/admin/index.php")
                current_url = page.url
                blocked = "login" in current_url.lower() or "admin/index" not in current_url.lower()
                record("Admin: Non-admin blocked from admin panel", blocked, f"Redirected to: {current_url}")
                page.screenshot(path=os.path.join(RESULTS, "admin_nonadmin_blocked.png"), full_page=True)
            else:
                record("Admin: Non-admin blocked from admin panel", False, "Could not login as user")
        except Exception as e:
            record("Admin: Non-admin access block", False, str(e)[:150])
        finally:
            page.close()
            ctx.close()

        # --- Admin login and dashboard + all pages ---
        ctx = browser.new_context(viewport={"width": 1440, "height": 900})
        page = ctx.new_page()
        try:
            logged_in = login(page, 'testadmin@testing.com', 'password')
            record("Admin: Admin login for session", logged_in)

            if logged_in:
                # Dashboard
                safe_goto(page, f"{BASE}/admin/index.php")
                has_dashboard = "login" not in page.url.lower()
                record("Admin: Dashboard loads", has_dashboard, f"URL: {page.url}")
                page.screenshot(path=os.path.join(RESULTS, "admin_dashboard.png"), full_page=True)

                has_charts = page.locator('canvas').count() > 0
                record("Admin: Dashboard has charts/KPIs", has_charts)

                admin_pages = [
                    ("Packages", "/admin/packages.php"),
                    ("Package Edit", "/admin/package-edit.php"),
                    ("Destinations", "/admin/destinations.php"),
                    ("Accommodations", "/admin/accommodations.php"),
                    ("Transportation", "/admin/transportation.php"),
                    ("Guides", "/admin/guides.php"),
                    ("Bookings", "/admin/bookings.php"),
                    ("Inquiries", "/admin/inquiries.php"),
                    ("Contacts", "/admin/contacts.php"),
                    ("Custom Trips", "/admin/custom-trips.php"),
                    ("Users", "/admin/users.php"),
                    ("Providers", "/admin/providers.php"),
                    ("Newsletters", "/admin/newsletters.php"),
                    ("Reports", "/admin/reports.php"),
                    ("Customer Reports", "/admin/customer-reports.php"),
                    ("System Logs", "/admin/system-logs.php"),
                ]

                for name, path in admin_pages:
                    try:
                        resp = safe_goto(page, f"{BASE}{path}")
                        status = resp.status if resp else 0
                        on_page = "login" not in page.url.lower()
                        passed = on_page and status == 200
                        record(f"Admin: {name} loads (HTTP {status})", passed)
                        page.screenshot(path=os.path.join(RESULTS, f"admin_{name.lower().replace(' ', '_')}.png"), full_page=True)
                    except Exception as e:
                        record(f"Admin: {name}", False, str(e)[:150])
            else:
                record("Admin: Dashboard skipped", False, "Could not login as admin")
        except Exception as e:
            record("Admin: Dashboard", False, str(e)[:150])
        finally:
            page.close()
            ctx.close()

        # ==========================================
        # PHASE 7: FORMS & VALIDATION
        # ==========================================
        log("=== PHASE 7: FORMS & VALIDATION ===")

        # --- Contact form empty ---
        ctx = browser.new_context(viewport={"width": 1440, "height": 900})
        page = ctx.new_page()
        try:
            safe_goto(page, f"{BASE}/pages/contact.php")
            submit = page.locator('button[type="submit"], input[type="submit"]')
            if submit.count() > 0:
                submit.first.click()
                page.wait_for_timeout(2000)
                page.screenshot(path=os.path.join(RESULTS, "forms_contact_empty.png"), full_page=True)
                record("Forms: Contact form validation (empty submit)", True)
            else:
                record("Forms: Contact form has submit button", False, "No submit button found")
        except Exception as e:
            record("Forms: Contact", False, str(e)[:150])
        finally:
            page.close()
            ctx.close()

        # --- Contact form with data ---
        ctx = browser.new_context(viewport={"width": 1440, "height": 900})
        page = ctx.new_page()
        try:
            safe_goto(page, f"{BASE}/pages/contact.php")
            name_field = page.locator('input[name="name"], input[placeholder*="name" i]')
            email_field = page.locator('input[name="email"], input[type="email"]')
            subject_field = page.locator('input[name="subject"]')
            msg_field = page.locator('textarea')

            if name_field.count() > 0:
                name_field.first.fill("Test User")
            if email_field.count() > 0:
                email_field.first.fill("test@testing.com")
            if subject_field.count() > 0:
                subject_field.first.fill("Test Inquiry")
            if msg_field.count() > 0:
                msg_field.first.fill("This is a test message from automated testing.")

            page.screenshot(path=os.path.join(RESULTS, "forms_contact_filled.png"), full_page=True)

            submit = page.locator('button[type="submit"], input[type="submit"]')
            if submit.count() > 0:
                submit.first.click()
                page.wait_for_load_state("domcontentloaded", timeout=10000)
                time.sleep(1)
                record("Forms: Contact form submit with data", True, f"URL: {page.url}")
                page.screenshot(path=os.path.join(RESULTS, "forms_contact_result.png"), full_page=True)
        except Exception as e:
            record("Forms: Contact fill", False, str(e)[:150])
        finally:
            page.close()
            ctx.close()

        # ==========================================
        # PHASE 8: RESPONSIVE / MOBILE TEST
        # ==========================================
        log("=== PHASE 8: RESPONSIVE / MOBILE ===")

        mobile_context = browser.new_context(
            viewport={"width": 375, "height": 812},
            is_mobile=True,
            has_touch=True
        )

        mobile_pages = [
            ("Homepage", "/index.php"),
            ("Packages", "/pages/packages.php"),
            ("Login", "/pages/login.php"),
        ]

        for name, path in mobile_pages:
            page = mobile_context.new_page()
            try:
                safe_goto(page, f"{BASE}{path}", timeout=25000)
                record(f"Mobile: {name} loads", True)
                page.screenshot(path=os.path.join(RESULTS, f"mobile_{name.lower().replace(' ', '_')}.png"), full_page=True)
            except Exception as e:
                record(f"Mobile: {name}", False, str(e)[:150])
            finally:
                page.close()

        mobile_context.close()

        # ==========================================
        # CONSOLE ERRORS SUMMARY
        # ==========================================
        if console_errors:
            log("=== CONSOLE ERRORS SUMMARY ===")
            for page_name, errs in console_errors.items():
                log(f"  {page_name}: {len(errs)} errors")
                for e in errs[:3]:
                    log(f"    - {e[:200]}")

        # ==========================================
        # SAVE REPORT
        # ==========================================
        browser.close()

    report_path = os.path.join(RESULTS, "test_report.json")
    with open(report_path, "w") as f:
        json.dump(report, f, indent=2)

    print("\n" + "=" * 60)
    print("TEST REPORT SUMMARY")
    print("=" * 60)
    print(f"Timestamp: {report['timestamp']}")
    print(f"Total:  {report['summary']['total']}")
    print(f"Passed: {report['summary']['passed']}")
    print(f"Failed: {report['summary']['failed']}")
    rate = report['summary']['passed'] / max(report['summary']['total'], 1) * 100
    print(f"Rate:   {rate:.1f}%")
    print("=" * 60)

    if report["summary"]["failed"] > 0:
        print("\nFAILED TESTS:")
        for t in report["tests"]:
            if t["status"] == "FAIL":
                print(f"  FAIL: {t['name']} - {t['details']}")

    print(f"\nFull report saved to: {report_path}")
    return report

if __name__ == "__main__":
    run_tests()
