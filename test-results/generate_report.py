import json
import os
from fpdf import FPDF

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
RESULTS_DIR = os.path.join(BASE_DIR, "test-results")
REPORT_JSON = os.path.join(RESULTS_DIR, "test_report.json")
OUTPUT_PDF = os.path.join(BASE_DIR, "GlobeTrek_Test_Report.pdf")

PHASE_ORDER = [
    "Phase 1: Public Pages Smoke Test",
    "Phase 2: Detail Pages",
    "Phase 3: Authentication Flow",
    "Phase 4: Booking & Wishlist Flow",
    "Phase 5: User Account Pages",
    "Phase 6: Admin Panel",
    "Phase 7: Forms & Validation",
    "Phase 8: Responsive / Mobile",
]

PHASE_KEYWORDS = {
    "Phase 1: Public Pages Smoke Test": ["Public:"],
    "Phase 2: Detail Pages": ["Detail:"],
    "Phase 3: Authentication Flow": ["Auth:"],
    "Phase 4: Booking & Wishlist Flow": ["Booking:"],
    "Phase 5: User Account Pages": ["User:"],
    "Phase 6: Admin Panel": ["Admin:"],
    "Phase 7: Forms & Validation": ["Forms:"],
    "Phase 8: Responsive / Mobile": ["Mobile:"],
}

SCREENSHOT_MAP = {
    "Public: Homepage loads": "public_homepage.png",
    "Public: Destinations loads": "public_destinations.png",
    "Public: Packages loads": "public_packages.png",
    "Public: Guides loads": "public_guides.png",
    "Public: Accommodations loads": "public_accommodations.png",
    "Public: Transportation loads": "public_transportation.png",
    "Public: About loads": "public_about.png",
    "Public: Contact loads": "public_contact.png",
    "Public: FAQ loads": "public_faq.png",
    "Public: Terms loads": "public_terms.png",
    "Public: Privacy loads": "public_privacy.png",
    "Public: Payment Policy loads": "public_payment_policy.png",
    "Public: Login loads": "public_login.png",
    "Public: Signup loads": "public_signup.png",
    "Detail: Destination Details loads": "detail_destination_details.png",
    "Detail: Package Details loads": "detail_package_details.png",
    "Auth: Signup page loads": "auth_signup_empty.png",
    "Auth: Signup form submits": "auth_signup_result.png",
    "Auth: Login page loads": "public_login.png",
    "Auth: Invalid login shows error": "auth_login_invalid.png",
    "Auth: Valid user login redirects": "auth_login_success.png",
    "Auth: Valid admin login redirects": "auth_admin_login_success.png",
    "Auth: Logout destroys session": "auth_logout.png",
    "Booking: Package details has book option": "booking_package_details.png",
    "Booking: Booking form loads": "booking_form.png",
    "Booking: My Bookings page loads": "booking_my_bookings.png",
    "Booking: Wishlist page loads": "booking_wishlist.png",
    "User: User Profile loads": "user_user_profile.png",
    "User: Settings loads": "user_settings.png",
    "User: Inquiries loads": "user_inquiries.png",
    "User: Custom Trips loads": "user_custom_trips.png",
    "Admin: Non-admin blocked from admin panel": "admin_nonadmin_blocked.png",
    "Admin: Dashboard loads": "admin_dashboard.png",
    "Admin: Packages loads": "admin_packages.png",
    "Admin: Package Edit loads": "admin_package_edit.png",
    "Admin: Destinations loads": "admin_destinations.png",
    "Admin: Accommodations loads": "admin_accommodations.png",
    "Admin: Transportation loads": "admin_transportation.png",
    "Admin: Guides loads": "admin_guides.png",
    "Admin: Bookings loads": "admin_bookings.png",
    "Admin: Inquiries loads": "admin_inquiries.png",
    "Admin: Contacts loads": "admin_contacts.png",
    "Admin: Custom Trips loads": "admin_custom_trips.png",
    "Admin: Users loads": "admin_users.png",
    "Admin: Providers loads": "admin_providers.png",
    "Admin: Newsletters loads": "admin_newsletters.png",
    "Admin: Reports loads": "admin_reports.png",
    "Admin: Customer Reports loads": "admin_customer_reports.png",
    "Admin: System Logs loads": "admin_system_logs.png",
    "Forms: Contact form validation": "forms_contact_empty.png",
    "Forms: Contact form submit with data": "forms_contact_result.png",
    "Mobile: Homepage loads": "mobile_homepage.png",
    "Mobile: Packages loads": "mobile_packages.png",
    "Mobile: Login loads": "mobile_login.png",
}


def find_screenshot(test_name):
    exact = SCREENSHOT_MAP.get(test_name)
    if exact:
        path = os.path.join(RESULTS_DIR, exact)
        if os.path.isfile(path):
            return path
    for key, fname in SCREENSHOT_MAP.items():
        if key in test_name:
            path = os.path.join(RESULTS_DIR, fname)
            if os.path.isfile(path):
                return path
    return None


