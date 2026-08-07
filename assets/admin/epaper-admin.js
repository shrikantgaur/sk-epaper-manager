jQuery(function ($) {
  const imageList = $('.sk-epaper-image-list');

  function updateImageIDsInput() {
    const currentIDs = [];
    $('.sk-epaper-image-list li').each(function () {
      const id = $(this).data('id');
      if (id) {
        currentIDs.push(id.toString());
      }
    });
    $('#sk_epaper_images').val(currentIDs.join(','));
    updatePageBadges();
  }

  function updatePageBadges() {
    let index = 1;
    $('.sk-epaper-image-list li').each(function () {
      let pill = $(this).find('.sk-page-number-pill');
      if (!pill.length) {
        pill = $('<span class="sk-page-number-pill"></span>').prependTo($(this));
      }
      pill.text('Page ' + index);
      index++;
    });

    const totalPages = index - 1;
    $('.sk-page-counter-badge .count-text').text('Total Pages: ' + totalPages);
  }

  // Initialize sortable drag-and-drop
  if (imageList.length && $.fn.sortable) {
    imageList.sortable({
      placeholder: 'sk-epaper-sortable-placeholder',
      cursor: 'move',
      handle: '.drag-handle, .sk-card-preview-container, .file-title',
      update: function () {
        updateImageIDsInput();
      }
    });
  }

  // Add ePaper Pages Button with Strict Media Type Validation
  $('.sk-epaper-upload-button').click(function (e) {
    e.preventDefault();

    const customUploader = wp.media({
      title: 'Choose Images or PDF Files for ePaper Pages',
      button: { text: 'Add to ePaper' },
      library: {
        type: ['image', 'application/pdf']
      },
      multiple: true
    });

    customUploader.on('select', function () {
      const selection = customUploader.state().get('selection');
      let currentIDs = $('#sk_epaper_images').val().split(',').map(id => id.trim()).filter(Boolean);
      let invalidFiles = [];
      const validExts = ['.jpg', '.jpeg', '.png', '.webp', '.gif', '.pdf'];

      selection.each(function (attachment) {
        const id       = attachment.id.toString();
        const full     = attachment.attributes.url;
        const type     = attachment.attributes.type;
        const mime     = attachment.attributes.mime;
        const filename = (attachment.attributes.filename || attachment.attributes.title || '').toLowerCase();
        const title    = attachment.attributes.title || attachment.attributes.filename || '';

        // Strict File Validation Check (Allow only JPG, PNG, WEBP, GIF, PDF)
        const isImage     = type === 'image';
        const isPDF       = mime === 'application/pdf' || filename.endsWith('.pdf');
        const hasValidExt = validExts.some(ext => filename.endsWith(ext));

        if (!isImage && !isPDF && !hasValidExt) {
          invalidFiles.push(title || filename);
          return; // Reject invalid file
        }

        const thumb = isImage
          ? (attachment.attributes.sizes && attachment.attributes.sizes.medium ? attachment.attributes.sizes.medium.url : (attachment.attributes.sizes && attachment.attributes.sizes.thumbnail ? attachment.attributes.sizes.thumbnail.url : full))
          : (attachment.attributes.icon || full);

        if (!currentIDs.includes(id)) {
          currentIDs.push(id);

          const itemHtml = isImage
            ? `<li data-id="${id}" class="is-image">
                <span class="drag-handle" title="Drag to reorder page"><span class="dashicons dashicons-menu"></span></span>
                <span class="sk-page-number-pill">Page</span>
                <div class="sk-card-preview-container">
                  <img src="${thumb}" alt="${title}" />
                </div>
                <span class="remove-image" title="Remove Page"><span class="text-remove-btn hidden">Remove</span></span>
              </li>`
            : `<li data-id="${id}" class="is-file">
                <span class="drag-handle" title="Drag to reorder page"><span class="dashicons dashicons-menu"></span></span>
                <span class="sk-page-number-pill">Page</span>
                <div class="sk-card-preview-container">
                  <img src="${thumb}" alt="${title}" class="file-icon" />
                </div>
                <span class="file-title">${title}</span>
                <span class="remove-image" title="Remove Page"><span class="text-remove-btn hidden">Remove</span></span>
              </li>`;

          imageList.append(itemHtml);
        }
      });

      // Alert user if any wrong files were rejected
      if (invalidFiles.length > 0) {
        alert('Invalid file format rejected: ' + invalidFiles.join(', ') + '\n\nPlease select only Image (JPG, PNG, WEBP) or PDF files for ePaper pages.');
      }

      updateImageIDsInput();
    });

    customUploader.open();
  });

  $('.sk-epaper-image-list').on('click', '.remove-image', function () {
    const li = $(this).closest('li');
    li.fadeOut(200, function () {
      $(this).remove();
      updateImageIDsInput();
    });
  });

  // Shortcodes 1-Click Copy Handler with Reliable Text Reset & Toast Notice
  $('.sk-copy-btn').click(function (e) {
    e.preventDefault();
    const btn = $(this);

    if (!btn.data('orig-html')) {
      btn.data('orig-html', btn.html());
    }

    const targetSelector = btn.data('target');
    const textToCopy = $(targetSelector).text().trim();

    function triggerCopySuccess() {
      btn.addClass('is-copied').html('<span class="dashicons dashicons-yes-alt"></span> Copied!');

      let toast = $('#sk-copy-toast');
      if (!toast.length) {
        toast = $('<div id="sk-copy-toast" class="sk-copy-toast-notice"><span class="dashicons dashicons-yes-alt"></span> Shortcode copied to clipboard!</div>').appendTo('body');
      }
      toast.stop(true, true).fadeIn(200).delay(1800).fadeOut(300);

      setTimeout(function () {
        btn.removeClass('is-copied').html(btn.data('orig-html'));
      }, 2000);
    }

    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(textToCopy).then(triggerCopySuccess).catch(function () {
        fallbackCopyText(textToCopy, triggerCopySuccess);
      });
    } else {
      fallbackCopyText(textToCopy, triggerCopySuccess);
    }
  });

  function fallbackCopyText(text, callback) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
      document.execCommand('copy');
      if (callback) callback();
    } catch (err) {
      console.error('Fallback copy failed', err);
    }
    document.body.removeChild(textArea);
  }

  // Dynamic Shortcode Builder Update
  $('#sk-select-epaper').change(function () {
    const selectedID = $(this).val();
    const sc = '[sk_epaper id="' + selectedID + '"]';
    $('#sc-single-code').text(sc);
    $('#sc-single-php').text("<?php echo do_shortcode('" + sc + "'); ?>");
  });

  $('#sk-archive-count, #sk-archive-edition, #sk-archive-lang').on('input change', function () {
    const count = $('#sk-archive-count').val() || 6;
    const edition = $('#sk-archive-edition').val();
    const lang = $('#sk-archive-lang').val();

    let sc = '[sk_epaper_archive count="' + count + '"';
    if (edition) {
      sc += ' edition="' + edition + '"';
    }
    if (lang) {
      sc += ' language="' + lang + '"';
    }
    sc += ']';
    $('#sc-archive-code').text(sc);
    $('#sc-archive-php').text("<?php echo do_shortcode('" + sc + "'); ?>");
  });

  // Admin Analytics AJAX Filter & Pagination without page reload (with Loading Spinner Overlay)
  const filterForm = $('#sk-analytics-filter-form');
  const tableContainer = $('#sk-analytics-table-container');

  if (filterForm.length && typeof sk_epaper_admin_ajax !== 'undefined') {
    let isFiltering = false;

    function fetchAnalyticsData(pagedNum) {
      if (isFiltering) return;
      isFiltering = true;

      const paged = pagedNum || $('#sk-analytics-paged').val() || 1;
      $('#sk-analytics-paged').val(paged);

      // Add & show animated loading spinner overlay
      let loader = tableContainer.find('.sk-analytics-loading-overlay');
      if (!loader.length) {
        loader = $(
          '<div class="sk-analytics-loading-overlay">' +
            '<div class="sk-spinner-ring"></div>' +
            '<div class="sk-spinner-text">Updating Analytics...</div>' +
          '</div>'
        ).appendTo(tableContainer);
      }
      loader.stop(true, true).fadeIn(150);
      tableContainer.css('opacity', '0.6').css('pointer-events', 'none');

      const postData = {
        action: 'sk_epaper_filter_analytics',
        nonce: sk_epaper_admin_ajax.nonce,
        paged: paged,
        filter_edition: $('#filter_edition').val() || '',
        filter_language: $('#filter_language').val() || '',
        filter_search: $('#filter_search').val() || ''
      };

      $.post(sk_epaper_admin_ajax.ajax_url, postData, function (response) {
        if (response && response.success) {
          tableContainer.html(response.data.html);
          $('#sk-total-editions-badge').text(response.data.total);
        } else {
          console.error('Analytics AJAX failed', response);
        }
      }).fail(function (xhr, status, error) {
        console.error('Analytics AJAX request error', error);
      }).always(function () {
        tableContainer.css('opacity', '1').css('pointer-events', 'auto');
        tableContainer.find('.sk-analytics-loading-overlay').fadeOut(200, function () {
          $(this).remove();
        });
        isFiltering = false;
      });
    }

    filterForm.on('submit', function (e) {
      e.preventDefault();
      fetchAnalyticsData(1); // Reset to page 1 on filter submit
    });

    // Instant live filter change on select dropdowns
    $('#filter_edition, #filter_language').on('change', function () {
      fetchAnalyticsData(1);
    });

    // Pagination link click AJAX handler
    $(document).on('click', '#sk-analytics-table-container .tablenav-pages a', function (e) {
      e.preventDefault();
      const href = $(this).attr('href');
      if (href) {
        const match = href.match(/[?&]paged=(\d+)/);
        const paged = match ? match[1] : 1;
        fetchAnalyticsData(paged);
      }
    });

    // Reset filters button AJAX handler
    $(document).on('click', '#sk-reset-filters-btn', function (e) {
      e.preventDefault();
      $('#filter_edition').val('');
      $('#filter_language').val('');
      $('#filter_search').val('');
      fetchAnalyticsData(1);
    });
  }

  // Initialize badges on load
  updatePageBadges();
});
