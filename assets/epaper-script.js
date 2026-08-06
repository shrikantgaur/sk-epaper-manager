const prevButton = document.getElementById('prev');
const nextButton = document.getElementById('next');
const imageSlider = document.querySelector('.image-slider');
const currentPageDisplay = document.querySelector('.current-page');
const slides = document.querySelectorAll('.image-slide');

const zoomInBtn = document.getElementById('zoom-in');
const zoomOutBtn = document.getElementById('zoom-out');
const downloadBtn = document.getElementById('download-btn');
const printBtn = document.getElementById('print-btn');
const printAllBtn = document.getElementById('print-all-btn');

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
    const img = slide.querySelector('img');
    if (img) {
      img.style.transform = 'scale(1)';
    }
    const iframe = slide.querySelector('iframe');
    if (iframe) {
      iframe.style.transform = 'scale(1)';
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

if (nextButton && prevButton && imageSlider && slides.length > 0) {
  nextButton.addEventListener('click', () => {
    currentIndex = (currentIndex + 1) % numImages;
    updateSlider();
  });

  prevButton.addEventListener('click', () => {
    currentIndex = (currentIndex - 1 + numImages) % numImages;
    updateSlider();
  });

  if (zoomInBtn) {
    zoomInBtn.addEventListener('click', () => {
      zoomImage(0.5);
    });
  }

  if (zoomOutBtn) {
    zoomOutBtn.addEventListener('click', () => {
      zoomImage(-0.5);
    });
  }

  updateSlider();
} else if (slides.length > 0) {
  updateSlider();
} else {
  console.warn('Slider elements not found.');
}

// Drag-to-pan
slides.forEach(slide => {
  const container = slide.querySelector('.zoom-container');
  if (!container) return;
  let isDown = false;
  let startX, startY, scrollLeft, scrollTop;

  container.addEventListener('mousedown', e => {
    if (zoomLevel <= 1) return;
    isDown = true;
    container.classList.add('dragging');
    startX = e.pageX - container.offsetLeft;
    startY = e.pageY - container.offsetTop;
    scrollLeft = container.scrollLeft;
    scrollTop = container.scrollTop;
  });

  container.addEventListener('mouseleave', () => {
    isDown = false;
    container.classList.remove('dragging');
  });

  container.addEventListener('mouseup', () => {
    isDown = false;
    container.classList.remove('dragging');
  });

  container.addEventListener('mousemove', e => {
    if (!isDown) return;
    e.preventDefault();
    const x = e.pageX - container.offsetLeft;
    const y = e.pageY - container.offsetTop;
    const walkX = (x - startX) * 1;
    const walkY = (y - startY) * 1;
    container.scrollLeft = scrollLeft - walkX;
    container.scrollTop = scrollTop - walkY;
  });
});

// ✅ Print Button Functionality
if (printBtn) {
  printBtn.addEventListener('click', () => {
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
              @page {
                size: A4 portrait;
                margin: 0;
              }
              html, body {
                margin: 0;
                padding: 0;
                height: 100%;
                width: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
                background: white;
              }
              img {
                width: 100%;
                height: auto;
                max-height: 100%;
                object-fit: contain;
                page-break-after: avoid;
              }
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

if (printAllBtn) {
  printAllBtn.addEventListener('click', () => {
    const iframe = document.createElement('iframe');
    iframe.style.position = 'fixed';
    iframe.style.width = '0';
    iframe.style.height = '0';
    iframe.style.border = 'none';
    document.body.appendChild(iframe);

    const doc = iframe.contentDocument || iframe.contentWindow?.document;
    if (!doc) return;

    doc.open();
    doc.write('<html><head><title>Print All</title><style>');
    doc.write(`
      @media print {
        @page {
          size: A4;
          margin: 0;
        }
        body {
          margin: 0;
          text-align: center;
        }
        img {
          page-break-after: always;
          max-width: 100%;
          max-height: 100vh;
        }
      }
    `);
    doc.write('</style></head><body>');

    slides.forEach(slide => {
      const img = slide.querySelector('img');
      if (img) {
        doc.write(`<img src="${img.src}" alt="Slide" />`);
      } else if (slide.dataset.fileUrl) {
        doc.write(`<p><a href="${slide.dataset.fileUrl}">${slide.dataset.fileUrl}</a></p>`);
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
