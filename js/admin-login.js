// =====================================
// BloodMate Admin Login JavaScript
// =====================================

// ── HARDCODED FALLBACK CREDENTIALS ──
// Used when the PHP API is unreachable (e.g. no server running).
// Change these to match your real admin credentials.
const FALLBACK_USERNAME = "admin";
const FALLBACK_PASSWORD = "admin123";

document.addEventListener("DOMContentLoaded", function () {

    const loginForm = document.getElementById("adminLoginForm");

    if (loginForm) {
        loginForm.addEventListener("submit", handleAdminLogin);

        const inputs = loginForm.querySelectorAll("input");
        inputs.forEach(input => {
            input.addEventListener("blur", validateField);
            input.addEventListener("input", () => clearFieldError(input));
        });
    }

});

// =====================================
// Handle Login
// =====================================

async function handleAdminLogin(event) {

    event.preventDefault();

    const form = event.target;

    if (!validateForm(form)) return;

    const submitButton = form.querySelector("button[type='submit']");
    const originalText = submitButton.innerHTML;

    showLoading(submitButton);

    const username = document.getElementById("username").value.trim();
    const password = document.getElementById("password").value.trim();

    // ── Try PHP API first ──
    let usedFallback = false;

    try {

        const formData = new FormData(form);

        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 4000); // 4s timeout

        const response = await fetch("../api/admin-login.php", {
            method: "POST",
            body: formData,
            signal: controller.signal
        });

        clearTimeout(timeout);

        if (!response.ok) throw new Error("Server error");

        const result = await response.json();

        if (result.success) {
            localStorage.setItem("admin_token", result.token);
            localStorage.setItem("admin_user", JSON.stringify(result.user));
            onLoginSuccess();
            return;
        } else {
            // API responded but credentials were wrong
            hideLoading(submitButton, originalText);
            showLoginError(result.message || "Invalid credentials.");
            return;
        }

    } catch (err) {

        // API unreachable (no PHP server) — fall through to local check
        if (err.name !== "AbortError" && err.message !== "Failed to fetch" && err.message !== "Server error") {
            console.warn("API error:", err.message);
        }
        usedFallback = true;

    }

    // ── Local fallback check ──
    if (usedFallback) {

        if (username === FALLBACK_USERNAME && password === FALLBACK_PASSWORD) {

            // Store a mock session
            localStorage.setItem("admin_token", "local_fallback_token_" + Date.now());
            localStorage.setItem("admin_user", JSON.stringify({
                id: 1,
                username: FALLBACK_USERNAME,
                role: "admin"
            }));

            onLoginSuccess();

        } else {

            hideLoading(submitButton, originalText);
            showLoginError("Invalid username or password.");

        }

    }

}

// =====================================
// On Successful Login
// =====================================

function onLoginSuccess() {
    showNotification("Login successful! Redirecting...", "success");
    setTimeout(() => {
        window.location.href = "dashboard.html";
    }, 900);
}

// =====================================
// Validate Form
// =====================================

function validateForm(form) {

    let valid = true;

    form.querySelectorAll("[required]").forEach(field => {
        clearFieldError(field);
        if (field.value.trim() === "") {
            showFieldError(field, "This field is required");
            valid = false;
        }
    });

    return valid;
}

// =====================================
// Validate Individual Field
// =====================================

function validateField(event) {

    const field = event.target;
    clearFieldError(field);

    if (field.hasAttribute("required") && field.value.trim() === "") {
        showFieldError(field, "This field is required");
        return false;
    }

    return true;
}

// =====================================
// Field Error Styling
// =====================================

function showFieldError(field, message) {
    field.style.border = "1.5px solid var(--blood-bright, #E8192C)";
    field.style.boxShadow = "0 0 0 3px rgba(232,25,44,0.15)";
}

function clearFieldError(field) {
    field.style.border = "";
    field.style.boxShadow = "";
}

// =====================================
// Show Login Error (in UI error box)
// =====================================

function showLoginError(message) {
    // Works with the new login.html error box
    const box = document.getElementById("errorBox");
    const msg = document.getElementById("errorMsg");

    if (box && msg) {
        msg.textContent = message;
        box.classList.remove("hidden");
        box.style.display = "flex";
    } else {
        // Fallback: toast notification
        showNotification(message, "error");
    }
}

// =====================================
// Loading Button
// =====================================

function showLoading(button) {
    button.disabled = true;
    button.innerHTML = "<i class='fas fa-spinner fa-spin'></i> Signing in...";
}

function hideLoading(button, originalText) {
    button.disabled = false;
    button.innerHTML = originalText;
}

// =====================================
// Toast Notification
// =====================================

function showNotification(message, type) {

    const old = document.querySelector(".bm-notification");
    if (old) old.remove();

    const n = document.createElement("div");
    n.className = "bm-notification";
    n.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'circle-exclamation'}"></i> ${message}`;

    Object.assign(n.style, {
        position:     "fixed",
        top:          "1.5rem",
        right:        "1.5rem",
        padding:      "0.85rem 1.3rem",
        background:   type === "success" ? "#16a34a" : "#C0152A",
        color:        "#fff",
        fontFamily:   "Inter, sans-serif",
        fontSize:     "0.84rem",
        fontWeight:   "500",
        borderRadius: "4px",
        zIndex:       "9999",
        display:      "flex",
        alignItems:   "center",
        gap:          "0.5rem",
        boxShadow:    "0 8px 24px rgba(0,0,0,0.35)",
        animation:    "slideIn 0.25s ease"
    });

    // Inject keyframe once
    if (!document.getElementById("bm-notif-style")) {
        const s = document.createElement("style");
        s.id = "bm-notif-style";
        s.textContent = `@keyframes slideIn { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }`;
        document.head.appendChild(s);
    }

    document.body.appendChild(n);
    setTimeout(() => n.remove(), 3000);
}

// =====================================
// Toggle Password Visibility
// =====================================

function togglePassword() {
    const pw  = document.getElementById("password");
    const ico = document.getElementById("eyeIcon") || document.querySelector(".toggle-password i, .toggle-pw i");
    if (!pw) return;
    const show = pw.type === "password";
    pw.type = show ? "text" : "password";
    if (ico) ico.className = show ? "fas fa-eye-slash" : "fas fa-eye";
}

// =====================================
// Session Check (called from dashboard)
// =====================================

function checkAdminLogin() {
    const token = localStorage.getItem("admin_token");
    if (!token) window.location.href = "login.html";
}

// =====================================
// Logout
// =====================================

function logout() {
    localStorage.removeItem("admin_token");
    localStorage.removeItem("admin_user");
    window.location.href = "login.html";
}