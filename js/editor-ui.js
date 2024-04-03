((Drupal, debounce, once) => {


  function onInput(e) {
    debounce(saveTitle, 500)(e);
  }

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
    const tab = el.querySelector('.c-tabs-group__tab-button');
    if (tab.querySelector('[contenteditable]')) {
      tab.innerHTML = tab.querySelector('span').innerText;
    }
    else {
      const text = tab.innerText;
      tab.innerHTML = `<span contenteditable="true">${text}</span>`;
      var el = tab.querySelector('[contenteditable=true]');
      el.addEventListener('input', onInput, false);
      var range = document.createRange();
      var sel = window.getSelection();
      range.selectNode(el.childNodes[0]);
      sel.removeAllRanges();
      sel.addRange(range);
      el.focus();
    }
  }

  Drupal.behaviors.mercuryEditorTabsEditorUi = {
    attach: function(context) {

      once('me-tabs-editor-ui', '.lp-builder .c-tabs-group__menu-item').forEach((el) => {
        el.insertAdjacentHTML('beforeend', `<div class="me-tabs--btn-group">
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
      once('me-tabs-editor-ui', '.lp-builder .c-tabs-group__menu').forEach((el) => {
        el.insertAdjacentHTML('beforeend', `<li class="me-tabs--new-tab"><button href="">Add Tab</a></button>`);
        el.querySelector('.me-tabs--new-tab').addEventListener('click', (e) => {
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
      // once('me-tabs-edit-btns', '.me-tabs-btn--edit').forEach((el) => {
      //   el.addEventListener('click', (e) => {
      //     toggleEditTab(el.closest('.c-tabs-group__menu-item'));
      //   });
      // });

    }
  }

})(Drupal, Drupal.debounce, once);
