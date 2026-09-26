/**
 * Swagger UI, bundled locally.
 *
 * The API reference is documentation — a page that fails to load because a
 * CDN is unreachable is a broken product, not a cosmetic problem.
 */
import SwaggerUIBundle from 'swagger-ui-dist/swagger-ui-bundle.js';
import 'swagger-ui-dist/swagger-ui.css';

window.SwaggerUIBundle = SwaggerUIBundle;

window.renderLinduSwagger = function (domId, url) {
    if (!domId || !url) {
        return;
    }
    SwaggerUIBundle({ url: url, dom_id: domId, presets: [SwaggerUIBundle.presets.apis] });
};
