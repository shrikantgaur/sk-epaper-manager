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
  }

  // Initialize sortable drag-and-drop
  if (imageList.length && $.fn.sortable) {
    imageList.sortable({
      placeholder: 'sk-epaper-sortable-placeholder',
      cursor: 'move',
      handle: '.drag-handle, img, .file-icon, .file-title',
      update: function () {
        updateImageIDsInput();
      }
    });
  }

  $('.sk-epaper-upload-button').click(function (e) {
    e.preventDefault();

    const customUploader = wp.media({
      title: 'Choose Images or PDF Files',
      button: { text: 'Add to ePaper' },
      multiple: true
    });

    customUploader.on('select', function () {
      const selection = customUploader.state().get('selection');
      let currentIDs = $('#sk_epaper_images').val().split(',').map(id => id.trim()).filter(Boolean);

      selection.each(function (attachment) {
        const id = attachment.id.toString();
        const full = attachment.attributes.url;
        const isImage = attachment.attributes.type === 'image';
        const title = attachment.attributes.title || attachment.attributes.filename || '';
        const thumb = isImage
          ? (attachment.attributes.sizes && attachment.attributes.sizes.thumbnail ? attachment.attributes.sizes.thumbnail.url : full)
          : (attachment.attributes.icon || full);

        if (!currentIDs.includes(id)) {
          currentIDs.push(id);

          const itemHtml = isImage
            ? `<li data-id="${id}" class="is-image">
                <span class="drag-handle" title="Drag to reorder"><span class="dashicons dashicons-menu"></span></span>
                <img src="${thumb}" alt="${title}" />
                <span class="remove-image"><span class="text-remove-btn hidden">Remove</span></span>
              </li>`
            : `<li data-id="${id}" class="is-file">
                <span class="drag-handle" title="Drag to reorder"><span class="dashicons dashicons-menu"></span></span>
                <img src="${thumb}" alt="${title}" class="file-icon" />
                <span class="file-title">${title}</span>
                <span class="remove-image"><span class="text-remove-btn hidden">Remove</span></span>
              </li>`;

          imageList.append(itemHtml);
        }
      });

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
});
