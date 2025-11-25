// Entry point for Adesione Terapie module.
// Intended to wire api, state, ui, events, logic, and signature layers.

import * as api from './api.js';
import * as state from './state.js';
import * as utils from './utils.js';

export { api, state, utils };

export function initializeAdesioneTerapieModule() {
    return { api, state, utils };
}
