// ============================================
// /direschool Manage - main.js
// ============================================

document.addEventListener("DOMContentLoaded", function () {
    // Mobile sidebar toggle
    const toggle = document.getElementById("menuToggle");
    const sidebar = document.querySelector(".sidebar");
    if (toggle && sidebar) {
        toggle.addEventListener("click", function () {
            sidebar.classList.toggle("open");
        });
    }

    // Confirm before any delete link/button
    document.querySelectorAll(".confirm-delete").forEach(function (el) {
        el.addEventListener("click", function (e) {
            if (!confirm("Are you sure you want to delete this? This cannot be undone.")) {
                e.preventDefault();
            }
        });
    });

    // Grade -> Section dependent dropdown, reused from portal idea
    const gradeSelect = document.getElementById("grade");
    const sectionInput = document.getElementById("section");
    if (gradeSelect && sectionInput && sectionInput.tagName === "SELECT") {
        // no-op placeholder: section options are rendered server-side already
    }

    // Auto-calc average in report card editor marks table
    document.querySelectorAll(".marks-table input[type=number]").forEach(function (input) {
        input.addEventListener("input", recalcRowAverage);
    });
});

function recalcRowAverage(e) {
    const row = e.target.closest("tr");
    if (!row) return;
    const inputs = row.querySelectorAll("input[type=number][data-quarter]");
    const avgCell = row.querySelector(".row-average");
    if (!inputs.length || !avgCell) return;
    let sum = 0, count = 0;
    inputs.forEach(function (i) {
        const v = parseFloat(i.value);
        if (!isNaN(v) && v > 0) { sum += v; count++; }
    });
    avgCell.textContent = count ? (sum / count).toFixed(1) : "-";
}

// ============================================================
// Scroll-reveal trigger (shared add-on)
// Elements with class"reveal" fade+rise into place the moment
// they scroll into the viewport, with a small stagger so groups
// of cards/list items don't all pop in at once.
// ============================================================
document.addEventListener("DOMContentLoaded", function () {
    const revealEls = document.querySelectorAll(".reveal");
    if (!revealEls.length) return;

    if (!("IntersectionObserver" in window)) {
        revealEls.forEach(function (el) { el.classList.add("in-view"); });
        return;
    }

    const io = new IntersectionObserver(function (entries, observer) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                const el = entry.target;
                const delay = Number(el.dataset.revealDelay || 0);
                setTimeout(function () { el.classList.add("in-view"); }, delay);
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.12, rootMargin: "0px 0px -40px 0px" });

    let groupIndex = 0;
    revealEls.forEach(function (el) {
        el.dataset.revealDelay = (groupIndex % 8) * 70;
        groupIndex++;
        io.observe(el);
    });
});

// ============================================================
// Dark / Light mode toggle (shared add-on)
// Persists the choice in localStorage; applied instantly on next
// load via the small inline script in <head> (before CSS paints).
// ============================================================
function toggleSiteTheme() {
    const html = document.documentElement;
    const current = html.getAttribute("data-theme") === "dark" ? "dark" : "light";
    const next = current === "dark" ? "light" : "dark";
    html.setAttribute("data-theme", next);
    try { localStorage.setItem("/direschool-theme", next); } catch (e) {}
    document.querySelectorAll(".theme-toggle-icon").forEach(function (el) {
        el.innerHTML = next === "dark" ? "<svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\"><circle cx=\"12\" cy=\"12\" r=\"4.2\"/><path d=\"M12 2.5v2.4M12 19.1v2.4M4.9 4.9l1.7 1.7M17.4 17.4l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.9 19.1l1.7-1.7M17.4 6.6l1.7-1.7\"/></svg>" : "<svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5Z\"/></svg>";
    });
}
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".theme-toggle-icon").forEach(function (el) {
        el.innerHTML = document.documentElement.getAttribute("data-theme") === "dark" ? "<svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\"><circle cx=\"12\" cy=\"12\" r=\"4.2\"/><path d=\"M12 2.5v2.4M12 19.1v2.4M4.9 4.9l1.7 1.7M17.4 17.4l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.9 19.1l1.7-1.7M17.4 6.6l1.7-1.7\"/></svg>" : "<svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"1.8\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M20 14.5A8.5 8.5 0 1 1 9.5 4a7 7 0 0 0 10.5 10.5Z\"/></svg>";
    });
});

