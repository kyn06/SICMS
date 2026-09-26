<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - DARIS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../layout/style.css">
    <style>
        .legal-page {
            width: 100%;
            max-width: 720px;
            background: #fff;
            border: 1px solid #dce7d9;
            border-radius: 8px;
            box-shadow: 0 18px 44px rgba(18, 60, 27, 0.10);
            padding: 36px 40px;
            margin-bottom: 24px;
        }

        .legal-page h1 {
            font-size: 22px;
            color: #123c1b;
            margin-bottom: 6px;
        }

        .legal-page .legal-updated {
            font-size: 12px;
            color: #7b8878;
            margin-bottom: 24px;
        }

        .legal-page h2 {
            font-size: 15px;
            color: #123c1b;
            margin-top: 22px;
            margin-bottom: 8px;
        }

        .legal-page p,
        .legal-page li {
            font-size: 13px;
            color: #333;
            line-height: 1.7;
        }

        .legal-page ul {
            padding-left: 20px;
            margin-bottom: 10px;
        }

        .legal-page li {
            margin-bottom: 4px;
        }

        .legal-back {
            font-size: 13px;
            color: #000;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            margin-bottom: 16px;
        }

        .legal-back:hover {
            text-decoration: underline;
        }

        .legal-footer {
            font-size: 12px;
            color: #7b8878;
            text-align: center;
            margin-top: 8px;
        }

        .legal-footer a {
            color: #1A9D00;
            text-decoration: none;
        }

        .legal-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <img class="seal" src="../../../public/assets/clsulogo.png" alt="CLSU logo">
    <p class="org-name">Office of Student Affairs - Student<br>Discipline and Reformation Unit</p>

    <a class="legal-back" href="create_acc.php">&#8592; Back</a>

    <div class="legal-page">
        <h1>Privacy Policy</h1>
        <p class="legal-updated">Last updated: August 19, 2026</p>

        <h2>1. Introduction</h2>
        <p>The Student Discipline and Reformation Unit (SDRU) of CLSU is committed to protecting the privacy of users of the Discipline and Reformation Information System (DARIS). This Privacy Policy explains how we collect, use, store, and protect your personal information.</p>

        <h2>2. Information We Collect</h2>
        <p>When you use DARIS, we collect the following information:</p>
        <ul>
            <li><strong>Account Information:</strong> Full name, email address, role, and account creation date.</li>
            <li><strong>Complaint Data:</strong> Details submitted in complaint forms, including descriptions, evidence, and related communications.</li>
            <li><strong>System Activity:</strong> Login timestamps, actions performed within the system, and audit log entries.</li>
            <li><strong>Device Information:</strong> IP address and browser user-agent string for security and audit purposes.</li>
        </ul>

        <h2>3. How We Use Your Information</h2>
        <p>We use the collected information for the following purposes:</p>
        <ul>
            <li>To process and manage student complaints and disciplinary cases.</li>
            <li>To authenticate users and manage account access.</li>
            <li>To send notifications related to case updates and system activity.</li>
            <li>To maintain audit logs for security and accountability.</li>
            <li>To generate anonymized reports for institutional planning.</li>
            <li>To comply with institutional policies and regulatory requirements.</li>
        </ul>

        <h2>4. Information Sharing</h2>
        <p>Your personal information is shared only with:</p>
        <ul>
            <li><strong>Authorized SDRU Personnel:</strong> Staff directly involved in processing your complaint or case.</li>
            <li><strong>System Administrators:</strong> Personnel responsible for maintaining and securing the system.</li>
            <li><strong>Institutional Authorities:</strong> When required by CLSU policy, legal obligation, or for the protection of rights and safety.</li>
        </ul>
        <p>We do not sell, rent, or share your personal information with third parties for commercial purposes.</p>

        <h2>5. Data Security</h2>
        <p>We implement industry-standard security measures to protect your data, including:</p>
        <ul>
            <li>Encrypted password storage using bcrypt hashing.</li>
            <li>CSRF protection on all form submissions.</li>
            <li>Role-based access control limiting data visibility to authorized personnel.</li>
            <li>Secure session management with automatic timeout.</li>
            <li>Audit logging of all system activities.</li>
        </ul>

        <h2>6. Data Retention</h2>
        <p>Account and complaint data are retained for the duration of the user's association with CLSU and for a period required by institutional records retention policies. Audit logs are maintained for security and compliance purposes.</p>

        <h2>7. Your Rights</h2>
        <p>As a user of DARIS, you have the right to:</p>
        <ul>
            <li>Access the personal information associated with your account.</li>
            <li>Request corrections to inaccurate information.</li>
            <li>Request deletion of your account, subject to institutional retention requirements.</li>
            <li>Receive notifications about your case status and system updates.</li>
        </ul>

        <h2>8. Cookies and Session Data</h2>
        <p>DARIS uses strictly necessary cookies to maintain your logged-in state and protect against cross-site request forgery. A cookie consent banner is displayed on your first visit to inform you of cookie usage and to record your preference. Your consent choice is stored in a cookie that expires after 12 months.</p>

        <h2>9. Children's Privacy</h2>
        <p>DARIS is designed for use by college students who are 18 years of age or older. We do not knowingly collect personal information from individuals under the age of 18.</p>

        <h2>10. Changes to This Policy</h2>
        <p>We may update this Privacy Policy from time to time. Significant changes will be communicated through the system. Your continued use of DARIS after changes are posted constitutes acceptance of the updated policy.</p>

        <h2>11. Contact Information</h2>
        <p>For questions, concerns, or requests regarding your personal data or this Privacy Policy, please contact the Student Discipline and Reformation Unit through the messaging feature in DARIS or visit the SDRU office during business hours.</p>
    </div>

    <div class="legal-footer">
        <a href="terms.php">Terms of Service</a>
    </div>
</body>

</html>
