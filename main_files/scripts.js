const menuToggle = document.getElementById("menuToggle");
const navMenu = document.getElementById("navMenu");

menuToggle.addEventListener("click", () => navMenu.classList.toggle("active"));

// --- Sign Out Modal ---
const signoutModal = document.getElementById("signoutModal");
document.getElementById("signoutBtn").addEventListener("click", () => {
    signoutModal.classList.add("show");
    signoutModal.setAttribute("aria-hidden", "false");
});
document.getElementById("cancelBtn").addEventListener("click", closeSignoutModal);
    document.getElementById("signoutYesBtn").addEventListener("click", () => {
    window.location.href = "../welcome-page.html";
});
window.addEventListener("click", e => { if (e.target === signoutModal) closeSignoutModal(); });
function closeSignoutModal() {
    signoutModal.classList.remove("show");
    signoutModal.setAttribute("aria-hidden", "true");
}

// --- Code Modal ---
const CODE_LENGTH = 6;
const codesModal = document.getElementById("codeModal");
const openBtn = document.getElementById("codeOpenModal");
const closeBtn = document.getElementById("closeModal");
const form = document.getElementById("codeForm");
const inputsContainer = document.getElementById("codeInputs");
const message = document.getElementById("message");
const loading = document.getElementById("loading");
const clearBtn = document.getElementById("clearCodeBtn");
const sendBtn = form.querySelector("button[type='submit']");

const codeInputs = [];
for (let i = 0; i < CODE_LENGTH; i++) {
    const input = document.createElement("input");
    input.type = "text";
    input.maxLength = 1;
    input.inputMode = "numeric";
    input.pattern = "[0-9]*";
    input.setAttribute("aria-label", `Digit ${i+1}`);
    inputsContainer.appendChild(input);
    codeInputs.push(input);
}

const getCodeValue = () => codeInputs.map(i => i.value).join("");
const resetForm = () => {
    codeInputs.forEach(i => i.value = "");
    message.innerText = "";
    loading.classList.add("hidden");
    sendBtn.disabled = false;
    codeInputs[0].focus();
};
function openCodeModal() {
    codesModal.classList.add("show");
    resetForm();
    trapFocus(codesModal);
}
function closeCodeModal() {
    codesModal.classList.remove("show");
}
openBtn.addEventListener("click", openCodeModal);
closeBtn.addEventListener("click", closeCodeModal);
window.addEventListener("click", e => { if (e.target === codesModal) closeCodeModal(); });
window.addEventListener("keydown", e => { if (e.key === "Escape") closeCodeModal(); });

// Input behavior
inputsContainer.addEventListener("input", e => {
    const input = e.target;
    if (!/^[0-9]$/.test(input.value)) { input.value = ""; return; }
    const idx = codeInputs.indexOf(input);
    if (idx < CODE_LENGTH - 1) codeInputs[idx + 1].focus();
});
inputsContainer.addEventListener("keydown", e => {
    const input = e.target;
    const idx = codeInputs.indexOf(input);
    if (e.key === "Backspace" && !input.value && idx > 0) {
        codeInputs[idx - 1].focus();
    }
});
codeInputs[0].addEventListener("paste", e => {
    e.preventDefault();
    const pasted = (e.clipboardData || window.clipboardData).getData("text").trim();
    if (/^[0-9]{6}$/.test(pasted)) {
        codeInputs.forEach((input, idx) => input.value = pasted[idx] || "");
        codeInputs[CODE_LENGTH - 1].focus();
    }
});

clearBtn.addEventListener("click", resetForm);

// Form Submit
form.addEventListener("submit", async e => {
    e.preventDefault();
    const code = getCodeValue();

    if (code.length !== CODE_LENGTH) {
        message.className = "error";
        message.innerText = "❌ Please enter all 6 digits.";
        form.classList.add("shake");
        setTimeout(() => form.classList.remove("shake"), 400);
        return;
    }
    message.innerText = "";
    loading.classList.remove("hidden");
    sendBtn.disabled = true;
    try {
        const res = await fetch("verify.php", {
            method: "POST",
            body: new URLSearchParams({ code })
        });
        const data = await res.json();
        loading.classList.add("hidden");

        if (data.success) {
            message.className = "success";
            let countdown = 5;
            message.innerText = `✅ ${data.message} ⏳ Redirecting in ${countdown}s...`;

            const timer = setInterval(() => {
                countdown--;
                if (countdown > 0) {
                    message.innerText = `✅${data.message} ⏳ Redirecting in ${countdown}s...`;
                } else {
                    clearInterval(timer);
                    window.location.href = "chekecha-bongo.php";
                }
            }, 1000);
        } else {
            message.className = "error";
            message.innerText = `❌ ${data.message}`;
            sendBtn.disabled = false;
            form.classList.add("shake");
            setTimeout(() => form.classList.remove("shake"), 400);
        }
    } catch {
        loading.classList.add("hidden");
        message.className = "error";
        message.innerText = "⚠️ Server error, try again later.";
        sendBtn.disabled = false;
    }
});

// Trap focus inside modal
function trapFocus(element) {
    const focusable = element.querySelectorAll("button, [href], input, select, textarea, [tabindex]:not([tabindex='-1'])");
    const firstEl = focusable[0];
    const lastEl = focusable[focusable.length - 1];
    const handleFocus = e => {
        if (e.key !== "Tab") return;
        if (e.shiftKey) {
            if (document.activeElement === firstEl) {
                e.preventDefault();
                lastEl.focus();
            }
        } else {
            if (document.activeElement === lastEl) {
                e.preventDefault();
                firstEl.focus();
            }
        }
    };
    element.addEventListener("keydown", handleFocus);
}