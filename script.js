function initCarousel(carousel) {
  const track = carousel.querySelector("[data-carousel-track]");
  const slides = Array.from(track?.querySelectorAll(".slide") || []);
  const indicators = Array.from(
    carousel.querySelectorAll(".indicators [data-slide-to]"),
  );
  const status = carousel.querySelector("[data-slide-status]");
  const prev = carousel.querySelector('[data-action="prev"]');
  const next = carousel.querySelector('[data-action="next"]');

  if (!track || slides.length === 0) return;

  let activeIndex = Math.max(
    0,
    slides.findIndex((slide) => slide.classList.contains("is-active")),
  );

  function render() {
    slides.forEach((slide, idx) => {
      const isActive = idx === activeIndex;
      slide.classList.toggle("is-active", isActive);
      slide.setAttribute("aria-hidden", isActive ? "false" : "true");
    });

    indicators.forEach((btn, idx) => {
      const isActive = idx === activeIndex;
      btn.classList.toggle("is-active", isActive);
      btn.setAttribute("aria-current", isActive ? "true" : "false");
    });

    if (status) {
      status.textContent = `Slide ${activeIndex + 1} of ${slides.length}`;
    }
  }

  function setActive(nextIndex) {
    const clamped = (nextIndex + slides.length) % slides.length;
    if (clamped === activeIndex) return;
    activeIndex = clamped;
    render();
  }

  prev?.addEventListener("click", () => setActive(activeIndex - 1));
  next?.addEventListener("click", () => setActive(activeIndex + 1));

  indicators.forEach((btn) => {
    btn.addEventListener("click", () => {
      const idx = Number(btn.getAttribute("data-slide-to"));
      if (!Number.isFinite(idx)) return;
      setActive(idx);
    });
  });

  carousel.addEventListener("keydown", (event) => {
    if (event.key === "ArrowLeft") {
      event.preventDefault();
      setActive(activeIndex - 1);
    }
    if (event.key === "ArrowRight") {
      event.preventDefault();
      setActive(activeIndex + 1);
    }
  });

  let timer = null;

  function start() {
    stop();
    timer = window.setInterval(() => setActive(activeIndex + 1), 6500);
  }

  function stop() {
    if (timer) window.clearInterval(timer);
    timer = null;
  }

  carousel.addEventListener("mouseenter", stop);
  carousel.addEventListener("mouseleave", start);
  carousel.addEventListener("focusin", stop);
  carousel.addEventListener("focusout", start);

  render();
  start();
}

document.addEventListener("DOMContentLoaded", () => {
  document
    .querySelectorAll("[data-carousel]")
    .forEach((carousel) => initCarousel(carousel));
});

