
<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CMS | Administrator Login</title>
    
    <link rel="icon" type="image/png" href="/public/img/logo.png">
<link rel="apple-touch-icon" href="/public/img/logo.png">

<style>
:root {
    --navy: #062447;
    --navy2: #0b315c;
    --red: #f01425;
    --blue: #1765bf;
    --line: #cbd7e6;
    --text: #0a2f5c;
}

* {
    box-sizing: border-box;
}

html, body {
    margin: 0;
    min-height: 100%;
    width: 100%;
    font-family: Arial, Helvetica, sans-serif;
}

body {
    min-height: 100vh;
    color: var(--text);
    background: #f4f7fb;
}

.login-page {
    min-height: 100vh;
    width: 100%;
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(480px, 0.9fr);
    background: #fff;
}

/* LEFT BRANDING PANEL */

.branding-panel {
    position: relative;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 100vh;
    padding: 55px 8%;
    color: #fff;
    background:
        linear-gradient(
            135deg,
            rgba(4, 27, 57, 0.90),
            rgba(8, 54, 99, 0.78)
        ),
        url("assets/login-branding-panel.png") center center / cover no-repeat;
    overflow: hidden;
}

.branding-panel::before {
    content: "";
    position: absolute;
    width: 440px;
    height: 440px;
    right: -190px;
    top: 10%;
    border: 65px solid rgba(255,255,255,0.07);
    border-radius: 50%;
    pointer-events: none;
}

.branding-panel::after {
    content: "";
    position: absolute;
    width: 70%;
    height: 6px;
    bottom: 18%;
    left: 0;
    background: var(--red);
    transform: skewY(-24deg);
    opacity: 0.9;
    pointer-events: none;
}

.branding-top,
.branding-main,
.branding-bottom {
    position: relative;
    z-index: 1;
}

.branding-logo {
    width: 92px;
    height: 82px;
    object-fit: contain;
    display: block;
    margin-bottom: 28px;
    padding: 8px;
    border-radius: 12px;
    background: rgba(255,255,255,0.96);
}

.branding-company {
    max-width: 560px;
    margin: 0;
    font-size: clamp(32px, 3.4vw, 53px);
    line-height: 1.1;
    font-weight: 900;
    letter-spacing: -1.6px;
    text-transform: uppercase;
}

.branding-company span {
    display: block;
    color: #ff3b49;
}

.branding-line {
    width: 80px;
    height: 4px;
    background: var(--red);
    margin: 27px 0;
}

.branding-description {
    max-width: 470px;
    margin: 0;
    color: rgba(255,255,255,0.86);
    font-size: 17px;
    line-height: 1.85;
}

.branding-features {
    display: grid;
    gap: 17px;
    margin-top: 38px;
}

.branding-feature {
    display: flex;
    align-items: center;
    gap: 13px;
    color: #fff;
    font-size: 14px;
    font-weight: 600;
}

.feature-check {
    width: 27px;
    height: 27px;
    flex: 0 0 27px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(240,20,37,0.95);
    border-radius: 50%;
    font-size: 15px;
}

.branding-bottom {
    margin-top: 45px;
    color: rgba(255,255,255,0.72);
    font-size: 11px;
    letter-spacing: 1px;
    line-height: 1.8;
}

/* RIGHT LOGIN PANEL */

