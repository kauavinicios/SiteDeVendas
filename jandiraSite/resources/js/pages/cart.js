document.addEventListener('DOMContentLoaded', () => {
    initCart();
});


function initCart() {
    const cartItems = document.querySelectorAll('[data-cart-item]');

    if (!cartItems.length) {
        return;
    }

    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    cartItems.forEach((cartItem) => {
        initCartItem(cartItem, csrfToken);
    });
}


function initCartItem(cartItem, csrfToken) {
    const quantityInput = cartItem.querySelector('[data-cart-quantity]');

    const decreaseButton = cartItem.querySelector(
        '[data-action="decrease"]'
    );

    const increaseButton = cartItem.querySelector(
        '[data-action="increase"]'
    );

    const removeButton = cartItem.querySelector(
        '[data-action="remove"]'
    );

    if (!quantityInput) {
        return;
    }


    /* =====================================================
       AUMENTAR QUANTIDADE
       ===================================================== */

    increaseButton?.addEventListener('click', async () => {
        const currentQuantity = Number(quantityInput.value);
        const maxStock = Number(quantityInput.max);

        if (currentQuantity >= maxStock) {
            return;
        }

        const newQuantity = currentQuantity + 1;

        await updateQuantity(
            cartItem,
            quantityInput,
            newQuantity,
            csrfToken
        );
    });


    /* =====================================================
       DIMINUIR QUANTIDADE
       ===================================================== */

    decreaseButton?.addEventListener('click', async () => {
        const currentQuantity = Number(quantityInput.value);

        /*
         * Se estiver em 1, diminuir significa remover.
         */
        if (currentQuantity <= 1) {
            await removeCartItem(cartItem, csrfToken);

            return;
        }

        const newQuantity = currentQuantity - 1;

        await updateQuantity(
            cartItem,
            quantityInput,
            newQuantity,
            csrfToken
        );
    });


    /* =====================================================
       QUANTIDADE DIGITADA MANUALMENTE
       ===================================================== */

    quantityInput.addEventListener('change', async () => {
        let quantity = Number(quantityInput.value);

        const min = Number(quantityInput.min) || 1;
        const max = Number(quantityInput.max);

        /*
         * Proteção para campos vazios ou valores inválidos.
         */
        if (!Number.isInteger(quantity)) {
            quantity = min;
        }

        if (quantity < min) {
            quantity = min;
        }

        if (quantity > max) {
            quantity = max;
        }

        quantityInput.value = quantity;

        await updateQuantity(
            cartItem,
            quantityInput,
            quantity,
            csrfToken
        );
    });


    /* =====================================================
       REMOVER ITEM
       ===================================================== */

    removeButton?.addEventListener('click', async () => {
        await removeCartItem(cartItem, csrfToken);
    });


    updateQuantityButtons(cartItem);
}


/* =========================================================
   ATUALIZAR QUANTIDADE
   ========================================================= */

async function updateQuantity(
    cartItem,
    quantityInput,
    quantity,
    csrfToken
) {
    const updateUrl = cartItem.dataset.updateUrl;

    if (!updateUrl) {
        return;
    }

    const previousQuantity = Number(quantityInput.value);

    setItemLoading(cartItem, true);

    try {
        const response = await fetch(updateUrl, {
            method: 'PATCH',

            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },

            body: JSON.stringify({
                quantity: quantity,
            }),
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || 'Não foi possível atualizar o carrinho.'
            );
        }

        /*
         * Quantidade confirmada pelo Laravel.
         */
        quantityInput.value = data.item.quantity;

        /*
         * Atualiza estoque máximo caso o backend
         * devolva um valor mais recente.
         */
        if (data.item.stock !== undefined) {
            quantityInput.max = data.item.stock;
        }

        updateItemSubtotal(
            cartItem,
            data.item.subtotal
        );

        updateCartSummary(data.cart);

        updateQuantityButtons(cartItem);

    } catch (error) {
        quantityInput.value = previousQuantity;

        showCartError(error.message);

    } finally {
        setItemLoading(cartItem, false);
    }
}


/* =========================================================
   REMOVER ITEM
   ========================================================= */

async function removeCartItem(cartItem, csrfToken) {
    const deleteUrl = cartItem.dataset.deleteUrl;

    if (!deleteUrl) {
        return;
    }

    setItemLoading(cartItem, true);

    try {
        const response = await fetch(deleteUrl, {
            method: 'DELETE',

            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(
                data.message || 'Não foi possível remover o produto.'
            );
        }

        updateCartSummary(data.cart);

        cartItem.classList.remove('is-loading');
        cartItem.classList.add('is-removing');

        window.setTimeout(() => {
            cartItem.remove();

            checkEmptyCart();
        }, 200);

    } catch (error) {
        setItemLoading(cartItem, false);

        showCartError(error.message);
    }
}


/* =========================================================
   SUBTOTAL
   ========================================================= */

function updateItemSubtotal(cartItem, subtotal) {
    const subtotalElement = cartItem.querySelector(
        '[data-cart-subtotal]'
    );

    if (!subtotalElement) {
        return;
    }

    subtotalElement.textContent = formatCurrency(subtotal);
}


/* =========================================================
   RESUMO DO CARRINHO
   ========================================================= */

function updateCartSummary(cart) {
    const summaryCount = document.querySelector(
        '[data-cart-summary-count]'
    );

    const totalElement = document.querySelector(
        '[data-cart-total]'
    );

    const headerCount = document.querySelector(
        '[data-cart-count]'
    );


    if (summaryCount) {
        summaryCount.textContent = cart.count;
    }


    if (totalElement) {
        totalElement.textContent = formatCurrency(cart.total);
    }


    if (headerCount) {
        headerCount.textContent = cart.count;

        headerCount.classList.toggle(
            'hidden',
            cart.count <= 0
        );
    }
}


/* =========================================================
   BOTÕES DE QUANTIDADE
   ========================================================= */

function updateQuantityButtons(cartItem) {
    const input = cartItem.querySelector(
        '[data-cart-quantity]'
    );

    const increaseButton = cartItem.querySelector(
        '[data-action="increase"]'
    );

    if (!input || !increaseButton) {
        return;
    }

    const quantity = Number(input.value);
    const max = Number(input.max);

    increaseButton.disabled = quantity >= max;
}


/* =========================================================
   LOADING
   ========================================================= */

function setItemLoading(cartItem, loading) {
    cartItem.classList.toggle(
        'is-loading',
        loading
    );
}


/* =========================================================
   CARRINHO VAZIO
   ========================================================= */

function checkEmptyCart() {
    const remainingItems = document.querySelectorAll(
        '[data-cart-item]'
    );

    if (remainingItems.length > 0) {
        return;
    }

    /*
     * Por enquanto recarregamos somente quando
     * o último item é removido.
     *
     * Depois podemos substituir isso por uma
     * renderização totalmente dinâmica.
     */
    window.location.reload();
}


/* =========================================================
   MENSAGENS
   ========================================================= */

function showCartError(message) {
    alert(message);
}


/* =========================================================
   FORMATAÇÃO DE MOEDA
   ========================================================= */

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(Number(value));
}