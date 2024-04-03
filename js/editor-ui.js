((Drupal, debounce, dragula, once, $) => {


  const onInput = Drupal.debounce((e) => {
    saveTitle(e);
  }, 250);

  function saveTitle(e) {
    const componentUuid = e.target.closest('[data-type="tabs"][data-uuid]').getAttribute('data-uuid');
    const layoutId = e.target.closest('[data-lpb-id]').getAttribute('data-lpb-id');
    const tabId = e.target.closest('[data-region]').getAttribute('data-region');
    Drupal.ajax({
      url: `${drupalSettings.path.baseUrl}${drupalSettings.path.pathPrefix}mercury-editor-tabs/${layoutId}/edit-title/${componentUuid}/${tabId}`,
      submit: {
        value: e.target.textContent
      },
    }).execute();
  }

  function removeTab(e) {
    const componentUuid = e.target.closest('[data-type="tabs"][data-uuid]').getAttribute('data-uuid');
    const layoutId = e.target.closest('[data-lpb-id]').getAttribute('data-lpb-id');
    const tabId = e.target.closest('[data-region]').getAttribute('data-region');
    Drupal.ajax({
      url: `${drupalSettings.path.baseUrl}${drupalSettings.path.pathPrefix}mercury-editor-tabs/${layoutId}/remove-tab/${componentUuid}/${tabId}`,
      submit: {
        delete: componentUuid
      }
    }).execute();
  }

  function toggleEditTab(el) {
    const controls = el.querySelector('.me-tabs__btn-group');
    controls.style.display = controls.getAttribute('data-me-orig-display');
    const tab = el.querySelector('.c-tabs-group__tab-button');
    if (tab.querySelector('[contenteditable]')) {
      tab.innerHTML = tab.querySelector('span').innerText;
    }
    else {
      controls.setAttribute('data-me-orig-display', getComputedStyle(controls).display);
      controls.style.display = 'none';
      const text = tab.innerText;
      tab.innerHTML = `<span contenteditable="true">${text}</span>`;
      var editable = tab.querySelector('[contenteditable=true]');
      editable.addEventListener('input', onInput, false);
      var range = document.createRange();
      var sel = window.getSelection();
      range.selectNode(editable.childNodes[0]);
      sel.removeAllRanges();
      sel.addRange(range);
      editable.focus();
      editable.addEventListener('blur', (e) => {
        saveTitle(e);
        toggleEditTab(el);
      });
    }
  }

  Drupal.behaviors.mercuryEditorTabsEditorUi = {
    attach: function(context) {

      once('me-tabs-editor-ui', '.lp-builder .c-tabs-group__menu-item').forEach((el) => {
        el.classList.add('me-tabs__ui-container');
        el.insertAdjacentHTML('beforeend', `<div class="me-tabs__btn-group">
          <button class="me-tabs-btn--edit">Edit</button>
          <button class="me-tabs-btn--delete">Delete</button>
        </div>`);
        el.querySelector('.me-tabs-btn--edit').addEventListener('click', (e) => {
          toggleEditTab(el);
        });
        el.querySelector('.me-tabs-btn--delete').addEventListener('click', (e) => {
          if (window.confirm(Drupal.t('Delete this tab and it\'s content? There is no undo.'))) {
            removeTab(e);
          }
        });
      });
      once('me-tabs-editor-ui', '.lp-builder .c-tabs-group__tabs').forEach((el) => {
        el.insertAdjacentHTML('beforeend', `<button class="me-tabs-btn--add" href="">Add Tab</a>`);
        el.querySelector('.me-tabs-btn--add').addEventListener('click', (e) => {
          const layoutId = el.closest('[data-lpb-id]').getAttribute('data-lpb-id');
          const componentUuid = el.closest('[data-type="tabs"][data-uuid]').getAttribute('data-uuid');
          Drupal.ajax({
            url: `${drupalSettings.path.baseUrl}${drupalSettings.path.pathPrefix}mercury-editor-tabs/${layoutId}/add-tab/${componentUuid}`,
            submit: {
              add: layoutId
            }
          }).execute();
        });
      });
      once('me-tabs-editor-ui', '.c-tabs-group__menu').forEach((el) => {
        const $builder = $(el.closest('[data-lpb-id]'));
        const drake = $builder.data('drake');
        if (drake) {
          console.log('add to drake!');
          drake.containers.push(el);
          Drupal.registerLpbMoveError((settings, el, target, source) => {
            if (el.classList.contains('c-tabs-group__menu-item') && target !== source) {
              return "You can't move tabs between tab groups.";
            }
          });
          drake.on('drop', (el, container) => {
            console.log(container);
            if (el.classList.contains('c-tabs-group__menu-item')) {
              setTimeout(() => {
                const layoutId = container.closest('[data-lpb-id]').getAttribute('data-lpb-id');
                const componentUuid = container.closest('[data-type="tabs"][data-uuid]').getAttribute('data-uuid');
                const items = [...container.querySelectorAll('.c-tabs-group__menu-item')];
                const order = items.map((el) => el.getAttribute('data-region'));
                console.log(order);
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
