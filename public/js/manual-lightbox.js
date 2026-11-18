(function () {
    function ready(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn, { once: true });
            return;
        }
        fn();
    }

    ready(function () {
        var manualImages = Array.prototype.slice.call(document.querySelectorAll('.shot--media img'));

        function buildManualFallbackCandidates(src) {
            if (!src) {
                return [];
            }

            var parser = document.createElement('a');
            parser.href = src;
            var path = parser.pathname || src;
            var fileName = path.split('/').pop() || '';
            if (!fileName) {
                return [];
            }

            var prefixes = ['/img/manual/', '/public/img/manual/'];
            var candidates = [];
            var seen = Object.create(null);

            var pushCandidate = function (candidate) {
                if (!candidate || seen[candidate]) {
                    return;
                }
                seen[candidate] = true;
                candidates.push(candidate);
            };

            for (var i = 0; i < prefixes.length; i += 1) {
                pushCandidate(prefixes[i] + fileName);
            }

            // Backward compatibility for environments still using 04-booking-step2 naming.
            if (fileName === '03-booking-step2.png' || fileName === '04-booking-step2.png') {
                var altFileName = fileName === '03-booking-step2.png'
                    ? '04-booking-step2.png'
                    : '03-booking-step2.png';
                for (var j = 0; j < prefixes.length; j += 1) {
                    pushCandidate(prefixes[j] + altFileName);
                }
            }

            return candidates;
        }

        function parseFallbackList(image) {
            var raw = (image.getAttribute('data-fallback-srcs') || '').trim();
            if (raw === '') {
                return buildManualFallbackCandidates(image.getAttribute('src') || '');
            }

            return raw
                .split(',')
                .map(function (part) {
                    return part.trim();
                })
                .filter(function (part) {
                    return part !== '';
                });
        }

        function attachManualImageFallback(image) {
            var fallbacks = parseFallbackList(image);
            if (!fallbacks.length) {
                return;
            }

            var index = 0;
            image.addEventListener('error', function () {
                while (index < fallbacks.length) {
                    var candidate = fallbacks[index];
                    index += 1;
                    if (!candidate) {
                        continue;
                    }
                    if (image.getAttribute('src') === candidate) {
                        continue;
                    }
                    image.setAttribute('src', candidate);
                    return;
                }
            });
        }

        manualImages.forEach(function (image) {
            attachManualImageFallback(image);
        });

        var nodes = Array.prototype.slice.call(
            document.querySelectorAll('.shot--media img, .issues-attachment-image-wrap')
        );
        if (nodes.length === 0) {
            return;
        }

        var overlay = document.createElement('div');
        overlay.className = 'manual-lightbox';
        overlay.setAttribute('aria-hidden', 'true');

        var zoomedImage = document.createElement('img');
        zoomedImage.className = 'manual-lightbox__image';
        zoomedImage.alt = '';

        var closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'manual-lightbox__close';
        closeButton.textContent = 'Close';
        closeButton.setAttribute('aria-label', 'Close image preview');

        overlay.appendChild(closeButton);
        overlay.appendChild(zoomedImage);
        document.body.appendChild(overlay);

        function closeLightbox() {
            overlay.classList.remove('is-open');
            overlay.setAttribute('aria-hidden', 'true');
            zoomedImage.removeAttribute('src');
            zoomedImage.alt = '';
            document.body.style.overflow = '';
        }

        function resolveSource(node) {
            if (!node) {
                return '';
            }

            if (node.tagName === 'IMG') {
                var dataSrc = node.getAttribute('data-lightbox-src') || '';
                return dataSrc || node.currentSrc || node.getAttribute('src') || '';
            }

            var nodeDataSrc = node.getAttribute('data-lightbox-src') || '';
            if (nodeDataSrc !== '') {
                return nodeDataSrc;
            }

            var href = node.getAttribute('href') || '';
            if (href !== '') {
                return href;
            }

            var childImage = node.querySelector('img');
            if (!childImage) {
                return '';
            }

            var childDataSrc = childImage.getAttribute('data-lightbox-src') || '';
            return childDataSrc || childImage.currentSrc || childImage.getAttribute('src') || '';
        }

        function resolveAlt(node) {
            if (!node) {
                return '';
            }

            if (node.tagName === 'IMG') {
                return node.getAttribute('alt') || '';
            }

            var childImage = node.querySelector('img');
            return childImage ? (childImage.getAttribute('alt') || '') : '';
        }

        function openLightbox(node) {
            var src = resolveSource(node);
            if (src === '') {
                return;
            }

            zoomedImage.setAttribute('src', src);
            zoomedImage.alt = resolveAlt(node);
            overlay.classList.add('is-open');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        nodes.forEach(function (node) {
            node.classList.add('is-zoomable');
            node.setAttribute('tabindex', '0');
            node.setAttribute('role', 'button');
            node.setAttribute('aria-label', (resolveAlt(node) || 'Image') + ' (click to zoom)');

            node.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                openLightbox(node);
            });

            node.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openLightbox(node);
                }
            });
        });

        closeButton.addEventListener('click', closeLightbox);

        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) {
                closeLightbox();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && overlay.classList.contains('is-open')) {
                closeLightbox();
            }
        });
    });
})();
