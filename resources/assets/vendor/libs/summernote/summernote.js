

import 'summernote/dist/summernote-bs5.js';

// Module-scope references and helpers
const $ = typeof window !== 'undefined' ? (window.jQuery || window.$) : undefined;

// Sensible default toolbar matching a typical CMS use-case
const defaultToolbar = [
  ['style', ['style']],
  ['font', ['bold', 'italic', 'underline', 'clear']],
  ['fontname', ['fontname']],
  ['fontsize', ['fontsize']],
  ['color', ['color']],
  ['para', ['ul', 'ol', 'paragraph']],
  ['insert', ['link', 'picture', 'video', 'hr']],
  ['table', ['table']],
  ['view', ['fullscreen', 'codeview', 'help']]
];

// Helper: initialize Summernote on a selector with defaults merged with user options
const initSummernote = (selector, options = {}) => {
  if (!$ || !$.fn || !$.fn.summernote) {
    // Gracefully no-op if summernote is not available
    console.warn('Summernote is not available: ensure jQuery and summernote-lite are loaded.');
    return undefined;
  }
  const defaults = {
    tabsize: 2,
    height: 300,
    toolbar: defaultToolbar,
    dialogsInBody: true
  };
  return $(selector).summernote({ ...defaults, ...options });
};

// Expose on window when available (without affecting module exports)
try {
  if ($ && $.fn && $.fn.summernote) {
    window.Summernote = $.fn.summernote;
    window.initSummernote = initSummernote;
  }
} catch (e) {
  // No-op
}

// Module exports
const summernote = $ && $.fn ? $.fn.summernote : undefined;
export { initSummernote, summernote };
export default summernote;
