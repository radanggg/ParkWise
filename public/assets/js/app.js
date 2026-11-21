const $ = s => document.querySelector(s);
const $$ = s => [...document.querySelectorAll(s)];

document.addEventListener("click", e => {
  const el = e.target.closest("[data-action]");
  if (el) {
    const act = el.dataset.action;
    if (act === "mobile-menu") $(".public-nav")?.classList.toggle("mobile-show");
    if (act === "sidebar-toggle") $("#sidebar")?.classList.toggle("open");
    if (act === "account-menu") $("#accountMenu")?.classList.toggle("hidden");
  }
  if (!e.target.closest(".account-wrap")) {
    $("#accountMenu")?.classList.add("hidden");
  }
});

// Auto-hide PHP flash toast
const toastEl = $("#toast");
if (toastEl && toastEl.classList.contains("show")) {
  setTimeout(() => toastEl.classList.remove("show"), 3200);
}

// Hero background carousel
let slide = 0;
setInterval(() => {
  const slides = $$(".hero-slide"), dots = $$(".hero-pagination i");
  if (!slides.length) return;
  slides[slide].classList.remove("active");
  dots[slide]?.classList.remove("active");
  slide = (slide + 1) % slides.length;
  slides[slide].classList.add("active");
  dots[slide]?.classList.add("active");
}, 5500);