def classify_test(test_name):
    for phase, keywords in PHASE_KEYWORDS.items():
        for kw in keywords:
            if test_name.startswith(kw):
                return phase
    if "console" in test_name.lower():
        return "Phase 1: Public Pages Smoke Test"
    return "Other"


class TestReportPDF(FPDF):
    def header(self):
        if self.page_no() > 1:
            self.set_font("Helvetica", "I", 8)
            self.set_text_color(120, 120, 120)
            self.cell(0, 8, "GlobeTrek Adventures - Full Web Test Report", align="L")
            self.cell(0, 8, f"Page {self.page_no()}", align="R", new_x="LMARGIN", new_y="NEXT")
            self.set_draw_color(200, 200, 200)
            self.line(10, 14, 200, 14)
            self.ln(4)

    def footer(self):
        self.set_y(-15)
        self.set_font("Helvetica", "I", 8)
        self.set_text_color(150, 150, 150)
        self.cell(0, 10, "Generated by GlobeTrek Automated Testing Suite", align="C")


def build_cover(pdf, summary):
    pdf.add_page()
    pdf.ln(30)

    pdf.set_font("Helvetica", "B", 32)
    pdf.set_text_color(26, 58, 75)
    pdf.cell(0, 15, "GlobeTrek Adventures", align="C", new_x="LMARGIN", new_y="NEXT")

    pdf.set_font("Helvetica", "", 20)
    pdf.set_text_color(80, 80, 80)
    pdf.cell(0, 12, "Full Web Test Report", align="C", new_x="LMARGIN", new_y="NEXT")

    pdf.ln(10)
    pdf.set_draw_color(232, 121, 56)
    pdf.set_line_width(1)
    pdf.line(70, pdf.get_y(), 140, pdf.get_y())
    pdf.ln(15)

    pdf.set_font("Helvetica", "", 12)
    pdf.set_text_color(100, 100, 100)
    pdf.cell(0, 8, "Automated Playwright Test Suite", align="C", new_x="LMARGIN", new_y="NEXT")

    from datetime import datetime
    ts = summary.get("timestamp", datetime.now().isoformat())
    try:
        dt = datetime.fromisoformat(ts)
        date_str = dt.strftime("%B %d, %Y at %I:%M %p")
    except Exception:
        date_str = ts
    pdf.cell(0, 8, f"Date: {date_str}", align="C", new_x="LMARGIN", new_y="NEXT")

    pdf.ln(20)

    total = summary.get("total", 0)
    passed = summary.get("passed", 0)
    failed = summary.get("failed", 0)
    rate = (passed / max(total, 1)) * 100

    box_w = 50
    gap = 10
    start_x = (210 - (box_w * 3 + gap * 2)) / 2

    y = pdf.get_y()
    for i, (label, val, color) in enumerate([
        ("TOTAL", str(total), (60, 60, 60)),
        ("PASSED", str(passed), (34, 139, 34)),
        ("FAILED", str(failed), (220, 50, 50)),
    ]):
        x = start_x + i * (box_w + gap)
        pdf.set_fill_color(245, 245, 245)
        pdf.rect(x, y, box_w, 40, "F")
        pdf.set_xy(x, y + 5)
        pdf.set_font("Helvetica", "B", 22)
        pdf.set_text_color(*color)
        pdf.cell(box_w, 12, val, align="C")
        pdf.set_xy(x, y + 22)
        pdf.set_font("Helvetica", "", 10)
        pdf.set_text_color(100, 100, 100)
        pdf.cell(box_w, 8, label, align="C")

    pdf.set_y(y + 50)
    pdf.set_font("Helvetica", "B", 16)
    pdf.set_text_color(34, 139, 34)
    pdf.cell(0, 12, f"Pass Rate: {rate:.1f}%", align="C", new_x="LMARGIN", new_y="NEXT")

    pdf.ln(15)
    pdf.set_font("Helvetica", "", 11)
    pdf.set_text_color(80, 80, 80)
    pdf.cell(0, 8, "Test Environment: XAMPP (Apache + MySQL + PHP 8) on Windows", align="C", new_x="LMARGIN", new_y="NEXT")
    pdf.cell(0, 8, "Browser: Chromium (Playwright Headless)", align="C", new_x="LMARGIN", new_y="NEXT")
    pdf.cell(0, 8, f"Total Pages Tested: 14 public + 2 detail + 16 admin + 4 user = 36 unique pages", align="C", new_x="LMARGIN", new_y="NEXT")


