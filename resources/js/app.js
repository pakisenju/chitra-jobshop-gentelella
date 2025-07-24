// CSS
import '../gentella/css/bootstrap.min.css';
import '../gentella/css/font-awesome.min.css';
import '../gentella/css/nprogress.css';
import '../gentella/css/custom.min.css';

// JS
import '../gentella/js/jquery.min.js';
import '../gentella/js/bootstrap.min.js';
import '../gentella/js/fastclick.js';
import '../gentella/js/nprogress.js';
import '../gentella/js/fullcalendar.min.js';
import '../gentella/js/custom.min.js';

console.log('app.js loaded.');

// Verify jQuery and Bootstrap
if (typeof jQuery !== 'undefined') {
    console.log('jQuery is loaded.');
    if (typeof jQuery.fn.modal !== 'undefined') {
        console.log('Bootstrap modal JS is loaded.');
    } else {
        console.log('Bootstrap modal JS NOT loaded.');
    }
} else {
    console.log('jQuery NOT loaded.');
}

// Verify Livewire
if (typeof Livewire !== 'undefined') {
    console.log('Livewire is loaded.');
} else {
    console.log('Livewire NOT loaded.');
}

// Verify FullCalendar
if (typeof FullCalendar !== 'undefined') {
    console.log('FullCalendar is loaded.');
} else {
    console.log('FullCalendar NOT loaded.');
}
