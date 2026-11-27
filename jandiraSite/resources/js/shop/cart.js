document.addEventListener('DOMContentLoaded', () => {
    initCart();
});


function initCart() {
    initInitialMessage();

    const cartItems = document.querySelectorAll('[data-cart-item]');
    const checkoutForm = document.querySelector('[data-checkout-form]');

    checkoutForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        submitOrderCheckout(checkoutForm);
    });

    if (!cartItems.length) {
        return;
    }

    const csrfToken = document .querySelector('meta[name="csrf-token"]') ?.getAttribute('content');

    cartItems.forEach((cartItem) => {
        initCartItem(cartItem, csrfToken);
    });
}


/* =========================================================
   MENSAGEM INICIAL
   ========================================================= */

function initInitialMessage() {
    const messageElement = document.querySelector(
        '[data-cart-message]'
    );

    if (!messageElement) {
        return;
    }

    if (messageElement.classList.contains('hidden')) {
        return;
    }

    messageElement.hideTimeout = window.setTimeout(() => {
        messageElement.classList.add('hidden');
    }, 3500);
}


/* =========================================================
   INICIALIZAÇÃO DE CADA ITEM
   ========================================================= */

function initCartItem(cartItem, csrfToken) {
    const quantityInput = cartItem.querySelector(
        '[data-cart-quantity]'
    );

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
            showCartMessage(
                `Quantidade máxima disponível: ${maxStock}.`,
                'error'
            );

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

        if (currentQuantity <= 1) {
            await removeCartItem(
                cartItem,
                csrfToken
            );

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

        if (!Number.isInteger(quantity)) {
            quantity = min;
        }

        if (quantity < min) {
            quantity = min;
        }

        if (quantity > max) {
            quantity = max;

            showCartMessage(
                `Quantidade ajustada para o estoque disponível: ${max}.`,
                'error'
            );
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
        await removeCartItem(
            cartItem,
            csrfToken
        );
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

        showCartMessage('Quantidade atualizada.');

    } catch (error) {
        quantityInput.value = previousQuantity;
        showCartMessage(error.message, 'error');

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

        showCartMessage('Produto removido do carrinho.');

        cartItem.classList.remove('is-loading');
        cartItem.classList.add('is-removing');

        window.setTimeout(() => {
            cartItem.remove();

            checkEmptyCart();
        }, 200);

    } catch (error) {
        setItemLoading(cartItem, false);
        showCartMessage(error.message, 'error');
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

    showEmptyCart();
}


/* =========================================================
   MENSAGENS
   ========================================================= */

function showCartMessage(message, type = 'success') {
    const messageElement = document.querySelector(
        '[data-cart-message]'
    );

    if (!messageElement) {
        return;
    }

    messageElement.textContent = message;

    messageElement.classList.remove(
        'hidden',

        'border-green-200',
        'bg-green-50',
        'text-green-700',

        'border-red-200',
        'bg-red-50',
        'text-red-700'
    );

    if (type === 'error') {
        messageElement.classList.add(
            'border-red-200',
            'bg-red-50',
            'text-red-700'
        );
    } else {
        messageElement.classList.add(
            'border-green-200',
            'bg-green-50',
            'text-green-700'
        );
    }

    window.clearTimeout(messageElement.hideTimeout);

    messageElement.hideTimeout = window.setTimeout(() => {
        messageElement.classList.add('hidden');
    }, 3500);
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

/* =========================================================
   CHECKOUT INTEGRADO INFINITEPAY COM REGISTRO DE PEDIDO
   ========================================================= */

async function submitOrderCheckout(checkoutForm) {
    const checkoutButton = checkoutForm.querySelector('[data-cart-checkout]');
    const cartItems = document.querySelectorAll('[data-cart-item]');

    if (!checkoutButton) {
        return;
    }

    if (!cartItems.length) {
        showCartMessage('Seu carrinho está vazio.', 'error');

        return;
    }

    const orderUrl = checkoutButton.dataset.orderUrl || checkoutForm.getAttribute('action');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const formData = new FormData(checkoutForm);
    const payload = {
        customer_name: String(formData.get('customer_name') ?? '').trim(),
        customer_phone: String(formData.get('customer_phone') ?? '').trim(),
        customer_email: String(formData.get('customer_email') ?? '').trim(),
        notes: String(formData.get('notes') ?? '').trim(),
    };

    const originalText = checkoutButton.textContent;
    checkoutButton.disabled = true;
    checkoutButton.textContent = 'Preparando pagamento...';

    try {
        const response = await fetch(orderUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            const firstError = data?.errors ? Object.values(data.errors).flat()[0] : null;
            throw new Error(firstError || data?.message || 'Não foi possível registrar o pedido.');
        }

        updateCartSummary({
            count: 0,
            total: 0,
        });

        const safeCheckoutUrl = isSafeCheckoutUrl(data.checkout_url) ? data.checkout_url : null;

        showOrderConfirmation(data.order, safeCheckoutUrl, data.whatsapp_url);

        if (safeCheckoutUrl) {
            window.location.assign(safeCheckoutUrl);
        }
    } catch (error) {
        showCartMessage(error.message, 'error');
    } finally {
        checkoutButton.disabled = false;
        checkoutButton.textContent = originalText;
    }
}

function isSafeCheckoutUrl(rawUrl) {
    if (typeof rawUrl !== 'string' || rawUrl.trim() === '') {
        return false;
    }

    try {
        const parsed = new URL(rawUrl, window.location.origin);

        return parsed.protocol === 'https:' || parsed.origin === window.location.origin;
    } catch {
        return false;
    }
}

function showOrderConfirmation(order, checkoutUrl, whatsappUrl) {
    const cartContent = document.querySelector('[data-cart-content]');
    const emptyCart = document.querySelector('[data-cart-empty]');
    const confirmation = document.querySelector('[data-order-confirmation]');
    const codeElement = document.querySelector('[data-order-code]');
    const checkoutLink = document.querySelector('[data-order-checkout-link]');
    const whatsappLink = document.querySelector('[data-order-whatsapp-link]');

    if (cartContent) {
        cartContent.classList.add('hidden');
    }

    if (emptyCart) {
        emptyCart.classList.add('hidden');
    }

    if (codeElement && order?.code) {
        codeElement.textContent = `#${order.code}`;
    }

    if (checkoutLink) {
        if (checkoutUrl) {
            checkoutLink.href = checkoutUrl;
            checkoutLink.classList.remove('hidden');
        } else {
            checkoutLink.classList.add('hidden');
        }
    }

    if (whatsappLink) {
        if (whatsappUrl) {
            whatsappLink.href = whatsappUrl;
            whatsappLink.classList.remove('hidden');
        } else {
            whatsappLink.classList.add('hidden');
        }
    }

    if (confirmation) {
        confirmation.classList.remove('hidden');
    }
}

function showEmptyCart() {
    const cartContent = document.querySelector(
        '[data-cart-content]'
    );

    const emptyCart = document.querySelector(
        '[data-cart-empty]'
    );

    if (cartContent) {
        cartContent.classList.add('hidden');
    }

    if (emptyCart) {
        emptyCart.classList.remove('hidden');
    }
}