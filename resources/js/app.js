/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Svelte and other libraries.
 */

require('./bootstrap');

// Materialize
// adds the most bloat so avoid if you can
// require('materialize-css');

import App from './_layout.svelte';
const app = new App({
    target: document.body
});
window.app = app;
export default app;
