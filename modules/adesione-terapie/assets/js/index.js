// Entry point for Adesione Terapie module.
// Wires api, state, ui, events, logic, and signature layers.

import * as api from './api.js';
import * as state from './state.js';
import * as utils from './utils.js';
import * as ui from './ui.js';
import * as events from './events.js';
import * as logic from './logic.js';
import * as signature from './signature.js';

export { api, state, utils, ui, events, logic, signature };

export function initializeAdesioneTerapieModule({ routesBase, csrfToken, moduleRoot }) {
    const dom = events.buildDomReferences(moduleRoot || document);
    events.initializeEvents({ routesBase, csrfToken, dom });
}
