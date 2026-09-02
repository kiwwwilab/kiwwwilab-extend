(function (blocks, blockEditor, components, element) {
  var el = element.createElement;
  var registerBlockType = blocks.registerBlockType;
  var MediaUpload = blockEditor.MediaUpload;
  var MediaUploadCheck = blockEditor.MediaUploadCheck;
  var Button = components.Button;
  var useState = element.useState;

  registerBlockType('kiwwwilab/image-compare', {
    edit: function (props) {
      var attributes = props.attributes;
      var setAttributes = props.setAttributes;
      var posState = useState(attributes.sliderPosition);
      var sliderPos = posState[0];
      var setSliderPos = posState[1];

      function onSelectBefore(media) {
        setAttributes({ beforeImage: media.url });
      }

      function onSelectAfter(media) {
        setAttributes({ afterImage: media.url });
      }

      function handleInput(e) {
        var val = parseInt(e.target.value, 10);
        setSliderPos(val);
        setAttributes({ sliderPosition: val });
      }

      return el(
        'div',
        { className: props.className + ' kiwwwilab-ic-container' },
        el(
          'div',
          { className: 'kiwwwilab-ic-controls' },
          el(
            MediaUploadCheck,
            null,
            el(MediaUpload, {
              onSelect: onSelectBefore,
              allowedTypes: ['image'],
              value: attributes.beforeImage,
              render: function (obj) {
                return el(
                  Button,
                  { isSecondary: true, onClick: obj.open },
                  attributes.beforeImage ? 'Cambiar Imagen "Antes"' : 'Seleccionar "Antes"'
                );
              }
            })
          ),
          el(
            MediaUploadCheck,
            null,
            el(MediaUpload, {
              onSelect: onSelectAfter,
              allowedTypes: ['image'],
              value: attributes.afterImage,
              render: function (obj) {
                return el(
                  Button,
                  { isSecondary: true, onClick: obj.open },
                  attributes.afterImage ? 'Cambiar Imagen "Después"' : 'Seleccionar "Después"'
                );
              }
            })
          )
        ),
        attributes.beforeImage && attributes.afterImage
          ? el(
              'div',
              { className: 'kiwwwilab-ic-wrapper' },
              el('img', { src: attributes.afterImage, alt: 'Después', className: 'kiwwwilab-ic-img kiwwwilab-ic-after' }),
              el(
                'div',
                { className: 'kiwwwilab-ic-before-wrap', style: { width: sliderPos + '%' } },
                el('img', { src: attributes.beforeImage, alt: 'Antes', className: 'kiwwwilab-ic-img kiwwwilab-ic-before' })
              ),
              el(
                'div',
                { className: 'kiwwwilab-ic-slide-button', style: { left: sliderPos + '%' } }
              ),
              el('input', {
                type: 'range',
                min: 0,
                max: 100,
                value: sliderPos,
                className: 'kiwwwilab-ic-slider',
                onInput: handleInput
              })
            )
          : el('p', { className: 'kiwwwilab-ic-placeholder' }, 'Adjunta ambas imágenes para ver la previsualización del comparador.')
      );
    },

    save: function (props) {
      var attributes = props.attributes;
      var pos = attributes.sliderPosition || 50;

      if (!attributes.beforeImage || !attributes.afterImage) {
        return null;
      }

      return el(
        'div',
        { className: 'kiwwwilab-ic-wrapper', 'data-pos': pos },
        el('img', { src: attributes.afterImage, alt: 'Después', className: 'kiwwwilab-ic-img kiwwwilab-ic-after' }),
        el(
          'div',
          { className: 'kiwwwilab-ic-before-wrap', style: { width: pos + '%' } },
          el('img', { src: attributes.beforeImage, alt: 'Antes', className: 'kiwwwilab-ic-img kiwwwilab-ic-before' })
        ),
        el(
          'div',
          { className: 'kiwwwilab-ic-slide-button', style: { left: pos + '%' } }
        ),
        el('input', {
          type: 'range',
          min: 0,
          max: 100,
          value: pos,
          className: 'kiwwwilab-ic-slider',
          ariaLabel: 'Control de comparación de imagen'
        })
      );
    }
  });
})(window.wp.blocks, window.wp.blockEditor, window.wp.components, window.wp.element);
