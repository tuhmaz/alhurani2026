import toastr from 'toastr';

// Expose globally (for inline usage in Blade/views)
try {
  window.toastr = toastr;
} catch (e) {}

// Sensible defaults aligned with the theme
const defaultOptions = {
  closeButton: true,
  progressBar: true,
  newestOnTop: true,
  preventDuplicates: true,
  timeOut: 3000,
  extendedTimeOut: 1500,
  positionClass: 'toast-top-right',
  showMethod: 'fadeIn',
  hideMethod: 'fadeOut'
};

Object.assign(toastr.options, defaultOptions);

export { toastr };