.login-side {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 0;
    padding: 45px clamp(25px, 5vw, 75px);
    background: linear-gradient(135deg, #fff, #f7f9fc);
}

.login-content {
    width: 100%;
    max-width: 470px;
}

.mobile-logo {
    display: none;
    width: 85px;
    height: 75px;
    object-fit: contain;
    margin: 0 auto 16px;
}

.login-top-label {
    margin: 0 0 12px;
    color: var(--red);
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.login-heading {
    margin: 0;
    color: #092b55;
    font-size: clamp(25px, 2.3vw, 35px);
    line-height: 1.2;
    font-weight: 900;
    letter-spacing: -0.8px;
}

.login-help {
    margin: 12px 0 28px;
    color: #718096;
    font-size: 15px;
    line-height: 1.7;
}

.error-message {
    display: none;
    margin: 0 0 20px;
    padding: 13px 15px;
    border: 1px solid #f0b8bd;
    border-radius: 9px;
    background: #fff1f2;
    color: #b40e18;
    font-size: 13px;
    font-weight: 700;
    line-height: 1.5;
}

.login-form {
    text-align: left;
}

.field {
    position: relative;
    margin-bottom: 20px;
}

.field label {
    display: block;
    margin-bottom: 9px;
    color: #193b61;
    font-size: 13px;
    font-weight: 700;
}

.field input {
    display: block;
    width: 100%;
    height: 57px;
    padding: 0 54px 0 49px;
    border: 1px solid var(--line);
    border-radius: 10px;
    outline: none;
    background: #fff;
    color: #14385f;
    font-family: inherit;
    font-size: 15px;
    transition: border-color .2s, box-shadow .2s;
}

.field input::placeholder {
    color: #8a98ab;
}

.field input:focus {
    border-color: #2d6fc0;
    box-shadow: 0 0 0 4px rgba(45,111,192,.09);
}

.field-icon {
    position: absolute;
    left: 16px;
    top: 41px;
    width: 21px;
    height: 21px;
    color: #426080;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.field-icon svg,
.password-toggle svg,
.login-button svg {
    width: 22px;
    height: 22px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
}

.password-toggle {
    position: absolute;
    right: 8px;
    top: 34px;
    width: 40px;
    height: 40px;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: #365274;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.password-toggle:hover {
    background: #f1f5fa;
}

.options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin: 4px 0 25px;
    color: #526982;
    font-size: 13px;
}

.remember {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    user-select: none;
}

.remember input {
    width: 17px;
    height: 17px;
    margin: 0;
    accent-color: var(--blue);
    cursor: pointer;
}

.forgot {
    color: #075dcc;
    text-decoration: none;
    font-weight: 700;
}

.forgot:hover {
    text-decoration: underline;
}

.login-button {
    width: 100%;
    height: 57px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    border: 0;
    border-radius: 10px;
    background: linear-gradient(90deg, #ec111f, #f20e20);
    color: #fff;
    font-size: 16px;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 9px 20px rgba(237,20,34,.17);
    transition: transform .2s, box-shadow .2s;
}

.login-button:hover {
    transform: translateY(-1px);
    box-shadow: 0 13px 24px rgba(237,20,34,.24);
}

.login-button:disabled {
    opacity: .75;
    cursor: not-allowed;
    transform: none;
}

.or {
    display: flex;
    align-items: center;
    gap: 16px;
    margin: 27px 0;
    color: #8a98ab;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
}

.or::before,
.or::after {
    content: "";
    flex: 1;
    height: 1px;
    background: #e0e6ee;
}

.access-note {
    display: flex;
    align-items: flex-start;
    gap: 13px;
    padding: 15px;
    border: 1px solid #dce5ef;
    border-radius: 10px;
    background: #f4f7fb;
    color: #12385f;
}

.access-icon {
    flex: 0 0 32px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.access-icon svg {
    width: 29px;
    height: 29px;
    stroke: #0b315c;
    fill: none;
    stroke-width: 1.8;
}

.access-note strong {
    display: block;
    margin: 1px 0 5px;
    font-size: 13px;
}

.access-note span {
    display: block;
    color: #697d95;
    font-size: 11px;
    line-height: 1.6;
}

.footer {
    margin-top: 25px;
    color: #95a1b1;
    text-align: center;
    font-size: 10px;
    letter-spacing: .5px;
    line-height: 1.7;
}

/* LOGIN LOADING OVERLAY */

.login-overlay {
    position: fixed;
    inset: 0;
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
    background: rgba(2,18,39,.68);
    backdrop-filter: blur(5px);
}

.login-dialog {
    width: min(380px, 100%);
    padding: 30px 25px;
    border-top: 6px solid var(--red);
    border-radius: 18px;
    background: #fff;
    text-align: center;
    box-shadow: 0 25px 70px rgba(0,0,0,.3);
}

.spinner {
    width: 48px;
    height: 48px;
    margin: 0 auto 16px;
    border: 4px solid #e8edf3;
    border-top-color: var(--red);
    border-radius: 50%;
    animation: spin .75s linear infinite;
}

.login-dialog h3 {
    margin: 0;
    color: #0a2f5c;
    font-size: 20px;
}

.login-dialog p {
    margin: 8px 0 0;
    color: #697b91;
    font-size: 13px;
    line-height: 1.5;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* TABLETS AND MOBILE */

@media (max-width: 1000px) {
    .login-page {
        grid-template-columns: minmax(0, .85fr) minmax(0, 1fr);
    }

    .branding-panel {
        padding: 45px 7%;
    }

    .login-side {
        padding: 35px 28px;
    }

    .branding-description {
        font-size: 15px;
    }

    .branding-company {
        font-size: 34px;
    }
}

@media (max-width: 760px) {
    .login-page {
        grid-template-columns: 1fr;
    }

    .branding-panel {
        min-height: 270px;
        padding: 28px 25px;
        justify-content: center;
    }

    .branding-logo {
        width: 58px;
        height: 52px;
        margin-bottom: 15px;
    }

    .branding-company {
        max-width: 500px;
        font-size: clamp(25px, 6vw, 36px);
        letter-spacing: -.7px;
    }

    .branding-line {
        margin: 15px 0;
        width: 55px;
    }

    .branding-description {
        max-width: 520px;
        font-size: 13px;
        line-height: 1.6;
    }

    .branding-features {
        display: none;
    }

    .branding-bottom {
        margin-top: 20px;
        font-size: 9px;
    }

    .branding-panel::after {
        bottom: 8%;
        opacity: .45;
    }

    .login-side {
        padding: 35px 22px 40px;
    }

    .login-content {
        max-width: 480px;
    }

    .login-heading {
        font-size: 29px;
    }
}

@media (max-width: 420px) {
    .branding-panel {
        min-height: 235px;
        padding: 23px 20px;
    }

    .branding-company {
        font-size: 25px;
    }

    .branding-description {
        font-size: 12px;
    }

    .login-side {
        padding: 28px 18px 32px;
    }

    .login-heading {
        font-size: 25px;
    }

    .login-help {
        font-size: 13px;
    }

    .options {
        font-size: 12px;
    }
}

@media (prefers-reduced-motion: reduce) {
    *, *::before, *::after {
        animation-duration: .01ms !important;
        transition-duration: .01ms !important;
    }
}
</style>
</head>

<body>

<div class="login-page">

    <!-- LEFT: BRANDING IMAGE AND COMPANY INFORMATION -->
    <aside class="branding-panel" aria-label="Calibration Management System branding">

        <div class="branding-top">
            <img
                class="branding-logo"
                src="assets/cms-login-logo.png"
                alt="Calibration Management System logo"
            >
        </div>

        <div class="branding-main">
            <h1 class="branding-company">
                Calibration
                <span>Management</span>
                System
            </h1>

            <div class="branding-line"></div>

            <p class="branding-description">
                A professional platform for managing calibration records,
                equipment readings, inspection activities and certificates.
            </p>

            <div class="branding-features">
                <div class="branding-feature">
                    <span class="feature-check" aria-hidden="true">✓</span>
                    <span>Equipment Calibration Management</span>
                </div>

                <div class="branding-feature">
                    <span class="feature-check" aria-hidden="true">✓</span>
                    <span>Calibration Records and Certificates</span>
                </div>

                <div class="branding-feature">
                    <span class="feature-check" aria-hidden="true">✓</span>
                    <span>Secure Administrator Access</span>
                </div>
            </div>
        </div>

        <div class="branding-bottom">
            CALIBRATION • TESTING • INSPECTION • CERTIFICATION
        </div>

    </aside>

    <!-- RIGHT: LOGIN FORM -->
    <main class="login-side">
        <div class="login-content">

            <img
                class="mobile-logo"
                src="assets/cms-login-logo.png"
                alt="CMS logo"
            >

            <p class="login-top-label">Welcome Back</p>

            <h2 class="login-heading">Administrator Login</h2>

            <p class="login-help">
                Sign in to your account to continue to your dashboard.
            </p>

            <div
                id="errorMessage"
                class="error-message"
                role="alert"
                aria-live="polite"
            ></div>

            <form
                id="loginForm"
                class="login-form"
                method="POST"
                action="../auth/login.php"
            >

                <div class="field">
                    <label for="username">Username</label>

                    <span class="field-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M20 21a8 8 0 0 0-16 0"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </span>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        autocomplete="username"
                        placeholder="Enter your username"
                        required
                    >
                </div>

                <div class="field">
                    <label for="password">Password</label>

                    <span class="field-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <rect x="5" y="10" width="14" height="10" rx="2"></rect>
                            <path d="M8 10V7a4 4 0 0 1 8 0v3"></path>
                        </svg>
                    </span>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        placeholder="Enter your password"
                        required
                    >

                    <button
                        type="button"
                        class="password-toggle"
                        id="togglePassword"
                        aria-label="Show password"
                    >
                        <svg id="eyeIcon" viewBox="0 0 24 24">
                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path>
                            <circle cx="12" cy="12" r="2.5"></circle>
                        </svg>
                    </button>
                </div>

                <div class="options">
                    <label class="remember">
                        <input type="checkbox" id="rememberUsername">
                        <span>Remember username</span>
                    </label>

                    <a href="#" class="forgot" id="forgotPassword">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" class="login-button" id="loginButton">
                    <svg viewBox="0 0 24 24">
                        <path d="M10 17l5-5-5-5"></path>
                        <path d="M15 12H3"></path>
                        <path d="M21 19V5a2 2 0 0 0-2-2h-6"></path>
                    </svg>
                    <span>Sign In to Dashboard</span>
                </button>

            </form>

            <div class="or"><span>SECURE ACCESS</span></div>

            <div class="access-note">
                <div class="access-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 3 20 6v5c0 5.2-3.4 8.7-8 10-4.6-1.3-8-4.8-8-10V6l8-3Z"></path>
                        <path d="m8.5 12 2.2 2.2 4.8-5"></path>
                    </svg>
                </div>

                <div>
                    <strong>Authorized Access Only</strong>
                    <span>
                        This system is for authorized personnel only.
                        Unauthorized access is prohibited.
                    </span>
                </div>
            </div>

            <div class="footer">
                Calibration Management System<br>
                © <?php echo date('Y'); ?> • All rights reserved
            </div>

        </div>
    </main>

</div>

<!-- AUTHENTICATION LOADING OVERLAY -->
<div class="login-overlay" id="loginOverlay" aria-hidden="true">
    <div class="login-dialog" role="status" aria-live="polite">
        <div class="spinner"></div>
        <h3>Authenticating...</h3>
        <p>Please wait while the system verifies your login details.</p>
    </div>
</div>

<script>
(function () {
    const params = new URLSearchParams(window.location.search);
    const error = params.get('error');
    const errorBox = document.getElementById('errorMessage');

    const messages = {
        invalid: 'Invalid username or password.',
        disabled: 'This user account has been disabled.',
        required: 'Please enter your username and password.',
        database: 'Unable to connect to the database.'
    };

    if (error) {
        errorBox.textContent =
            messages[error] || 'Login failed. Please try again.';
        errorBox.style.display = 'block';
    }

    const username = document.getElementById('username');
    const remember = document.getElementById('rememberUsername');
    const password = document.getElementById('password');
    const toggle = document.getElementById('togglePassword');

    // Restore a previously remembered username.
    try {
        const saved = localStorage.getItem('cms_remembered_username');

        if (saved) {
            username.value = saved;
            remember.checked = true;
        }
    } catch (e) {
        // Login still works if browser storage is unavailable.
    }

    // Show or hide the password.
    toggle.addEventListener('click', function () {
        const show = password.type === 'password';

        password.type = show ? 'text' : 'password';
        this.setAttribute(
            'aria-label',
            show ? 'Hide password' : 'Show password'
        );

        document.getElementById('eyeIcon').innerHTML = show
            ? '<path d="M3 3l18 18"></path>' +
              '<path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>' +
              '<path d="M9.9 5.1A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-3.1 3.9"></path>' +
              '<path d="M6.1 6.1C3.6 8 2 12 2 12s3.5 7 10 7c1 0 2-.2 2.9-.5"></path>'
            : '<path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"></path>' +
              '<circle cx="12" cy="12" r="2.5"></circle>';
    });

    // Keep the existing password-reset guidance.
    document.getElementById('forgotPassword').addEventListener('click', function (e) {
        e.preventDefault();
        alert('Please contact the CMS administrator to reset your password.');
    });

    // Preserve form field names, POST method and authentication endpoint.
    document.getElementById('loginForm').addEventListener('submit', function () {
        try {
            if (remember.checked) {
                localStorage.setItem(
                    'cms_remembered_username',
                    username.value.trim()
                );
            } else {
                localStorage.removeItem('cms_remembered_username');
            }
        } catch (e) {
            // Continue login if browser storage is unavailable.
        }

        document.getElementById('loginOverlay').style.display = 'flex';
        document.getElementById('loginOverlay').setAttribute('aria-hidden', 'false');

        const button = document.getElementById('loginButton');
        button.disabled = true;
        button.querySelector('span').textContent = 'Authenticating...';
    });
})();
</script>

</body>
</html>
