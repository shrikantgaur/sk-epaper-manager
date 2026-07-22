jQuery(function ($) {
  $('.sk-epaper-upload-button').click(function (e) {
    e.preventDefault();

    const customUploader = wp.media({
      title: 'Choose Images',
      button: { text: 'Choose Images' },
      multiple: true
    });

    customUploader.on('select', function () {
      const selection = customUploader.state().get('selection');
      const imageList = $('.sk-epaper-image-list');
      let currentIDs = $('#sk_epaper_images').val().split(',').map(id => id.trim()).filter(Boolean);

      selection.each(function (attachment) {
        const id = attachment.id.toString();
        const full = attachment.attributes.url; // Always full size

        if (!currentIDs.includes(id)) {
          currentIDs.push(id);

          imageList.append(
            `<li data-id="${id}">
              <img src="${full}" alt="" />
              <span class="remove-image"><span class="text-remove-btn hidden">Remove</span></span>
            </li>`
          );
        }
      });

      $('#sk_epaper_images').val(currentIDs.join(','));
    });

    customUploader.open();
  });

  $('.sk-epaper-image-list').on('click', '.remove-image', function () {
    const li = $(this).closest('li');
    const id = li.data('id').toString();
    let ids = $('#sk_epaper_images').val().split(',').map(id => id.trim()).filter(Boolean);

    ids = ids.filter(item => item !== id);
    $('#sk_epaper_images').val(ids.join(','));

    li.fadeOut(200, function () {
      $(this).remove();
    });
  });
});
