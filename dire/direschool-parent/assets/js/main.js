// ============================================
// Dire Dawa Schools Portal - main.js
// Small helper scripts (no framework, plain JS)
// ============================================

// Print the current page (used on report card / result pages)
function printPage() {
    window.print();
}

// Simple client-side check so empty search forms don't get submitted
function validateSearchForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    form.addEventListener("submit", function (e) {
        const inputs = form.querySelectorAll("input[required]");
        for (const input of inputs) {
            if (!input.value.trim()) {
                e.preventDefault();
                alert("Please fill in: " + (input.previousElementSibling ? input.previousElementSibling.textContent : input.name));
                input.focus();
                return;
            }
        }
    });
}

// Grade -> Section dependent dropdown (used on school.php)
function setupGradeSectionPicker(gradeSelectId, sectionSelectId, sectionsByGrade) {
    const gradeSelect = document.getElementById(gradeSelectId);
    const sectionSelect = document.getElementById(sectionSelectId);
    if (!gradeSelect || !sectionSelect) return;

    function refreshSections() {
        const grade = gradeSelect.value;
        sectionSelect.innerHTML = '<option value="">-- Select Section --</option>';
        (sectionsByGrade[grade] || []).forEach(function (sec) {
            const opt = document.createElement("option");
            opt.value = sec;
            opt.textContent = sec;
            sectionSelect.appendChild(opt);
        });
    }

    gradeSelect.addEventListener("change", refreshSections);
    if (gradeSelect.value) refreshSections();
}

document.addEventListener("DOMContentLoaded", function () {
    // Fade in cards slightly for a nicer feel
    document.querySelectorAll(".card, .list-item").forEach(function (el) {
        el.style.opacity = "0";
        el.style.transition = "opacity 0.25s ease";
        setTimeout(function () { el.style.opacity = "1"; }, 30);
    });
});

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

