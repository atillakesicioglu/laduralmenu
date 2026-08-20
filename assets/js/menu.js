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
    const SPINNER_MS = 1500;
    const LIFT_MS = 850;

    const finish = () => {
      document.body.classList.remove("is-loading");
      document.body.classList.add("is-ready");
      if (pageLoader.isConnected) pageLoader.remove();
    };

    const hideLoader = () => {
      if (!pageLoader.isConnected) return;

      if (reducedMotion) {
        finish();
        return;
      }

      document.body.classList.remove("is-loading");
      document.body.classList.add("is-ready");
      pageLoader.classList.add("is-lifting");
      window.setTimeout(() => {
        if (pageLoader.isConnected) pageLoader.remove();
      }, LIFT_MS);
    };

    const boot = () => window.setTimeout(hideLoader, SPINNER_MS);

    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", boot, { once: true });
    } else {
      boot();
    }
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

  const productSheet = document.getElementById("productSheet");
  const sheetPanel = productSheet ? productSheet.querySelector(".product-sheet-panel") : null;
  const sheetGrab = document.getElementById("sheetGrab");
  const sheetImage = document.getElementById("sheetImage");
  const sheetImageEmpty = document.getElementById("sheetImageEmpty");
  const sheetCategory = document.getElementById("sheetCategory");
  const sheetTitle = document.getElementById("sheetTitle");
  const sheetDescription = document.getElementById("sheetDescription");
  const sheetNote = document.getElementById("sheetNote");
  const sheetPrice = document.getElementById("sheetPrice");

  let sheetDrag = null;

  function setSheetOffset(y) {
    if (!sheetPanel) return;
    sheetPanel.style.transform = "translateY(" + Math.max(0, y) + "px)";
  }

  function clearSheetOffset() {
    if (!sheetPanel) return;
    sheetPanel.style.transform = "";
  }

  function openProductSheet(item) {
    if (!productSheet || !item) return;

    const name = item.dataset.name || "";
    const description = item.dataset.description || "";
    const note = item.dataset.note || "";
    const category = item.dataset.category || "";
    const price = item.dataset.price || "";
    const image = item.dataset.image || "";

    sheetTitle.textContent = name;
    sheetCategory.textContent = category;
    sheetDescription.textContent = description;
    sheetPrice.textContent = price;
    clearSheetOffset();
    productSheet.classList.remove("is-dragging");

    if (note) {
      sheetNote.hidden = false;
      sheetNote.textContent = note;
    } else {
      sheetNote.hidden = true;
      sheetNote.textContent = "";
    }

    if (image) {
      sheetImage.hidden = false;
      sheetImageEmpty.hidden = true;
      sheetImage.src = image;
      sheetImage.alt = name;
    } else {
      sheetImage.hidden = true;
      sheetImage.removeAttribute("src");
      sheetImage.alt = "";
      sheetImageEmpty.hidden = false;
    }

    productSheet.hidden = false;
    productSheet.setAttribute("aria-hidden", "false");
    document.body.classList.add("sheet-open");
    requestAnimationFrame(() => {
      productSheet.classList.add("is-open");
    });
  }

  function closeProductSheet() {
    if (!productSheet || productSheet.hidden) return;
    productSheet.classList.remove("is-open", "is-dragging");
    document.body.classList.remove("sheet-open");
    productSheet.setAttribute("aria-hidden", "true");
    window.setTimeout(() => {
      if (!productSheet.classList.contains("is-open")) {
        productSheet.hidden = true;
        clearSheetOffset();
        sheetImage.removeAttribute("src");
      }
    }, reducedMotion ? 0 : 320);
  }

  function onSheetPointerDown(event) {
    if (!productSheet || !sheetPanel || productSheet.hidden) return;
    if (event.target.closest("#sheetClose")) return;
    sheetDrag = {
      pointerId: event.pointerId,
      startY: event.clientY,
      lastY: event.clientY,
      lastT: performance.now(),
      offset: 0,
      velocity: 0
    };
    productSheet.classList.add("is-dragging");
    if (sheetGrab && sheetGrab.setPointerCapture) {
      try { sheetGrab.setPointerCapture(event.pointerId); } catch (_) {}
    }
  }

  function onSheetPointerMove(event) {
    if (!sheetDrag || event.pointerId !== sheetDrag.pointerId) return;
    const now = performance.now();
    const dy = event.clientY - sheetDrag.startY;
    const dt = Math.max(16, now - sheetDrag.lastT);
    sheetDrag.velocity = (event.clientY - sheetDrag.lastY) / dt;
    sheetDrag.lastY = event.clientY;
    sheetDrag.lastT = now;
    sheetDrag.offset = Math.max(0, dy);
    setSheetOffset(sheetDrag.offset);
  }

  function onSheetPointerUp(event) {
    if (!sheetDrag || event.pointerId !== sheetDrag.pointerId) return;
    const shouldClose = sheetDrag.offset > 110 || sheetDrag.velocity > 0.55;
    const offset = sheetDrag.offset;
    sheetDrag = null;
    productSheet.classList.remove("is-dragging");

    if (shouldClose) {
      closeProductSheet();
      return;
    }

    if (offset > 0) {
      clearSheetOffset();
    }
  }

  if (sheetGrab) {
    sheetGrab.addEventListener("pointerdown", onSheetPointerDown);
    sheetGrab.addEventListener("pointermove", onSheetPointerMove);
    sheetGrab.addEventListener("pointerup", onSheetPointerUp);
    sheetGrab.addEventListener("pointercancel", onSheetPointerUp);
  }

  document.addEventListener("click", (event) => {
    const item = event.target.closest(".menu-item");
    if (item) {
      openProductSheet(item);
      return;
    }
    if (event.target.closest(".product-sheet-backdrop") || event.target.closest("#sheetClose")) {
      closeProductSheet();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closeProductSheet();
      return;
    }
    if ((event.key === "Enter" || event.key === " ") && event.target.classList.contains("menu-item")) {
      event.preventDefault();
      openProductSheet(event.target);
    }
  });
})();
