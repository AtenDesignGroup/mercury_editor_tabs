((Drupal, debounce, dragula, once, $) => {


  const onInput = Drupal.debounce((e) => {
    saveLabel(e);
  }, 250);

  function saveLabel() {
    const container = document.querySelector('[data-editable-label]');
    const componentUuid = container.closest('[data-uuid]').getAttribute('data-uuid');
    const layoutId = container.closest('[data-lpb-id]').getAttribute('data-lpb-id');
    const regionId = container.closest('[data-region-label]').getAttribute('data-region-label');
    Drupal.ajax({
      url: `${drupalSettings.path.baseUrl}${drupalSettings.path.pathPrefix}mercury-editor-tabs/${layoutId}/edit-title/${componentUuid}/${regionId}`,
      submit: {
        label: container.querySelector('[contenteditable]').textContent
      },
    }).execute();
  }

  /**
   * Removes a region from the layout.
   *
   * @param {Event} e The event object.
   */
  function removeRegion(e) {
    const componentUuid = e.target.closest('[data-uuid]').getAttribute('data-uuid');
    const layoutId = e.target.closest('[data-lpb-id]').getAttribute('data-lpb-id');
    const regionId = e.target.closest('[data-region-label]').getAttribute('data-region-label');
    Drupal.ajax({
      url: `${drupalSettings.path.baseUrl}${drupalSettings.path.pathPrefix}mercury-editor-tabs/${layoutId}/remove-tab/${componentUuid}/${regionId}`,
      submit: {
        delete: componentUuid
      }
    }).execute();
  }

  /**
   * Saves the title of a tab.
   *
   * @param {Event} e The event that triggered the save.
   */
  function onDocumentClick(e) {
    if (!e.target.hasAttribute('contenteditable')) {
      document.querySelector('[contenteditable]').blur();
    }
  }

  /**
   * Disables label editing for a region.
   */
  function stopEditor(e) {
    saveLabel();
    const container = document.querySelector('[data-editable-label]');
    container.closest('[data-me-accordion-tabs-region-label]').querySelector('.me-accordion-tabs__btn-group').style.display = 'block';
    const btn = container.closest('[data-me-accordion-tabs-region-label]').querySelector('button[aria-controls]');
    const textNode = btn.querySelector('.text') || btn;
    const text = container.querySelector('[contenteditable]').innerText;
    textNode.innerText = text;
    btn.style.display = btn.getAttribute('data-original-style-display');
    btn.removeAttribute('data-original-style-display');
    container.remove();
    document.removeEventListener('mousedown', onDocumentClick);
  }

  /**
   * Enables label editing for a region.
   *
   * @param {Element} el  The element to enable the edit mode on.
   */
  function startEditor(el) {
    el.querySelector('.me-accordion-tabs__btn-group').style.display = 'none';
    const btn = el.querySelector('button[aria-controls]');
    const classes = btn.classList;
    const container = document.createElement('div');
    container.classList = classes;
    container.setAttribute('data-editable-label', true);
    const editableSpan = document.createElement('span');
    editableSpan.contentEditable = true;
    editableSpan.addEventListener('input', onInput);
    editableSpan.innerText = btn.innerText;
    container.appendChild(editableSpan);
    btn.setAttribute('data-original-style-display', btn.style.display);
    btn.style.display = 'none';
    btn.insertAdjacentElement('beforebegin', container);
    var range = document.createRange();
    var sel = window.getSelection();
    range.selectNode(editableSpan.childNodes[0]);
    sel.removeAllRanges();
    sel.addRange(range);
    editableSpan.focus();
    document.addEventListener('mousedown', onDocumentClick);
    editableSpan.addEventListener('blur', stopEditor);
  }

  Drupal.behaviors.mercuryEditorTabsEditorUi = {
    attach: function(context) {
      once('me-tabs-editor-ui', '[data-me-accordion-tabs-label-group]').forEach((labelGroup) => {
        const layout_id = labelGroup.closest('[data-layout]').getAttribute('data-layout');
        const labels = [...labelGroup.querySelectorAll('[data-me-accordion-tabs-region-label]')]
          .filter((label) => label.closest('[data-me-accordion-tabs-label-group]') === labelGroup);
        labels.forEach((label) => {
          label.classList.add('me-accordion-tabs__ui-container');
          label.insertAdjacentHTML('beforeend', `<div class="me-accordion-tabs__btn-group">
            <button class="me-accordion-tabs-btn--edit">Edit</button>
            <button class="me-accordion-tabs-btn--delete">Delete</button>
          </div>`);
          label.querySelector('.me-accordion-tabs-btn--edit').addEventListener('click', (e) => {
            startEditor(label);
          });
          label.querySelector('.me-accordion-tabs-btn--delete').addEventListener('click', (e) => {
            if (window.confirm(Drupal.t('Delete this tab and it\'s content? There is no undo.'))) {
              removeRegion(e);
            }
          });
        });
        if (labels.length < 2) {
          [...labelGroup.querySelectorAll('.me-accordion-tabs-btn--delete')].forEach((btn) => btn.remove());
        }

        const btnLabel = layout_id === 'me_tabs'
          ? Drupal.t('Add Tab')
          : Drupal.t('Add Accordion Item');

        labelGroup.insertAdjacentHTML('beforeend', `<button class="me-accordion-tabs-btn--add" href="">${btnLabel}</a>`);
        labelGroup.parentNode.querySelector('.me-accordion-tabs-btn--add').addEventListener('click', (e) => {
          const layoutId = labelGroup.closest('[data-lpb-id]').getAttribute('data-lpb-id');
          const componentUuid = labelGroup.closest('[data-uuid]').getAttribute('data-uuid');
          Drupal.ajax({
            url: `${drupalSettings.path.baseUrl}${drupalSettings.path.pathPrefix}mercury-editor-tabs/${layoutId}/add-region/${componentUuid}`,
            submit: {
              add: layoutId
            }
          }).execute();
        });

        const $builder = $(labelGroup.closest('[data-lpb-id]'));
        const drake = $builder.data('drake');
        if (drake) {
          drake.containers.push(labelGroup.querySelector('[data-me-accordion-tabs-draggable]').parentNode);
          Drupal.registerLpbMoveError((settings, el, target, source) => {
            if (el.hasAttribute('data-me-accordion-tabs-draggable') && target !== source) {
              return "You can't move tabs between tab groups.";
            }
          });
          drake.on('drop', (el, container) => {
            if (el.hasAttribute('data-me-accordion-tabs-draggable')) {
              setTimeout(() => {
                const layoutId = container.closest('[data-lpb-id]').getAttribute('data-lpb-id');
                const componentUuid = container.closest('[data-uuid]').getAttribute('data-uuid');
                const labels = [...container.querySelectorAll('[data-me-accordion-tabs-region-label]')];
                const order = labels.map((el) => el.getAttribute('data-me-accordion-tabs-region-label'));
                Drupal.ajax({
                  url: `${drupalSettings.path.baseUrl}${drupalSettings.path.pathPrefix}mercury-editor-tabs/${layoutId}/reorder/${componentUuid}`,
                  submit: {
                    order: JSON.stringify(order)
                  }
                }).execute();
              }, 200);
            }
          });
        }
      });
    }
  }

})(Drupal, Drupal.debounce, dragula, once, jQuery);
