/**
 * SK ePaper Manager - frontend viewer.
 *
 * Pages load on demand: only the current page and its neighbours are fetched,
 * so a 24 page edition no longer pushes every full size image on first paint.
 */
/**
 * Archive date filter.
 *
 * A native date input can only express a range, so days without an edition
 * would still look selectable and lead to an empty page. Where jQuery UI is
 * available (WordPress ships it) the field upgrades to a calendar that greys
 * out every day that has no edition. Otherwise the native input stays, bounded
 * by the min/max the template already prints.
 */
document.addEventListener('DOMContentLoaded', function () {
  const dateField = document.getElementById('sk-epaper-date');

  if (
    dateField &&
    typeof sk_epaper_dates !== 'undefined' &&
    window.jQuery &&
    window.jQuery.fn &&
    window.jQuery.fn.datepicker
  ) {
    const available = sk_epaper_dates.available || [];

    if (available.length) {
      const lookup = Object.create(null);
      available.forEach(d => { lookup[d] = true; });

      const $ = window.jQuery;
      const $field = $(dateField);
      const current = dateField.value;

      // Swap to text so the browser's own picker does not fight jQuery UI.
      dateField.type = 'text';
      dateField.readOnly = true;
      dateField.value = current;

      const pad = n => (n < 10 ? '0' : '') + n;
      const toKey = date => date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());

      $field.datepicker({
        dateFormat: 'yy-mm-dd',
        changeMonth: true,
        changeYear: true,
        maxDate: sk_epaper_dates.today || 0,
        minDate: available[available.length - 1] || null,
        beforeShowDay: function (date) {
          const key = toKey(date);
          return lookup[key] ? [true, 'sk-has-edition', ''] : [false, 'sk-no-edition', ''];
        },
        onSelect: function () {
          $field.trigger('change');
        }
      });

      $field.addClass('sk-datepicker-input');
    }
  }

  const epaperWrappers = document.querySelectorAll('.epaper-wrapper');

  // Report the view out of band so counting survives full page caching, where
  // the cached HTML never reaches PHP. Deduplication and bot filtering happen
  // server side.
  function reportView(wrapper) {
    if (typeof sk_epaper_views === 'undefined' || !sk_epaper_views.enabled) return;

    const epaperId = wrapper.dataset.epaperId;
    if (!epaperId) return;

    const endpoint = sk_epaper_views.endpoint + encodeURIComponent(epaperId);

    try {
      if (navigator.sendBeacon) {
        navigator.sendBeacon(endpoint, new Blob([], { type: 'text/plain' }));
      } else {
        fetch(endpoint, { method: 'POST', credentials: 'same-origin', keepalive: true }).catch(() => {});
      }
    } catch (err) {
      /* counting is best effort - never break the reader */
    }
  }

  epaperWrappers.forEach(wrapper => {
    const prevButton = wrapper.querySelector('.left-arrow') || wrapper.querySelector('#prev');
    const nextButton = wrapper.querySelector('.right-arrow') || wrapper.querySelector('#next');
    const imageSlider = wrapper.querySelector('.image-slider');
    const currentPageDisplay = wrapper.querySelector('.current-page');
    const slides = wrapper.querySelectorAll('.image-slide');
    const thumbButtons = wrapper.querySelectorAll('.epaper-thumb-btn');
    const thumbViewport = wrapper.querySelector('.epaper-thumb-viewport');
    const thumbPrev = wrapper.querySelector('.epaper-thumb-prev');
    const thumbNext = wrapper.querySelector('.epaper-thumb-next');

    const zoomInBtn = wrapper.querySelector('#zoom-in');
    const zoomOutBtn = wrapper.querySelector('#zoom-out');
    const downloadBtn = wrapper.querySelector('#download-btn');
    const printBtn = wrapper.querySelector('#print-btn');
    const printAllBtn = wrapper.querySelector('#print-all-btn');

    let currentIndex = 0;
    const numImages = slides.length;
    let zoomLevel = 1;
    let started = false;

    /* ----------------------------------------------------------------
       Lazy loading
    ---------------------------------------------------------------- */

    // Swap a deferred page's data-src attributes in. Safe to call repeatedly.
    function loadSlide(index) {
      const slide = slides[index];
      if (!slide || slide.dataset.loaded === '1') return;

      slide.dataset.loaded = '1';

      const img = slide.querySelector('img[data-src]');
      if (img) {
        if (img.dataset.srcset) img.srcset = img.dataset.srcset;
        if (img.dataset.sizes) img.sizes = img.dataset.sizes;
        img.src = img.dataset.src;
        img.removeAttribute('data-src');
        img.addEventListener('load', () => {
          const skeleton = slide.querySelector('.epaper-page-skeleton');
          if (skeleton) skeleton.remove();
        }, { once: true });
      }

      const frame = slide.querySelector('iframe[data-src]');
      if (frame) {
        frame.src = frame.dataset.src;
        frame.removeAttribute('data-src');
      }

      renderPdfCanvas(slide);
    }

    // Current page plus one either side, so a swipe never lands on a blank.
    function loadNeighbours(index) {
      loadSlide(index);
      loadSlide(index - 1);
      loadSlide(index + 1);
    }

    function loadAll() {
      for (let i = 0; i < numImages; i++) {
        loadSlide(i);
      }
    }

    /* ----------------------------------------------------------------
       Optional PDF.js rendering
    ---------------------------------------------------------------- */

    function renderPdfCanvas(slide) {
      const holder = slide.querySelector('.epaper-pdf-canvas');
      if (!holder || holder.dataset.rendered === '1') return;

      const url = holder.dataset.pdfUrl;
      if (!url) return;

      holder.dataset.rendered = '1';

      // Fall back to the plain embed whenever the vendor library is absent.
      if (typeof pdfjsLib === 'undefined' || typeof sk_epaper_pdfjs === 'undefined') {
        const frame = document.createElement('iframe');
        frame.src = url;
        frame.className = 'epaper-pdf-embed';
        frame.width = '100%';
        frame.height = '800px';
        frame.style.cssText = 'border:none;min-height:800px;width:100%;';
        holder.replaceWith(frame);
        return;
      }

      try {
        pdfjsLib.GlobalWorkerOptions.workerSrc = sk_epaper_pdfjs.worker;

        pdfjsLib.getDocument(url).promise.then(pdf => {
          const renderPage = pageNumber => pdf.getPage(pageNumber).then(page => {
            const viewport = page.getViewport({ scale: 1.5 });
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');

            canvas.width = viewport.width;
            canvas.height = viewport.height;
            canvas.className = 'epaper-pdf-page';
            canvas.style.maxWidth = '100%';
            canvas.style.height = 'auto';
            holder.appendChild(canvas);

            return page.render({ canvasContext: context, viewport }).promise;
          });

          let chain = Promise.resolve();
          for (let n = 1; n <= pdf.numPages; n++) {
            chain = chain.then(() => renderPage(n));
          }
          return chain;
        }).catch(() => {
          holder.dataset.rendered = '0';
        });
      } catch (err) {
        holder.dataset.rendered = '0';
      }
    }

    /* ----------------------------------------------------------------
       Slider
    ---------------------------------------------------------------- */

    function updateSlider() {
      if (imageSlider) {
        const translateX = -currentIndex * 100;
        imageSlider.style.transform = `translateX(${translateX}%)`;
      }
      if (currentPageDisplay) {
        currentPageDisplay.textContent = currentIndex + 1;
      }

      loadNeighbours(currentIndex);
      updateThumbs();
      updateDownloadLink();
      resetZoom();
    }

    function goTo(index) {
      if (numImages < 1) return;
      currentIndex = ((index % numImages) + numImages) % numImages;
      updateSlider();
    }

    function updateThumbs() {
      thumbButtons.forEach(btn => {
        const isActive = parseInt(btn.dataset.index, 10) === currentIndex;
        btn.classList.toggle('is-active', isActive);

        if (isActive) {
          btn.setAttribute('aria-current', 'true');
          scrollThumbIntoView(btn);
        } else {
          btn.removeAttribute('aria-current');
        }
      });

      updateThumbNavState();
    }

    // A side rail scrolls vertically, a bottom rail horizontally. Below the
    // responsive breakpoint a side rail becomes horizontal too, so measure the
    // element rather than trusting the setting.
    function railIsVertical() {
      if (!thumbViewport) return false;
      return thumbViewport.scrollHeight > thumbViewport.clientHeight + 4;
    }

    // Scroll the rail itself rather than the page: scrollIntoView() on a thumb
    // would drag the whole document around on every page change.
    function scrollThumbIntoView(btn) {
      if (!thumbViewport) return;

      const item = btn.parentElement || btn;
      const vertical = railIsVertical();

      const start = vertical ? item.offsetTop : item.offsetLeft;
      const size = vertical ? item.offsetHeight : item.offsetWidth;
      const end = start + size;
      const viewStart = vertical ? thumbViewport.scrollTop : thumbViewport.scrollLeft;
      const viewSize = vertical ? thumbViewport.clientHeight : thumbViewport.clientWidth;
      const viewEnd = viewStart + viewSize;

      let target = null;
      if (start < viewStart) {
        target = start - 16;
      } else if (end > viewEnd) {
        target = end - viewSize + 16;
      }

      if (target === null) return;

      if (vertical) {
        thumbViewport.scrollTop = target;
      } else {
        thumbViewport.scrollLeft = target;
      }
    }

    // Arrows only appear when the rail actually overflows.
    function updateThumbNavState() {
      if (!thumbViewport || !thumbPrev || !thumbNext) return;

      const vertical = railIsVertical();
      const scrollSize = vertical ? thumbViewport.scrollHeight : thumbViewport.scrollWidth;
      const clientSize = vertical ? thumbViewport.clientHeight : thumbViewport.clientWidth;

      if (scrollSize <= clientSize + 4) {
        thumbPrev.hidden = true;
        thumbNext.hidden = true;
        return;
      }

      const pos = vertical ? thumbViewport.scrollTop : thumbViewport.scrollLeft;
      const maxScroll = scrollSize - clientSize;

      thumbPrev.hidden = pos <= 2;
      thumbNext.hidden = pos >= maxScroll - 2;
    }

    function scrollThumbRail(direction) {
      if (!thumbViewport) return;

      const vertical = railIsVertical();
      const clientSize = vertical ? thumbViewport.clientHeight : thumbViewport.clientWidth;
      const step = direction * Math.max(160, clientSize * 0.8);

      if (vertical) {
        thumbViewport.scrollTop += step;
      } else {
        thumbViewport.scrollLeft += step;
      }
    }

    if (thumbPrev) {
      thumbPrev.addEventListener('click', e => {
        e.preventDefault();
        scrollThumbRail(-1);
      });
    }

    if (thumbNext) {
      thumbNext.addEventListener('click', e => {
        e.preventDefault();
        scrollThumbRail(1);
      });
    }

    if (thumbViewport) {
      thumbViewport.addEventListener('scroll', updateThumbNavState, { passive: true });
      window.addEventListener('resize', updateThumbNavState);
    }

    function resetZoom() {
      zoomLevel = 1;
      slides.forEach(slide => {
        const target = slide.querySelector('.zoom-container img, .zoom-container iframe, .zoom-container canvas');
        if (target) {
          target.style.transform = 'scale(1)';
        }
      });
    }

    function zoomImage(factor) {
      if (!slides[currentIndex]) return;
      zoomLevel += factor;
      if (zoomLevel < 1) zoomLevel = 1;
      if (zoomLevel > 5) zoomLevel = 5;

      const currentTarget = slides[currentIndex].querySelector('.zoom-container img, .zoom-container iframe, .zoom-container canvas');
      if (currentTarget) {
        currentTarget.style.transform = `scale(${zoomLevel})`;
      }
    }

    function updateDownloadLink() {
      if (!slides[currentIndex] || !downloadBtn) return;
      const slide = slides[currentIndex];
      const img = slide.querySelector('img');
      const downloadUrl = slide.dataset.downloadUrl || slide.dataset.fileUrl || (img && (img.src || img.dataset.src)) || '#';
      downloadBtn.href = downloadUrl;
    }

    if (nextButton) {
      nextButton.addEventListener('click', e => {
        e.preventDefault();
        goTo(currentIndex + 1);
      });
    }

    if (prevButton) {
      prevButton.addEventListener('click', e => {
        e.preventDefault();
        goTo(currentIndex - 1);
      });
    }

    thumbButtons.forEach(btn => {
      btn.addEventListener('click', e => {
        e.preventDefault();
        goTo(parseInt(btn.dataset.index, 10) || 0);
      });
    });

    if (zoomInBtn) {
      zoomInBtn.addEventListener('click', e => {
        e.preventDefault();
        zoomImage(0.5);
      });
    }

    if (zoomOutBtn) {
      zoomOutBtn.addEventListener('click', e => {
        e.preventDefault();
        zoomImage(-0.5);
      });
    }

    /* ----------------------------------------------------------------
       Keyboard, scoped to this viewer
    ---------------------------------------------------------------- */

    if (!wrapper.hasAttribute('tabindex')) {
      wrapper.setAttribute('tabindex', '0');
    }

    function isTypingTarget(el) {
      if (!el) return false;
      if (el.isContentEditable) return true;
      return ['INPUT', 'TEXTAREA', 'SELECT'].indexOf(el.tagName) !== -1;
    }

    function isOnlyViewerOnPage() {
      return epaperWrappers.length === 1;
    }

    document.addEventListener('keydown', e => {
      if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
      if (isTypingTarget(e.target)) return;

      // Only react when this viewer holds focus, or when it is the sole viewer.
      const focused = wrapper.contains(document.activeElement);
      if (!focused && !isOnlyViewerOnPage()) return;

      if (numImages < 1) return;
      e.preventDefault();
      goTo(currentIndex + (e.key === 'ArrowRight' ? 1 : -1));
    });

    /* ----------------------------------------------------------------
       Touch
    ---------------------------------------------------------------- */

    let touchStartX = 0;
    let touchEndX = 0;
    const sliderContainer = wrapper.querySelector('.slider-container');

    if (sliderContainer) {
      sliderContainer.addEventListener('touchstart', e => {
        touchStartX = e.changedTouches[0].screenX;
      }, { passive: true });

      sliderContainer.addEventListener('touchend', e => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
      }, { passive: true });
    }

    function handleSwipe() {
      if (touchEndX < touchStartX - 40) {
        goTo(currentIndex + 1);
      }
      if (touchEndX > touchStartX + 40) {
        goTo(currentIndex - 1);
      }
    }

    /* ----------------------------------------------------------------
       Printing
    ---------------------------------------------------------------- */

    // Wait for an image to be decodable before handing it to the printer,
    // otherwise the print dialog can open on blank pages.
    function whenReady(img) {
      if (!img) return Promise.resolve();
      if (img.decode) return img.decode().catch(() => {});
      if (img.complete) return Promise.resolve();
      return new Promise(resolve => {
        img.onload = resolve;
        img.onerror = resolve;
      });
    }

    function openPrintFrame(writeBody, images) {
      const iframe = document.createElement('iframe');
      iframe.style.position = 'fixed';
      iframe.style.width = '0';
      iframe.style.height = '0';
      iframe.style.border = '0';
      document.body.appendChild(iframe);

      const doc = iframe.contentDocument || (iframe.contentWindow && iframe.contentWindow.document);
      if (!doc) {
        iframe.remove();
        return;
      }

      doc.open();
      doc.write(writeBody);
      doc.close();

      let done = false;
      const run = () => {
        if (done) return;
        done = true;
        try {
          iframe.contentWindow.focus();
          iframe.contentWindow.print();
        } catch (err) {
          /* printing blocked - fall through to cleanup */
        }
        // Always detach: previously every print left an iframe behind.
        setTimeout(() => iframe.remove(), 1000);
      };

      Promise.all(Array.from(doc.images || []).map(whenReady)).then(run);
      // Safety net so the iframe is never orphaned if an image never settles.
      setTimeout(run, 8000);
    }

    if (printBtn) {
      printBtn.addEventListener('click', e => {
        e.preventDefault();
        const currentSlide = slides[currentIndex];
        if (!currentSlide) return;

        loadSlide(currentIndex);

        const img = currentSlide.querySelector('img');
        const fileUrl = currentSlide.dataset.fileUrl || (img && img.src);
        if (!fileUrl) return;

        if (!img) {
          window.open(fileUrl, '_blank');
          return;
        }

        whenReady(img).then(() => {
          openPrintFrame(`
            <html>
              <head>
                <title>Print Page</title>
                <style>
                  @page { size: A4 portrait; margin: 0; }
                  html, body { margin: 0; padding: 0; height: 100%; width: 100%; display: flex; justify-content: center; align-items: center; background: white; }
                  img { width: 100%; height: auto; max-height: 100%; object-fit: contain; }
                </style>
              </head>
              <body><img src="${img.currentSrc || img.src}" alt="" /></body>
            </html>
          `);
        });
      });
    }

    if (printAllBtn) {
      printAllBtn.addEventListener('click', e => {
        e.preventDefault();

        // Deferred pages have no src yet - pull them all in first.
        loadAll();

        const imgs = Array.from(slides)
          .map(slide => slide.querySelector('img'))
          .filter(Boolean);

        if (!imgs.length) return;

        Promise.all(imgs.map(whenReady)).then(() => {
          const body = imgs
            .map(img => `<img src="${img.currentSrc || img.src}" alt="" />`)
            .join('');

          openPrintFrame(`
            <html>
              <head>
                <title>Print All Pages</title>
                <style>
                  @media print {
                    @page { size: A4; margin: 0; }
                    body { margin: 0; text-align: center; }
                    img { page-break-after: always; max-width: 100%; max-height: 100vh; }
                  }
                </style>
              </head>
              <body>${body}</body>
            </html>
          `);
        });
      });
    }

    /* ----------------------------------------------------------------
       Start
    ---------------------------------------------------------------- */

    function start() {
      if (started) return;
      started = true;
      updateSlider();
      reportView(wrapper);
    }

    // Embedded viewers can sit far below the fold; hold off until they are
    // actually approaching the viewport.
    if (wrapper.dataset.lazy === '1' && 'IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            obs.disconnect();
            start();
          }
        });
      }, { rootMargin: '300px' });

      observer.observe(wrapper);

      // Never leave the reader empty if the observer never fires.
      setTimeout(start, 3000);
    } else {
      start();
    }
  });
});