def build_summary_table(pdf, tests):
    pdf.add_page()
    pdf.set_font("Helvetica", "B", 18)
    pdf.set_text_color(26, 58, 75)
    pdf.cell(0, 12, "Test Results Summary", new_x="LMARGIN", new_y="NEXT")
    pdf.ln(5)

    col_w = [8, 105, 18, 59]
    headers = ["#", "Test Name", "Status", "Details"]
    pdf.set_font("Helvetica", "B", 9)
    pdf.set_fill_color(26, 58, 75)
    pdf.set_text_color(255, 255, 255)
    for i, h in enumerate(headers):
        pdf.cell(col_w[i], 8, h, border=1, fill=True, align="C")
    pdf.ln()

    pdf.set_font("Helvetica", "", 8)
    for idx, t in enumerate(tests, 1):
        is_fail = t["status"] == "FAIL"
        if is_fail:
            pdf.set_fill_color(255, 230, 230)
        elif idx % 2 == 0:
            pdf.set_fill_color(248, 248, 248)
        else:
            pdf.set_fill_color(255, 255, 255)

        name = t["name"]
        if len(name) > 65:
            name = name[:62] + "..."
        details = t.get("details", "")
        if len(details) > 35:
            details = details[:32] + "..."

        pdf.set_text_color(40, 40, 40)
        pdf.cell(col_w[0], 7, str(idx), border=1, fill=True, align="C")
        pdf.cell(col_w[1], 7, name, border=1, fill=True)

        if is_fail:
            pdf.set_text_color(220, 50, 50)
            pdf.set_font("Helvetica", "B", 8)
        else:
            pdf.set_text_color(34, 139, 34)
            pdf.set_font("Helvetica", "", 8)
        pdf.cell(col_w[2], 7, t["status"], border=1, fill=True, align="C")
        pdf.set_font("Helvetica", "", 8)

        pdf.set_text_color(40, 40, 40)
        pdf.cell(col_w[3], 7, details, border=1, fill=True)
        pdf.ln()

        if pdf.get_y() > 275:
            pdf.add_page()
            pdf.set_font("Helvetica", "B", 9)
            pdf.set_fill_color(26, 58, 75)
            pdf.set_text_color(255, 255, 255)
            for i, h in enumerate(headers):
                pdf.cell(col_w[i], 8, h, border=1, fill=True, align="C")
            pdf.ln()
            pdf.set_font("Helvetica", "", 8)


def build_phase_section(pdf, phase_name, tests):
    pdf.add_page()
    pdf.set_fill_color(26, 58, 75)
    pdf.set_text_color(255, 255, 255)
    pdf.set_font("Helvetica", "B", 16)
    pdf.cell(0, 14, f"  {phase_name}", fill=True, new_x="LMARGIN", new_y="NEXT")
    pdf.ln(5)

    phase_passed = sum(1 for t in tests if t["status"] == "PASS")
    phase_total = len(tests)
    pdf.set_font("Helvetica", "", 10)
    pdf.set_text_color(80, 80, 80)
    pdf.cell(0, 8, f"{phase_passed}/{phase_total} tests passed", new_x="LMARGIN", new_y="NEXT")
    pdf.ln(5)

    for t in tests:
        if pdf.get_y() > 200:
            pdf.add_page()

        is_fail = t["status"] == "FAIL"

        pdf.set_font("Helvetica", "B", 11)
        if is_fail:
            pdf.set_text_color(220, 50, 50)
        else:
            pdf.set_text_color(34, 139, 34)
        status_label = "PASS" if not is_fail else "FAIL"
        pdf.cell(0, 8, f"[{status_label}] {t['name']}", new_x="LMARGIN", new_y="NEXT")

        if t.get("details"):
            pdf.set_font("Helvetica", "I", 9)
            pdf.set_text_color(100, 100, 100)
            det = t["details"]
            if len(det) > 120:
                det = det[:117] + "..."
            pdf.cell(0, 6, det, new_x="LMARGIN", new_y="NEXT")

        screenshot = find_screenshot(t["name"])
        if screenshot:
            try:
                pdf.ln(3)
                img_w = 170
                pdf.image(screenshot, x=20, w=img_w)
                pdf.ln(5)
            except Exception as e:
                pdf.set_font("Helvetica", "I", 9)
                pdf.set_text_color(180, 180, 180)
                pdf.cell(0, 6, f"[Screenshot error: {e}]", new_x="LMARGIN", new_y="NEXT")
        else:
            pdf.ln(2)

        pdf.set_draw_color(220, 220, 220)
        pdf.line(10, pdf.get_y(), 200, pdf.get_y())
        pdf.ln(5)


def main():
    with open(REPORT_JSON, "r", encoding="utf-8") as f:
        data = json.load(f)

    tests = data["tests"]
    summary = data["summary"]

    phases = {p: [] for p in PHASE_ORDER}
    for t in tests:
        phase = classify_test(t["name"])
        if phase in phases:
            phases[phase].append(t)

    pdf = TestReportPDF()
    pdf.set_auto_page_break(auto=True, margin=20)

    build_cover(pdf, summary)
    build_summary_table(pdf, tests)

    for phase_name in PHASE_ORDER:
        phase_tests = phases[phase_name]
        if phase_tests:
            build_phase_section(pdf, phase_name, phase_tests)

    pdf.output(OUTPUT_PDF)
    print(f"PDF report generated: {OUTPUT_PDF}")
    print(f"Pages: {pdf.pages_count}")
    print(f"Size: {os.path.getsize(OUTPUT_PDF) / 1024:.0f} KB")


if __name__ == "__main__":
    main()
