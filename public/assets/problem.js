(function () {
    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function getInsertBeforeElement(container, y) {
        const elements = [...container.querySelectorAll('.fragment-card:not(.is-dragging)')];
        return elements.find(element => {
            const box = element.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            return offset < 0;
        }) || null;
    }

    ready(function () {
        const arena = document.querySelector('[data-parsons]');
        if (!arena) {
            return;
        }

        const canPlay = arena.dataset.canPlay === 'true';
        const palette = arena.querySelector('[data-fragment-palette]');
        const canvas = arena.querySelector('[data-fragment-canvas]');
        const feedback = arena.querySelector('[data-feedback]');
        const checkButton = arena.querySelector('[data-check]');
        const resetButton = arena.querySelector('[data-reset]');
        const shuffleButton = arena.querySelector('[data-shuffle]');
        const submitUrl = arena.dataset.submitUrl;
        const hint = canvas.querySelector('.drop-hint');
        let dragged = null;

        function setFeedback(message, type) {
            if (!feedback) {
                return;
            }
            feedback.textContent = message;
            feedback.classList.remove('success', 'error');
            if (type) {
                feedback.classList.add(type);
            }
        }

        function syncHint() {
            if (!hint) {
                return;
            }
            const hasFragments = canvas.querySelector('.fragment-card') !== null;
            hint.style.display = hasFragments ? 'none' : '';
        }

        function updateLineNumbers() {
            const paletteCards = palette.querySelectorAll('.fragment-card');
            paletteCards.forEach(function (card) {
                const index = card.querySelector('.line-index');
                if (index) {
                    index.textContent = '⋮';
                }
                card.classList.remove('in-canvas');
            });

            const canvasCards = canvas.querySelectorAll('.fragment-card');
            canvasCards.forEach(function (card, idx) {
                const index = card.querySelector('.line-index');
                if (index) {
                    index.textContent = idx + 1;
                }
                card.classList.add('in-canvas');
            });

            syncHint();
        }

        function resetPuzzle() {
            const cards = [...arena.querySelectorAll('.fragment-card')];
            cards.sort(function (a, b) {
                return Number(a.dataset.originalOrder) - Number(b.dataset.originalOrder);
            }).forEach(function (card) {
                palette.appendChild(card);
            });
            setFeedback('Puzzle reset. Try a fresh arrangement!', null);
            updateLineNumbers();
        }

        function shufflePalette() {
            const cards = [...palette.querySelectorAll('.fragment-card')];
            for (let i = cards.length - 1; i > 0; i -= 1) {
                const j = Math.floor(Math.random() * (i + 1));
                [cards[i], cards[j]] = [cards[j], cards[i]];
            }
            cards.forEach(function (card) {
                palette.appendChild(card);
            });
            updateLineNumbers();
            setFeedback('Fragments reshuffled for another attempt.', null);
        }

        function attachFragmentEvents(card) {
            if (canPlay) {
                card.setAttribute('draggable', 'true');
            }

            card.addEventListener('dragstart', function () {
                if (!canPlay) {
                    return;
                }
                dragged = card;
                card.classList.add('is-dragging');
            });

            card.addEventListener('dragend', function () {
                if (!canPlay) {
                    return;
                }
                card.classList.remove('is-dragging');
                dragged = null;
            });

            card.addEventListener('dblclick', function () {
                if (!canPlay) {
                    return;
                }
                if (card.parentElement === palette) {
                    canvas.appendChild(card);
                } else {
                    palette.appendChild(card);
                }
                updateLineNumbers();
            });

            card.addEventListener('keydown', function (event) {
                if (!canPlay) {
                    return;
                }
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    if (card.parentElement === palette) {
                        canvas.appendChild(card);
                    } else {
                        palette.appendChild(card);
                    }
                    updateLineNumbers();
                }
            });
        }

        arena.querySelectorAll('.fragment-card').forEach(function (card) {
            attachFragmentEvents(card);
        });

        function handleDrop(event, container) {
            if (!canPlay) {
                return;
            }
            event.preventDefault();
            const target = getInsertBeforeElement(container, event.clientY);
            if (dragged) {
                if (target) {
                    container.insertBefore(dragged, target);
                } else {
                    container.appendChild(dragged);
                }
                updateLineNumbers();
            }
            container.classList.remove('drop-active');
        }

        [palette, canvas].forEach(function (container) {
            container.addEventListener('dragover', function (event) {
                if (!canPlay) {
                    return;
                }
                event.preventDefault();
            });
            container.addEventListener('dragenter', function () {
                if (!canPlay) {
                    return;
                }
                container.classList.add('drop-active');
            });
            container.addEventListener('dragleave', function (event) {
                if (!canPlay || container.contains(event.relatedTarget)) {
                    return;
                }
                container.classList.remove('drop-active');
            });
            container.addEventListener('drop', function (event) {
                handleDrop(event, container);
            });
        });

        if (resetButton) {
            resetButton.addEventListener('click', function () {
                if (!canPlay) {
                    return;
                }
                resetPuzzle();
            });
        }

        if (shuffleButton) {
            shuffleButton.addEventListener('click', function () {
                if (!canPlay) {
                    return;
                }
                shufflePalette();
            });
        }

        if (checkButton) {
            checkButton.addEventListener('click', function () {
                if (!canPlay) {
                    return;
                }
                const sequence = [...canvas.querySelectorAll('.fragment-card')].map(function (card) {
                    return Number(card.dataset.fragmentId);
                });

                setFeedback('Checking arrangement…', null);

                fetch(submitUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ fragments: sequence }),
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Submission failed');
                        }
                        return response.json();
                    })
                    .then(function (payload) {
                        if (payload.success) {
                            arena.dataset.solved = 'true';
                            setFeedback(payload.message + (payload.alreadySolved ? '' : ' +' + payload.xp + ' XP!'), 'success');
                        } else {
                            setFeedback(payload.message || 'Not quite right yet.', 'error');
                        }
                    })
                    .catch(function () {
                        setFeedback('Connection error. Please try again.', 'error');
                    });
            });
        }

        updateLineNumbers();
    });
})();
