(function (window, document) {
  'use strict';

  var config = window.transitiAdmin || {};
  var maxLength = Number(config.rubricaMaxCombinedLength || 0);

  function decodeHtml(value) {
    var textarea = document.createElement('textarea');
    textarea.innerHTML = value;
    return textarea.value;
  }

  function normalizeText(value) {
    return String(value || '')
      .replace(/\[[^\]]*]/g, '')
      .replace(/<[^>]+>/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
  }

  function getExcerptText() {
    var excerptField = document.getElementById('excerpt');
    return excerptField ? normalizeText(excerptField.value) : '';
  }

  function getContentText() {
    if (window.tinymce && window.tinymce.get('content')) {
      return normalizeText(decodeHtml(window.tinymce.get('content').getContent({ format: 'raw' })));
    }

    var contentField = document.getElementById('content');
    return contentField ? normalizeText(contentField.value) : '';
  }

  function getCombinedLength() {
    var combined = (getExcerptText() + ' ' + getContentText()).trim();
    return combined.length;
  }

  function getCounterContainer() {
    return document.getElementById('wp-word-count') || document.querySelector('.word-count');
  }

  function getOrCreateCounterNode() {
    var container = getCounterContainer();
    if (!container) {
      return null;
    }

    var existing = document.getElementById('transiti-rubrica-char-count');
    if (existing) {
      return existing;
    }

    var node = document.createElement('span');
    node.id = 'transiti-rubrica-char-count';
    node.className = 'transiti-rubrica-char-count';
    container.appendChild(document.createTextNode(' | '));
    container.appendChild(node);

    return node;
  }

  function renderCount() {
    var node = getOrCreateCounterNode();
    if (!node) {
      return;
    }

    var currentLength = getCombinedLength();
    var label = String(config.rubricaCharactersLabel || 'Caratteri');
    node.textContent = label + ': ' + currentLength + (maxLength > 0 ? '/' + maxLength : '');
    node.classList.toggle('is-over-limit', maxLength > 0 && currentLength > maxLength);
  }

  function bindListeners() {
    var excerptField = document.getElementById('excerpt');
    var contentField = document.getElementById('content');

    if (excerptField) {
      excerptField.addEventListener('input', renderCount);
      excerptField.addEventListener('change', renderCount);
    }

    if (contentField) {
      contentField.addEventListener('input', renderCount);
      contentField.addEventListener('change', renderCount);
    }

    if (window.tinymce) {
      var attachEditorListeners = function () {
        var editor = window.tinymce.get('content');
        if (!editor) {
          return false;
        }

        editor.on('input keyup change SetContent Undo Redo', renderCount);
        return true;
      };

      if (!attachEditorListeners()) {
        window.tinymce.on('AddEditor', function (event) {
          if (event.editor && event.editor.id === 'content') {
            event.editor.on('input keyup change SetContent Undo Redo', renderCount);
            renderCount();
          }
        });
      }
    }
  }

  function init() {
    bindListeners();
    renderCount();
    window.setInterval(renderCount, 1000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})(window, document);
