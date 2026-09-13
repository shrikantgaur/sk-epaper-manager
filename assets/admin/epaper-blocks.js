/**
 * SK ePaper Manager - block editor integration.
 *
 * Written against the wp.* globals rather than JSX so the plugin ships without
 * a build step. Both blocks render server side, reusing the shortcode handlers.
 */
(function (blocks, element, blockEditor, components, i18n, data, serverSideRender) {
  'use strict';

  const el = element.createElement;
  const __ = i18n.__;
  const { InspectorControls } = blockEditor;
  const { PanelBody, SelectControl, RangeControl, TextControl, Placeholder, Spinner } = components;
  const { useSelect } = data;
  const ServerSideRender = serverSideRender;

  /**
   * Options list of published ePapers for the picker.
   */
  function useEpaperOptions() {
    return useSelect(function (select) {
      const query = { per_page: 100, status: 'publish', orderby: 'date', order: 'desc' };
      const records = select('core').getEntityRecords('postType', 'epaper', query);

      if (!records) return null;

      return [{ label: __('— Select an edition —', 'sk-epaper-manager'), value: 0 }].concat(
        records.map(function (record) {
          return {
            label: record.title && record.title.rendered ? record.title.rendered : '#' + record.id,
            value: record.id
          };
        })
      );
    }, []);
  }

  blocks.registerBlockType('sk/epaper-viewer', {
    title: __('ePaper Viewer', 'sk-epaper-manager'),
    description: __('Embed a single ePaper edition with the page reader.', 'sk-epaper-manager'),
    icon: 'book-alt',
    category: 'media',
    keywords: [__('epaper', 'sk-epaper-manager'), __('newspaper', 'sk-epaper-manager'), __('pdf', 'sk-epaper-manager')],
    supports: { html: false },

    edit: function (props) {
      const options = useEpaperOptions();
      const id = props.attributes.id;

      const inspector = el(
        InspectorControls,
        null,
        el(
          PanelBody,
          { title: __('Edition', 'sk-epaper-manager'), initialOpen: true },
          options
            ? el(SelectControl, {
                label: __('ePaper edition', 'sk-epaper-manager'),
                value: id,
                options: options,
                onChange: function (value) {
                  props.setAttributes({ id: parseInt(value, 10) || 0 });
                }
              })
            : el(Spinner)
        )
      );

      if (!id) {
        return el(
          element.Fragment,
          null,
          inspector,
          el(
            Placeholder,
            {
              icon: 'book-alt',
              label: __('ePaper Viewer', 'sk-epaper-manager'),
              instructions: __('Choose which edition to display.', 'sk-epaper-manager')
            },
            options
              ? el(SelectControl, {
                  value: id,
                  options: options,
                  onChange: function (value) {
                    props.setAttributes({ id: parseInt(value, 10) || 0 });
                  }
                })
              : el(Spinner)
          )
        );
      }

      return el(
        element.Fragment,
        null,
        inspector,
        el('div', { className: props.className },
          el(ServerSideRender, { block: 'sk/epaper-viewer', attributes: props.attributes })
        )
      );
    },

    save: function () {
      return null; // Rendered server side.
    }
  });

  blocks.registerBlockType('sk/epaper-archive', {
    title: __('ePaper Archive', 'sk-epaper-manager'),
    description: __('Show a grid of recent ePaper editions.', 'sk-epaper-manager'),
    icon: 'grid-view',
    category: 'media',
    keywords: [__('epaper', 'sk-epaper-manager'), __('archive', 'sk-epaper-manager')],
    supports: { html: false },

    edit: function (props) {
      const attrs = props.attributes;

      return el(
        element.Fragment,
        null,
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            { title: __('Archive settings', 'sk-epaper-manager'), initialOpen: true },
            el(RangeControl, {
              label: __('Number of editions', 'sk-epaper-manager'),
              value: attrs.count,
              min: 1,
              max: 50,
              onChange: function (value) {
                props.setAttributes({ count: value || 6 });
              }
            }),
            el(TextControl, {
              label: __('Edition slug', 'sk-epaper-manager'),
              help: __('Leave empty for all editions.', 'sk-epaper-manager'),
              value: attrs.edition,
              onChange: function (value) {
                props.setAttributes({ edition: value });
              }
            }),
            el(TextControl, {
              label: __('Language slug', 'sk-epaper-manager'),
              help: __('Leave empty for all languages.', 'sk-epaper-manager'),
              value: attrs.language,
              onChange: function (value) {
                props.setAttributes({ language: value });
              }
            })
          )
        ),
        el('div', { className: props.className },
          el(ServerSideRender, { block: 'sk/epaper-archive', attributes: attrs })
        )
      );
    },

    save: function () {
      return null; // Rendered server side.
    }
  });
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.blockEditor,
  window.wp.components,
  window.wp.i18n,
  window.wp.data,
  window.wp.serverSideRender
);
