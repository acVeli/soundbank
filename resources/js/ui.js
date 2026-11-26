/**
 * Petits comportements d'interface : panier et favoris sans rechargement,
 * confirmations, menus mobiles, sélection groupée.
 */
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

export function toast(message, isError = false) {
    let el = document.querySelector('.toast');

    if (!el) {
        el = document.createElement('div');
        el.className = 'toast';
        el.setAttribute('role', 'status');
        document.body.appendChild(el);
    }

    el.textContent = message;
    el.classList.toggle('is-error', isError);
    el.classList.add('is-visible');
    clearTimeout(el._timer);
    el._timer = setTimeout(() => el.classList.remove('is-visible'), 3200);
}

export function initUi() {
    document.addEventListener('submit', async (event) => {
        const form = event.target;

        if (form.matches('[data-confirm]') && !window.confirm(form.dataset.confirm)) {
            event.preventDefault();

            return;
        }

        if (form.matches('[data-ajax-cart]')) {
            event.preventDefault();
            const button = form.querySelector('button');
            button?.setAttribute('aria-disabled', 'true');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                });

                if (response.status === 419) {
                    window.location.reload();

                    return;
                }

                const data = await response.json();
                toast(data.message, !data.ok);

                if (data.ok) {
                    document.querySelectorAll('.cart-count').forEach((el) => {
                        el.textContent = data.count;
                        el.dataset.count = data.count;
                    });

                    if (button) {
                        button.innerHTML = button.dataset.added || 'Au panier ✓';
                        button.classList.add('btn-ghost');
                        button.classList.remove('btn-primary');
                    }

                    return;
                }
            } catch {
                toast('Impossible d’ajouter au panier pour le moment.', true);
            }

            button?.removeAttribute('aria-disabled');
        }

        if (form.matches('[data-ajax-fav]')) {
            event.preventDefault();
            const button = form.querySelector('button');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
                });

                if (response.status === 401) {
                    window.location.href = form.dataset.login;

                    return;
                }

                const data = await response.json();
                button?.classList.toggle('is-on', data.favorite);
                toast(data.favorite ? 'Ajouté à vos favoris.' : 'Retiré de vos favoris.');
            } catch {
                toast('Action impossible pour le moment.', true);
            }
        }
    });

    document.querySelectorAll('[data-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            document.querySelector(button.dataset.toggle)?.classList.toggle('is-open');
        });
    });

    document.querySelectorAll('[data-select-all]').forEach((master) => {
        const scope = document.querySelector(master.dataset.selectAll);
        const boxes = () => scope?.querySelectorAll('input[type="checkbox"][name="ids[]"]') ?? [];
        const counter = document.querySelector('[data-selected-count]');
        const update = () => {
            const n = [...boxes()].filter((box) => box.checked).length;

            if (counter) {
                counter.textContent = n;
            }
        };

        master.addEventListener('change', () => {
            boxes().forEach((box) => {
                box.checked = master.checked;
            });
            update();
        });

        scope?.addEventListener('change', update);
    });

    document.querySelectorAll('[data-show-when]').forEach((el) => {
        const [name, value] = el.dataset.showWhen.split('=');
        const inputs = document.querySelectorAll(`[name="${name}"]`);
        const refresh = () => {
            const checked = [...inputs].find((input) => (input.type === 'radio' ? input.checked : true));
            el.hidden = (checked?.value ?? '') !== value;
        };

        inputs.forEach((input) => input.addEventListener('change', refresh));
        refresh();
    });

    document.querySelectorAll('details.menu').forEach((menu) => {
        document.addEventListener('click', (event) => {
            if (!menu.contains(event.target)) {
                menu.removeAttribute('open');
            }
        });
    });

    document.querySelectorAll('[data-autosubmit]').forEach((el) => {
        el.addEventListener('change', () => el.form?.requestSubmit());
    });
}
