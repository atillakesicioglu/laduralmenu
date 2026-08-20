(() => {
  "use strict";

  const searchToggle = document.getElementById("searchToggle");
  const searchPanel = document.getElementById("searchPanel");
  const searchInput = document.getElementById("menuSearch");
  const clearSearch = document.getElementById("clearSearch");
  const normalContent = document.getElementById("normalContent");
  const resultsBlock = document.getElementById("resultsBlock");
  const resultList = document.getElementById("resultList");
  const resultSummary = document.getElementById("resultSummary");
  const emptyResult = document.getElementById("emptyResult");
  const toTop = document.getElementById("toTop");
  const stickyShell = document.querySelector(".sticky-shell");
  const chipRail = document.querySelector(".chip-rail");
  const chips = Array.from(document.querySelectorAll(".chip"));
  const sections = Array.from(document.querySelectorAll(".menu-section"));
  const productRows = Array.from(document.querySelectorAll(".menu-item"));
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  const pageLoader = document.getElementById("pageLoader");
  if (pageLoader) {
    const minMs = reducedMotion ? 350 : 260; // hızlı başlat, sonra yavaş zoom
    const zoomMs = reducedMotion ? 0 : 1500; // logo yavaşça büyüsün
    const fadeMs = reducedMotion ? 280 : 420; // zoom bitti, yumuşak fade
    const start = performance.now();
    let stopped = false;

    const stop = () => {
      if (stopped) return;
      stopped = true;
      const elapsed = performance.now() - start;
      const delay = Math.max(0, minMs - elapsed);

      window.setTimeout(() => {
        if (!pageLoader.isConnected) return;

        if (reducedMotion) {
          document.body.classList.remove("is-loading");
          document.body.classList.add("is-ready");
          pageLoader.remove();
          return;
        }

        pageLoader.classList.add("is-zooming");
        window.setTimeout(() => {
          if (!pageLoader.isConnected) return;
          pageLoader.classList.add("is-fading");
          document.body.classList.remove("is-loading");
          document.body.classList.add("is-ready");
          window.setTimeout(() => {
            if (pageLoader.isConnected) pageLoader.remove();
          }, fadeMs);
        }, zoomMs);
      }, delay);
    };

    document.addEventListener("DOMContentLoaded", stop, { once: true });
    window.addEventListener("load", stop, { once: true });
  }

  let activeCategory = chips[0] ? chips[0].dataset.target : "";
  let scrollSpyLockedUntil = 0;
  let scrollTicking = false;

  const normalize = (value) => String(value || "").toLocaleLowerCase("tr-TR");

  function setSearchOpen(open) {
    searchPanel.classList.toggle("open", open);
    searchPanel.setAttribute("aria-hidden", String(!open));
    searchToggle.setAttribute("aria-expanded", String(open));
    searchToggle.setAttribute("aria-label", open ? "Aramayı kapat" : "Menüde ara");
    searchToggle.classList.toggle("open", open);
    searchInput.tabIndex = open ? 0 : -1;
    if (open) requestAnimationFrame(() => searchInput.focus());
  }

  function leaveSearch() {
    normalContent.hidden = false;
    resultsBlock.hidden = true;
    resultList.replaceChildren();
    clearSearch.hidden = true;
  }

  function runSearch() {
    const rawQuery = searchInput.value.trim();
    const query = normalize(rawQuery);
    clearSearch.hidden = query.length === 0;

    if (!query) {
      leaveSearch();
      return;
    }

    const matches = productRows.filter((row) => {
      return normalize(row.dataset.name).includes(query)
        || normalize(row.dataset.description).includes(query)
        || normalize(row.dataset.category).includes(query);
    });

    resultList.replaceChildren();
    matches.forEach((row) => {
      const wrapper = document.createElement("div");
      wrapper.className = "search-result";
      const category = document.createElement("div");
      category.className = "result-category";
      category.textContent = row.dataset.category;
      const clone = row.cloneNode(true);
      clone.removeAttribute("data-name");
      clone.removeAttribute("data-description");
      clone.removeAttribute("data-category");
      wrapper.append(category, clone);
      resultList.append(wrapper);
    });

    resultSummary.textContent = "\u201c" + rawQuery + "\u201d için " + matches.length + " sonuç";
    emptyResult.hidden = matches.length !== 0;
    normalContent.hidden = true;
    resultsBlock.hidden = false;
  }

  function centerChip(chip) {
    if (!chip || !chipRail) return;
    const left = chip.offsetLeft - (chipRail.clientWidth - chip.offsetWidth) / 2;
    chipRail.scrollTo({ left: Math.max(0, left), behavior: reducedMotion ? "auto" : "smooth" });
  }

  function activateChip(id, forceCenter = false) {
    if (!id) return;
    const changed = activeCategory !== id;
    activeCategory = id;
    chips.forEach((chip) => {
      const active = chip.dataset.target === id;
      chip.classList.toggle("active", active);
      if (active) chip.setAttribute("aria-current", "true");
      else chip.removeAttribute("aria-current");
    });
    const active = chips.find((chip) => chip.dataset.target === id);
    if (active && (changed || forceCenter)) centerChip(active);
  }

  function updateActiveCategory() {
    if (performance.now() < scrollSpyLockedUntil || normalContent.hidden) return;
    const marker = (stickyShell ? stickyShell.getBoundingClientRect().height : 0) + 18;
    let current = sections[0];
    for (const section of sections) {
      if (section.getBoundingClientRect().top <= marker) current = section;
      else break;
    }
    if (current) activateChip(current.dataset.section);
  }

  function handleScroll() {
    if (scrollTicking) return;
    scrollTicking = true;
    requestAnimationFrame(() => {
      updateToTop();
      updateActiveCategory();
      scrollTicking = false;
    });
  }

  searchToggle.addEventListener("click", () => {
    const opening = !searchPanel.classList.contains("open");
    setSearchOpen(opening);
    if (!opening) {
      searchInput.value = "";
      leaveSearch();
    }
  });

  searchInput.addEventListener("input", runSearch);

  clearSearch.addEventListener("click", () => {
    searchInput.value = "";
    leaveSearch();
    searchInput.focus();
  });

  chips.forEach((chip) => chip.addEventListener("click", () => {
    searchInput.value = "";
    leaveSearch();
    const id = chip.dataset.target;
    activateChip(id, true);
    const section = document.getElementById("sec-" + id);
    if (section) {
      const lockMs = reducedMotion ? 0 : 900;
      scrollSpyLockedUntil = performance.now() + lockMs;
      section.scrollIntoView({ behavior: reducedMotion ? "auto" : "smooth", block: "start" });
      window.setTimeout(updateActiveCategory, lockMs + 40);
    }
  }));

  function updateToTop() {
    toTop.classList.toggle("visible", window.scrollY >= 520);
  }

  window.addEventListener("scroll", handleScroll, { passive: true });
  updateToTop();
  updateActiveCategory();

  toTop.addEventListener("click", () => {
    const firstId = sections[0] ? sections[0].dataset.section : "";
    const lockMs = reducedMotion ? 0 : 900;
    scrollSpyLockedUntil = performance.now() + lockMs;
    activateChip(firstId, true);
    window.scrollTo({ top: 0, behavior: reducedMotion ? "auto" : "smooth" });
    window.setTimeout(updateActiveCategory, lockMs + 40);
  });
})();
