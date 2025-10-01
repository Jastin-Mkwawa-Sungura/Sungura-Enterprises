const openModal = document.getElementById("openModal");
const modal = document.getElementById("loginModal");
const closeModalBtns = [document.getElementById("closeModal1"), document.getElementById("closeModal2")];
const loginForm = document.getElementById("loginForm");
const signupForm = document.getElementById("signupForm");
const loginTab = document.getElementById("loginTab");
const signupTab = document.getElementById("signupTab");

// Open modal
openModal.addEventListener("click", () => {
    modal.classList.add("show");
    modal.setAttribute("aria-hidden", "false");
});

// Close modal
closeModalBtns.forEach(btn => {
    btn.addEventListener("click", () => {
        modal.classList.remove("show");
        modal.setAttribute("aria-hidden", "true");
    });
});

// Close modal when clicking outside content
window.addEventListener("click", (e) => {
    if (e.target === modal) {
        modal.classList.remove("show");
        modal.setAttribute("aria-hidden", "true");
    }
});

// Close modal on ESC key
window.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && modal.classList.contains("show")) {
        modal.classList.remove("show");
        modal.setAttribute("aria-hidden", "true");
    }
});

// Switch tabs
loginTab.addEventListener("click", () => {
    loginForm.classList.add("active");
    signupForm.classList.remove("active");
    loginTab.classList.add("active");
    signupTab.classList.remove("active");
});

signupTab.addEventListener("click", () => {
    signupForm.classList.add("active");
    loginForm.classList.remove("active");
    signupTab.classList.add("active");
    loginTab.classList.remove("active");
});