document.addEventListener('DOMContentLoaded', () => {
    initCart();
});


function initCart() {
    initInitialMessage();

    const cartItems = document.querySelectorAll('[data-cart-item]');
    const checkoutButton = document.querySelector(
        '[data-cart-checkout]'
    );

    checkoutButton?.addEventListener('click', () => {
        openWhatsAppCheckout();
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
   CHECKOUT WHATSAPP
   ========================================================= */

/* =========================================================
   CHECKOUT WHATSAPP
   ========================================================= */

async function openWhatsAppCheckout() {
    const checkoutButton = document.querySelector(
        '[data-cart-checkout]'
    );

    const cartItems = document.querySelectorAll(
        '[data-cart-item]'
    );

    if (!checkoutButton) {
        return;
    }

    if (!cartItems.length) {
        showCartMessage(
            'Seu carrinho está vazio.',
            'error'
        );

        return;
    }

    const whatsappNumber =
        checkoutButton.dataset.whatsappNumber;

    const clearUrl =
        checkoutButton.dataset.clearUrl;

    if (!whatsappNumber) {
        showCartMessage(
            'Não foi possível iniciar o atendimento pelo WhatsApp.',
            'error'
        );

        return;
    }

    const lines = [];

    lines.push(
        'Olá! Gostaria de fazer um pedido na Butique da Jandira.'
    );

    lines.push('');
    lines.push('Itens do pedido:');
    lines.push('');

    let total = 0;

    cartItems.forEach((cartItem, index) => {
        const productName =
            cartItem.dataset.productName;

        const variantName =
            cartItem.dataset.variantName;

        const unitPrice =
            Number(cartItem.dataset.unitPrice);

        const quantityInput =
            cartItem.querySelector('[data-cart-quantity]');

        const quantity =
            Number(quantityInput?.value ?? 0);

        const subtotal =
            unitPrice * quantity;

        total += subtotal;

        lines.push(`${index + 1}. ${productName}`);

        lines.push(
            `Variação: ${variantName}`
        );

        lines.push(
            `Quantidade: ${quantity}`
        );

        lines.push(
            `Valor unitário: ${formatCurrency(unitPrice)}`
        );

        lines.push(
            `Subtotal: ${formatCurrency(subtotal)}`
        );

        lines.push('');
    });

    lines.push(
        `Total do pedido: ${formatCurrency(total)}`
    );

    lines.push('');

    lines.push(
        'Gostaria de confirmar a disponibilidade e combinar o pagamento e a entrega.'
    );

    const message = lines.join('\n');

    const whatsappUrl =
        `https://wa.me/${whatsappNumber}?text=${encodeURIComponent(message)}`;

    /*
     * Abrimos primeiro o WhatsApp.
     * Isso reduz a chance de o navegador bloquear a nova aba.
     */
    window.open(
        whatsappUrl,
        '_blank',
        'noopener,noreferrer'
    );

    /*
     * Depois limpamos o carrinho da sessão.
     */
    await clearCart(clearUrl);
}

async function clearCart(clearUrl) {
    if (!clearUrl) {
        return;
    }

    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute('content');

    try {
        const response = await fetch(clearUrl, {
            method: 'DELETE',

            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            console.error(
                'Não foi possível limpar o carrinho.'
            );

            return;
        }

        updateCartSummary({
            count: 0,
            total: 0,
        });

        showEmptyCart();

    } catch (error) {
        console.error(
            'Erro ao limpar o carrinho:',
            error
        );
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