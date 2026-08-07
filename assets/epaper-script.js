document.addEventListener('DOMContentLoaded', function () {
  const epaperWrappers = document.querySelectorAll('.epaper-wrapper');

  epaperWrappers.forEach(wrapper => {
    const prevButton = wrapper.querySelector('.left-arrow') || wrapper.querySelector('#prev');
    const nextButton = wrapper.querySelector('.right-arrow') || wrapper.querySelector('#next');
    const imageSlider = wrapper.querySelector('.image-slider');
    const currentPageDisplay = wrapper.querySelector('.current-page');
    const slides = wrapper.querySelectorAll('.image-slide');

    const zoomInBtn = wrapper.querySelector('#zoom-in');
    const zoomOutBtn = wrapper.querySelector('#zoom-out');
    const downloadBtn = wrapper.querySelector('#download-btn');
    const printBtn = wrapper.querySelector('#print-btn');
    const printAllBtn = wrapper.querySelector('#print-all-btn');

    let currentIndex = 0;
    const numImages = slides.length;
    let zoomLevel = 1;

    if (currentPageDisplay) {
      currentPageDisplay.textContent = currentIndex + 1;
    }

    function updateSlider() {
      if (imageSlider) {
        const translateX = -currentIndex * 100;
        imageSlider.style.transform = `translateX(${translateX}%)`;
      }
      if (currentPageDisplay) {
        currentPageDisplay.textContent = currentIndex + 1;
      }
      updateDownloadLink();
      resetZoom();
    }

    function resetZoom() {
      zoomLevel = 1;
      slides.forEach(slide => {
        const target = slide.querySelector('.zoom-container img, .zoom-container iframe');
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

      const currentTarget = slides[currentIndex].querySelector('.zoom-container img, .zoom-container iframe');
      if (currentTarget) {
        currentTarget.style.transform = `scale(${zoomLevel})`;
      }
    }

    function updateDownloadLink() {
      if (!slides[currentIndex] || !downloadBtn) return;
      const slide = slides[currentIndex];
      const downloadUrl = slide.dataset.downloadUrl || slide.dataset.fileUrl || slide.querySelector('img')?.src || '#';
      downloadBtn.href = downloadUrl;
    }

    if (nextButton) {
      nextButton.addEventListener('click', e => {
        e.preventDefault();
        if (numImages > 0) {
          currentIndex = (currentIndex + 1) % numImages;
          updateSlider();
        }
      });
    }

    if (prevButton) {
      prevButton.addEventListener('click', e => {
        e.preventDefault();
        if (numImages > 0) {
          currentIndex = (currentIndex - 1 + numImages) % numImages;
          updateSlider();
        }
      });
    }

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

    // Keyboard Arrow Keys Navigation
    document.addEventListener('keydown', e => {
      if (e.key === 'ArrowRight') {
        if (numImages > 0) {
          currentIndex = (currentIndex + 1) % numImages;
          updateSlider();
        }
      } else if (e.key === 'ArrowLeft') {
        if (numImages > 0) {
          currentIndex = (currentIndex - 1 + numImages) % numImages;
          updateSlider();
        }
      }
    });

    // Touch Swipe Support for Mobile Devices
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
        // Swipe Left -> Next Page
        if (numImages > 0) {
          currentIndex = (currentIndex + 1) % numImages;
          updateSlider();
        }
      }
      if (touchEndX > touchStartX + 40) {
        // Swipe Right -> Prev Page
        if (numImages > 0) {
          currentIndex = (currentIndex - 1 + numImages) % numImages;
          updateSlider();
        }
      }
    }

    // Print Current Page
    if (printBtn) {
      printBtn.addEventListener('click', e => {
        e.preventDefault();
        const currentSlide = slides[currentIndex];
        if (!currentSlide) return;
        const img = currentSlide.querySelector('img');
        const fileUrl = currentSlide.dataset.fileUrl || img?.src;
        if (!fileUrl) return;

        if (img) {
          const iframe = document.createElement('iframe');
          iframe.style.position = 'fixed';
          iframe.style.width = '0';
          iframe.style.height = '0';
          iframe.style.border = '0';
          document.body.appendChild(iframe);

          const iframeDoc = iframe.contentDocument || iframe.contentWindow?.document;
          if (!iframeDoc) return;

          iframeDoc.open();
          iframeDoc.write(`
            <html>
              <head>
                <title>Print Page</title>
                <style>
                  @page { size: A4 portrait; margin: 0; }
                  html, body { margin: 0; padding: 0; height: 100%; width: 100%; display: flex; justify-content: center; align-items: center; background: white; }
                  img { width: 100%; height: auto; max-height: 100%; object-fit: contain; }
                </style>
              </head>
              <body>
                <img src="${img.src}" onload="window.focus(); window.print(); setTimeout(() => window.close(), 100);" />
              </body>
            </html>
          `);
          iframeDoc.close();
        } else {
          window.open(fileUrl, '_blank');
        }
      });
    }

    // Print All Pages
    if (printAllBtn) {
      printAllBtn.addEventListener('click', e => {
        e.preventDefault();
        const iframe = document.createElement('iframe');
        iframe.style.position = 'fixed';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = 'none';
        document.body.appendChild(iframe);

        const doc = iframe.contentDocument || iframe.contentWindow?.document;
        if (!doc) return;

        doc.open();
        doc.write('<html><head><title>Print All Pages</title><style>');
        doc.write(`
          @media print {
            @page { size: A4; margin: 0; }
            body { margin: 0; text-align: center; }
            img { page-break-after: always; max-width: 100%; max-height: 100vh; }
          }
        `);
        doc.write('</style></head><body>');

        slides.forEach(slide => {
          const img = slide.querySelector('img');
          if (img) {
            doc.write(`<img src="${img.src}" alt="Slide" />`);
          }
        });

        doc.write('</body></html>');
        doc.close();

        iframe.onload = () => {
          iframe.contentWindow?.focus();
          iframe.contentWindow?.print();
          setTimeout(() => document.body.removeChild(iframe), 1000);
        };
      });
    }

    // Initial slider state
    updateSlider();
  });
});
